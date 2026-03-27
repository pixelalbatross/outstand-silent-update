<?php

namespace Outstand\WP\SilentUpdate;

class Assets extends BaseModule {
	use GetAssetInfo;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$this->setup_asset_vars(
			dist_path: OUTSTAND_SILENT_UPDATE_DIST_PATH,
			fallback_version: OUTSTAND_SILENT_UPDATE_VERSION
		);

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_scripts' ] );
	}

	/**
	 * Enqueue block editor scripts.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_scripts(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		$post_type = $screen->post_type ?? '';

		if ( ! in_array( $post_type, Plugin::get_post_types(), true ) ) {
			return;
		}

		wp_enqueue_script(
			'outstand-silent-update-block-editor',
			OUTSTAND_SILENT_UPDATE_DIST_URL . 'js/block-editor.js',
			$this->get_asset_info( 'block-editor', 'dependencies' ),
			$this->get_asset_info( 'block-editor', 'version' ),
			true
		);

		wp_set_script_translations(
			'outstand-silent-update-block-editor',
			'outstand-silent-update',
			OUTSTAND_SILENT_UPDATE_PATH . 'languages'
		);

		wp_localize_script(
			'outstand-silent-update-block-editor',
			'outstandSilentUpdate',
			[
				'postTypes' => Plugin::get_post_types(),
			]
		);
	}
}
