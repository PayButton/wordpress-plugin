<?php
/**
 * PayButton Public Class
 *
 * Handles the public-facing functionality of the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PayButton_Public {

    /**
     * Constructor for the public-facing functionality of the PayButton plugin.
     *
     * Sets up all necessary hooks and shortcodes for the front-end:
     * - Enqueues public CSS/JS assets via 'wp_enqueue_scripts'.
     * - Inserts the sticky header into the page using the 'wp_body_open' hook.
     * - Registers the [paywalled_content] shortcode to handle content unlocking.
     * - Registers the [paybutton_profile] shortcode for displaying the user's profile.
     * - Applies filters on comments via 'comments_open' and 'pre_get_comments' to hide
     *   comments on paywalled posts until the content is unlocked.
    */

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'wp_body_open', array( $this, 'output_sticky_header' ) );
        add_shortcode( 'paywalled_content', array( $this, 'paybutton_paywall_shortcode' ) );
        add_shortcode( 'paybutton_profile', array( $this, 'profile_shortcode' ) );
        add_shortcode( 'paybutton', [ $this, 'paybutton_generator_shortcode' ] );
        // Hard-block comment creation server-side if paywalled content locked
        add_filter( 'preprocess_comment', array( $this, 'block_comment_if_locked' ) );
        // Hard-block comment creation via REST API as well when the paywalled content is locked
        add_filter( 'rest_pre_insert_comment', array( $this, 'block_comment_if_locked_rest' ), 10, 2 );
    }

    /**
     * Enqueue public-facing assets.
     */
    public function enqueue_public_assets() {
        wp_enqueue_style( 'paybutton-sticky-header', PAYBUTTON_PLUGIN_URL . 'assets/css/sticky-header.css', array(), '1.0' );

        // Enqueue our new paywall styles
        wp_enqueue_style( 'paywall-styles', PAYBUTTON_PLUGIN_URL . 'assets/css/paywall-styles.css', array(), '1.0' );

        // Read the admin-chosen color for the unlocked content indicator from options
        $pb_indicator_color = get_option('paybutton_unlocked_indicator_color', '#000000');

        // Read the admin-chosen color for the frontend unlock label
        $frontend_label_color = esc_attr( get_option( 'paybutton_frontend_unlock_color', '#0074C2' ) );

        // Add inline CSS variables.
        $custom_css = "
            :root {
                --sticky-header-bg-color: " . esc_attr( get_option('paybutton_sticky_header_bg_color', '#007bff') ) . ";
                --sticky-header-text-color: " . esc_attr( get_option('paybutton_sticky_header_text_color', '#fff') ) . ";
                --profile-button-bg-color: " . esc_attr( get_option('paybutton_profile_button_bg_color', '#ffc107') ) . ";
                --profile-button-text-color: " . esc_attr( get_option('paybutton_profile_button_text_color', '#000') ) . ";
                --logout-button-bg-color: " . esc_attr( get_option('paybutton_logout_button_bg_color', '#d9534f') ) . ";
                --logout-button-text-color: " . esc_attr( get_option('paybutton_logout_button_text_color', '#fff') ) . ";
                --pb-unlocked-indicator-color: {$pb_indicator_color};
                --pb-frontend-unlock-color: {$frontend_label_color};
            }
        ";
        wp_add_inline_style( 'paybutton-sticky-header', esc_attr( $custom_css ) );

        // Enqueue the PayButton core script.
        wp_enqueue_script(
            'paybutton-core',
            PAYBUTTON_PLUGIN_URL . 'assets/js/paybutton.js', // Local file path
            array(),
            '5.0.2',
            false
        );

        // Enqueue our custom JS files.
        wp_enqueue_script(
            'paybutton-cashtab-login',
            PAYBUTTON_PLUGIN_URL . 'assets/js/paybutton-paywall-cashtab-login.js',
            array('jquery', 'paybutton-core'),
            '1.0',
            true
        );
        wp_enqueue_script(
            'paywalled-content',
            PAYBUTTON_PLUGIN_URL . 'assets/js/paywalled-content.js',
            array('jquery', 'paybutton-core'),
            '1.0',
            true
        );

        // Load a new JS file to render the [paybutton] shortcodes
        wp_enqueue_script(
            'paybutton-generator',
            PAYBUTTON_PLUGIN_URL . 'assets/js/paybutton-generator.js',
            array('paybutton-core'),
            '1.0',
            true
        );

        // Load the comment reply script
        if ( is_singular() && get_option( 'thread_comments' ) ) {
            wp_enqueue_script( 'comment-reply' );
        }

        /**
         * Localizes the 'paybutton-cashtab-login' script with variables needed for AJAX interactions.
         *
         * This code makes the following data available to the script as the global object "PaywallAjax":
         * - ajaxUrl: The URL (admin-ajax.php) to which AJAX requests should be sent.
         * - nonce: A security token generated via wp_create_nonce('paybutton_paywall_nonce') to validate AJAX requests.
         * - isUserLoggedIn: A flag (1 if a user address exists in the session, 0 otherwise) indicating the payment-based login state.
         * - userAddress: The user address stored in the session, or an empty string if not set.
         * - defaultAddress: The default wallet address from the plugin settings.
         *
         * These localized variables allow the front-end script to safely and effectively communicate with the back-end.
        */

        wp_localize_script( 'paybutton-cashtab-login', 'PaywallAjax', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'paybutton_paywall_nonce' ),
            'isUserLoggedIn' => PayButton_State::get_address() ? 1 : 0,
            'userAddress' => sanitize_text_field( PayButton_State::get_address() ),
            'defaultAddress' => get_option( 'paybutton_admin_wallet_address', '' ),
            'scrollToUnlocked' => get_option( 'paybutton_scroll_to_unlocked', '1' ),
        ) );
    }

    /**
     * Renders the [paybutton][/paybutton] shortcode
    */
    public function paybutton_generator_shortcode( $atts, $content = null ) {
        // Provide a default empty JSON if not supplied
        $atts = shortcode_atts( [ 'config' => '{}' ], $atts );
        $decoded = json_decode( $atts['config'], true );
        if ( ! is_array( $decoded ) ) {
            $decoded = [];
        }

        // Encode the config for a data attribute
        $encodedConfig = wp_json_encode( $decoded );

        ob_start();
        ?>
        <div class="paybutton-shortcode-container" data-config="<?php echo esc_attr($encodedConfig); ?>"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Helper method to load a public template.
     *
     * @param string $template_name Template file name (without extension).
     * @param array $args Data to extract.
     */
    private function load_public_template( $template_name, $args = array() ) {
        extract( $args );
        include PAYBUTTON_PLUGIN_DIR . 'templates/public/' . $template_name . '.php';
    }

    /**
     * Output the sticky header HTML.
     */
    public function output_sticky_header() {
        $paybutton_user_wallet_address = sanitize_text_field( PayButton_State::get_address() );
        $this->load_public_template( 'sticky-header', array(
            'paybutton_user_wallet_address' => $paybutton_user_wallet_address
        ) );
    }

    /**
     * Generates the Paywalled Content shortcode [paywalled_content].
     *
     * Ensures paywall logic runs only inside a single post/page (is_singular())
     * and within The Loop (in_the_loop()). If not, the shortcode is ignored to 
     * prevent execution in unintended areas like:
     * -    Widgets, Headers, Sidebars
     * -    Homepage loops with multiple posts
     * Retrieves default paywall settings (price, unit, button text, colors).
     * Merges user-defined attributes ($atts) with defaults.
     * Checks if the post is already unlocked for the user (post_is_unlocked), returning the content if true.
     * Prepares PayButton configuration with payment details and theme settings.
     * Outputs a `div` with encoded PayButton config for front-end handling.
    */

    public function paybutton_paywall_shortcode( $atts, $content = null ) {
        if ( ! is_singular() || ! in_the_loop() ) {
            return '';
        }
        $default_price = get_option( 'paybutton_paywall_default_price', 10 );
        $default_unit  = get_option( 'paybutton_paywall_unit', 'XEC' );
        $default_text  = get_option( 'paybutton_text', 'Pay to Unlock' );
        $default_hover = get_option( 'paybutton_hover_text', 'Send Payment' );
        $color_primary   = get_option( 'paybutton_color_primary', '#0074c2' );
        $color_secondary = get_option( 'paybutton_color_secondary', '#fefbf8' );
        $color_tertiary  = get_option( 'paybutton_color_tertiary', '#000000' );

        $atts = shortcode_atts( array(
            'price'       => $default_price,
            'address'     => get_option( 'paybutton_admin_wallet_address', '' ),
            'unit'        => $default_unit,
            'button_text' => $default_text,
            'hover_text'  => $default_hover,
        ), $atts );

        $post_id = get_the_ID();
        //Modified the unlocked content logic to add the unlock indicator when the content is unlocked
        if ( $this->post_is_unlocked( $post_id ) ) {
            $indicator = '';
            if ( get_option('paybutton_scroll_to_unlocked', '0') === '1' ) {
                $indicator = '<div id="unlocked" class="unlocked-indicator">
                                  <span>Unlocked Content Below</span>
                              </div>';
            }
            return $indicator . do_shortcode( $content );
        }
        // Prepare configuration data for the PayButton.
        $config = array(
            'postId'      => $post_id,
            'to'          => $atts['address'],
            'amount'      => floatval( $atts['price'] ),
            'currency'    => $atts['unit'],
            'buttonText'  => $atts['button_text'],
            'hoverText'   => $atts['hover_text'],
            'successText' => 'Payment Successful!',
            'theme'       => array(
                'palette' => array(
                    'primary'   => $color_primary,
                    'secondary' => $color_secondary,
                    'tertiary'  => $color_tertiary,
                ),
            ),
            'opReturn'    => (string) $post_id, //This is a hack to give the PB server the post ID to send it back to WP's DB
            'autoClose'   => true
        );

        //NEW: If the admin enabled “Show Unlock Count on Front‐end,” and this post is NOT yet unlocked then display unlock count on the front end.
        $unlock_label_html = '';

        if ( '1' === get_option( 'paybutton_enable_frontend_unlock_count', '0' ) ) {
            global $wpdb;
            $unlock_table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';

            $unlock_count = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$unlock_table_name} WHERE post_id = %d",
                $post_id
            ) );

            if ( $unlock_count < 1 ) {
                $unlock_text = '🔓 Be the first to unlock this content!';
            } elseif ( $unlock_count === 1 ) {
                $unlock_text = '🔥 1 unlock and counting...';
            } else {
                $unlock_text = "🔥 {$unlock_count} unlocks and counting...";
            }

            // Build the <p> into a variable, but do not echo yet:
            $unlock_label_html = '<p class="pb-frontend-unlock-count">' 
                            . esc_html( $unlock_text ) 
                            . '</p>';
        }

        ob_start(); //When ob_start() is called, PHP begins buffering all subsequent output instead of printing it to the browser.
        ?>
        <div id="pb-paywall-<?php echo esc_attr( $post_id ); ?>" class="pb-paywall">
            <?php echo wp_kses_post($unlock_label_html) ?>
            <div id="paybutton-container-<?php echo esc_attr( $post_id ); ?>"
                class="paybutton-container"
                data-config="<?php echo esc_attr( json_encode( $config ) ); ?>"
                style="text-align: center;"></div>
        </div>
        <?php
        return ob_get_clean(); // ob_get_clean() Returns the HTML string to WordPress so it is inserted properly.
    }

    /**
     * Shortcode for displaying the user profile.
     *
     * @return string
     */
    public function profile_shortcode() {
        $paybutton_user_wallet_address = sanitize_text_field( PayButton_State::get_address() );
        if ( empty( $paybutton_user_wallet_address ) ) {
            return '<p>You must be logged in to view your unlocked content.</p>';
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';
        $paybutton_rows       = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT post_id FROM $table_name WHERE pb_paywall_user_wallet_address = %s ORDER BY id DESC",
            $paybutton_user_wallet_address
        ) );
        ob_start();
        $this->load_public_template( 'profile', array(
            'paybutton_user_wallet_address' => $paybutton_user_wallet_address,
            'paybutton_rows'                => $paybutton_rows
        ) );
        return ob_get_clean();
    }

    /**
     * Checks if the given post is unlocked for the current user.
     */
    private function post_is_unlocked( $post_id ) {
        if ( isset( PayButton_State::get_articles()[ $post_id ] ) ) {
            return true;
        }
        $addr = PayButton_State::get_address(); if ( $addr ) { 
            $address = sanitize_text_field( $addr );
            if ( $this->is_unlocked_in_db( $address, $post_id ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a post is unlocked in the database.
    */
    private function is_unlocked_in_db( $address, $post_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';
        $row = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table_name WHERE pb_paywall_user_wallet_address = %s AND post_id = %d LIMIT 1",
            $address,
            $post_id
        ) );
        return ! empty( $row );
    }

    /**
     * Is the given post paywalled (contains the [paywalled_content] shortcode)? 
    */
    private function is_paywalled_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) return false;
        return ( stripos( $post->post_content, '[paywalled_content' ) !== false );
    }

    /** 
     * Server-side comment blocking for classic form submissions
    */
    public function block_comment_if_locked( $commentdata ) {
        $post_id = isset( $commentdata['comment_post_ID'] ) ? intval( $commentdata['comment_post_ID'] ) : 0;

        if ( $post_id
            && '1' === get_option( 'paybutton_hide_comments_until_unlocked', '1' )
            && $this->is_paywalled_post( $post_id )
            && ! $this->post_is_unlocked( $post_id ) ) {

            wp_die(
                esc_html__( 'Please unlock this content before posting a comment.', 'paybutton' ),
                esc_html__( 'Unlock required', 'paybutton' ),
                array( 'response' => 403 )
            );
        }
        return $commentdata;
    }

    /** 
     * Server-side comment blocking for REST API (block themes / AJAX submissions) 
    */
    public function block_comment_if_locked_rest( $prepared, $request ) {
        $post_id = isset( $prepared['comment_post_ID'] ) ? (int) $prepared['comment_post_ID'] : 0;

        if ( $post_id
            && '1' === get_option( 'paybutton_hide_comments_until_unlocked', '1' )
            && $this->is_paywalled_post( $post_id )
            && ! $this->post_is_unlocked( $post_id ) ) {

            return new WP_Error(
                'paybutton_paywall_comment_blocked',
                __( 'Please unlock this content before posting a comment.', 'paybutton' ),
                array( 'status' => 403 )
            );
        }
        return $prepared;
    }
}