<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class PayButton_State {

    /**
     * cookie names & session-only cookies 
    */
    const COOKIE_USER_ADDR = 'paybutton_user_wallet_address';
    const COOKIE_CONTENT  = 'paybutton_paid_content';
    const TTL         = 604800; // one week

    /**
     * Generate HMAC of a value using WP auth salt.
     * The function computes a SHA-256 hash of the value $message combined with 
     * a secret key (wp_salt('auth')).
    */
    private static function hmac( $message ) {
        return hash_hmac( 'sha256', $message, wp_salt( 'auth' ) );
    }

    /**
     * Build a fingerprint string from client headers
    */
    private static function fingerprint() {
        // Get and sanitize User-Agent
        $ua_raw   = $_SERVER['HTTP_USER_AGENT']      ?? '';
        $ua       = sanitize_text_field( wp_unslash( $ua_raw ) );

        // Get and validate IP address (support IPv4 and IPv6)
        $ip_raw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        // Handle multiple IPs in X-Forwarded-For
        $ip_raw = sanitize_text_field( trim( explode( ',', $ip_raw )[0] ) ); // Trim after selecting first IP

        // Default if we can’t extract a stable prefix
        $ip_prefix = '';

        // IPv4: grab the first two octets
        if ( filter_var( $ip_raw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            $octets = explode( '.', $ip_raw );
            if ( count( $octets ) >= 2 ) {
                $ip_prefix = $octets[0] . '.' . $octets[1];
            }

        // IPv6: first two hextets
        } elseif ( filter_var( $ip_raw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
            // Normalize IP to its full canonical form (optional)
            // https://www.php.net/manual/en/function.inet-ntop.php
            // Inet_pton converts IPv6 to binary, inet_ntop converts it back to string
            $normalized_ip = inet_ntop( inet_pton( $ip_raw ) );
            $hextets = explode( ':', $normalized_ip );
            if ( count( $hextets ) >= 2 ) {
                $ip_prefix = strtolower( $hextets[0] . ':' . $hextets[1] );
            }
        }
        
        // Get and sanitize Accept-Language
        $lang_raw = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $lang_clean = sanitize_text_field(wp_unslash($lang_raw));
        $lang = preg_match('/^[a-zA-Z]{1,3}(?:-[a-zA-Z]{1,3})?(?:,[a-zA-Z]{1,3}(?:-[a-zA-Z]{1,3})?)*$/i', $lang_clean) ? $lang_clean : '';
        
        return "{$ua}|{$ip_prefix}|{$lang}";
    }

    /**
     * Compose the cookie payload|fpHash|mac
    */
    private static function make_cookie_value( $payload ) {
        $fpHash = self::hmac( self::fingerprint() );
        $mac    = self::hmac( "{$payload}|{$fpHash}" );
        return "{$payload}|{$fpHash}|{$mac}";
    }

    /**
     * Verify the cookie structure, HMAC, and fingerprint
    */
    private static function verify_and_extract( $cookie, &$out_payload ) {
        //Sanitaize the cookie value
        $cookie = sanitize_text_field( wp_unslash( $cookie ) );
        //Split the stored cookie into three parts
        list( $payload, $fpHash, $mac ) = explode( '|', $cookie, 3 ) + [ '', '', '' ];
        // 1) verify HMAC
        if ( ! hash_equals( self::hmac( "{$payload}|{$fpHash}" ), $mac ) ) {
            return false;
        }
        // 2) verify fingerprint
        if ( ! hash_equals( self::hmac( self::fingerprint() ), $fpHash ) ) {
            return false;
        }
        $out_payload = $payload;
        return true;
    }

    /**
     * Store the user wallet address in a cookie tied to fingerprint
    */
    public static function set_address( $addr ) {
        $addr = sanitize_text_field( (string) $addr );
        $cookieValue = self::make_cookie_value( $addr );

        if (isset($_COOKIE[self::COOKIE_USER_ADDR]) &&
            hash_equals($_COOKIE[self::COOKIE_USER_ADDR], $cookieValue)) {
            return;   // nothing new → don’t send a Set-Cookie header, good for caching
        }

        if ( PHP_VERSION_ID >= 70300 ) {
            setcookie(
                self::COOKIE_USER_ADDR,
                $cookieValue,
                [
                    'expires'  => time() + self::TTL,
                    'path'     => '/',
                    'domain'   => COOKIE_DOMAIN ?: '',
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        } else {
            //Fall back to a raw header with SameSite=Lax for older PHP versions
            $expiry = gmdate( 'D, d-M-Y H:i:s T', time() + self::TTL );
            $header = sprintf(
                '%s=%s; Expires=%s; Path=%s; Domain=%s; %s; HttpOnly; SameSite=Lax',
                self::COOKIE_USER_ADDR,
                $cookieValue,
                $expiry,
                '/',
                COOKIE_DOMAIN ?: '',
                is_ssl() ? 'Secure' : ''
            );
            header( 'Set-Cookie: ' . $header, false );
        }
        $_COOKIE[ self::COOKIE_USER_ADDR ] = $cookieValue;
    }

    /**
     * Retrieve and validate the wallet address from cookie
    */
    public static function get_address() {
        if ( empty( $_COOKIE[ self::COOKIE_USER_ADDR ] ) ) {
            return '';
        }
        // Sanitize the cookie value before verify_and_extract
        $cookie = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_USER_ADDR ] ) );

        if ( ! self::verify_and_extract( $cookie, $addr ) ) {
            return '';
        }
        return $addr;
    }

    /**
     * Clear the wallet address cookie
    */
    public static function clear_address() {
        if ( PHP_VERSION_ID >= 70300 ) {
            setcookie(
                self::COOKIE_USER_ADDR,
                '',
                [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'domain'   => COOKIE_DOMAIN ?: '',
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        } else {
            //Fall back to a raw header with SameSite=Lax for older PHP versions
            $header = sprintf(
                '%s=; Expires=%s; Path=%s; Domain=%s; %s; HttpOnly; SameSite=Lax',
                self::COOKIE_USER_ADDR,
                gmdate( 'D, d-M-Y H:i:s T', time() - 3600 ),
                '/',
                COOKIE_DOMAIN ?: '',
                is_ssl() ? 'Secure' : ''
            );
            header( 'Set-Cookie: ' . $header, false );
        }
        unset( $_COOKIE[ self::COOKIE_USER_ADDR ] );
    }

    /**
     * Add a post ID to the unlocked content cookie
    */
    public static function add_article( $post_id ) {
        $list = array_keys( self::get_articles() );
        $list[] = (int) $post_id;
        $json   = wp_json_encode( array_values( array_unique( $list ) ) );
        $payload = base64_encode( $json );
        $cookieValue = self::make_cookie_value( $payload );

        if ( isset( $_COOKIE[ self::COOKIE_CONTENT ] ) &&
            hash_equals( $_COOKIE[ self::COOKIE_CONTENT ], $cookieValue ) ) {
            return; // nothing new → don’t send a Set-Cookie header, good for caching
        }
        
        if ( PHP_VERSION_ID >= 70300 ) {
            setcookie(
                self::COOKIE_CONTENT,
                $cookieValue,
                [
                    'expires'  => time() + self::TTL,
                    'path'     => '/',
                    'domain'   => COOKIE_DOMAIN ?: '',
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        } else {
            //Fall back to a raw header with SameSite=Lax for older PHP versions
            $expiry = gmdate( 'D, d-M-Y H:i:s T', time() + self::TTL );
            $header = sprintf(
                '%s=%s; Expires=%s; Path=%s; Domain=%s; %s; HttpOnly; SameSite=Lax',
                self::COOKIE_CONTENT,
                $cookieValue,
                $expiry,
                '/',
                COOKIE_DOMAIN ?: '',
                is_ssl() ? 'Secure' : ''
            );
            header( 'Set-Cookie: ' . $header, false );
        }
        $_COOKIE[ self::COOKIE_CONTENT ] = $cookieValue;
    }

    /**
     * Get the list of unlocked post IDs from cookie
    */
    public static function get_articles() {
        if ( empty( $_COOKIE[ self::COOKIE_CONTENT ] ) ) {
            return [];
        }

        // Sanitize the cookie value before verify_and_extract
        $cookie = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_CONTENT ] ) );

        if ( ! self::verify_and_extract( $cookie, $payload ) ) {
            return [];
        }
        $json = base64_decode( $payload );
        $article_ids    = json_decode( wp_unslash( $json ), true );
        return is_array( $article_ids ) ? array_fill_keys( $article_ids, true ) : [];
    }

    /**
     * Clear the unlocked content cookie
    */
    public static function clear_articles() {
        if ( PHP_VERSION_ID >= 70300 ) {
            setcookie(
                self::COOKIE_CONTENT,
                '',
                [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'domain'   => COOKIE_DOMAIN ?: '',
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        } else {
            //Fall back to a raw header with SameSite=Lax for older PHP versions
            $header = sprintf(
                '%s=; Expires=%s; Path=%s; Domain=%s; %s; HttpOnly; SameSite=Lax',
                self::COOKIE_CONTENT,
                gmdate( 'D, d-M-Y H:i:s T', time() - 3600 ),
                '/',
                COOKIE_DOMAIN ?: '',
                is_ssl() ? 'Secure' : ''
            );
            header( 'Set-Cookie: ' . $header, false );
        }
        unset( $_COOKIE[ self::COOKIE_CONTENT ] );
    }
}