<?php

namespace Outstand\WP\SilentUpdate\Tests\Unit;

use Outstand\WP\SilentUpdate\Plugin;
use Outstand\WP\SilentUpdate\SilentUpdate;
use WP_REST_Request;

/**
 * Covers the two save paths that must preserve post_modified during a silent
 * update: the REST save (same request) and the follow-up legacy-metabox save
 * (a separate `action=editpost` request, reached via the cross-request marker).
 */
class SilentUpdateTest extends \WP_UnitTestCase {

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		unset( $_REQUEST['action'], $_REQUEST['post_type'] );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		unset( $_REQUEST['action'], $_REQUEST['post_type'] );
		parent::tearDown();
	}

	/**
	 * REST silent save: preserve_modified_date restores the DB timestamps that
	 * WordPress would otherwise bump, and intercept arms the cross-request marker.
	 */
	public function test_rest_silent_save_preserves_modified_date_and_sets_marker(): void {
		$post_id  = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$original = '2020-01-01 00:00:00';

		$request = new WP_REST_Request( 'PUT', "/wp/v2/posts/{$post_id}" );
		$request->set_param( 'id', $post_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'update_type' => 'silent' ] ) );

		$module = Plugin::get_instance()->get_module( SilentUpdate::class );
		$this->assertInstanceOf( SilentUpdate::class, $module );

		$module->intercept_silent_update( new \stdClass(), $request );

		$this->assertTrue(
			(bool) get_transient( "outstand_silent_update_{$post_id}_" . get_current_user_id() ),
			'Silent REST save should arm the cross-request marker.'
		);

		$data = $module->preserve_modified_date(
			[
				'post_modified'     => '2026-07-09 12:00:00',
				'post_modified_gmt' => '2026-07-09 12:00:00',
			],
			[],
			[
				'post_modified'     => $original,
				'post_modified_gmt' => $original,
			],
			true
		);

		$this->assertSame( $original, $data['post_modified'] );
		$this->assertSame( $original, $data['post_modified_gmt'] );
	}

	/**
	 * The bug this feature regressed on: the legacy-metabox save is a separate
	 * request where the instance flag is false. The cross-request marker must
	 * still let preserve_modified_date_on_metabox_update restore the timestamps.
	 */
	public function test_metabox_save_uses_marker_from_separate_request(): void {
		$post_id  = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$original = '2020-01-01 00:00:00';

		// Marker as armed by the earlier REST request.
		set_transient( "outstand_silent_update_{$post_id}_" . get_current_user_id(), 1, 60 );

		// A fresh instance stands in for the separate metabox HTTP request:
		// its $is_silent_update property is false.
		$module = new SilentUpdate();

		$_REQUEST['action'] = 'editpost';

		$data = $module->preserve_modified_date_on_metabox_update(
			[
				'ID'                => $post_id,
				'post_type'         => 'post',
				'post_status'       => 'publish',
				'post_modified'     => '2026-07-09 12:00:00',
				'post_modified_gmt' => '2026-07-09 12:00:00',
			],
			[
				'ID'                => $post_id,
				'post_modified'     => $original,
				'post_modified_gmt' => $original,
			],
			[],
			true
		);

		$this->assertSame( $original, $data['post_modified'], 'Metabox save must restore the original modified date.' );
		$this->assertSame( $original, $data['post_modified_gmt'] );
		$this->assertFalse(
			(bool) get_transient( "outstand_silent_update_{$post_id}_" . get_current_user_id() ),
			'Marker should be consumed after the metabox save uses it.'
		);
	}

	/**
	 * Without a marker and outside a silent request, an ordinary metabox save
	 * is left untouched.
	 */
	public function test_metabox_save_without_marker_is_untouched(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$new     = '2026-07-09 12:00:00';

		$module = new SilentUpdate();

		$_REQUEST['action'] = 'editpost';

		$data = $module->preserve_modified_date_on_metabox_update(
			[
				'ID'                => $post_id,
				'post_type'         => 'post',
				'post_status'       => 'publish',
				'post_modified'     => $new,
				'post_modified_gmt' => $new,
			],
			[
				'ID'                => $post_id,
				'post_modified'     => '2020-01-01 00:00:00',
				'post_modified_gmt' => '2020-01-01 00:00:00',
			],
			[],
			true
		);

		$this->assertSame( $new, $data['post_modified_gmt'], 'Non-silent metabox save must keep the bumped date.' );
	}

	/**
	 * The marker is scoped by user: another user's metabox save on the same
	 * post must not pick up this user's silent-update marker.
	 */
	public function test_marker_is_scoped_to_the_requesting_user(): void {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$new     = '2026-07-09 12:00:00';

		// User A arms the marker.
		set_transient( "outstand_silent_update_{$post_id}_" . get_current_user_id(), 1, 60 );

		// User B performs the metabox save.
		$other = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $other );

		$module = new SilentUpdate();

		$_REQUEST['action'] = 'editpost';

		$data = $module->preserve_modified_date_on_metabox_update(
			[
				'ID'                => $post_id,
				'post_type'         => 'post',
				'post_status'       => 'publish',
				'post_modified'     => $new,
				'post_modified_gmt' => $new,
			],
			[
				'ID'                => $post_id,
				'post_modified'     => '2020-01-01 00:00:00',
				'post_modified_gmt' => '2020-01-01 00:00:00',
			],
			[],
			true
		);

		$this->assertSame( $new, $data['post_modified_gmt'], "User B's save must not read User A's marker." );
	}
}
