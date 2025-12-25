<?php
/**
 * PayButton Activator Class
 *
 * Handles plugin activation tasks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PayButton_Activator {

    /**
     * Activation hook callback.
    */
    public static function activate() {
        self::create_tables();
        self::create_profile_page();
        // Set a flag to redirect the admin to the Paywall Settings page after activation
        update_option('paybutton_activation_redirect', true);
        //self::migrate_old_option();
    }

    private static function migrate_old_option() {
        // Empty function for future use
    }

    /**
     * Create the custom table for unlocked content.
    */
    public static function create_tables() {
        global $wpdb; //$wpdb is WordPress’s way of interacting with the database, and it provides methods for running queries and getting the correct table prefix.
        $charset_collate = $wpdb->get_charset_collate();

        // ---- PayButton Paywall Unlocks table ----
        $table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';

        $sql = "CREATE TABLE $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            pb_paywall_user_wallet_address VARCHAR(255) NOT NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            tx_hash VARCHAR(64) DEFAULT '',
            tx_amount DECIMAL(20,2) DEFAULT 0,
            tx_timestamp DATETIME DEFAULT '0000-00-00 00:00:00',
            is_logged_in TINYINT(1) DEFAULT 0,
            unlock_token VARCHAR(64) DEFAULT '',
            used TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY pb_paywall_user_wallet_address_idx (pb_paywall_user_wallet_address),
            KEY post_id_idx (post_id),
            UNIQUE KEY tx_hash_idx (tx_hash),
            KEY unlock_token_idx (unlock_token)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // ---- PayButton Logins table ----
        $login_table = $wpdb->prefix . 'paybutton_logins';

        $sql_login = "CREATE TABLE $login_table (
            id INT NOT NULL AUTO_INCREMENT,
            wallet_address VARCHAR(255) NOT NULL,
            tx_hash VARCHAR(64) NOT NULL,
            tx_amount DECIMAL(20,2) NOT NULL,
            tx_timestamp INT(11) NOT NULL,
            login_token VARCHAR(64) DEFAULT '',
            used TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tx_hash_idx (tx_hash),
            KEY wallet_addr_idx (wallet_address(190)),
            KEY used_idx (used),
            KEY login_token_idx (login_token)
        ) {$charset_collate};";

        dbDelta( $sql_login );
    }

    /**
     * Creates the Profile page if it doesn't already exist.
     *
     * This function checks for an existing page ID stored in the 'paybutton_profile_page_id'
     * option and verifies the page exists. If not, it creates a new page titled "Profile"
     * with the content set to the [paybutton_profile] shortcode, publishes it, and updates
     * the option with the new page ID.
    */

    public static function create_profile_page() {
        $existing_page_id = get_option( 'paybutton_profile_page_id', 0 );
        if ( $existing_page_id && get_post( $existing_page_id ) ) {
            return;
        }

        $page_data = array(
            'post_title'     => 'Profile',
            'post_content'   => '[paybutton_profile]',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed'
        );
        $page_id = wp_insert_post( $page_data );

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( 'paybutton_profile_page_id', $page_id );
        }
    }
}
