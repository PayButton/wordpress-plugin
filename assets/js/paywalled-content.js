/* File: assets/js/paywalled-content.js
 *
 * This file implements the client-side functionality for the paywall feature.
 * It is responsible for:
 *   - Rendering the PayButton inside each element with the class "paybutton-container"
 *     (which is output by the [paywalled_content] shortcode).
 *   - Reading configuration data (stored in a data attribute as JSON) to determine the payment
 *     details (amount, recipient address, button text, theme, etc.).
 *   - Setting up an onSuccess callback that triggers when a payment is completed.
 *
 * How it works:
 *   1. On document ready, jQuery selects all ".paybutton-container" elements.
 *   2. For each container:
 *      - The script retrieves its configuration data via the data-config attribute.
 *      - If the data is a JSON string, it parses it into a JavaScript object.
 *      - It then sets up an onSuccess callback. When the payment is successful, this callback:
 *           a) Sends an AJAX POST request (using jQuery's $.ajax) to the server endpoint 
 *              (action: "mark_payment_successful") with the transaction details.
 *           b) On success, waits 2000 ms (2 seconds) and then reloads the page so that
 *              the now unlocked content is displayed.
 *   3. Finally, the render() method is called on the container to display the button.
 */
jQuery(document).ready(function($) {
    $('.paybutton-container').each(function() {
        var $container = $(this);
        var configData = $container.data('config');
        if (typeof configData === 'string') {
            try {
                configData = JSON.parse(configData);
            } catch(e) {
                console.error('Invalid JSON in paybutton-container data-config');
                return;
            }
        }
        configData.onSuccess = function(tx) {
            $.ajax({
                method: 'POST',
                url: PaywallAjax.ajaxUrl,
                data: {
                    action: 'mark_payment_successful',
                    post_id: configData.postId,
                    security: PaywallAjax.nonce,
                    tx_hash: tx.hash || '',
                    tx_amount: tx.amount || '',
                    tx_timestamp: tx.timestamp || '',
                    // NEW: Pass the first input address to store in the DB even for non-logged-in users
                    user_address: (tx.inputAddresses && tx.inputAddresses.length > 0) ? tx.inputAddresses[0] : ''
                },
                success: function() {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            });
        };
        PayButton.render($container[0], {
            to: configData.to,
            amount: configData.amount,
            currency: configData.currency,
            text: configData.buttonText,
            hoverText: configData.hoverText,
            successText: configData.successText,
            onSuccess: configData.onSuccess,
            theme: configData.theme,
            opReturn: configData.opReturn //This is a hack to give the PB server the post ID to send it back to WP's DB
        });
    });
});
