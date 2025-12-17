<?php
/**
 * WooCommerce PayButton Gateway Integration
 *
 * Registers the PayButton payment gateway with WooCommerce and adds Gutenberg block support.
*/
if ( ! defined( 'ABSPATH' ) ) exit;

// We hook into plugins_loaded to ensuring WC is loaded first
add_action( 'plugins_loaded', 'init_wc_gateway_paybutton' );

function init_wc_gateway_paybutton() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    class WC_Gateway_PayButton extends WC_Payment_Gateway {

        public function __construct() {
            $this->id                 = 'paybutton';
            $this->icon               = PAYBUTTON_PLUGIN_URL . 'assets/icon-128x128.jpg'; 
            $this->has_fields         = false;
            $this->method_title       = 'PayButton';
            $this->method_description = 'Accept eCash (XEC) payments directly via PayButton.';

            // Load settings
            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );

            // Admin Options Save Action
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            
            // Inject PayButton on the Thank You page
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
        }

        /**
         * Initialize Gateway Settings Form Fields
        */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Enable/Disable',
                    'type'    => 'checkbox',
                    'label'   => 'Enable PayButton Payment',
                    'default' => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'default'     => 'Pay with eCash (XEC)',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'default'     => 'Pay securely using your eCash wallet.',
                ),
                'address' => array(
                    'title'       => 'Wallet Address',
                    'type'        => 'text',
                    'description' => 'The eCash wallet address where you want to receive payments.',
                    'default'     => '', 
                    'desc_tip'    => true,
                    'placeholder' => 'ecash:qr...',
                ),
            );
        }

        /**
         * Overridden to prevent activation if address is missing.
        */
        public function process_admin_options() {

            // Is the admin trying to enable the gateway?
            $is_enabling = isset( $_POST['woocommerce_paybutton_enabled'] );

            // Gateway-specific wallet address ONLY
            $posted_address = isset( $_POST['woocommerce_paybutton_address'] )
                ? sanitize_text_field( $_POST['woocommerce_paybutton_address'] )
                : '';

            // Block enabling if address is missing
            if ( $is_enabling && empty( $posted_address ) ) {

                WC_Admin_Settings::add_error(
                    __( 'PayButton Error: You must enter a wallet address to use this payment method.', 'paybutton' )
                );

                // Force-disable the gateway
                unset( $_POST['woocommerce_paybutton_enabled'] );
            }

            parent::process_admin_options();
        }

        /**
         * Process the payment and return the result.
        */
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            $order->update_status( 'on-hold', __( 'Awaiting PayButton payment.', 'paybutton' ) );
            $order->reduce_order_stock();
            WC()->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }

        /**
         * Output for the order received page.
        */
        public function thankyou_page( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) return;

            if ( $order->is_paid() ) {
                echo '<div class="woocommerce-message woocommerce-message--success">';
                echo '<strong>Payment confirmed!</strong><br>';
                echo 'Your payment has been received and your order is now being processed.';
                echo '</div>';
                return;
            }

            // Get Address (Gateway Setting -> Global Fallback)
            $address = $this->get_option( 'address' );
            
            if ( empty( $address ) ) {
                // Should logically not happen if we block activation, but good as a failsafe
                return;
            }

            $amount_formatted = $order->get_formatted_order_total();
            $pay_text = 'Pay ' . strip_tags( $amount_formatted ); 

            $config = array(
                'to'          => $address,
                'amount'      => (float) $order->get_total(),
                'currency'    => $order->get_currency(),
                'text'        => $pay_text,               
                'hoverText'   => 'Click to Pay',
                'opReturn'    => (string) $order_id, 
                'successText' => 'Payment Received! Processing...',
                'autoClose'   => true
            );

            wp_enqueue_script( 
                'paybutton-woo', 
                PAYBUTTON_PLUGIN_URL . 'assets/js/paybutton-woo.js', 
                array( 'jquery', 'paybutton-core' ), 
                '1.0', 
                true 
            );
            
            wp_localize_script( 'paybutton-woo', 'PaywallAjax', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'paybutton_paywall_nonce' )
            ));

            echo '<h3>Complete your payment</h3>';
            echo '<div class="paybutton-woo-container" data-config="' . esc_attr( json_encode( $config ) ) . '" style="margin: 20px 0;"></div>';
        }

        /**
         * Check if the gateway is available for use.
        */
        public function is_available() {

            if ( 'yes' !== $this->get_option( 'enabled' ) ) {
                return false;
            }

            $address = $this->get_option( 'address' );

            if ( empty( $address ) ) {
                return false;
            }

            return parent::is_available();
        }
    }
}