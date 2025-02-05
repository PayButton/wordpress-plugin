<!-- File: templates/public/profile.php -->
<div class="paybutton-profile">
    <p>
        <strong>eCash Address:</strong>
        <a href="https://explorer.e.cash/address/<?php echo esc_attr( $address ); ?>" target="_blank">
            <?php echo esc_html( $address ); ?>
        </a>
    </p>
    <h3>Unlocked Content:</h3>
    <?php if ( ! empty( $rows ) ): ?>
        <ol>
            <?php foreach ( $rows as $row ):
                $title = get_the_title( $row->post_id );
                $link  = get_permalink( $row->post_id );
                if ( $title && $link ): ?>
                    <li><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></li>
                <?php endif;
            endforeach; ?>
        </ol>
    <?php else: ?>
        <p>You have not unlocked any content yet.</p>
    <?php endif; ?>
</div>
