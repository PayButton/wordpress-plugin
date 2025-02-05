<!-- File: templates/public/sticky-header.php -->
<div id="cashtab-sticky-header">
    <?php if ( ! $address ): ?>
        <div id="loginPaybutton"></div>
    <?php else: ?>
        <div class="logged-in-actions">
            <button class="profile-button" onclick="window.location.href='<?php echo esc_url( get_permalink( get_option( 'paybutton_profile_page_id', 0 ) ) ); ?>'">Profile</button>
            <button class="logout-button" onclick="handleLogout()">Logout</button>
        </div>
    <?php endif; ?>
</div>
