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
	 * Registered module instances, keyed by class name.
	 *
	 * @var array<class-string<BaseModule>, BaseModule>
	 */
	private array $modules = [];

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
				$this->modules[ $module::class ] = $module;
			}
		}
	}

	/**
	 * Get a registered module instance by class name.
	 *
	 * @param string $class_name Fully-qualified module class name.
	 * @return BaseModule|null The module instance, or null if not registered.
	 */
	public function get_module( string $class_name ): ?BaseModule {
		return $this->modules[ $class_name ] ?? null;
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
