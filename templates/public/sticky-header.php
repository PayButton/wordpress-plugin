<!-- File: templates/public/sticky-header.php -->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    // Check if the admin has set a wallet address
    $admin_wallet_address = get_option('paybutton_admin_wallet_address', '');
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
            <button class="profile-button paybutton-animated" onclick="window.location.href='<?php echo esc_url( get_permalink( get_option( 'paybutton_profile_page_id', 0 ) ) ); ?>'">
            <span class="btn-icon">
                <?php
                    echo file_get_contents(
                        PAYBUTTON_PLUGIN_DIR . 'assets/icons/profile.svg'
                    );
                ?>
            </span>
            <span>Profile</span></button>
            <button class="logout-button paybutton-animated" onclick="handleLogout(this)">
                <span class="btn-icon">
                    <?php
                        echo file_get_contents(
                            PAYBUTTON_PLUGIN_DIR . 'assets/icons/logout.svg'
                        );
                    ?>
                </span>
                <!-- Default text -->
                <span class="btn-text btn-text-default">Logout</span>
                <!-- "Logging out..." text (hidden until .is-logging-out is added) -->
                <span class="btn-text btn-text-logging-out" aria-hidden="true">Logging out...</span>
            </button>
        </div>
    <?php endif; ?>
</div>