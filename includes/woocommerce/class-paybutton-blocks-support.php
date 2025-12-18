<?php
/**
 * WooCommerce PayButton Blocks Support
*/
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PayButton Blocks Integration
*/
final class WC_PayButton_Blocks_Support extends AbstractPaymentMethodType {

    protected $name = 'paybutton'; // MUST match the JS name and Gateway ID

    public function initialize() {
    }

    /**
     * Check if the gateway is active using WooCommerce's native logic.
     * This handles defaults and "Toggle" states correctly.
    */
    public function is_active() {
        $gateways = WC()->payment_gateways->payment_gateways();
        
        if ( isset( $gateways[ $this->name ] ) ) {
            return $gateways[ $this->name ]->is_available();
        }
        
        return false;
    }

    /**
     * Register the payment method script handles.
    */
    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-paybutton-blocks',
            PAYBUTTON_PLUGIN_URL . 'assets/js/paybutton-blocks.js',
            array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ),
            '1.0.0',
            true
        );

        return array( 'wc-paybutton-blocks' );
    }

    /**
     * Get payment method data for the blocks checkout.
    */
    public function get_payment_method_data() {
        $gateways = WC()->payment_gateways->payment_gateways();
        $gateway  = isset( $gateways[ $this->name ] ) ? $gateways[ $this->name ] : null;

        return array(
            'title'       => $gateway ? $gateway->get_title() : 'PayButton',
            'description' => $gateway ? $gateway->get_description() : '',
            'icon'        => PAYBUTTON_PLUGIN_URL . 'assets/paybutton-logo.png',
            // NEW: Secondary icon (eCash)
            'icon2'       => PAYBUTTON_PLUGIN_URL . 'assets/icons/eCash.png',
            'supports'    => array( 'products' ),
        );
    }
}