<!-- File: templates/public/sticky-header.php -->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    // Check if the admin has set a wallet address
    $paybutton_admin_wallet_address = get_option('paybutton_admin_wallet_address', '');
    if ( empty( $paybutton_admin_wallet_address ) ) {
        // If no valid address is set, do not display the sticky header.
        return;
    }

    $paybutton_svg_allowed = array(
    'svg' => array(
        'xmlns'   => true,
        'viewbox' => true,
        'viewBox' => true,
        'width'   => true,
        'height'  => true,
        'fill'    => true,
    ),
    'path' => array(
        'd'    => true,
        'fill' => true,
    ),
    'circle' => array(
        'cx'   => true,
        'cy'   => true,
        'r'    => true,
        'fill' => true,
    ),
    'rect' => array(
        'x'      => true,
        'y'      => true,
        'width'  => true,
        'height' => true,
        'fill'   => true,
    ),
    );
?>

<div id="cashtab-sticky-header">
    <?php if ( ! $paybutton_user_wallet_address ): ?>
        <div id="loginPaybutton"></div>
    <?php else: ?>
        <div class="logged-in-actions">
            <button class="profile-button paybutton-animated" onclick="window.location.href='<?php echo esc_url( get_permalink( get_option( 'paybutton_profile_page_id', 0 ) ) ); ?>'">
            <span class="btn-icon">
                <?php
                    $paybutton_svg_path = PAYBUTTON_PLUGIN_DIR . 'assets/icons/profile.svg';
                    if (file_exists($paybutton_svg_path)) {
                        echo wp_kses( file_get_contents( $paybutton_svg_path ), $paybutton_svg_allowed );
                    }
                ?>
            </span>
            <span class="btn-text">Profile</span></button>
            <button class="logout-button paybutton-animated" onclick="handleLogout(this)">
            <span class="btn-icon">
                <?php
                    $paybutton_svg_path = PAYBUTTON_PLUGIN_DIR . 'assets/icons/logout.svg';
                    if (file_exists($paybutton_svg_path)) {
                        echo wp_kses( file_get_contents( $paybutton_svg_path ), $paybutton_svg_allowed );
                    }
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