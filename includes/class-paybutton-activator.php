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
        self::migrate_old_option();
    }

    private static function migrate_old_option() {
        $old_value = get_option( 'paybutton_paywall_ecash_address', '' );
        $new_value = get_option( 'pb_paywall_admin_wallet_address', '' );
    
        // If old_value is present and new_value is empty, copy old to new and remove old.
        if ( ! empty( $old_value ) && empty( $new_value ) ) {
            update_option( 'pb_paywall_admin_wallet_address', $old_value );
            delete_option( 'paybutton_paywall_ecash_address' );
        }
    }

    /**
     * Create the custom table for unlocked content.
     */
    public static function create_tables() {
        global $wpdb; //$wpdb is WordPress’s way of interacting with the database, and it provides methods for running queries and getting the correct table prefix.

        $table_name      = $wpdb->prefix . 'paybutton_paywall_unlocked';
        $charset_collate = $wpdb->get_charset_collate();

        // Updated table definition with the new column name pb_paywall_user_wallet_address
        // and index name pb_paywall_user_wallet_address_idx
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            pb_paywall_user_wallet_address VARCHAR(255) NOT NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            tx_hash VARCHAR(64) DEFAULT '',
            tx_amount DECIMAL(20,8) DEFAULT 0,
            tx_timestamp DATETIME DEFAULT '0000-00-00 00:00:00',
            is_logged_in TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY pb_paywall_user_wallet_address_idx (pb_paywall_user_wallet_address),
            KEY post_id_idx (post_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Rename old 'ecash_address' column to 'pb_paywall_user_wallet_address', if it still exists
        $old_col_check = $wpdb->get_var(
            "SHOW COLUMNS FROM $table_name LIKE 'ecash_address'"
        );
        $new_col_check = $wpdb->get_var(
            "SHOW COLUMNS FROM $table_name LIKE 'pb_paywall_user_wallet_address'"
        );

        if ( $old_col_check && ! $new_col_check ) {
            $wpdb->query("ALTER TABLE $table_name CHANGE ecash_address pb_paywall_user_wallet_address VARCHAR(255) NOT NULL");
        }

        // Rename old index 'ecash_address_idx' to 'pb_paywall_user_wallet_address_idx'
        $old_idx_check = $wpdb->get_var("
            SHOW INDEX FROM $table_name WHERE Key_name = 'ecash_address_idx'
        ");
        $new_idx_check = $wpdb->get_var("
            SHOW INDEX FROM $table_name WHERE Key_name = 'pb_paywall_user_wallet_address_idx'
        ");
        if ( $old_idx_check && ! $new_idx_check ) {
            // Drop the old index
            $wpdb->query("ALTER TABLE $table_name DROP INDEX ecash_address_idx");
            // Add the new one
            $wpdb->query("ALTER TABLE $table_name ADD INDEX pb_paywall_user_wallet_address_idx (pb_paywall_user_wallet_address)");
        }
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
