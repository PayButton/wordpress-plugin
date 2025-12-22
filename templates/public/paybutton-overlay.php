<!-- File: templates/public/paybutton-overlay.php -->
<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div id="paybutton_overlay" class="paybutton_overlay" style="display:none;">
    <div class="paybutton_overlay_inner">
        <div class="paybutton_overlay_content">
            <span class="paybutton_overlay_spinner"></span>
            <p id="paybutton_overlay_text">Verifying Payment...</p>
        </div>
    </div>
</div>