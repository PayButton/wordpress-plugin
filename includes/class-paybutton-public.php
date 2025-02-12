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
        add_shortcode( 'paywalled_content', array( $this, 'paywalled_content_shortcode' ) );
        add_shortcode( 'paybutton_profile', array( $this, 'profile_shortcode' ) );
        add_filter( 'comments_open', array( $this, 'filter_comments_open' ), 999, 2 );
        add_action( 'pre_get_comments', array( $this, 'filter_comments_query' ), 999 );
    }

    /**
     * Enqueue public-facing assets.
     */
    public function enqueue_public_assets() {
        wp_enqueue_style( 'paybutton-sticky-header', PAYBUTTON_PLUGIN_URL . 'assets/css/sticky-header.css', array(), '1.0' );

        // Enqueue your new paywall styles
        wp_enqueue_style( 'paywall-styles', PAYBUTTON_PLUGIN_URL . 'assets/css/paywall-styles.css', array(), '1.0' );

        // Add inline CSS variables.
        $custom_css = "
            :root {
                --sticky-header-bg-color: " . esc_attr( get_option('paybutton_sticky_header_bg_color', '#007bff') ) . ";
                --sticky-header-text-color: " . esc_attr( get_option('paybutton_sticky_header_text_color', '#fff') ) . ";
                --profile-button-bg-color: " . esc_attr( get_option('paybutton_profile_button_bg_color', '#ffc107') ) . ";
                --profile-button-text-color: " . esc_attr( get_option('paybutton_profile_button_text_color', '#000') ) . ";
                --logout-button-bg-color: " . esc_attr( get_option('paybutton_logout_button_bg_color', '#d9534f') ) . ";
                --logout-button-text-color: " . esc_attr( get_option('paybutton_logout_button_text_color', '#fff') ) . ";
            }
        ";
        wp_add_inline_style( 'paybutton-sticky-header', $custom_css );

        // Enqueue the PayButton core script.
        wp_enqueue_script(
            'paybutton-core',
            'https://unpkg.com/@paybutton/paybutton/dist/paybutton.js',
            array(),
            '1.0',
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

        /**
         * Localizes the 'paybutton-cashtab-login' script with variables needed for AJAX interactions.
         *
         * This code makes the following data available to the script as the global object "PaywallAjax":
         * - ajaxUrl: The URL (admin-ajax.php) to which AJAX requests should be sent.
         * - nonce: A security token generated via wp_create_nonce('paybutton_paywall_nonce') to validate AJAX requests.
         * - isUserLoggedIn: A flag (1 if an eCash address exists in the session, 0 otherwise) indicating the payment-based login state.
         * - userAddress: The eCash address stored in the session, or an empty string if not set.
         * - defaultAddress: The default eCash address from the plugin settings.
         *
         * These localized variables allow the front-end script to safely and effectively communicate with the back-end.
        */

        wp_localize_script( 'paybutton-cashtab-login', 'PaywallAjax', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'paybutton_paywall_nonce' ),
            'isUserLoggedIn' => ! empty( $_SESSION['cashtab_ecash_address'] ) ? 1 : 0,
            'userAddress'    => ! empty( $_SESSION['cashtab_ecash_address'] ) ? $_SESSION['cashtab_ecash_address'] : '',
            'defaultAddress' => get_option( 'paybutton_paywall_ecash_address', '' ),
            //Localize the Unlocked Content Indicator variable
            'scrollToUnlocked' => get_option( 'paybutton_scroll_to_unlocked', '1' ),
        ) );
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
        $address = ! empty( $_SESSION['cashtab_ecash_address'] ) ? sanitize_text_field( $_SESSION['cashtab_ecash_address'] ) : '';
        $this->load_public_template( 'sticky-header', array( 'address' => $address ) );
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

    public function paywalled_content_shortcode( $atts, $content = null ) {
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
            'address'     => get_option( 'paybutton_paywall_ecash_address', '' ),
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
                                  <hr>
                                  <p>Unlocked Content Below</p>
                                  <hr>
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
            'opReturn'    => (string) $post_id //This is a hack to give the PB server the post ID to send it back to WP's DB
        );
        ob_start(); //When ob_start() is called, PHP begins buffering all subsequent output instead of printing it to the browser.
        ?>
        <div id="paybutton-container-<?php echo esc_attr( $post_id ); ?>" class="paybutton-container" data-config="<?php echo esc_attr( json_encode( $config ) ); ?>" style="text-align: center;"></div>
        <?php
        return ob_get_clean(); // ob_get_clean() Returns the HTML string to WordPress so it is inserted properly.
    }

    /**
     * Shortcode for displaying the user profile.
     *
     * @return string
     */
    public function profile_shortcode() {
        if ( empty( $_SESSION['cashtab_ecash_address'] ) ) {
            return '<p>You must be logged in to view your unlocked content.</p>';
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';
        $address    = sanitize_text_field( $_SESSION['cashtab_ecash_address'] );
        $rows       = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT post_id FROM $table_name WHERE ecash_address = %s ORDER BY id DESC",
            $address
        ) );
        ob_start();
        $this->load_public_template( 'profile', array( 'address' => $address, 'rows' => $rows ) );
        return ob_get_clean();
    }

    /**
     * Checks if the given post is unlocked for the current user.
     *
     * This function first ensures the session is active, then checks:
     * 1. If the post ID is stored as "unlocked" in the session (`$_SESSION['paid_articles']`).
     * 2. If the user is logged in via PayButton (`$_SESSION['cashtab_ecash_address']`), 
     *    it verifies if the post is unlocked in the database (`is_unlocked_in_db`).
     * 
     * Returns `true` if the content is unlocked, otherwise `false`.
    */

    private function post_is_unlocked( $post_id ) {
        if ( ! session_id() ) {
            session_start();
        }
        if ( ! empty( $_SESSION['paid_articles'][ $post_id ] ) && $_SESSION['paid_articles'][ $post_id ] === true ) {
            return true;
        }
        if ( ! empty( $_SESSION['cashtab_ecash_address'] ) ) {
            $address = $_SESSION['cashtab_ecash_address'];
            if ( $this->is_unlocked_in_db( $address, $post_id ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a post is unlocked in the database.
     *
     * @param string $address
     * @param int $post_id
     * @return bool
     */
    private function is_unlocked_in_db( $address, $post_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paybutton_paywall_unlocked';
        $row = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table_name WHERE ecash_address = %s AND post_id = %d LIMIT 1",
            $address,
            $post_id
        ) );
        return ! empty( $row );
    }

    /**
     * Filter the "comments_open" value to hide comments until content is unlocked.
     *
     * @param bool $open
     * @param int $post_id
     * @return bool
     */
    public function filter_comments_open( $open, $post_id ) {
        if ( '1' !== get_option( 'paybutton_hide_comments_until_unlocked', '1' ) ) {
            return $open;
        }
        $post = get_post( $post_id );
        if ( ! $post ) return $open;
        if ( stripos( $post->post_content, '[paywalled_content' ) === false ) {
            return $open;
        }
        if ( $this->post_is_unlocked( $post_id ) ) {
            return $open;
        }
        return false;
    }

    /**
     * Modify the comments query so that comments aren’t shown on locked posts.
     *
     * @param WP_Comment_Query $comment_query
     */
    public function filter_comments_query( $comment_query ) {
        if ( is_admin() ) return;
        if ( '1' !== get_option( 'paybutton_hide_comments_until_unlocked', '1' ) ) {
            return;
        }
        $post_id = isset( $comment_query->query_vars['post_id'] ) ? $comment_query->query_vars['post_id'] : 0;
        if ( ! $post_id ) return;
        $post = get_post( $post_id );
        if ( ! $post ) return;
        if ( stripos( $post->post_content, '[paywalled_content' ) === false ) {
            return;
        }
        if ( ! $this->post_is_unlocked( $post_id ) ) {
            $comment_query->query_vars['comment__in'] = array(0);
        }
    }
}
