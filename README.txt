=== PayButton ===
Contributors: xecdev, klakurka
Donate link: https://donate.paybutton.org/
Tags: paywall, monetization, donation, crypto, ecash
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.0
Stable tag: 2.0
License: MIT
License URI: https://github.com/PayButton/wordpress-plugin/blob/master/LICENSE

Monetize your content with configurable no-signup paywalls.

== Description ==
PayButton transforms your WordPress site into a robust paywall platform. With its frictionless payment process, visitors pay via eCash (XEC) to unlock protected content immediately. Fully customizable through an intuitive admin dashboard, PayButton is perfect for bloggers, publishers, and content creators who want to monetize without the hassle of traditional payment gateways or user registration. PayButton offers an innovative and secure paywall solution for FREE! Enjoy a smooth user experience with instant content unlocking, customizable settings, and detailed transaction tracking, all with minimal setup.

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


== EXTERNAL DEPENDENCY NOTICE: ==
PayButton relies on its core JavaScript library, which is loaded remotely from [https://unpkg.com/@paybutton/paybutton/dist/paybutton.js](https://unpkg.com/@paybutton/paybutton/dist/paybutton.js). This approach ensures that all users automatically receive the latest features and security updates without having to update the plugin manually. It also helps keep the plugin lightweight while still providing robust payment processing functionality. No additional accounts or configurations are needed.


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

=04. Is the PayButton plugin free?=
Yes, the plugin is completely free and open-source.

== Screenshots ==
1. Admin Dashboard Overview
2. Frontend Paywall in Action
3. Customizable Paywall Settings
4. PayButton Paywall Shortcode
5. Transaction Tracking and Analytics

== Changelog ==

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

= 2.0.0 =
Upgrade to version 2.0.0 to benefit from the new server-to-server messaging feature, improved header button centering, and the scroll-to-unlocked content feature.