<?php
/**
 * PayButton Transactions Service
 *
 * Shared transaction verification and replay-protection helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PayButton_Transactions {

    /* ============================================================
     * Cryptography
     * ============================================================
    */

    public static function verify_signature(
        string $payload,
        string $signature_hex,
        string $public_key_hex
    ): bool {

        if ($payload === '' || $signature_hex === '' || $public_key_hex === '') {
            return false;
        }

        $binary_signature  = hex2bin($signature_hex);
        $binary_public_key = hex2bin($public_key_hex);

        if (!$binary_signature || !$binary_public_key) {
            return false;
        }

        // Handle DER-wrapped public key (44 bytes)
        if (strlen($binary_public_key) === 44) {
            $binary_public_key = substr($binary_public_key, 12);
        }

        $binary_payload = mb_convert_encoding(
            $payload,
            'ISO-8859-1',
            'UTF-8'
        );

        return sodium_crypto_sign_verify_detached(
            $binary_signature,
            $binary_payload,
            $binary_public_key
        );
    }

    /* ============================================================
     * Tokens & replay protection
     * ============================================================
    */

    public static function generate_secure_token(): string {
        $raw = random_bytes(18); // ~24 chars base64url
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function consume_row_and_attach_token(
        string $table,
        array $where,
        string $token_column
    ): ?string {

        global $wpdb;

        // Explicit table whitelist
        $allowed_tables = [
            $wpdb->prefix . 'paybutton_logins',
            $wpdb->prefix . 'paybutton_paywall_unlocked',
        ];

        if ( ! in_array( $table, $allowed_tables, true ) ) {
            return null;
        }

        // Explicit column whitelist
        $allowed_columns = [
            'wallet_address',
            'tx_hash',
            'used',
        ];

        $conditions = [];
        $values     = [];

        foreach ( $where as $column => $value ) {

            if ( ! in_array( $column, $allowed_columns, true ) ) {
                continue;
            }

            $conditions[] = "`{$column}` = %s";
            $values[]     = $value;
        }

        if ( empty( $conditions ) ) {
            return null;
        }

        // We construct the SQL dynamically here because the WHERE clause varies.
        $sql = "
            SELECT id
            FROM `{$table}`
            WHERE " . implode( ' AND ', $conditions ) . "
            AND used = 0
            ORDER BY id DESC
            LIMIT 1
        ";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is constructed dynamically with whitelisted columns and placeholders.
        $row = $wpdb->get_row( $wpdb->prepare( $sql, ...$values ) );

        if (!$row) {
            return null;
        }

        $token = self::generate_secure_token();

        $wpdb->update(
            $table,
            [
                'used'        => 1,
                $token_column => $token,
            ],
            [ 'id' => (int) $row->id ],
            [ '%d', '%s' ],
            [ '%d' ]
        );

        return $token;
    }

    /* ============================================================
     * Price & currency validation
     * ============================================================
    */

    public static function validate_price_and_unit(
        float $paid_amount,
        string $paid_unit,
        float $expected_price,
        string $expected_unit
    ): bool {

        $epsilon = 0.05;

        if ($paid_amount + $epsilon < $expected_price) {
            return false;
        }

        if (strtoupper($paid_unit) !== strtoupper($expected_unit)) {
            return false;
        }

        return true;
    }

    /* ============================================================
     * Store the unlock information in the database.
     * ============================================================
    */
    public static function insert_unlock_if_new(
        string $address,
        int $post_id,
        string $tx_hash,
        float $tx_amount,
        string $tx_dt,
        int $is_logged_in
    ): void {

        global $wpdb;
        $table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';

        // Check if the transaction already exists using tx hash
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE tx_hash = %s LIMIT 1",
                $tx_hash
            )
        );

        if ($exists) {
            return; // Transaction already recorded, so we don't insert again.
        }

        // Insert the transaction if it's not already recorded
        $wpdb->insert(
            $table_name,
            array(
                'pb_paywall_user_wallet_address' => $address,
                'post_id'                        => $post_id,
                'tx_hash'                        => $tx_hash,
                'tx_amount'                      => $tx_amount,
                'tx_timestamp'                   => $tx_dt,
                'is_logged_in'                   => $is_logged_in,
            ),
            array( '%s', '%d', '%s', '%f', '%s', '%d' )
        );
    }

    /* ============================================================
     * Store the login tx information in the database.
     * ============================================================
    */

    public static function record_login_tx_if_new(
        string $wallet_address,
        string $tx_hash,
        float $tx_amount,
        int $tx_timestamp
    ): void {

        global $wpdb;
        $table = $wpdb->prefix . 'paybutton_logins';

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE wallet_address = %s AND tx_hash = %s LIMIT 1",
                $wallet_address,
                $tx_hash
            )
        );

        if ($exists) {
            return;
        }

        $wpdb->insert(
            $table,
            [
                'wallet_address' => $wallet_address,
                'tx_hash'        => $tx_hash,
                'tx_amount'      => $tx_amount,
                'tx_timestamp'   => $tx_timestamp,
                'used'           => 0,
            ],
            ['%s','%s','%f','%d','%d']
        );
    }


    /**
     * Get expected paywall price and unit for a post/page by parsing its
     * first [paywalled_content] shortcode.
     *
     * @param int $post_id
     * @return array|null Array( 'price' => float, 'unit' => string ) or null if not paywalled.
    */
    public static function get_paywall_requirements( int $post_id ): ?array {

        $post_id = absint( $post_id );
        if ( ! $post_id ) {
            return null;
        }

        $post = get_post( $post_id );
        if ( ! $post || ! isset( $post->post_content ) ) {
            return null;
        }

        $content = $post->post_content;

        // Capture the first [paywalled_content ...] opening tag attributes
        if ( preg_match( '/\[paywalled_content([^\]]*)\]/i', $content, $matches ) ) {
            $atts_raw = isset( $matches[1] ) ? $matches[1] : '';
            $atts     = shortcode_parse_atts( $atts_raw );

            $price = null;
            $unit  = '';

            if ( isset( $atts['price'] ) && $atts['price'] !== '' ) {
                $price = floatval( trim( $atts['price'] ) );
            }

            if ( isset( $atts['unit'] ) && $atts['unit'] !== '' ) {
                $unit  = strtoupper( sanitize_text_field( trim( $atts['unit'] ) ) );
            }

            // Fallbacks to plugin options
            if ( $price === null || $price === 0.0 ) {
                $price = floatval( get_option( 'paybutton_paywall_default_price', 5.5 ) );
            }
            if ( $unit === '' ) {
                $unit = strtoupper( sanitize_text_field( get_option( 'paybutton_paywall_unit', 'XEC' ) ) );
            }

            return array(
                'price' => $price,
                'unit'  => $unit,
            );
        }

        return null;
    }
}