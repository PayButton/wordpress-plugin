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
     * addresses in cookies), from WP’s point of view, most of our pay-to-login users 
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

        // Function that improves UX by fetching unlocked content without reloading page
        add_action( 'wp_ajax_fetch_unlocked_content', array( $this, 'fetch_unlocked_content' ) );
        add_action( 'wp_ajax_nopriv_fetch_unlocked_content', array( $this, 'fetch_unlocked_content' ) );

        // AJAX endpoint to validate a login transaction (marking it as "used" after consumption)
        add_action('wp_ajax_validate_login_tx', array($this, 'ajax_validate_login_tx'));
        add_action('wp_ajax_nopriv_validate_login_tx', array($this, 'ajax_validate_login_tx'));

        // AJAX endpoint to validate an unlock transaction
        add_action('wp_ajax_validate_unlock_tx', array($this, 'ajax_validate_unlock_tx'));
        add_action('wp_ajax_nopriv_validate_unlock_tx', array($this, 'ajax_validate_unlock_tx'));
    }
    /**
     * Payment Trigger Handler with Cryptographic Verification
     *
     * This endpoint is called directly by the PayButton server when a transaction is received.
     * It validates the request using a cryptographic signature to ensure authenticity.
    */
    public function payment_trigger() {
        /* Note to reviewers:
            * A wp_nonce cannot be used here (no WP session).
            * We instead verify a cryptographic Ed25519 signature, which guarantees authenticity.
            * The PayButton server POSTS JSON (`Content‑Type: application/json`), so `$_POST`
            * is empty and we must read php://input and sanitize data.
            * Reading the raw body – capped at 4 KB (DoS protection) so we never process an
            * arbitrary‑size payload
        */
        $max_bytes     = 4096;  // 4 KB is more than enough
        $raw_post_data = file_get_contents(
            'php://input',
            false,
            null,
            0,
            $max_bytes + 1       // read one extra byte → oversize flag
        );

        if ( strlen( $raw_post_data ) > $max_bytes ) {
            wp_send_json_error( array( 'message' => 'Payload too large.' ), 413 );
            return;
        }

        //Decode JSON and copy ONLY the fields we actually use
        
        $json = json_decode( $raw_post_data, true );

        // error_log('[paybutton] payment_trigger hit');

        if ( ! is_array( $json ) ) {
            wp_send_json_error( array( 'message' => 'Malformed JSON.' ), 400 );
            return;
        }

        $signature      = $json['signature']['signature'] ?? '';
        $payload        = $json['signature']['payload']   ?? ''; // This is the signed data
        $post_id_raw    = $json['post_id']['rawMessage']  ?? 0;
        $tx_hash_raw    = $json['tx_hash']                ?? '';
        $tx_amount_raw  = $json['tx_amount']              ?? '';
        $ts_raw         = $json['tx_timestamp']           ?? 0;
        $user_addr_raw = $json['user_address'][0]['address'] ?? ($json['user_address'][0] ?? '');

        unset( $json );   // discard the rest immediately

        if ( empty( $signature ) || empty( $payload ) || empty( $post_id_raw ) || empty( $user_addr_raw ) ) {
            wp_send_json_error( array( 'message' => 'Required fields missing.' ), 400 );
            return;
        }

        // Get the Public Key from plugin settings
        $public_key = get_option('paybutton_public_key', '');
        if (empty($public_key)) {
            wp_send_json_error(['message' => 'Missing public key in plugin settings.']);
            return;
        }

        // Verify the signature
        $verification_result = $this->verify_signature($payload, $signature, $public_key);
        if (!$verification_result) {
            wp_send_json_error(['message' => 'Signature verification failed.']);
            return;
        }
        // error_log('[paybutton] signature ok');

        //Sanitize data
        $post_id      = intval( $post_id_raw );
        $tx_hash      = sanitize_text_field( $tx_hash_raw );
        $tx_amount    = sanitize_text_field( $tx_amount_raw );
        $tx_timestamp = intval( $ts_raw );
        $user_address = sanitize_text_field( $user_addr_raw );
        // error_log('[paybutton] rawMessage=' . print_r($post_id_raw, true));
        /**
         * If PayButton OP_RETURN (carried via post_id.rawMessage) indicates a login flow,
         * skip unlock logic and just record a login tx row.
         * This short-circuits the rest of payment_trigger() when it’s a login payment,
         * so it won’t run the normal unlock write path.
         * TODO: Rename the post_id field to "opReturn" to avoid confusion.
        */
        if ( is_string( $post_id_raw ) && stripos( $post_id_raw, 'login' ) !== false ) {
            if ( empty( $user_address ) || empty( $tx_hash ) || empty( $tx_timestamp ) ) {
                wp_send_json_error(['message' => 'Missing login tx fields.'], 400);
                return;
            }

            global $wpdb;
            $login_table = $wpdb->prefix . 'paybutton_logins';

            // Idempotency: avoid dupes on replays
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$login_table} WHERE wallet_address = %s AND tx_hash = %s LIMIT 1",
                $user_address, $tx_hash
            ) );
            error_log('[paybutton] login-branch addr=' . $user_address . ' tx=' . $tx_hash . ' ts=' . $tx_timestamp);
            if ( ! $exists ) {
                $wpdb->insert(
                    $login_table,
                    array(
                        'wallet_address' => $user_address,
                        'tx_hash'        => $tx_hash,
                        'tx_amount'      => (float) $tx_amount,
                        'tx_timestamp'   => (int) $tx_timestamp,
                        'used'           => 0,
                    ),
                    array('%s','%s','%f','%d','%d')
                );
            }

            if ($wpdb->last_error) {
                error_log('[paybutton] insert error: ' . $wpdb->last_error);
            } else {
                error_log('[paybutton] insert ok id=' . $wpdb->insert_id);
            }

            wp_send_json_success(['message' => 'Login tx recorded']);
            global $wpdb;
            return;
        }
        // Convert timestamp to MySQL datetime
        $mysql_timestamp = $tx_timestamp ? gmdate('Y-m-d H:i:s', $tx_timestamp) : '0000-00-00 00:00:00';

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
     * The following function sets the user's wallet address in a cookie via AJAX after
     * a successful login transaction.
    */
    public function save_address() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );
        $address = sanitize_text_field( $_POST['address'] ?? '' );
        $tx_hash = sanitize_text_field( $_POST['tx_hash'] ?? '' );
        $login_token  = sanitize_text_field( $_POST['login_token'] ?? '' );

        if (!$address || !$tx_hash || !$login_token) {
            wp_send_json_error(['message' => 'Missing address, tx_hash, or login_token']);
        }

        // Find the specific validated row for this token + wallet address + tx hash
        global $wpdb;
        $login_table = $wpdb->prefix . 'paybutton_logins';
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$login_table}
            WHERE wallet_address = %s
            AND tx_hash = %s
            AND login_token = %s
            AND used = 1
            LIMIT 1",
            $address, $tx_hash, $login_token
        ));

        if (!$row) {
            wp_send_json_error(['message' => 'No validated login found for this token']);
        }

        // Retrieve the blacklist and check the address
        $blacklist = get_option( 'paybutton_blacklist', array() );
        if ( in_array( $address, $blacklist ) ) {
            wp_send_json_error( array( 'message' => 'This wallet address is blocked.' ) );
            return;
        }
        // blacklist End

        PayButton_State::set_address( $address ); 
        wp_send_json_success();
    }

    /**
     * Logs the user out via AJAX.
     *
     * This function verifies the AJAX nonce for security.
     * It then removes the stored 'pb_paywall_user_wallet_address' from the cookie and clears
     * the corresponding cookie. Additionally, it unsets any cookie data tracking paid articles.
    */
    public function logout() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );
        PayButton_State::clear_address(); 
        PayButton_State::clear_articles();
        wp_send_json_success( array( 'message' => 'Logged out' ) );
    }

    /**
     * Marks a payment as successful and unlocks content.
    */
    public function mark_payment_successful() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );

        $post_id      = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $tx_hash      = isset( $_POST['tx_hash'] ) ? sanitize_text_field( $_POST['tx_hash'] ) : '';
        $tx_amount    = isset( $_POST['tx_amount'] ) ? sanitize_text_field( $_POST['tx_amount'] ) : '';
        $tx_timestamp = isset( $_POST['tx_timestamp'] ) ? sanitize_text_field( $_POST['tx_timestamp'] ) : '';
        // NEW: Address passed from front-end if user is not logged in
        $user_address = isset( $_POST['user_address'] ) ? sanitize_text_field( $_POST['user_address'] ) : '';
        $unlock_token  = isset( $_POST['unlock_token'] ) ? sanitize_text_field( $_POST['unlock_token'] ) : '';

        if ( $post_id <= 0 || empty( $tx_hash ) || empty( $user_address ) || empty( $unlock_token ) ) {
                wp_send_json_error( array( 'message' => 'Missing required payment fields.' ), 400 );
        }

        $mysql_timestamp = '0000-00-00 00:00:00';
        if ( is_numeric( $tx_timestamp ) ) {
            $mysql_timestamp = gmdate( 'Y-m-d H:i:s', intval( $tx_timestamp ) );
        }

        // Verify that this token corresponds to a row inserted by the signed webhook in Payment_Trigger().
        global $wpdb;
        $table = $wpdb->prefix . 'paybutton_paywall_unlocked';

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM {$table}
            WHERE pb_paywall_user_wallet_address = %s
            AND post_id = %d
            AND tx_hash = %s
            AND unlock_token = %s
            AND used = 1
            LIMIT 1",
            $user_address,
            $post_id,
            $tx_hash,
            $unlock_token
        ) );

        if ( ! $row ) {
            wp_send_json_error( array( 'message' => 'No validated unlock found for this token.' ), 403 );
        }

        // At this point, we know:
        // - PayButton webhook inserted the row (signature verified in payment_trigger) in DB
        // - ajax_validate_unlock_tx() validated it and attached unlock_token
        // - This mark_payment_successful call has the same addr+tx+post+token

        // Check blacklist before unlocking
        $blacklist = get_option( 'paybutton_blacklist', array() );
        if ( in_array( $user_address, $blacklist, true ) ) {
            wp_send_json_error( array( 'message' => 'This wallet address is blocked.' ) );
        }

        // Mark this post as "unlocked" in the cookie for this browser session
        PayButton_State::add_article( $post_id );

        // If the user is logged in via Cashtab login cookie, mark is_logged_in for this row in DB.
        $login_addr = sanitize_text_field(PayButton_State::get_address());
        if ( $login_addr && $login_addr === $user_address ) {
            $wpdb->update(
                $table,
                array( 'is_logged_in' => 1 ),
                array( 'id' => (int) $row->id ),
                array( '%d' ),
                array( '%d' )
            );
        }
        wp_send_json_success();
    }

    /**
     * Fetches the unlocked content and comments for a post via AJAX to display on the front-end without reloading the page.
     *
     * This function verifies the AJAX nonce for security,
     * checks if the current user has unlocked the specified post,
     * and if so, retrieves and returns the inner content of the [paywalled_content] shortcode.
    */
    public function fetch_unlocked_content() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => 'Missing post_id' ), 400 );
        }

        if ( ! $this->is_unlocked_for_current_user( $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Not unlocked' ), 403 );
        }

        $post = get_post( $post_id );
        if ( ! $post || 'publish' !== $post->post_status ) {
            wp_send_json_error( array( 'message' => 'Invalid post' ), 404 );
        }

        // Extract inner [paywalled_content]...[/paywalled_content] shortcode content
        $inner = $this->extract_shortcode_inner_content( $post->post_content );

        // Include the optional unlocked content indicator if enabled
        $indicator = '';
        if ( get_option('paybutton_scroll_to_unlocked', '0') === '1' ) {
            $indicator = '<div id="unlocked" class="unlocked-indicator"><span>Unlocked Content Below</span></div>';
        }

        // Render the inner content like the shortcode does
        $GLOBALS['post'] = $post;
        setup_postdata( $post );

        // Some filters check in_the_loop(), so temporarily set it
        global $wp_query;
        $__prev_in_loop = isset( $wp_query ) ? $wp_query->in_the_loop : null;
        if ( isset( $wp_query ) ) {
            $wp_query->in_the_loop = true;
        }

        // Run the full post-content pipeline (blocks, shortcodes, embeds, autop, etc.) filter
        $body = apply_filters( 'the_content', $inner );

        // Restore the flag
        if ( isset( $wp_query ) ) {
            $wp_query->in_the_loop = $__prev_in_loop;
        }
        wp_reset_postdata();

        wp_send_json_success( array(
            'unlocked_html' => $indicator . $body,
        ) );
    }

    /**
     * Checks if the current user has unlocked the specified post.
     *
     * This function first checks if the post ID is present in the cookie-based
     * unlock state. If not found, it then checks the database for an unlock record
     * associated with the user's wallet address (if available).
     *
     * @param int $post_id The ID of the post to check.
     * @return bool True if the post is unlocked for the current user, false otherwise.
    */
    private function is_unlocked_for_current_user( $post_id ) {
        // Cookie-based (set by mark_payment_successful)
        $articles = PayButton_State::get_articles();
        if ( isset( $articles[ $post_id ] ) ) {
            return true;
        }
        // Also allow DB-based unlock if the user has a wallet "login"
        $addr = PayButton_State::get_address();
        if ( $addr ) {
            global $wpdb;
            $table = $wpdb->prefix . 'paybutton_paywall_unlocked';
            $found = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $table WHERE pb_paywall_user_wallet_address = %s AND post_id = %d LIMIT 1",
                sanitize_text_field( $addr ),
                $post_id
            ) );
            if ( $found ) return true;
        }
        return false;
    }

    /**
     * Extracts the inner content of the [paywalled_content] shortcode from the post content.
     *
     * @param string $post_content The full post content.
     * @return string The inner content of the shortcode, or an empty string if not found.
    */
    private function extract_shortcode_inner_content( $post_content ) {
        $inner = '';
        if ( preg_match( '/\\[paywalled_content[^\\]]*\\](.*?)\\[\\/paywalled_content\\]/is', $post_content, $m ) ) {
            $inner = $m[1];
        }
        return $inner;
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

    /**
     * AJAX endpoint to validate a login transaction.
     * This checks that the provided wallet address and tx hash correspond to
     * an unused login transaction. If valid, it generates and attaches a login token 
     * and marks the transaction as used to prevent replay.
    */
    public function ajax_validate_login_tx() {
        check_ajax_referer('paybutton_paywall_nonce', 'security');

        $wallet_address = sanitize_text_field($_POST['wallet_address'] ?? '');
        $tx_hash        = sanitize_text_field($_POST['tx_hash'] ?? '');

        if (empty($wallet_address) || empty($tx_hash)) {
            wp_send_json_error('Missing data');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'paybutton_logins';

        // Only accept unused login tx rows
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table}
            WHERE wallet_address = %s AND tx_hash = %s AND used = 0
            ORDER BY id DESC LIMIT 1",
            $wallet_address, $tx_hash
        ));

        if (!$row) {
            wp_send_json_error('Login validation failed'); // no match or already used
        }

        // Generate a random, unguessable token like "9fx0..._..." so that malicious actors
        // can't fake login attempts by reusing the same wallet address + tx hash using fake
        // AJAX calls from the browser.
        $raw  = random_bytes(18); // 18 bytes → ~24 chars base64url
        $token = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        // Mark as used + attach token
        $wpdb->update(
            $table,
            array(
                'used'        => 1,
                'login_token' => $token,
            ),
            array('id' => (int)$row->id),
            array('%d','%s'),
            array('%d')
        );

        wp_send_json_success(array(
            'login_token' => $token,
        ));
    }

    /**
     * AJAX endpoint to validate a content–unlock transaction.
     * This checks that the provided wallet address + tx hash + post_id
     * correspond to an unused unlock row created by the signed webhook in payment_trigger().
     * If valid, it generates a random unlock_token, attaches it, and marks the row used.
    */
    public function ajax_validate_unlock_tx() {
        check_ajax_referer('paybutton_paywall_nonce', 'security');

        $wallet_address = sanitize_text_field($_POST['wallet_address'] ?? '');
        $tx_hash        = sanitize_text_field($_POST['tx_hash'] ?? '');
        $post_id        = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

        if (empty($wallet_address) || empty($tx_hash) || $post_id <= 0) {
            wp_send_json_error('Missing data');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'paybutton_paywall_unlocked';

        // Only accept unused unlock rows matching this wallet + tx + post
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table}
            WHERE pb_paywall_user_wallet_address = %s
            AND tx_hash = %s
            AND post_id = %d
            AND used = 0
            ORDER BY id DESC
            LIMIT 1",
            $wallet_address,
            $tx_hash,
            $post_id
        ));

        if (!$row) {
            wp_send_json_error('Unlock validation failed'); // no match or already used
        }

        // Generate a random, unguessable token
        $raw   = random_bytes(18); // ~24 chars base64url
        $token = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        // Mark row as used + attach unlock token
        $wpdb->update(
            $table,
            array(
                'used'         => 1,
                'unlock_token' => $token,
            ),
            array( 'id' => (int) $row->id ),
            array( '%d', '%s' ),
            array( '%d' )
        );

        wp_send_json_success(array(
            'unlock_token' => $token,
        ));
    }
}