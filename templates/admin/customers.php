<!-- File: templates/admin/customers.php -->
<div class="wrap">
    <?php if ( isset( $user_address ) ): ?>
        <h1>Unlocked Content for: <a href="https://explorer.e.cash/address/<?php echo esc_attr( $user_address ); ?>" target="_blank"><?php echo esc_html( $user_address ); ?></a></h1>
        <?php if ( ! empty( $rows ) ): ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Content Title</th>
                        <th>Amount Paid (XEC)</th>
                        <th>Timestamp</th>
                        <th>Transaction Hash</th>
                        <th>Login Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $row ):
                        $post_title = get_the_title( $row->post_id );
                        $permalink  = get_permalink( $row->post_id );
                        if ( $post_title && $permalink ): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( $permalink ); ?>" target="_blank">
                                        <?php echo esc_html( $post_title ); ?>
                                    </a>
                                </td>
                                <td><?php echo number_format( floatval( $row->tx_amount ), 2 ); ?></td>
                                <?php
                                $converted_ts = '(none)';
                                if ( ! empty( $row->tx_timestamp ) && $row->tx_timestamp !== '0000-00-00 00:00:00' ) {
                                    $local_time = get_date_from_gmt( $row->tx_timestamp );
                                    if ( $local_time ) {
                                        $converted_ts = date_i18n( 'Y-m-d H:i:s', strtotime( $local_time ) );
                                    }
                                }
                                ?>
                                <td><?php echo esc_html( $converted_ts ); ?></td>
                                <?php if ( ! empty( $row->tx_hash ) ): ?>
                                    <td>
                                        <a href="https://explorer.e.cash/tx/<?php echo urlencode( $row->tx_hash ); ?>" target="_blank">
                                            <?php echo esc_html( $row->tx_hash ); ?>
                                        </a>
                                    </td>
                                <?php else: ?>
                                    <td>(none)</td>
                                <?php endif; ?>
                                <td>
                                    <?php echo esc_html( $row->is_logged_in ? 'true' : 'false' ); ?>
                                </td>
                            </tr>
                        <?php endif;
                    endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No unlocked content found.</p>
        <?php endif; ?>
        <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=paybutton-paywall-customers' ) ); ?>">← Back to Customers</a></p>
    <?php else: ?>
        <h1>Customers</h1>
        <p><strong>Total Customers:</strong> <?php echo intval( $total_customers ); ?></p>
        <p><strong>Total Earned (XEC):</strong> <?php echo number_format( $grand_total_xec, 2 ); ?> XEC</p>
        <?php
        function paybutton_sort_customers_table( $col, $label, $orderby, $order, $base_url ) {
            $next_order = 'ASC';
            $arrow = '';
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
                <th><?php echo wp_kses_post( paybutton_sort_customers_table( 'ecash_address', 'Customer', $orderby, $order, $base_url ) ); ?></th>
                <th><?php echo wp_kses_post( paybutton_sort_customers_table( 'unlocked_count', 'Unlocked Content', $orderby, $order, $base_url ) ); ?></th>
                <th><?php echo wp_kses_post( paybutton_sort_customers_table( 'total_paid', 'Total Paid (XEC)', $orderby, $order, $base_url ) ); ?></th>
                <th><?php echo wp_kses_post( paybutton_sort_customers_table( 'last_unlock_ts', 'Last Unlock', $orderby, $order, $base_url ) ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $customers ) ): ?>
                    <?php foreach ( $customers as $row ): 
                        $detail_link = add_query_arg( array(
                            'page'    => 'paybutton-paywall-customers',
                            'address' => $row['ecash_address']
                        ), admin_url( 'admin.php' ) );
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url( $detail_link ); ?>">
                                    <?php echo esc_html( $row['ecash_address'] ); ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                echo intval( $row['unlocked_count'] )
                                    //  . ' (' . intval( $row['unlocked_logged_in_count'] ) . ' accounts)'
                                    ;
                                ?>
                            </td>
                            <td><?php echo esc_html( number_format( $row['total_paid'], 2 ) ); ?></td>
                            <td>
                                <?php
                                // Convert MySQL datetime to something friendly
                                if ( ! empty( $row['last_unlock_ts'] ) && $row['last_unlock_ts'] !== '0000-00-00 00:00:00' ) {
                                    $local_time = get_date_from_gmt( $row['last_unlock_ts'] );
                                    if ( $local_time ) {
                                        echo esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $local_time ) ) );
                                    } else {
                                        echo esc_html( $row['last_unlock_ts'] ); // fallback
                                    }
                                } else {
                                    echo '(none)';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No customers found yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p style="margin-top: 1rem;">
            Sign up for a <a href="https://paybutton.org/signup" target="_blank">FREE PayButton account</a> to get access to advanced payment tracking & business features.
        </p>
    <?php endif; ?>
</div>
