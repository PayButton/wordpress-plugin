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
            } catch (e) {
                console.error('Invalid JSON in paybutton-container data-config');
                return;
            }
        }

        // Shared state: user wallet address + unlock tx captured in onSuccess, consumed in onClose.
        let unlockAddr = null;
        let unlockTx   = null;

        // Helper to fetch and inject unlocked content
        function fetchUnlocked() {
            jQuery.ajax({
                method: 'POST',
                url: PaywallAjax.ajaxUrl,
                data: {
                    action: 'fetch_unlocked_content',
                    post_id: configData.postId,
                    security: PaywallAjax.nonce
                },
                success: function (resp) {
                    if (resp && resp.success) {
                        var $wrapper = jQuery('#pb-paywall-' + configData.postId);
                        if ($wrapper.length && resp.data.unlocked_html) {
                            $wrapper.html(resp.data.unlocked_html);
                        }

                        // Cache bust + scroll to unlocked content indicator
                        var baseUrl = location.href.split('#')[0].split('?')[0];
                        var newUrl = baseUrl + '?t=' + Date.now() + '#unlocked';
                        window.history.replaceState(null, '', newUrl);

                        if (PaywallAjax.scrollToUnlocked === '1' || PaywallAjax.scrollToUnlocked === 1) {
                            var $target = jQuery('#unlocked');
                            if ($target.length) {
                                var headerOffset = 80;
                                jQuery('html, body').animate({ scrollTop: $target.offset().top - headerOffset }, 500);
                            }
                        }
                        // --- NEW: update sticky header to the logged-in state without reload ---
                        // Keep JS state in sync (used by login script)
                        if (typeof isLoggedIn !== 'undefined') {
                            isLoggedIn = true;
                        }

                        jQuery.post(
                            PaywallAjax.ajaxUrl,
                            {
                                action: 'paybutton_get_sticky_header',
                                security: PaywallAjax.nonce
                            },
                            function(resp) {
                                if (resp && resp.success && resp.data && resp.data.html) {
                                    var $header = jQuery('#cashtab-sticky-header');
                                    if ($header.length) {
                                        // Replace the whole header with the freshly rendered one
                                        $header.replaceWith(resp.data.html);
                                    }
                                }
                            }
                        );
                    }
                }
            });
        }

        // Configure the PayButton like before, but:
        // - onSuccess captures tx data and does the secure validate -> mark_payment_successful -> fetch flow
        PayButton.render($container[0], {
            to: configData.to,
            amount: configData.amount,
            currency: configData.currency,
            text: configData.buttonText,
            hoverText: configData.hoverText,
            successText: configData.successText,
            theme: configData.theme,
            opReturn: configData.opReturn, // carries postID
            autoClose: configData.autoClose,

            onSuccess: function (tx) {
                unlockAddr = (tx.inputAddresses && tx.inputAddresses.length > 0)
                    ? tx.inputAddresses[0]
                    : '';
                unlockTx = {
                    hash: tx.hash || '',
                    amount: tx.amount || '',
                    timestamp: tx.timestamp || 0
                };

                if (unlockAddr && unlockTx && unlockTx.hash) {
                    const addrCopy   = unlockAddr;
                    const hashCopy   = unlockTx.hash;
                    const amtCopy    = unlockTx.amount;
                    const tsCopy     = unlockTx.timestamp;
                    const postIdCopy = configData.postId;

                    function tryValidateUnlock(attempt) {
                        jQuery.post(
                            PaywallAjax.ajaxUrl,
                            {
                                action: 'validate_unlock_tx',
                                security: PaywallAjax.nonce,
                                wallet_address: addrCopy,
                                tx_hash: hashCopy,
                                post_id: postIdCopy
                            },
                            function (resp) {
                                if (resp && resp.success && resp.data && resp.data.unlock_token) {
                                    // We have a server-issued token – now mark payment as successful.
                                    jQuery.ajax({
                                        method: 'POST',
                                        url: PaywallAjax.ajaxUrl,
                                        data: {
                                            action: 'mark_payment_successful',
                                            post_id: postIdCopy,
                                            security: PaywallAjax.nonce,
                                            tx_hash: hashCopy,
                                            tx_amount: amtCopy,
                                            tx_timestamp: tsCopy,
                                            user_address: addrCopy,
                                            unlock_token: resp.data.unlock_token
                                        },
                                        success: function () {
                                            // Finally, fetch and render the unlocked content
                                            fetchUnlocked();
                                        }
                                    });
                                } else {
                                    if (attempt === 1) {
                                        // Retry after brief delay
                                        setTimeout(function () { tryValidateUnlock(2); }, 1200); // 1.2s delay to give the PayButton webhook time
                                        console.log('Retrying unlock validation (attempt 2)...');
                                    }
                                    else if(attempt === 2) {
                                        // Retry after brief delay
                                        setTimeout(function () { tryValidateUnlock(3); }, 1000); // 1s delay to give the PayButton webhook time
                                        console.log('Retrying unlock validation (attempt 3)...');
                                    }
                                    else if(attempt === 3) {
                                        // Retry after brief delay
                                        setTimeout(function () { tryValidateUnlock(4); }, 1000); // 1s delay to give the PayButton webhook time
                                        console.log('Retrying unlock validation (attempt 4)...');
                                    }
                                    else if (attempt === 4) {
                                        // Worst case, one final retry after a longer delay
                                        setTimeout(function () { tryValidateUnlock(5); }, 2000); // 2s delay to give the PayButton webhook time
                                        console.log('Retrying unlock validation (attempt 5 - Final attempt)...');
                                    }
                                    else {
                                        alert('⚠️ Payment could not be verified on-chain. Please try again.');
                                    }
                                }
                            }
                        );
                    }

                    // Initial delay before first attempt to give the PayButton webhook time
                    setTimeout(function () {
                        tryValidateUnlock(1);
                        console.log('Attempting unlock validation (attempt 1)...');
                    }, 1000); // First attempt, 1s delay (selected experimentally)
                }

                // Safe to clear shared state (the flow above uses the copies)
                unlockAddr = null;
                unlockTx   = null;
            },
        });
    });
});

//Scrolling to the unlocked content indicator element when the page loads
jQuery(document).ready(function($) {
    // Check if the URL hash is '#unlocked'
    if (window.location.hash === '#unlocked') {
        // Find the unlocked indicator element
        var $target = $('#unlocked');
        if ($target.length) {
            // Calculate the scroll offset to the sticky header's height.
            var headerOffset = 80;
            var targetOffset = $target.offset().top - headerOffset;
            
            // Animate scrolling to the calculated offset so that the unlocked content indicator is visible.
            $('html, body').animate({
                scrollTop: targetOffset
            }, 500);
        }
    }
});