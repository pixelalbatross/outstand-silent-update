<?php

namespace Outstand\WP\SilentUpdate;

use WP_REST_Request;

class SilentUpdate extends BaseModule {

	/**
	 * Lifetime, in seconds, of the cross-request silent-update marker.
	 *
	 * @var int
	 */
	private const MARKER_TTL = 60;

	/**
	 * Whether a silent update was requested in the current request.
	 *
	 * @var bool
	 */
	private bool $is_silent_update = false;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {

		foreach ( Plugin::get_post_types() as $post_type ) {
			add_filter( "rest_pre_insert_{$post_type}", [ $this, 'intercept_silent_update' ], 90, 2 );
		}

		add_filter( 'wp_insert_post_data', [ $this, 'preserve_modified_date_on_metabox_update' ], 10, 4 );
	}

	/**
	 * Detect a silent update request and hook into post data insertion.
	 *
	 * Fires on `rest_pre_insert_{$post_type}` so it only activates
	 * when the block editor sends `update_type: 'silent'` in the
	 * request body.
	 *
	 * @param \stdClass       $prepared_post Prepared post object.
	 * @param WP_REST_Request $request       Incoming REST request.
	 * @return \stdClass Unmodified prepared post.
	 */
	public function intercept_silent_update( $prepared_post, WP_REST_Request $request ) {
		$params      = $request->get_json_params();
		$update_type = $params['update_type'] ?? '';

		if ( 'silent' === $update_type ) {
			$this->is_silent_update = true;
			add_filter( 'wp_insert_post_data', [ $this, 'preserve_modified_date' ], 10, 4 );

			// Persist a short-lived marker so the follow-up legacy-metabox save
			// (a separate `action=editpost` request that never hits the REST
			// pipeline) can still detect the silent update. Scoped by post ID
			// and user ID so one user's flag can't suppress another user's
			// concurrent metabox save on the same post.
			$post_id = isset( $request['id'] ) ? (int) $request['id'] : 0;

			if ( $post_id > 0 ) {
				set_transient( $this->marker_key( $post_id ), 1, self::MARKER_TTL );
			}
		}

		return $prepared_post;
	}

	/**
	 * Restore the original post_modified timestamps so they survive the REST save.
	 *
	 * WordPress computes new timestamps inside wp_insert_post(); this filter
	 * replaces them with the values already in the database (carried through
	 * via $unsanitized_postarr by wp_update_post()).
	 *
	 * Removes itself after firing once to avoid affecting other saves in the
	 * same request.
	 *
	 * @param array $data                Slashed, sanitized post data destined for the DB.
	 * @param array $postarr             Sanitized (slashed) post data.
	 * @param array $unsanitized_postarr Slashed but unsanitized original post data.
	 * @param bool  $update              Whether this is an update (vs insert).
	 * @return array Filtered post data.
	 */
	public function preserve_modified_date( array $data, array $postarr, array $unsanitized_postarr, bool $update ): array {

		if ( $update && isset( $unsanitized_postarr['post_modified'], $unsanitized_postarr['post_modified_gmt'] ) ) {
			$data['post_modified']     = $unsanitized_postarr['post_modified'];
			$data['post_modified_gmt'] = $unsanitized_postarr['post_modified_gmt'];

			// Run once per request.
			remove_filter( 'wp_insert_post_data', [ $this, 'preserve_modified_date' ] );
		}

		return $data;
	}

	/**
	 * Prevent the legacy-metabox save from overwriting post_modified dates.
	 *
	 * When a post is saved in the block editor, WordPress may fire a second
	 * wp_insert_post call via a POST to post.php to save legacy metabox data.
	 * This resets post_modified to the current time, undoing any date
	 * preservation from the REST save.
	 *
	 * Detects the metabox update and restores the dates from $postarr
	 * (which carries the DB values through wp_update_post()).
	 *
	 * @param array $data                Slashed, sanitized post data destined for the DB.
	 * @param array $postarr             Sanitized (slashed) post data.
	 * @param array $unsanitized_postarr Slashed but unsanitized original post data.
	 * @param bool  $update              Whether this is an update (vs insert).
	 * @return array Filtered post data.
	 */
	public function preserve_modified_date_on_metabox_update( array $data, array $postarr, array $unsanitized_postarr, bool $update ): array {

		if ( ! $update || ! isset( $data['post_type'] ) ) {
			return $data;
		}

		if ( ! in_array( $data['post_type'], Plugin::get_post_types(), true ) ) {
			return $data;
		}

		$post_id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;

		if ( ! $this->is_silent_update_for_post( $post_id ) ) {
			return $data;
		}

		$is_published             = isset( $data['post_status'] ) && 'publish' === $data['post_status'];
		$is_changed_modified_date = isset( $data['post_modified_gmt'], $postarr['post_modified_gmt'] ) && $data['post_modified_gmt'] !== $postarr['post_modified_gmt'];

		if (
			$this->is_legacy_metabox_update()
			&& $is_published
			&& $is_changed_modified_date
		) {
			$data['post_modified']     = $postarr['post_modified'];
			$data['post_modified_gmt'] = $postarr['post_modified_gmt'];

			// Marker consumed by the metabox save; drop it so it can't leak
			// into a later save in this or another request.
			delete_transient( $this->marker_key( $post_id ) );
		}

		return $data;
	}

	/**
	 * Check if the current request is a legacy metabox save from the block editor.
	 *
	 * @return bool
	 */
	private function is_legacy_metabox_update(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_REQUEST['action'] ) && 'editpost' === $_REQUEST['action'] && ( empty( $_REQUEST['post_type'] ) || 'attachment' !== $_REQUEST['post_type'] );
	}

	/**
	 * Whether the given post is under a silent update, in this request or a
	 * follow-up one carrying the cross-request marker.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_silent_update_for_post( int $post_id ): bool {

		if ( $this->is_silent_update ) {
			return true;
		}

		if ( $post_id <= 0 ) {
			return false;
		}

		return (bool) get_transient( $this->marker_key( $post_id ) );
	}

	/**
	 * Build the transient key for the cross-request silent-update marker.
	 *
	 * Scoped by post ID and current user ID.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function marker_key( int $post_id ): string {
		return sprintf( 'outstand_silent_update_%d_%d', $post_id, get_current_user_id() );
	}
}
