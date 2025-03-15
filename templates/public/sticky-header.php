<!-- File: templates/public/sticky-header.php -->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    // Check if the admin has set a wallet address
    $admin_wallet_address = get_option('pb_paywall_admin_wallet_address', '');
    if ( empty( $admin_wallet_address ) ) {
        // If no valid address is set, do not display the sticky header.
        return;
    }
?>

<div id="cashtab-sticky-header">
    <?php if ( ! $user_wallet_address ): ?>
        <div id="loginPaybutton"></div>
    <?php else: ?>
        <div class="logged-in-actions">
            <button class="profile-button" onclick="window.location.href='<?php echo esc_url( get_permalink( get_option( 'paybutton_profile_page_id', 0 ) ) ); ?>'">Profile</button>
            <button class="logout-button" onclick="handleLogout()">Logout</button>
        </div>
    <?php endif; ?>
</div>