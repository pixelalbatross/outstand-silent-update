<?php // phpcs:ignore Generic.Commenting.DocComment.MissingShort
/**
 * @wordpress-plugin
 * Plugin Name:       Outstand Silent Update
 * Description:       Update a post without changing its modified date in the Block Editor.
 * Plugin URI:        https://outstand.site/?utm_source=wp-plugins&utm_medium=outstand-silent-update&utm_campaign=plugin-uri
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Version:           1.0.0
 * Author:            Outstand
 * Author URI:        https://outstand.site/?utm_source=wp-plugins&utm_medium=outstand-silent-update&utm_campaign=author-uri
 * License:           GPL-3.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-3.0-or-later.html
 * Update URI:        https://outstand.site/
 * GitHub Plugin URI: https://github.com/outstand-labs/outstand-silent-update
 * Text Domain:       outstand-silent-update
 * Domain Path:       /languages
 */

namespace Outstand\WP\SilentUpdate;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'OUTSTAND_SILENT_UPDATE_VERSION', '1.0.0' );
define( 'OUTSTAND_SILENT_UPDATE_BASENAME', plugin_basename( __FILE__ ) );
define( 'OUTSTAND_SILENT_UPDATE_URL', plugin_dir_url( __FILE__ ) );
define( 'OUTSTAND_SILENT_UPDATE_PATH', plugin_dir_path( __FILE__ ) );
define( 'OUTSTAND_SILENT_UPDATE_DIST_URL', OUTSTAND_SILENT_UPDATE_URL . 'build/' );
define( 'OUTSTAND_SILENT_UPDATE_DIST_PATH', OUTSTAND_SILENT_UPDATE_PATH . 'build/' );

if ( file_exists( OUTSTAND_SILENT_UPDATE_PATH . 'vendor/autoload.php' ) ) {
	require_once OUTSTAND_SILENT_UPDATE_PATH . 'vendor/autoload.php';
}

PucFactory::buildUpdateChecker(
	'https://github.com/outstand-labs/outstand-silent-update/',
	__FILE__,
	'outstand-silent-update'
)->setBranch( 'main' );

/**
 * Load the plugin.
 */
add_action(
	'plugins_loaded',
	function () {
		$plugin = Plugin::get_instance();
		$plugin->enable();
	}
);
