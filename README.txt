=== PayButton ===
Contributors: xecdev, klakurka
Donate link: https://donate.paybutton.org/
Tags: paywall, monetization, donation, crypto, ecash
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 3.2.0
PayButton Client: 4.1.0
PayButton Client URI: https://github.com/PayButton/paybutton
License: MIT
License URI: https://github.com/PayButton/wordpress-plugin/blob/master/LICENSE

Monetize your content with configurable no-signup paywalls.

== Description ==
PayButton transforms your WordPress site into a robust paywall platform. With its frictionless payment process, visitors pay to unlock protected content immediately. Fully customizable through an intuitive admin dashboard, PayButton is perfect for bloggers, publishers, and content creators who want to monetize without the hassle of traditional payment gateways or user registration. Enjoy a smooth user experience with instant content unlocking, customizable settings, and detailed transaction tracking, all with minimal setup.

== 🔥 FEATURES: ==

**💸 Paywall Digital Content**  
With PayButton, you can put your digital content behind a paywall easily. Visitors complete a secure eCash payment and immediately gain access to the paywalled content (in less than 3secs).

**🛡️ Secure Payment Handling**  
The payment is handled by PayButton and you receive all payments instantly.

**🎨 Customizable Settings**  
Easily configure button texts, pricing, color schemes, and more through the admin dashboard.

**📊 Transaction Tracking**  
Monitor unlocked content, payment amounts, and user activity with comprehensive analytics.

**🔗 Simple Shortcode Integration**  
Wrap your content in the `[paywalled_content]` shortcode to protect it, no coding knowledge required.


== DOCUMENTATION & SUPPORT ==
* **[Documentation](https://docs.paybutton.org/)**
* **[Support](https://t.me/paybutton)**
* **[Website](https://paybutton.org)**

== Frequently Asked Questions ==

=01. How does PayButton unlock content?=
Once a visitor completes an eCash payment, the plugin verifies the transaction via PayButton and instantly unlocks the protected content.

=02. Do users need to register?=
No – PayButton’s no-signup approach uses session tracking and database entries to grant immediate access using the public key of the payer's wallet.

=03. Can I customize the appearance of the paywall?=
Absolutely, the admin dashboard allows you to modify button texts, pricing, color schemes, and more to match your theme.

=04. Will PayButton’s cookies break page caching?=
No. PayButton sets two HMAC-signed paybutton_user_wallet_address and paybutton_paid_content to track user wallet address and unlocked content. Cookies only get updated (and send a Set-Cookie header) when their value actually changes, so full-page caches (e.g. WP Super Cache, W3 Total Cache) and server caches (Nginx, Varnish) remain valid.

== Screenshots ==
1. Admin Dashboard Overview
2. Frontend PayButton Paywall in Action
3. Customizable PayButton Paywall Settings
4. PayButton Paywall Shortcode
5. Transaction Tracking and Analytics
6. PayButton Generator

== External services ==

- PayButton websocket
This plugin connects to the PayButton WebSocket, a service that monitors blockchain transactions. It is used to detect payments made to the PayButton/Widget address in real time. The plugin uses the provided address to establish a connection and begins listening for events emitted when new transactions are detected. This service is provided by PayButton: [terms of use](https://github.com/PayButton/paybutton-server/blob/master/TERMS.md), [privacy policy](https://github.com/PayButton/paybutton-server/blob/master/PRIVACY.md).

- PayButton API 
This plugin communicates with the PayButton API to fetch information about the address and its transactions. It provides data such as the transaction price and the address balance, which are used by the widget and the button. This service is also provided by PayButton: [terms of use](https://github.com/PayButton/paybutton-server/blob/master/TERMS.md), [privacy policy](https://github.com/PayButton/paybutton-server/blob/master/PRIVACY.md).

- SideShift.AI API
The integration uses the SideShift API to enable the button or widget to accept payments in a different currency than the one being received. This service is provided by SideShift.AI: [terms of use](https://sideshift.ai/legal).

== Changelog ==

= 3.2.0 (2025/05/21) =
* Sanitized and validated cookies and data
* Fixed nonce logic in Content & Customers page

= 3.1.0 (2025/05/10) =
* Added nonce verification and user capability checks for enhanced security.
* Added paybutton_ prefix to all generic option names to avoid naming conflicts.
* Escaped variables properly when echoed to prevent XSS vulnerabilities.
* Replaced session usage with cookies for improved caching compatibility and plugin support.
* Updated the plugin's README file with latest plugin details and usage instructions.

= 3.0.0 (2025/03/21) =
* Enhanced admin wallet address configuration flow.
* Implemented wallet address validation feature using the ecashaddrjs library.
* Refactored all eCash related identifiers (variables, CSS classes, DB fields) to generic terms.
* Improved the styling of the Unlocked Content Indicator with added customization support.
* Added native support for a streamlined PayButton generator that lets WordPress admins easily create and embed custom XEC/BCH donation buttons (e.g., "donate," "buy me a coffee") via shortcode.
* Improved admin dashboard UI/UX.

= 2.3.0 (2025/03/04) =
* Created a new paybutton-admin.css file to centralize admin styles.
* Updated table to auto-size columns.
* Refactored dashboard.php and other admin templates to remove inline CSS, replacing them with appropriate CSS classes.

= 2.2.0 (2025/02/28) =
* The PayButton core JavaScript file is now bundled with the plugin.
* The wp-admin?payment_trigger AJAX endpoint is now dynamically generated.

= 2.1.0 (2025/02/21) =
* Improved session synchronization and implemented a cache busting mechanism.
* Removed inline CSS and refactored generic function names for clarity.
* Enhanced security: sanitized session data, escaped outputs, and added ABSPATH checks.
* Updated the README for clearer documentation.

= 2.0.0 (2025/02/13) =
* New server-to-server messaging feature.
* Improve the vertical centering of the buttons on the sticky header.
* New scroll to unlocked content feature.
* Added guide for admins to setup the new Payment Trigger feature.

= 1.0.1 (2025/02/07) =
* Better header button centering style.

= 1.0.0 (2025/02/06) =
* Initial release.

== Upgrade Notice ==

= 3.2.0 =
Upgrade to version 3.2.0 for improved compatibility and reliability.
=======