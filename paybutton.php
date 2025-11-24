<?php
/**
 * Plugin Name: PayButton
 * Description: Monetize your content with configurable no-signup paywalls.
 * Version: 4.0.0
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
require_once PAYBUTTON_PLUGIN_DIR . 'includes/class-paybutton-state.php';

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
    // Make sure tables (including any newly added ones) exist after upgrades.
    if ( class_exists( 'PayButton_Activator' ) ) {
        PayButton_Activator::create_tables();
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

add_action('admin_init', function() {
    if (get_option('paybutton_activation_redirect', false)) {
        delete_option('paybutton_activation_redirect');
        // Prevent redirect during bulk plugin activation
        if (!isset($_GET['activate-multi'])) {
            wp_redirect(admin_url('admin.php?page=paybutton-paywall'));
            exit;
        }
    }
});
