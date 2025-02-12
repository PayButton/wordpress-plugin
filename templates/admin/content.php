<!-- File: templates/admin/content.php -->
<div class="wrap">
    <h1>Content</h1>
    <p><strong>Total Content Unlocks: </strong><?php echo intval( $total_unlocks ); ?></p>
    <p><strong>Total Earned (XEC):</strong> <?php echo number_format( $grand_total_earned, 2 ); ?></p>
    <?php
    function sort_link_content( $col, $label, $orderby, $order, $base_url ) {
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
        return '<a href="' . esc_url( $url ) . '">' . esc_html( $label . $arrow ) . '</a>';
    }
    ?>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo sort_link_content( 'title', 'Content Title', $orderby, $order, $base_url ); ?></th>
                <th><?php echo sort_link_content( 'unlock_count', 'Unlocks', $orderby, $order, $base_url ); ?></th>
                <th><?php echo sort_link_content( 'total_earned', 'Total Earned (XEC)', $orderby, $order, $base_url ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $contentData ) ): ?>
                <?php foreach ( $contentData as $row ): 
                    $permalink = get_permalink( $row['post_id'] );
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( $permalink ); ?>" target="_blank">
                                <?php echo esc_html( $row['title'] ); ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            echo intval( $row['unlock_count'] )
                                //  . ' (' . intval( $row['unlock_logged_in_count'] ) . ' accounts)'
                                ;
                            ?>
                        </td>
                        <td><?php echo number_format( $row['total_earned'], 2 ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">No posts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <p style="margin-top: 1rem;">
        Sign up for a <a href="https://paybutton.org/signup" target="_blank">FREE PayButton account</a> to get access to advanced payment tracking & business features.
    </p>
</div>
