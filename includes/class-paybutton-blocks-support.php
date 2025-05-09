<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class PayButton_Blocks_Support extends AbstractPaymentMethodType {

    protected $name = 'paybutton';
    private $gateway;

    public function initialize() {
        $this->gateway = new WC_Gateway_PayButton();
        $this->settings = $this->gateway->settings;
    }

    public function is_active() {
        $is_active = $this->gateway->is_available();
        return $is_active;
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'paybutton-blocks-integration',
            PAYBUTTON_PLUGIN_URL . 'assets/js/paybutton-blocks.js',
            array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' ),
            '1.0.1',
            true
        );
        return array( 'paybutton-blocks-integration' );
    }

    public function get_payment_method_data() {
        return array(
            'title'       => $this->gateway->title,
            'description' => $this->gateway->description,
            'icon'        => $this->gateway->icon,
            'supports'    => $this->gateway->supports,
        );
    }
}