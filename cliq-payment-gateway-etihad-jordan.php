<?php
/**
 * Plugin Name: Cliq Payment Gateway - Etihad Bank API
 * Plugin URI:  https://github.com/CliqJo/cliq-payment-gateway-etihad-jordan
 * Description: Enables Bank Al Etihad CliQ instant payments for WooCommerce.
 * Version:     1.0.0
 * Author:      CliqJo
 * Author URI:  https://cliqpal.vercel.app
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: al-etihad-cliq
 * Domain Path: /i18n/languages/
 *
 * @package AlEtihadCliq
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Make sure WooCommerce is active.
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
	return;
}

define('AL_ETIHAD_CLIQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AL_ETIHAD_CLIQ_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Initialize the gateway.
 */
function al_etihad_cliq_init_gateway()
{
	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	require_once AL_ETIHAD_CLIQ_PLUGIN_DIR . 'includes/class-al-etihad-cliq-api.php';
	require_once AL_ETIHAD_CLIQ_PLUGIN_DIR . 'includes/class-al-etihad-cliq-wc-gateway.php';
}
add_action('plugins_loaded', 'al_etihad_cliq_init_gateway', 11);

/**
 * Add the gateway to WooCommerce.
 *
 * @param array $methods Payment methods.
 * @return array
 */
function al_etihad_cliq_add_gateway($methods)
{
	$methods[] = 'AL_Etihad_Cliq_WC_Gateway';
	return $methods;
}
add_filter('woocommerce_payment_gateways', 'al_etihad_cliq_add_gateway');

/**
 * Register WooCommerce Blocks support.
 */
function al_etihad_cliq_blocks_support()
{
	if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		require_once AL_ETIHAD_CLIQ_PLUGIN_DIR . 'includes/class-al-etihad-cliq-blocks-integration.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
				$payment_method_registry->register(new AL_Etihad_Cliq_Blocks_Integration());
			}
		);
	}
}
add_action('woocommerce_blocks_loaded', 'al_etihad_cliq_blocks_support');

/**
 * Capture custom fields from Store API (Blocks Checkout)
 *
 * @param WC_Order $order   WooCommerce Order Object.
 * @param WP_REST_Request $request Request Object.
 */
function al_etihad_cliq_store_api_update_order($order, $request)
{
	$payment_data = $request->get_param('payment_data');
	if (is_array($payment_data)) {
		foreach ($payment_data as $data) {
			if (is_array($data) && isset($data['key'], $data['value']) && 'al_etihad_cliq_alias' === $data['key']) {
				$order->update_meta_data('_al_etihad_cliq_customer_alias_tmp', sanitize_text_field($data['value']));
				$order->save();
				break;
			}
		}
	}
}
add_action('woocommerce_store_api_checkout_update_order_from_request', 'al_etihad_cliq_store_api_update_order', 10, 2);

/**
 * Add custom links to the Plugin page.
 *
 * @param array $links Plugin links.
 * @return array
 */
function al_etihad_cliq_plugin_action_links($links)
{
	$settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=al_etihad_cliq');
	$plugin_links = array(
		'<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'al-etihad-cliq') . '</a>',
	);
	return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'al_etihad_cliq_plugin_action_links');

/**
 * Load plugin textdomain.
 */
function al_etihad_cliq_load_textdomain()
{
	load_plugin_textdomain('al-etihad-cliq', false, basename(dirname(__FILE__)) . '/i18n/languages/');
}
add_action('plugins_loaded', 'al_etihad_cliq_load_textdomain');
