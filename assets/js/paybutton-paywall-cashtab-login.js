/* File: assets/js/paybutton-paywall-cashtab-login.js */
let isLoggedIn = false;

/* Show/Hide Verification Overlay*/
function showPBVerificationOverlay(msg = "Processing, please wait!") {
    const el = document.getElementById('paybutton_overlay');
    if (!el) return;
    document.getElementById('paybutton_overlay_text').innerText = msg;
    el.style.display = 'block';
}

function hidePBVerificationOverlay() {
    const el = document.getElementById('paybutton_overlay');
    if (!el) return;
    el.style.display = 'none';
}

/**
 * Handle user login:
 * Called when the PayButton login flow completes successfully.
*/
function handleLogin(address, txHash, loginToken) {
    isLoggedIn = true;
    jQuery.post(
        PaywallAjax.ajaxUrl,
        {
            action: 'paybutton_save_address',
            security: PaywallAjax.nonce,
            address: address,
            tx_hash: txHash,
            login_token: loginToken
        },
        function() {
            var baseUrl = location.href.split('?')[0];
            // Build a new URL that includes a timestamp parameter to bust caches
            var newUrl = baseUrl + '?t=' + Date.now();
            window.history.replaceState(null, '', newUrl);
            location.reload();
        }
    );
}

/**
 * Handle user logout.
*/
function handleLogout(logoutButton) {
    if (logoutButton) {
        // Disable the button and mark as logging out
        logoutButton.disabled = true;
        logoutButton.classList.add('is-logging-out');
    }

    jQuery.post(
        PaywallAjax.ajaxUrl,
        {
            action: 'paybutton_logout',
            security: PaywallAjax.nonce
        }
    )
    .done(function () {
        isLoggedIn = false;
        location.reload();
    })
    .fail(function () {
        if (logoutButton) {
            logoutButton.disabled = false;
            logoutButton.classList.remove('is-logging-out');
        }
        alert('Logout failed. Please try again.');
    });
}

/**
 * Render the "Login via Cashtab" PayButton.
 * (5.5 XEC is hard-coded.)
*/
function renderLoginPaybutton() {
    // Shared state: login address captured in onSuccess, consumed in onClose.
    let loginAddr = null;
    let loginTx = null;

    PayButton.render(document.getElementById('loginPaybutton'), {
        to: PaywallAjax.defaultAddress,
        amount: 5.5,
        currency: 'XEC',
        text: 'Login via Cashtab',
        hoverText: 'Click to Login',
        successText: 'Login Successful!',
        autoClose: true,
        opReturn: 'login',
        onSuccess: function (tx) {
            loginAddr = tx?.inputAddresses?.[0] ?? null;
            loginTx = {
                hash: tx?.hash ?? '',
                timestamp: tx?.timestamp ?? 0
            };
        },
        onClose: function () {
            // Show verification overlay immediately
            showPBVerificationOverlay("Verifying login...");

            if (loginAddr && loginTx && loginTx.hash) {
                // Make stable copies for the whole retry flow
                const addrCopy = loginAddr;
                const hashCopy = loginTx.hash;

                function tryValidateLogin(attempt) {
                    jQuery.post(
                        PaywallAjax.ajaxUrl,
                        {
                            action: 'validate_login_tx',
                            security: PaywallAjax.nonce,
                            wallet_address: addrCopy,
                            tx_hash: hashCopy
                        },
                        function (resp) {
                            if (resp && resp.success && resp.data && resp.data.login_token) {
                                // Pass the random token from the server
                                handleLogin(addrCopy, hashCopy, resp.data.login_token);
                            } else {
                                if (attempt === 1) {
                                    // Retry once again after 3 seconds
                                    setTimeout(() => tryValidateLogin(2), 3000);
                                } else {
                                    hidePBVerificationOverlay();
                                    alert('⚠️ Login failed: Invalid or expired transaction.');
                                }
                            }
                        }
                    );
                }
                tryValidateLogin(1);
            }
            // Safe to clear shared state (the flow above uses the copies)
            loginAddr = null;
            loginTx = null;
        }
    });
}

jQuery(function ($) {
    if (!parseInt(PaywallAjax.isUserLoggedIn)) {
        renderLoginPaybutton();
    } else {
        isLoggedIn = true;
    }
});