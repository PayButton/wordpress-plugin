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

            // Load Frontend Scripts (Separated Logic)
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

            // Render PayButton order metadata in WooCommerce admin
            add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'render_order_admin_panel' ) );
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
            $is_enabling = isset( $_POST['woocommerce_paybutton_enabled'] );
            $posted_address = isset( $_POST['woocommerce_paybutton_address'] )
                ? sanitize_text_field( $_POST['woocommerce_paybutton_address'] )
                : '';

            if ( $is_enabling && empty( $posted_address ) ) {
                WC_Admin_Settings::add_error(
                    __( 'PayButton Error: You must enter a wallet address to use this payment method.', 'paybutton' )
                );
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
         * Separate Script Loading Logic
        */
        public function payment_scripts() {
            if ( ! is_wc_endpoint_url( 'order-received' ) ) return;

            global $wp;
            $order_id = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
            if ( ! $order_id ) return;

            $order = wc_get_order( $order_id );
            if ( ! $order ) return;

            if ( $this->id !== $order->get_payment_method() ) return;
            if ( $order->is_paid() ) return;

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
        }

        /**
         * Output for the order received page.
        */
        public function thankyou_page( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) return;

            if ( $order->is_paid() ) {
                echo '<div class="woocommerce-message woocommerce-message--success">';
                echo '<strong>Payment confirmed! Your order is now being processed.</strong>';
                echo '</div>';
                return;
            }

            $address = $this->get_option( 'address' );
            if ( empty( $address ) ) return;

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
                'autoClose'   => true,
                'size'        => 'xl'
            );

            echo '<h2>Complete your payment</h2>';
            echo '<div class="paybutton-woo-container" data-config="' . esc_attr( json_encode( $config ) ) . '" style="margin: 20px 0;"></div>';
        }

        /**
         * Check if the gateway is available for use.
        */
        public function is_available() {
            if ( 'yes' !== $this->get_option( 'enabled' ) ) return false;
            $address = $this->get_option( 'address' );
            if ( empty( $address ) ) return false;

            return parent::is_available();
        }

        /**
         * Render PayButton metadata in WooCommerce Order Edit screen
        */
        public function render_order_admin_panel( $order ) {
            if ( ! $order instanceof WC_Order ) return;
            if ( $order->get_payment_method() !== 'paybutton' ) return;

            $tx_hash = $order->get_meta( '_paybutton_tx_hash' );
            $val_usd = $order->get_meta( '_paybutton_fiat_value' );

            echo '<div style="clear:both;"></div>';
            echo '<div class="paybutton-order-panel" style="margin-top:18px; border-top:1px solid #eee; width:100%;">';
            echo '<h4>' . esc_html__( 'PayButton Transaction Details', 'paybutton' ) . '</h4>';

            if ( ! $tx_hash ) {
                echo '<p>No PayButton transaction detected.</p>';
            } else {
                echo '<p><strong>Transaction Hash:</strong><br>';
                echo '<a href="https://explorer.e.cash/tx/' . esc_attr( $tx_hash ) . '" target="_blank">';
                echo esc_html( $tx_hash );
                echo '</a></p>';

                if ( $val_usd ) {
                    $formatted_val = number_format( (float) $val_usd, 5, '.', '' );
                    echo '<p><strong>Amount Paid:</strong> $' . esc_html( $formatted_val ) . '</p>';
                }
            }

            echo '</div>';
        }
    }
}