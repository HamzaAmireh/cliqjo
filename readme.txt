=== Sugarbyte Payment Gateway with CliQ for WooCommerce ===
Contributors: sugarbyte
Donate link: https://cliqpal.vercel.app
Tags: woocommerce, cliq, payment-gateway, bank-al-etihad, jordan
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enables Sugarbyte Payment Gateway with CliQ instant payments for your WooCommerce store.

== Description ==

The **Sugarbyte Payment Gateway with CliQ for WooCommerce** is a streamlined method to accept instant mobile payments on your WordPress store directly through the powerful CliQ network. By requesting the customer's CliQ Alias during checkout, the gateway instantly pushes a secure payment request to their mobile banking app for seamless approval.

This plugin is designed around WooCommerce best practices and fully supports the new WooCommerce Blocks Checkout.

### Key Features
* Seamless checkout experience with dedicated CliQ Alias fields.
* Support for standard shortcode checkout and Block-based checkout.
* Secure OAuth2 based API integration to ensure all transactions are authenticated.
* Upload TLS/SSL merchant certificates securely directly via WP Admin. 
* Order stock management sync with payment validation.

== Installation ==

1. Upload the entire `sugarbyte-mobile-bank-payments` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly via the zip file.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **WooCommerce -> Settings -> Payments**.
4. Enable the **Sugarbyte Payment Gateway with CliQ** payment gateway and click on "Manage" or "Settings".
5. Fill in your Base URL, Token Endpoint URL, Client ID, Client Secret, and upload your merchant `.crt` and `.key` files provided by Bank Al Etihad.
6. Save settings.

== Frequently Asked Questions ==

= Do I need a merchant account? =
Yes, you must be a registered merchant with Bank Al Etihad to obtain your Client ID, Secret, and mTLS certificates.

= Does this support automatic refunds? =
Not in this base version. You must process refunds manually through your merchant portal.

== External services ==

This plugin relies on the Sugarbyte Payment Gateway with CliQ APIs to process payments securely.
When a customer initiates an order using CliQ checkout, the plugin sends the order `Amount`, the internal `ExternalTransactionId` (your order ID), and the customer's `Alias` directly to Bank Al Etihad's servers to generate a payment request on their mobile banking application. 

The data is transmitted securely via OAuth2 and mutual TLS certificates provided by your merchant account. 
The default API endpoint configuration interacts directly with: `https://api.developer.bankaletihad.com/api/v1/partner/cliq/payment`.
	
This service is entirely provided and managed by Bank Al Etihad:
* [Bank Al Etihad Terms and Privacy Policy](https://www.bankaletihad.com/en/terms-and-privacy/)

== Changelog ==

= 1.0.0 =
* Initial public release with basic gateway support.
* Support for WooCommerce Blocks Checkout.
* Secure cert uploading function.
