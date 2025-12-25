/**
 * WooCommerce PayButton Integration JS
*/
jQuery(document).ready(function($) {
    $('.paybutton-woo-container').each(function() {
        var $container = $(this);
        var configData = $container.data('config');

        if (typeof configData === 'string') {
            try { configData = JSON.parse(configData); } 
            catch (e) { return; }
        }

        let paymentInitiated = false;

        function showOverlay(msg) {
            const el = document.getElementById('paybutton_overlay');
            if (el) {
                document.getElementById('paybutton_overlay_text').innerText = msg;
                el.style.display = 'block';
            }
        }

        function pollOrderStatus() {
            setInterval(function() {
                $.ajax({
                    url: PaywallAjax.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'paybutton_check_order_status',
                        security: PaywallAjax.nonce,
                        order_id: configData.opReturn
                    },
                    success: function(response) {
                        if (response.success) {
                            // Success! Reload to hide button and show receipt
                            location.reload();
                        }
                    }
                });
            }, 3000);
        }

        PayButton.render($container[0], {
            ...configData,
            onSuccess: function(tx) {
                paymentInitiated = true;
                pollOrderStatus();
            },
            onClose: function() {
                if (paymentInitiated) {
                    showOverlay("Verifying Payment...");
                }
            }
        });
    });
});