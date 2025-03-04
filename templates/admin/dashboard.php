<!--File: templates/admin/dashboard.php-->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap">
    <h1>PayButton</h1>
    <!-- New heading above the buttons -->
    <h2>Monetize your content with custom no-signup paywalls & donation buttons</h2>

    <div class="paybutton-dashboard-buttons">
        <!-- Button 1: Generate Button -->
        <div class="paybutton-dashboard-button">
            <a href="https://paybutton.org/#button-generator" target="_blank" class="button button-primary paybutton-dashboard-link">
                Add Simple PayButton
            </a>
        </div>
        <!-- Button 2: Paywall Settings -->
        <div class="paybutton-dashboard-button">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=paybutton-paywall' ) ); ?>" class="button button-primary paybutton-dashboard-link">
                Paywall Settings
            </a>
        </div>
        <!-- Button 3: PayButton Woocomerce (Coming soon) -->
        <div class="paybutton-dashboard-button disabled">
            <p class="paybutton-dashboard-text">
                PayButton Woocomerce – Coming soon!
            </p>
        </div>

        <p class="pb-paragraph-margin-top">
            Sign up for a <a href="https://paybutton.org/signup" target="_blank">FREE PayButton account</a> to get access to advanced payment tracking &amp; business features.
        </p>
    </div>

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
