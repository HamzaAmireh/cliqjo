=== Cliq Payment Gateway - Etihad Bank API ===
Contributors: cliqjo
Donate link: https://cliqpal.vercel.app
Tags: woocommerce, cliq, payment gateway, bank al etihad
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enables Bank Al Etihad CliQ instant payments for your WooCommerce store.

== Description ==

The **Cliq Payment Gateway - Etihad Bank API** is the easiest way to accept instant mobile payments on your WordPress store directly through the powerful CliQ network. By requesting the customer's CliQ Alias during checkout, the gateway instantly pushes a secure payment request to their mobile banking app for seamless approval.

This plugin is designed around WooCommerce best practices and fully supports the new WooCommerce Blocks Checkout.

### Key Features
* Seamless checkout experience with dedicated CliQ Alias fields.
* Support for standard shortcode checkout and Block-based checkout.
* Secure OAuth2 based API integration to ensure all transactions are authenticated.
* Upload TLS/SSL merchant certificates securely directly via WP Admin. 
* Order stock management sync with payment validation.

== Installation ==

1. Upload the entire `cliq-payment-gateway-etihad-jordan` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly via the zip file.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **WooCommerce -> Settings -> Payments**.
4. Enable the **Bank Al Etihad CliQ** payment gateway and click on "Manage" or "Settings".
5. Fill in your Base URL, Token Endpoint URL, Client ID, Client Secret, and upload your merchant `.crt` and `.key` files provided by Bank Al Etihad.
6. Save settings.

== Frequently Asked Questions ==

= Do I need a merchant account? =
Yes, you must be a registered merchant with Bank Al Etihad to obtain your Client ID, Secret, and mTLS certificates.

= Does this support automatic refunds? =
Not in this base version. You must process refunds manually through your merchant portal.

== Changelog ==

= 1.0.0 =
* Initial public release with basic gateway support.
* Support for WooCommerce Blocks Checkout.
* Secure cert uploading function.
