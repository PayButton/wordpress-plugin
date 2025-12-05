<!-- File: templates/admin/content.php -->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap">
    <div class="pb-header">
        <img class="paybutton-logo" src="<?php echo esc_url( PAYBUTTON_PLUGIN_URL . 'assets/paybutton-logo.png' ); ?>" alt="PayButton Logo">
    </div>
    <h1>Content</h1>
    <p><strong>Total Content Unlocks: </strong><?php echo esc_html( intval( $total_unlocks ) ); ?></p>
    <p><strong>Total Earned:</strong> <?php echo esc_html( number_format( $grand_total_earned, 2 ) ); ?> XEC</p>
    <?php
    function paybutton_sort_content_table( $col, $label, $orderby, $order, $base_url ) {
        $arrow = '';
        $next_order = 'ASC';
        if ( $orderby === $col ) {
            if ( $order === 'ASC' ) {
                $arrow = ' ↑';
                $next_order = 'DESC';
            } else {
                $arrow = ' ↓';
            }
        }
        $url = add_query_arg( array( 'orderby' => $col, 'order' => $next_order ), $base_url );
        $url = wp_nonce_url( $url, 'paybutton_content_sort', 'paybutton_content_nonce' );
        return '<a href="' . esc_url( $url ) . '">' . esc_html( $label . $arrow ) . '</a>';
    }
    ?>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo wp_kses_post( paybutton_sort_content_table( 'title', 'Content Title', $orderby, $order, $base_url ) ); ?></th>
                <th><?php echo wp_kses_post( paybutton_sort_content_table( 'unlock_count', 'Unlocks', $orderby, $order, $base_url ) ); ?></th>
                <th><?php echo wp_kses_post( paybutton_sort_content_table( 'total_earned', 'Total Earned (XEC)', $orderby, $order, $base_url ) ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $contentData ) ): ?>
                <?php foreach ( $contentData as $paybutton_row ): 
                    $paybutton_permalink = get_permalink( $paybutton_row['post_id'] );
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( $paybutton_permalink ); ?>" target="_blank">
                                <?php echo esc_html( $paybutton_row['title'] ); ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            echo intval( $paybutton_row['unlock_count'] );
                            ?>
                        </td>
                        <td><?php echo esc_html( number_format( $paybutton_row['total_earned'], 2 ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">No posts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <p class="pb-paragraph-margin-top">
        Sign up for a <a href="https://paybutton.org/signup" target="_blank">FREE PayButton account</a> to get access to advanced payment tracking & business features.
    </p>
</div>