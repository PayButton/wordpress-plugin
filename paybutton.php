<?php
/**
 * Plugin Name: PayButton
 * Description: Monetize your content with configurable no-signup paywalls.
 * Version: 2.3.0
 * Author: PayButton
 * Author URI:  https://github.com/PayButton/wordpress-plugin
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Defines plugin directory constants:
 * - PAYBUTTON_PLUGIN_DIR: Absolute filesystem path to the plugin's directory.
 * - PAYBUTTON_PLUGIN_URL: Web-accessible URL to the plugin's directory.
 *
 * These constants allow easy and consistent referencing of plugin files (such as assets and templates)
 * without hardcoding paths.
*/
define( 'PAYBUTTON_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAYBUTTON_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required class files.
require_once PAYBUTTON_PLUGIN_DIR . 'includes/class-paybutton-activator.php';
require_once PAYBUTTON_PLUGIN_DIR . 'includes/class-paybutton-deactivator.php';
require_once PAYBUTTON_PLUGIN_DIR . 'includes/class-paybutton-admin.php';
require_once PAYBUTTON_PLUGIN_DIR . 'includes/class-paybutton-public.php';
require_once PAYBUTTON_PLUGIN_DIR . 'includes/class-paybutton-ajax.php';

/**
 * Registers the plugin's activation and deactivation hooks.
 *
 * - When the plugin is activated, the static method 'activate' of the PayButton_Activator
 *   class is called to perform setup tasks (like creating custom database table for unlocked contents).
 *
 * - When the plugin is deactivated, the static method 'deactivate' of the PayButton_Deactivator
 *   class is called to perform any necessary cleanup.
*/
register_activation_hook( __FILE__, array( 'PayButton_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PayButton_Deactivator', 'deactivate' ) );

// Initialize plugin functionality.
add_action( 'plugins_loaded', function() {
    // Start a PHP session if none exists.
    if ( ! session_id() ) {
        session_start();
    }
    // Sync cookie value into session.
    if ( ! empty( $_COOKIE['cashtab_ecash_address'] ) ) {
        $_SESSION['cashtab_ecash_address'] = sanitize_text_field( $_COOKIE['cashtab_ecash_address'] );
    }

    // Initialize admin functionality if in admin area.
    if ( is_admin() ) {
        new PayButton_Admin();
    }
    // Initialize public-facing functionality.
    new PayButton_Public();

    // Initialize AJAX handlers.
    new PayButton_AJAX();
}, 1);  // Use a priority to ensure this runs before other actions that might depend on session data.
