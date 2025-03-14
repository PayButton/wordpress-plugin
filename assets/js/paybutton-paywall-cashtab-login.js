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
    PayButton.render(document.getElementById('loginPaybutton'), {
        to: PaywallAjax.defaultAddress,
        amount: 5.5,
        currency: 'XEC',
        text: 'Login via Cashtab',
        hoverText: 'Click to Login',
        successText: 'Success!',
        onSuccess: function (tx) {
            console.log('Login Payment TX:', tx);
            if (tx && tx.inputAddresses && tx.inputAddresses.length > 0) {
                const userAddress = tx.inputAddresses[0];
                // Add a 2500 ms delay before processing the login so that the PB's ding sound dosen't get cutoff.
                setTimeout(function(){
                    handleLogin(userAddress);
                }, 2500);
            }
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
