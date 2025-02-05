<div class="wrap">
    <h1>PayButton</h1>
    <!-- New heading above the buttons -->
    <h2>Monetize your content with custom no-signup paywalls & donation buttons</h2>

    <div style="display: flex; gap: 2rem; margin-top: 2rem; flex-wrap: wrap;">
        <!-- Button 1: Generate Button -->
        <div style="flex: 1; min-width: 250px; border: 1px solid #ddd; padding: 2rem; text-align: center;">
            <a href="https://paybutton.org/#button-generator" target="_blank" class="button button-primary" style="font-size: 1.2em; padding: 2rem; display: inline-block; width: 100%;">Add Simple PayButton</a>
        </div>
        <!-- Button 2: Paywall Settings -->
        <div style="flex: 1; min-width: 250px; border: 1px solid #ddd; padding: 2rem; text-align: center;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=paybutton-paywall' ) ); ?>" class="button button-primary" style="font-size: 1.2em; padding: 2rem; display: inline-block; width: 100%;">Paywall Settings</a>
        </div>
        <!-- Button 3: PayButton Woocomerce (Coming soon) -->
        <div style="flex: 1; min-width: 250px; border: 1px solid #ddd; padding: 2rem; text-align: center; opacity: 0.5;">
            <p style="font-size: 1.2em; padding: 2rem; margin: 0;">PayButton Woocomerce – Coming soon!</p>
        </div>

        <p style="margin-top: 1rem;">
            Sign up for a <a href="https://paybutton.org/signup" target="_blank">FREE PayButton account</a> to get access to advanced payment tracking & business features.
        </p>
    </div>

    <!-- New icon block below the buttons -->
    <div style="margin-top: 2rem; text-align: left;">
        <!-- Home Icon -->
        <a href="https://paybutton.org/" target="_blank" style="margin-right: 1rem; text-decoration: none; outline: none;">
            <img src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/icons/home.png' ); ?>" alt="Home" width="16" height="16" style="border: none;">
        </a>
        <!-- X (Twitter) Icon -->
        <a href="https://x.com/thepaybutton" target="_blank" style="margin-right: 1rem; text-decoration: none; outline: none;">
            <img src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/icons/x.png' ); ?>" alt="X" width="16" height="16" style="border: none;">
        </a>
        <!-- Telegram Icon -->
        <a href="https://t.me/paybutton" target="_blank" style="margin-right: 1rem; text-decoration: none; outline: none;">
            <img src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/icons/telegram.png' ); ?>" alt="Telegram" width="16" height="16" style="border: none;">
        </a>
        <!-- Github Icon -->
        <a href="https://github.com/PayButton/wordpress-plugin" target="_blank" style="text-decoration: none; outline: none;">
            <img src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/icons/github.png' ); ?>" alt="Github" width="16" height="16" style="border: none;">
        </a>
    </div>
</div>
