<!-- File: templates/public/comments-placeholder.php -->
<?php
/**
 * Placeholder that keeps theme layout where comments_template() is called.
 * We replace this node via AJAX after payment.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$post_id = get_the_ID();
?>
<div id="paybutton-comments-placeholder"
     class="comments-area"
     data-post-id="<?php echo esc_attr( $post_id ); ?>">
</div>