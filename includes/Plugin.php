<?php

namespace Outstand\WP\SilentUpdate;

class Plugin {

	/**
	 * Singleton instance of the Plugin.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns singleton instance.
	 *
	 * @return Plugin The singleton instance.
	 */
	public static function get_instance(): Plugin {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Enable plugin functionality.
	 *
	 * @return void
	 */
	public function enable(): void {

		$modules = [
			new Assets(),
			new SilentUpdate(),
		];

		foreach ( $modules as $module ) {
			if ( $module instanceof BaseModule && $module->can_register() ) {
				$module->register();
			}
		}
	}

	/**
	 * Get the supported post types.
	 *
	 * @return array<string>
	 */
	public static function get_post_types(): array {
		/**
		 * Filters the post types that support silent updates.
		 *
		 * @param array<string> $post_types Post types. Default: ['post'].
		 */
		return apply_filters( 'outstand_silent_update_post_types', [ 'post' ] );
	}
}
