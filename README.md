# Cliq Payment Gateway - Etihad Bank API

A WooCommerce payment gateway integrated with Bank Al Etihad's CliQ network, allowing customers to pay instantly using their CliQ Alias.

## Features
* Secure checkout using native WooCommerce UI.
* Compatible with the new Cart & Checkout Blocks in WooCommerce.
* Fully authenticated via OAuth2 Client Credentials for merchant API calls.
* Dedicated configuration panel within WooCommerce Payments dashboard for TLS configuration and file uploads.

## Merchant Setup & Configuration
1. Open a Merchant Account with Bank Al Etihad.
2. Request access to their CliQ Payment APIs.
3. You will receive:
   - Client ID & Client Secret
   - Target Environment URLs (Token & Base API)
   - A `.crt` signed TLS Certificate and your `.key` file.
4. Install and activate this plugin in WordPress.
5. Go to **WooCommerce > Settings > Payments > Bank Al Etihad CliQ**:
   - Fill in the required endpoints.
   - Upload your signed certificate `.crt` and server key `.key` files directly.
   - Activate the gateway.

## Troubleshooting Tips
* **Connection Refused / Failed to retrieve token:** Ensure your server's IP address is whitelisted by Bank Al Etihad.
* **Certificate Errors:** Check if the uploaded `.crt` matches the provided `.key`. Your provider may require the certificate to be combined with a CA bundle. If so, contact your Bank Al Etihad support representative for assistance preparing a standard PEM bundle.
* **400 Bad Request during checkout:** Verify that the cart total and currency configuration matches what Bank Al Etihad expects, and that the CliQ alias provided by the customer is active.

## Developers & Contribution
For code modifications, note that the codebase has been structured keeping WordPress official repo guidelines in mind. Ensure you sanitize inputs using functions like `sanitize_text_field` and escape outputs via `esc_html__()` before submitting PRs.
