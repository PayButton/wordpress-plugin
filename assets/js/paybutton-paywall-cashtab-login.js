/* File: assets/js/paybutton-paywall-cashtab-login.js */
let isLoggedIn = false;

/**
 * Handle user login:
 * Called when the PayButton payment returns a successful login transaction.
 */
function handleLogin(address) {
    isLoggedIn = true;
    jQuery.post(
        PaywallAjax.ajaxUrl,
        {
            action: 'paybutton_save_address',
            security: PaywallAjax.nonce,
            address: address
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
function handleLogout() {
    jQuery.post(
        PaywallAjax.ajaxUrl,
        {
            action: 'paybutton_logout',
            security: PaywallAjax.nonce
        },
        function() {
            isLoggedIn = false;
            location.reload();
        }
    );
}

/**
 * Render the "Login via Cashtab" PayButton.
 * (5.5 XEC is hard-coded.)
 */
function renderLoginPaybutton() {
    // Shared state: login address captured in onSuccess, consumed in onClose.
    let loginAddr = null;
    PayButton.render(document.getElementById('loginPaybutton'), {
        to: PaywallAjax.defaultAddress,
        amount: 5.5,
        currency: 'XEC',
        text: 'Login via Cashtab',
        hoverText: 'Click to Login',
        successText: 'Login Successful!',
        autoClose: true,
        onSuccess: function (tx) {
            loginAddr = tx?.inputAddresses?.[0] ?? null;
        },
        onClose: function () {
            if (loginAddr) {
                handleLogin(loginAddr);
            }
            // Prevent stale reuse on subsequent opens
            loginAddr = null;
        }
    });
}

window.addEventListener('load', function() {
    if (!parseInt(PaywallAjax.isUserLoggedIn)) {
        renderLoginPaybutton();
    } else {
        isLoggedIn = true;
    }
});

//The following code reloads the page if the user has been idle for more than 10 minutes, to renew the logout button's nonce validity.
// Track last time the page was active
let lastActive = Date.now();

// Update timestamp whenever the tab is focused or user interacts
window.addEventListener('focus', () => {
    const now = Date.now();
    const idleMinutes = (now - lastActive) / 60000; // Convert ms to minutes
    if (idleMinutes > 10) {
        location.reload(); // Reload if idle for more than 10 minutes to renew the logout button nonce
    }
    lastActive = now;
});

// Also update the timestamp on clicks/scrolls to keep it fresh
['click','scroll','keydown','mousemove'].forEach(evt => {
    window.addEventListener(evt, () => { lastActive = Date.now(); });
});