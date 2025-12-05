<!-- File: templates/public/profile.php -->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="paybutton-profile">
    <p>
        <strong>Wallet Address:</strong>
        <a href="https://explorer.e.cash/address/<?php echo esc_attr( $paybutton_user_wallet_address ); ?>" target="_blank">
            <?php echo esc_html( $paybutton_user_wallet_address ); ?>
        </a>
    </p>
    <h3>Unlocked Content:</h3>
    <?php if ( ! empty( $paybutton_rows ) ): ?>
        <ol>
            <?php foreach ( $paybutton_rows as $paybutton_row ):
                $title = get_the_title( $paybutton_row->post_id );
                $link  = get_permalink( $paybutton_row->post_id );
                if ( $title && $link ): ?>
                    <li><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></li>
                <?php endif;
            endforeach; ?>
        </ol>
    <?php else: ?>
        <p>You have not unlocked any content yet.</p>
    <?php endif; ?>
</div>