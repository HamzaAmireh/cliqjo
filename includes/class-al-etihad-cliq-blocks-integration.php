<?php
/**
 * Blocks integration for Bank Al Etihad CliQ.
 *
 * @package AlEtihadCliq
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
	return;
}

final class AL_Etihad_Cliq_Blocks_Integration extends AbstractPaymentMethodType {
	
	protected $name = 'al_etihad_cliq'; // Matches Gateway ID

	public function initialize() {
		$this->settings = get_option( 'woocommerce_al_etihad_cliq_settings', [] );
	}

	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'al-etihad-cliq-blocks-integration',
			AL_ETIHAD_CLIQ_PLUGIN_URL . 'assets/js/blocks.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			'1.0.0',
			true // In footer
		);

		return [ 'al-etihad-cliq-blocks-integration' ];
	}

	public function get_payment_method_data() {
		return [
			'title'       => ! empty( $this->settings['title'] ) ? esc_html( $this->settings['title'] ) : esc_html__( 'Bank Al Etihad CliQ', 'al-etihad-cliq' ),
			'description' => ! empty( $this->settings['description'] ) ? esc_html( $this->settings['description'] ) : esc_html__( 'Pay easily using your CliQ Alias.', 'al-etihad-cliq' ),
		];
	}
}
