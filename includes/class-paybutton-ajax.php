<?php
/**
 * PayButton AJAX Class
 *
 * Handles AJAX requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PayButton_AJAX {

    /**
     * WordPress provides two AJAX hooks:
     *   1) wp_ajax_{action} 
     *      - Fires if the visitor is recognized as "logged in" by WordPress's native system.
     *   2) wp_ajax_nopriv_{action}
     *      - Fires if the visitor is not recognized as logged in by WordPress.
     *
     * Since our plugin implements a separate "pay-to-login" process (storing eCash 
     * addresses in sessions), from WP’s point of view, most of our pay-to-login users 
     * are still not "logged in" in the standard WordPress sense.
     *
     * If we want both WP-logged-in and non-WP-logged-in visitors to access the same 
     * AJAX endpoint, we register the callback on both hooks (wp_ajax_ and wp_ajax_nopriv_).
    */

    public function __construct() {
        add_action( 'wp_ajax_paybutton_save_address', array( $this, 'save_address' ) );
        add_action( 'wp_ajax_nopriv_paybutton_save_address', array( $this, 'save_address' ) );

        add_action( 'wp_ajax_paybutton_logout', array( $this, 'logout' ) );
        add_action( 'wp_ajax_nopriv_paybutton_logout', array( $this, 'logout' ) );

        add_action( 'wp_ajax_mark_payment_successful', array( $this, 'mark_payment_successful' ) );
        add_action( 'wp_ajax_nopriv_mark_payment_successful', array( $this, 'mark_payment_successful' ) );
    }

    /**
     * The following functioon saves the 'loggged in via PayButton' user's eCash address 
     * in a variable called cashtab_ecash_address from the handleLogin() method of the 
     * "paybutton-paywall-cashtab-login.js" file via AJAX.
     *
     * This function verifies the AJAX nonce for security, ensures a PHP session is active,
     * sanitizes the 'address' field from the POST data, and then stores it in both the session
     * (under 'cashtab_ecash_address') and as a cookie (lasting 30 days).
    */

    public function save_address() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );
        if ( ! session_id() ) {
            session_start();
        }
        $address = sanitize_text_field( $_POST['address'] );

        // Retrieve the blocklist and check the address
        $blocklist = get_option( 'paybutton_blocklist', array() );
        if ( in_array( $address, $blocklist ) ) {
            wp_send_json_error( array( 'message' => 'This eCash address is blocked.' ) );
            return;
        }
        // Blocklist End

        $_SESSION['cashtab_ecash_address'] = $address;
        setcookie(
            'cashtab_ecash_address',
            $address,
            time() + 2592000,
            COOKIEPATH ?: '/',
            COOKIE_DOMAIN ?: '',
            is_ssl(),
            true
        );
        wp_send_json_success( array( 'message' => 'Address stored in session & cookie' ) );
    }

    /**
     * Logs the user out via AJAX.
     *
     * This function verifies the AJAX nonce for security and ensures a PHP session is active.
     * It then removes the stored 'cashtab_eCash_address' from the session and clears the corresponding cookie.
     * Additionally, it unsets any session data tracking paid articles and sends a JSON success response.
    */

    public function logout() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );
        if ( ! session_id() ) {
            session_start();
        }
        unset( $_SESSION['cashtab_ecash_address'] );
        setcookie(
            'cashtab_ecash_address',
            '',
            time() - 3600,
            COOKIEPATH ?: '/',
            COOKIE_DOMAIN ?: '',
            is_ssl(),
            true
        );
        unset( $_SESSION['paid_articles'] );
        wp_send_json_success( array( 'message' => 'Logged out' ) );
    }

    /**
     * Marks a payment as successful and unlocks content.
     *
     * This function is triggered via AJAX when a payment succeeds. It performs three main tasks:
     * 1. Sets a session flag (in $_SESSION['paid_articles']) to mark the current post as unlocked.
     * 2. If an eCash address (cashtab_ecash_address) exists in the session 
     *    (indicating the user is "logged in" via PayButton), it records the transaction details 
     *    (post ID, transaction hash, amount, and timestamp) in the database by calling 
     *    store_unlock_in_db(); otherwise, uses the address provided from the front end.
     * 3. Prevents blocked addresses from being stored.
    */
    public function mark_payment_successful() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );
        if ( ! session_id() ) {
            session_start();
        }

        $post_id      = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $tx_hash      = isset( $_POST['tx_hash'] ) ? sanitize_text_field( $_POST['tx_hash'] ) : '';
        $tx_amount    = isset( $_POST['tx_amount'] ) ? sanitize_text_field( $_POST['tx_amount'] ) : '';
        $tx_timestamp = isset( $_POST['tx_timestamp'] ) ? sanitize_text_field( $_POST['tx_timestamp'] ) : '';
        // NEW: Address passed from front-end if user is not logged in
        $user_address = isset( $_POST['user_address'] ) ? sanitize_text_field( $_POST['user_address'] ) : '';

        $mysql_timestamp = '0000-00-00 00:00:00';
        if ( is_numeric( $tx_timestamp ) ) {
            $mysql_timestamp = gmdate( 'Y-m-d H:i:s', intval( $tx_timestamp ) );
        }

        if ( $post_id > 0 ) {
            // Mark this post as "unlocked" in the session
            $_SESSION['paid_articles'][ $post_id ] = true;

            // Determine if user was "logged in" (i.e., session has a stored eCash address)
            $is_logged_in = ! empty( $_SESSION['cashtab_ecash_address'] ) ? 1 : 0;

            // Decide which address to store:
            // If logged in, store the session address. Otherwise, store the user_address from the front end.
            $address_to_store = $is_logged_in ? sanitize_text_field( $_SESSION['cashtab_ecash_address'] ) : $user_address;

            // If we have any address to store, insert a record
            if ( ! empty( $address_to_store ) ) {
                // Check blocklist again in case user isn't logged in
                $blocklist = get_option( 'paybutton_blocklist', array() );
                if ( in_array( $address_to_store, $blocklist ) ) {
                    wp_send_json_error( array( 'message' => 'This eCash address is blocked.' ) );
                    return;
                }

                $this->store_unlock_in_db(
                    $address_to_store,
                    $post_id,
                    $tx_hash,
                    $tx_amount,
                    $mysql_timestamp,
                    $is_logged_in
                );
            }
        }
        wp_send_json_success();
    }

    /**
     * Store the unlock information in the database.
     */
    private function store_unlock_in_db( $address, $post_id, $tx_hash, $tx_amount, $tx_dt, $is_logged_in ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';

        // $exists = $wpdb->get_var( $wpdb->prepare(
        //     "SELECT id FROM $table_name WHERE ecash_address = %s AND post_id = %d LIMIT 1",
        //     $address,
        //     $post_id
        // ) );
        // if ( $exists ) {
        //     return;
        // }

        $wpdb->insert(
            $table_name,
            array(
                'ecash_address' => $address,
                'post_id'       => $post_id,
                'tx_hash'       => $tx_hash,
                'tx_amount'     => $tx_amount,
                'tx_timestamp'  => $tx_dt,
                'is_logged_in'  => $is_logged_in,
            ),
            array( '%s', '%d', '%s', '%f', '%s', '%d' )
        );
    }
}
