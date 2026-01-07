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

        // AJAX endpoint to get sticky header HTML for auto-login after content unlock
        add_action( 'wp_ajax_paybutton_get_sticky_header', array( $this, 'get_sticky_header' ) );
        add_action( 'wp_ajax_nopriv_paybutton_get_sticky_header', array( $this, 'get_sticky_header' ) );

        // WooCommerce Order Status Polling
        add_action( 'wp_ajax_paybutton_check_order_status', array( $this, 'check_order_status' ) );
        add_action( 'wp_ajax_nopriv_paybutton_check_order_status', array( $this, 'check_order_status' ) );
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
        $user_addr_raw  = $json['user_address'][0]['address'] ?? ($json['user_address'][0] ?? '');
        $currency_raw   = $json['currency']               ?? '';
        $fiatValue      = $json['value']                  ?? 0;

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
        $verification_result = PayButton_Transactions::verify_signature(
            $payload,
            $signature,
            $public_key
        );

        if (!$verification_result) {
            wp_send_json_error(['message' => 'Signature verification failed.']);
            return;
        }
        // error_log('[paybutton] signature ok');

        // Ensure OP_RETURN (rawMessage) is cryptographically bound to the payload
        $op_return = (string) $post_id_raw;
        $op_return = trim($op_return);

        if ( $op_return === '' || strpos( $payload, $op_return ) === false ) {
            wp_send_json_error(
                [ 'message' => 'Payload does not match OP_RETURN.' ],
                400
            );
            return;
        }

        if ( $tx_hash_raw && strpos( $payload, $tx_hash_raw ) === false ) {
            wp_send_json_error(
                [ 'message' => 'Payload does not match tx_hash.' ],
                400
            );
            return;
        }

        //Sanitize data
        $post_id      = intval( $post_id_raw );
        $tx_hash      = sanitize_text_field( $tx_hash_raw );
        $tx_amount = (float) $tx_amount_raw;
        $tx_timestamp = intval( $ts_raw );
        $user_address = sanitize_text_field( $user_addr_raw );
        $currency     = sanitize_text_field( $currency_raw );
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

            PayButton_Transactions::record_login_tx_if_new(
                $user_address,
                $tx_hash,
                (float) $tx_amount,
                (int) $tx_timestamp
            );

            wp_send_json_success(['message' => 'Login tx recorded']);
            return;
        }

        // --- BRANCH 2: WOOCOMMERCE PAYMENTS ---
        if ( class_exists( 'WooCommerce' ) ) {
            $order = wc_get_order( $post_id );
            
            if ( $order && $order instanceof WC_Order ) {
                
                // 1. Check if already paid
                if ( $order->is_paid() ) {
                    wp_send_json_success( array( 'message' => 'Order already paid' ) );
                    return;
                }

                // 2. Validate Amount using Fiat Value (USD) from Webhook
                // We compare the webhook's USD value ($fiatValue) against the Order Total.
                
                $expected_fiat = (float) $order->get_total();
                // Allow small epsilon for floating point math
                if ( $fiatValue < ( $expected_fiat - 0.05 ) ) {
                     // Add note about underpayment but don't mark complete yet
                     $order->add_order_note( sprintf( 'Underpayment detected. Expected $%s but received $%s worth of crypto. Tx: %s', $expected_fiat, $fiatValue, $tx_hash ) );
                     wp_send_json_error( array( 'message' => 'Insufficient fiat value.' ), 400 );
                     return;
                }

                // 3. Mark as Paid
                $order->payment_complete( $tx_hash );
                
                // 4. Add informative note
                $note = sprintf( 'PayButton Payment Received via Webhook. Value: $%s. Tx Hash: %s', $fiatValue, $tx_hash );
                $order->add_order_note( $note );
                $order->update_meta_data( '_paybutton_tx_hash', $tx_hash );
                $order->update_meta_data( '_paybutton_fiat_value', $fiatValue );
                $order->save();
                
                wp_send_json_success( array( 'message' => 'WooCommerce Order Updated' ) );
                return; // Stop processing
            }
        }
        // Convert timestamp to MySQL datetime
        $mysql_timestamp = $tx_timestamp ? gmdate('Y-m-d H:i:s', $tx_timestamp) : '0000-00-00 00:00:00';

        /*
         * Real-time price + currency validation and DB write:
         *  - Use numeric post_id
         *  - Use webhook-supplied currency field (payload->currency)
         *  - Parse expected price/unit from shortcode
         *  - Enforce numeric price (>= expected within epsilon)
         *  - Enforce unit equality using webhook currency
        */
        $incoming_unit = '';
        if ( ! empty( $currency ) ) {
            $incoming_unit = strtoupper( $currency );
        } else {
            wp_send_json_error( array( 'message' => 'Missing currency/unit in payload.' ), 400 );
            return;
        }

        if ( $post_id <= 0 || empty( $user_address ) ) {
            wp_send_json_error( array( 'message' => 'Missing post_id or user_address.' ), 400 );
            return;
        }

        // Get expected price and unit by parsing shortcode in the post content
        $required = PayButton_Transactions::get_paywall_requirements( $post_id );
        if ( $required === null ) {
            wp_send_json_error( array( 'message' => 'Post not configured for paywall.' ), 400 );
            return;
        }

        $expected_price = floatval( $required['price'] );
        $expected_unit = strtoupper( $required['unit'] );
        
        if (
            ! PayButton_Transactions::validate_price_and_unit(
                (float) $tx_amount,
                $incoming_unit,
                $expected_price,
                $expected_unit
            )
        ) {
            wp_send_json_error(
                array( 'message' => 'Invalid payment amount or currency.' ),
                400
            );
            return;
        }

        // Passed validation -> store unlock in DB
        $is_logged_in = 0;
        PayButton_Transactions::insert_unlock_if_new(
            sanitize_text_field( $user_address ),
            $post_id,
            sanitize_text_field( $tx_hash ),
            (float) $tx_amount,
            $mysql_timestamp,
            $is_logged_in
        );

        wp_send_json_success();
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
        $tx_amount    = isset( $_POST['tx_amount'] ) ? (float) $_POST['tx_amount'] : 0.0;
        $tx_timestamp = isset( $_POST['tx_timestamp'] ) ? sanitize_text_field( $_POST['tx_timestamp'] ) : '';
        // NEW: Address passed from front-end if user is not logged in
        $user_address = isset( $_POST['user_address'] ) ? sanitize_text_field( $_POST['user_address'] ) : '';
        $unlock_token = isset( $_POST['unlock_token'] ) ? sanitize_text_field( $_POST['unlock_token'] ) : '';

        if ( $post_id <= 0 || empty( $tx_hash ) || empty( $user_address ) || empty( $unlock_token ) ) {
                wp_send_json_error( array( 'message' => 'Missing required payment fields.' ), 400 );
        }

        // Verify that this token corresponds to a row inserted by the signed webhook in Payment_Trigger().
        global $wpdb;
        $table = $wpdb->prefix . 'paybutton_paywall_unlocked';

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, pb_paywall_user_wallet_address FROM {$table}
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

        $wallet_address = sanitize_text_field($row->pb_paywall_user_wallet_address);
        // 🔐 Auto-login from wallet address returned in the server-verified row
        if ( ! empty( $wallet_address ) && PayButton_State::get_address() !== $wallet_address ) {
            PayButton_State::set_address( $wallet_address );
        }

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
        $body = apply_filters( 'the_content', $inner ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook

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

        $token = PayButton_Transactions::consume_row_and_attach_token(
            $wpdb->prefix . 'paybutton_logins',
            [
                'wallet_address' => $wallet_address,
                'tx_hash'        => $tx_hash,
            ],
            'login_token'
        );

        if (!$token) {
            wp_send_json_error('Login validation failed');
        }

        wp_send_json_success(['login_token' => $token]);
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

        $token = PayButton_Transactions::consume_row_and_attach_token(
            $wpdb->prefix . 'paybutton_paywall_unlocked',
            [
                'pb_paywall_user_wallet_address' => $wallet_address,
                'tx_hash' => $tx_hash,
                'post_id' => $post_id,
            ],
            'unlock_token'
        );

        if (!$token) {
            wp_send_json_error('Unlock validation failed');
        }

        wp_send_json_success(['unlock_token' => $token]);
    }

    /** 
     * AJAX endpoint to get sticky header HTML for auto-login after content unlock.
    */
    public function get_sticky_header() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );

        if ( get_option( 'paybutton_hide_sticky_header', '0' ) === '1' ) {
            wp_send_json_success( array( 'html' => '' ) );
        }

        $template = PAYBUTTON_PLUGIN_DIR . 'templates/public/sticky-header.php';
        if ( ! file_exists( $template ) ) {
            wp_send_json_error( array( 'message' => 'Sticky header template not found.' ), 500 );
        }

        // IMPORTANT: use the same name as in output_sticky_header() and sticky-header.php
        $paybutton_user_wallet_address = sanitize_text_field( PayButton_State::get_address() );

        ob_start();
        include $template;
        $html = ob_get_clean();

        wp_send_json_success( array(
            'html' => $html,
        ) );
    }

    /**
     * Polls to see if a WooCommerce order has been paid.
    */
    public function check_order_status() {
        check_ajax_referer( 'paybutton_paywall_nonce', 'security' );
        
        if ( ! class_exists( 'WooCommerce' ) ) wp_send_json_error();

        $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $order    = wc_get_order( $order_id );

        if ( $order && $order->is_paid() ) {
            wp_send_json_success();
        }
        
        // Also check if status is processing (for manual verification workflows)
        if ( $order && ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) ) {
            wp_send_json_success();
        }

        wp_send_json_error();
    }
}