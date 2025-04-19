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
     * Since our plugin implements a separate "pay-to-login" process (storing user wallet 
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

        add_action( 'wp_ajax_payment_trigger', array( $this, 'payment_trigger' ) );
        add_action( 'wp_ajax_nopriv_payment_trigger', array( $this, 'payment_trigger' ) );
    }
    /**
     * Payment Trigger Handler with Cryptographic Verification
     *
     * This endpoint is called directly by the PayButton server when a transaction is received.
     * It validates the request using a cryptographic signature to ensure authenticity.
    */
    public function payment_trigger() {
        /*  Note to reviewers:
        *  This endpoint is called by PayButton.org’s server.
        *  A wp_nonce cannot be used here (no WP session).
        *  We instead verify a cryptographic Ed25519 signature, which guarantees authenticity.
        */
        // Read the raw request body
        $raw_post_data = file_get_contents('php://input');

        // Decode JSON data
        $json_data = json_decode($raw_post_data, true);
        if (!$json_data || !isset($json_data['signature']['signature']) || !isset($json_data['signature']['payload'])) {
            wp_send_json_error(['message' => 'Invalid JSON format or missing signature.']);
            return;
        }

        // Get the Public Key from plugin settings
        $public_key = get_option('paybutton_public_key', '');
        if (empty($public_key)) {
            wp_send_json_error(['message' => 'Missing public key in plugin settings.']);
            return;
        }

        // Extract signature and payload from nested JSON
        $signature = $json_data['signature']['signature'];
        $payload = $json_data['signature']['payload']; // This is the signed data

        // Verify the signature
        $verification_result = $this->verify_signature($payload, $signature, $public_key);
        if (!$verification_result) {
            wp_send_json_error(['message' => 'Signature verification failed.']);
            return;
        }

        // Extract post_id from 'post_id' -> 'rawMessage'
        $post_id = isset($json_data['post_id']['rawMessage']) ? intval($json_data['post_id']['rawMessage']) : 0;

        // Extract transaction details
        $tx_hash = $json_data['tx_hash'] ?? '';
        $tx_amount = $json_data['tx_amount'] ?? '';
        $tx_timestamp = $json_data['tx_timestamp'] ?? '';
        $user_address = $json_data['user_address'][0] ?? '';

        // Convert timestamp to MySQL datetime
        $mysql_timestamp = is_numeric($tx_timestamp) ? gmdate('Y-m-d H:i:s', intval($tx_timestamp)) : '0000-00-00 00:00:00';

        if ($post_id > 0 && !empty($user_address)) {
            $is_logged_in = 0;

            // Store the payment in the database
            $this->store_unlock_in_db(
                sanitize_text_field($user_address),
                $post_id,
                sanitize_text_field($tx_hash),
                sanitize_text_field($tx_amount),
                $mysql_timestamp,
                $is_logged_in
            );
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'Missing post_id or user_address.']);
        }
    }

    // Verify the signature using the public key
    private function verify_signature($payload, $signature, $public_key_hex) {
        // Convert hex signature to binary
        $binary_signature = hex2bin($signature);
        if (!$binary_signature) {
            return false;
        }

        // Convert hex public key to binary
        $binary_public_key = hex2bin($public_key_hex);
        if (!$binary_public_key) {
            return false;
        }

        // If the public key is in DER format (44 bytes), extract the raw 32-byte key.
        if (strlen($binary_public_key) === 44) {
            $raw_public_key = substr($binary_public_key, 12);
        } else {
            $raw_public_key = $binary_public_key;
        }

        // Ensure payload is in exact binary format
        $binary_payload = mb_convert_encoding($payload, 'ISO-8859-1', 'UTF-8');

        // Verify signature using Sodium (Ed25519)
        $verification = sodium_crypto_sign_verify_detached($binary_signature, $binary_payload, $raw_public_key);

        if ($verification) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * The following function saves the 'logged in via PayButton' user's wallet address 
     * in a variable called pb_paywall_user_wallet_address from the handleLogin() method
     * of the "paybutton-paywall-cashtab-login.js" file via AJAX.
     *
     * This function verifies the AJAX nonce for security, ensures a PHP session is active,
     * sanitizes the 'address' field from the POST data, and then stores it in both the session
     * (under 'pb_paywall_user_wallet_address') and as a cookie (lasting 30 days).
    */
    public function save_address() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );
        if ( ! session_id() ) {
            session_start();
        }
        $address = sanitize_text_field( $_POST['address'] );

        // Retrieve the blacklist and check the address
        $blacklist = get_option( 'paybutton_blacklist', array() );
        if ( in_array( $address, $blacklist ) ) {
            wp_send_json_error( array( 'message' => 'This wallet address is blocked.' ) );
            return;
        }
        // blacklist End

        $_SESSION['pb_paywall_user_wallet_address'] = $address;

        // Write the new cookie
        setcookie(
            'pb_paywall_user_wallet_address',
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
     * It then removes the stored 'pb_paywall_user_wallet_address' from the session and clears
     * the corresponding cookie. Additionally, it unsets any session data tracking paid articles.
    */
    public function logout() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );
        if ( ! session_id() ) {
            session_start();
        }
        unset( $_SESSION['pb_paywall_user_wallet_address'] );
        setcookie(
            'pb_paywall_user_wallet_address',
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

            // Determine if user was "logged in" (i.e., session has a stored user wallet address)
            $is_logged_in = ! empty( $_SESSION['pb_paywall_user_wallet_address'] ) ? 1 : 0;

            // Decide which address to store:
            $address_to_store = $is_logged_in ? sanitize_text_field( $_SESSION['pb_paywall_user_wallet_address'] ) : $user_address;

            // If we have any address to store, insert a record
            if ( ! empty( $address_to_store ) ) {
                // Check blacklist again in case user isn't logged in
                $blacklist = get_option( 'paybutton_blacklist', array() );
                if ( in_array( $address_to_store, $blacklist ) ) {
                    wp_send_json_error( array( 'message' => 'This wallet address is blocked.' ) );
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

        // Check if the transaction already exists using tx hash
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE tx_hash = %s LIMIT 1",
            $tx_hash
        ));

        if ($exists) {
            return; // Transaction already recorded, so we don't insert again.
        }

        // Insert the transaction if it's not already recorded
        $wpdb->insert(
            $table_name,
            array(
                'pb_paywall_user_wallet_address' => $address,
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