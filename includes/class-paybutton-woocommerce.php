<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WC_Gateway_PayButton
 *
 * Extends WC_Payment_Gateway to integrate PayButton payments into WooCommerce.
 * Falls back to thank-you page rendering with payment_trigger verification.
 */
class WC_Gateway_PayButton extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'paybutton';
        $this->icon               = PAYBUTTON_PLUGIN_URL . 'assets/icons/pb.png';
        $this->has_fields         = false; // No fields on checkout; render on thank-you
        $this->method_title       = 'PayButton';
        $this->method_description = 'Accept secure eCash (XEC) payments via PayButton.';
        $this->supports           = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option( 'title', 'PayButton' );
        $this->description        = $this->get_option( 'description', 'Pay securely using PayButton (supports XEC, USD, CAD). After placing your order, you’ll complete payment on the next page.' );
        $this->enabled            = $this->get_option( 'enabled', 'yes' );
        $this->receiving_address  = get_option( 'paybutton_paywall_ecash_address', '' );
        $this->public_key         = get_option( 'paybutton_public_key', '' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'receipt_page' ] );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => 'Enable PayButton Payment Gateway',
                'default' => 'yes',
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'The title displayed to customers during checkout.',
                'default'     => 'PayButton',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'The description displayed to customers during checkout.',
                'default'     => 'Pay eCash (XEC) payments securely using PayButton. After placing your order, you’ll complete payment on the next page.',
                'desc_tip'    => true,
            ),
        );
    }

    public function payment_fields() {
        if ( $this->description ) {
            echo wpautop( wp_kses_post( $this->description ) );
        }
        if ( empty( $this->receiving_address ) || empty( $this->public_key ) ) {
            echo '<p style="color: red;">PayButton is not fully configured. Please contact the site administrator.</p>';
        }
    }

    public function payment_scripts() {
        if ( ! is_wc_endpoint_url( 'order-pay' ) ) {
            return;
        }

        wp_enqueue_script(
            'paybutton-core',
            PAYBUTTON_PLUGIN_URL . 'assets/js/paybutton.js', // Local file path
            array(),
            '1.0',
            false
        );

        wp_localize_script( 'paybutton-core', 'PayButtonWooConfig', array(
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'paybutton_woocommerce_nonce' ),
            'receivingAddress'=> $this->receiving_address,
            'primaryColor'    => get_option( 'paybutton_color_primary', '#0074c2' ),
            'secondaryColor'  => get_option( 'paybutton_color_secondary', '#fefbf8' ),
            'tertiaryColor'   => get_option( 'paybutton_color_tertiary', '#000000' ),
        ) );
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        $order->update_status( 'pending', 'Awaiting PayButton payment confirmation via Payment Trigger.' );
        wc_reduce_stock_levels( $order_id );
        WC()->cart->empty_cart();
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url( true ),
        );
    }

    public function receipt_page($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_payment_method() === $this->id && $order->has_status('pending')) {
            $amount = $order->get_total();  // returns the order total as a float/string with the store’s decimals
            $currency = $order->get_currency();
            $supported_currencies = ['XEC', 'USD', 'CAD'];
            if (!in_array($currency, $supported_currencies, true)) {
                $currency = 'USD';
            }
            ?>
            <div id="paybutton-woocommerce-checkout" style="text-align: center;"></div>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                var $container = $("#paybutton-woocommerce-checkout");
                if (
                    $container.length &&
                    typeof PayButton !== "undefined" &&
                    typeof PayButtonWooConfig !== "undefined"
                ) {
                    var config = {
                        to: PayButtonWooConfig.receivingAddress,
                        amount: <?php echo floatval($amount); ?>,
                        currency: "<?php echo esc_js($currency); ?>",
                        text: "Pay Now",
                        hoverText: "Click to Pay",
                        successText: "Payment Initiated!",
                        theme: {
                            palette: {
                                primary: PayButtonWooConfig.primaryColor,
                                secondary: PayButtonWooConfig.secondaryColor,
                                tertiary: PayButtonWooConfig.tertiaryColor,
                            },
                        },
                        opReturn: "wc_order_<?php echo esc_js($order_id); ?>",
    
                        // Here's the important part:
                        onSuccess: function(tx) {
                            console.log("PayButton onSuccess: TX broadcast. TX object:", tx);
                            // For a minimal approach, just redirect:
                            window.location.href = "<?php echo esc_url($order->get_checkout_order_received_url()); ?>";
                        }
                    };
                    PayButton.render($container[0], config);
                }
            });
            </script>
            <?php
        }
    }    

    public function is_available() {
        $is_available = $this->enabled === 'yes' && ! empty( $this->receiving_address ) && ! empty( $this->public_key );
        error_log( 'PayButton: is_available - Enabled: ' . $this->enabled . ', Public Key: ' . ( empty( $this->public_key ) ? 'empty' : 'set' ) . ', eCash Address: ' . ( empty( $this->receiving_address ) ? 'empty' : 'set' ) . ', Result: ' . ( $is_available ? 'true' : 'false' ) );
        return $is_available;
    }
}