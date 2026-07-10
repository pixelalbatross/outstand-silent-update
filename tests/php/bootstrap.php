<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Outstand\WP\SilentUpdate
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore
	exit( 1 );
}

// Load Composer autoloader.
$plugin_dir = dirname( __DIR__, 2 );
if ( file_exists( $plugin_dir . '/vendor/autoload.php' ) ) {
	require_once $plugin_dir . '/vendor/autoload.php';
}

// Define plugin constants normally set in plugin.php.
if ( ! defined( 'OUTSTAND_SILENT_UPDATE_VERSION' ) ) {
	define( 'OUTSTAND_SILENT_UPDATE_VERSION', '1.1.2-test' );
}

if ( ! defined( 'OUTSTAND_SILENT_UPDATE_BASENAME' ) ) {
	define( 'OUTSTAND_SILENT_UPDATE_BASENAME', 'outstand-silent-update/plugin.php' );
}

if ( ! defined( 'OUTSTAND_SILENT_UPDATE_PATH' ) ) {
	define( 'OUTSTAND_SILENT_UPDATE_PATH', $plugin_dir . '/' );
}

if ( ! defined( 'OUTSTAND_SILENT_UPDATE_URL' ) ) {
	define( 'OUTSTAND_SILENT_UPDATE_URL', 'http://example.org/wp-content/plugins/outstand-silent-update/' );
}

if ( ! defined( 'OUTSTAND_SILENT_UPDATE_DIST_PATH' ) ) {
	define( 'OUTSTAND_SILENT_UPDATE_DIST_PATH', OUTSTAND_SILENT_UPDATE_PATH . 'build/' );
}

if ( ! defined( 'OUTSTAND_SILENT_UPDATE_DIST_URL' ) ) {
	define( 'OUTSTAND_SILENT_UPDATE_DIST_URL', OUTSTAND_SILENT_UPDATE_URL . 'build/' );
}

// Load WordPress test suite functions.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin's modules on muplugins_loaded.
 *
 * We don't load the full plugin.php because it wires the PUC updater which
 * hits the network — tests boot the modules directly.
 */
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		$plugin = \Outstand\WP\SilentUpdate\Plugin::get_instance();
		$plugin->enable();
	}
);

// Bootstrap WordPress test suite.
require $_tests_dir . '/includes/bootstrap.php';
