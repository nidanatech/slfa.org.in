<?php
/**
 * Plugin Name:       Sydkic Chat and Support
 * Plugin URI:        https://sydkic.com/wordpress
 * Description:       Official plugin for Sydkic. Embeds chat widget, syncs user data, and provides deep integration with your support desk.
 * Version:           1.0.1
 * Author:            Sydkic
 * Text Domain:       sydkic-chat
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define Constants
define( 'SYDKIC_VERSION', '1.0.1' );
define( 'SYDKIC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SYDKIC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include Core Class
require_once SYDKIC_PLUGIN_DIR . 'includes/class-sydkic-core.php';

/**
 * Begins execution of the plugin.
 */
function run_sydkic_chat() {
	$plugin = new Sydkic_Core();
	$plugin->run();
}
run_sydkic_chat();
