<?php
/**
 * Plugin Name: Sugarbyte CliQ Jordan - Payment Gateway for WooCommerce
 * Plugin URI:  https://github.com/HamzaAmireh/cliqjo
 * Description: Payment processing via the CliQ Jordan network. Developed by Sugarbyte. Built for Jordanian WooCommerce stores using only commercial bank account.
 * Version:     1.0.0
 * Author:      Sugarbyte
 * Author URI:  https://cliqpal.vercel.app
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sugarbyte-mobile-bank-payments
 */


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Make sure WooCommerce is active.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
	return;
}

define('sugarbyte_cliq_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('sugarbyte_cliq_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Initialize the gateway.
 */
function sugarbyte_cliq_init_gateway()
{
	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	require_once sugarbyte_cliq_PLUGIN_DIR . 'includes/class-sugarbyte-cliq-api.php';
	require_once sugarbyte_cliq_PLUGIN_DIR . 'includes/class-sugarbyte-cliq-wc-gateway.php';
}
add_action('plugins_loaded', 'sugarbyte_cliq_init_gateway', 11);

/**
 * Add the gateway to WooCommerce.
 *
 * @param array $methods Payment methods.
 * @return array
 */
function sugarbyte_cliq_add_gateway($methods)
{
	$methods[] = 'sugarbyte_cliq_WC_Gateway';
	return $methods;
}
add_filter('woocommerce_payment_gateways', 'sugarbyte_cliq_add_gateway');

/**
 * Register WooCommerce Blocks support.
 */
function sugarbyte_cliq_blocks_support()
{
	if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		require_once sugarbyte_cliq_PLUGIN_DIR . 'includes/class-sugarbyte-cliq-blocks-integration.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
				$payment_method_registry->register(new sugarbyte_cliq_Blocks_Integration());
			}
		);
	}
}
add_action('woocommerce_blocks_loaded', 'sugarbyte_cliq_blocks_support');

/**
 * Capture custom fields from Store API (Blocks Checkout)
 *
 * @param WC_Order $order   WooCommerce Order Object.
 * @param WP_REST_Request $request Request Object.
 */
function sugarbyte_cliq_store_api_update_order($order, $request)
{
	$payment_data = $request->get_param('payment_data');
	if (is_array($payment_data)) {
		foreach ($payment_data as $data) {
			if (is_array($data) && isset($data['key'], $data['value']) && 'sugarbyte_cliq_alias' === $data['key']) {
				$order->update_meta_data('_sugarbyte_cliq_customer_alias_tmp', sanitize_text_field($data['value']));
				$order->save();
				break;
			}
		}
	}
}
add_action('woocommerce_store_api_checkout_update_order_from_request', 'sugarbyte_cliq_store_api_update_order', 10, 2);

/**
 * Add custom links to the Plugin page.
 *
 * @param array $links Plugin links.
 * @return array
 */
function sugarbyte_cliq_plugin_action_links($links)
{
	$settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=sugarbyte_cliq');
	$plugin_links = array(
		'<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'sugarbyte-mobile-bank-payments') . '</a>',
	);
	return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sugarbyte_cliq_plugin_action_links');


