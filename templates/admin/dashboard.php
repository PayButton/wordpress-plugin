<!--File: templates/admin/dashboard.php-->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap">
    <div class="pb-header">
        <img class="paybutton-logo" src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/paybutton-logo.png' ); ?>" alt="PayButton Logo">
    </div>
    <!-- New heading above the buttons -->
    <h2>Monetize your content with custom no-signup paywalls & donation buttons</h2>

    <div class="paybutton-dashboard-buttons">
        <!-- Button 1: Generate Button -->
        <div class="paybutton-dashboard-button">
            <a href="<?php echo esc_url( $generate_button_url ); ?>" class="button button-primary paybutton-dashboard-link">
                PayButton Generator
            </a>
        </div>
        <!-- Button 2: Paywall Settings -->
        <div class="paybutton-dashboard-button">
        <a href="<?php echo esc_url( $paywall_settings_url ); ?>" class="button button-primary paybutton-dashboard-link">
                Paywall Settings
            </a>
        </div>
        <!-- Button 3: PayButton WooCommerce -->
        <div class="paybutton-dashboard-button">
            <a href="<?php echo esc_url( $woocommerce_payments_url ); ?>" class="button button-primary paybutton-dashboard-link">
                    WooCommerce Payments
            </a>
        </div>
    </div>

    <p class="pb-paragraph-margin-top">
            Sign up for a <a href="https://paybutton.org/signup" target="_blank">FREE PayButton account</a> to get access to advanced payment tracking &amp; business features.
    </p>
    <!-- New icon block below the buttons -->
    <div class="paybutton-dashboard-icons">
        <!-- Home Icon -->
        <a href="https://paybutton.org/" target="_blank" class="paybutton-dashboard-icon-link">
            <img src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/icons/home.png' ); ?>" alt="Home" width="16" height="16" class="paybutton-dashboard-icon">
        </a>
        <!-- X (Twitter) Icon -->
        <a href="https://x.com/thepaybutton" target="_blank" class="paybutton-dashboard-icon-link">
            <img src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/icons/x.png' ); ?>" alt="X" width="16" height="16" class="paybutton-dashboard-icon">
        </a>
        <!-- Telegram Icon -->
        <a href="https://t.me/paybutton" target="_blank" class="paybutton-dashboard-icon-link">
            <img src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/icons/telegram.png' ); ?>" alt="Telegram" width="16" height="16" class="paybutton-dashboard-icon">
        </a>
        <!-- Github Icon -->
        <a href="https://github.com/PayButton/wordpress-plugin" target="_blank" class="paybutton-dashboard-icon-link">
            <img src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/icons/github.png' ); ?>" alt="Github" width="16" height="16" class="paybutton-dashboard-icon">
        </a>
    </div>
</div>