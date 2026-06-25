<?php
/**
 * WooCommerce Gateway extension for Sugarbyte Payment Gateway with CliQ
 *
 * @package AlEtihadCliq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class sugarbyte_cliq_WC_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'sugarbyte_cliq';
		$this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name.
		$this->has_fields         = true; // We need a custom field for the Alias.
		$this->method_title       = esc_html__( 'Sugarbyte Payment Gateway with CliQ', 'sugarbyte-mobile-bank-payments' );
		$this->method_description = esc_html__( 'Allows payments via Sugarbyte Payment Gateway with CliQ instant payment system.', 'sugarbyte-mobile-bank-payments' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title         = $this->get_option( 'title' );
		$this->description   = $this->get_option( 'description' );
		$this->enabled       = $this->get_option( 'enabled' );

		$this->api_base_url  = $this->get_option( 'api_base_url' );
		$this->token_url     = $this->get_option( 'token_url' );
		$this->client_id     = $this->get_option( 'client_id' );
		$this->client_secret = $this->get_option( 'client_secret' );
		$this->cert_file     = $this->get_option( 'cert_file_upload' );
		$this->key_file      = $this->get_option( 'key_file_upload' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'sugarbyte_cliq_add_multipart_form_data' ) );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => esc_html__( 'Enable/Disable', 'sugarbyte-mobile-bank-payments' ),
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Enable Sugarbyte Payment Gateway with CliQ Payment', 'sugarbyte-mobile-bank-payments' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => esc_html__( 'Title', 'sugarbyte-mobile-bank-payments' ),
				'type'        => 'text',
				'description' => esc_html__( 'This controls the title which the user sees during checkout.', 'sugarbyte-mobile-bank-payments' ),
				'default'     => esc_html__( 'CliQ Payment', 'sugarbyte-mobile-bank-payments' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => esc_html__( 'Description', 'sugarbyte-mobile-bank-payments' ),
				'type'        => 'textarea',
				'description' => esc_html__( 'This controls the description which the user sees during checkout.', 'sugarbyte-mobile-bank-payments' ),
				'default'     => esc_html__( 'Pay easily using your CliQ Alias. You will receive a payment request on your mobile banking app.', 'sugarbyte-mobile-bank-payments' ),
			),
			'api_base_url' => array(
				'title'       => esc_html__( 'API Base URL', 'sugarbyte-mobile-bank-payments' ),
				'type'        => 'text',
				'description' => esc_html__( 'The base URL for the Bank Al Etihad payment API.', 'sugarbyte-mobile-bank-payments' ),
				'default'     => 'https://api.developer.bankaletihad.com/api/v1/partner/cliq/payment',
				'desc_tip'    => true,
			),
			'token_url' => array(
				'title'       => esc_html__( 'Token Endpoint URL', 'sugarbyte-mobile-bank-payments' ),
				'type'        => 'text',
				'description' => esc_html__( 'The identity provider URL to fetch the OAuth token.', 'sugarbyte-mobile-bank-payments' ),
				'default'     => 'https://api.developer.bankaletihad.com:443/api/v1/tppa/token',
				'desc_tip'    => true,
			),
			'client_id' => array(
				'title'       => esc_html__( 'Client ID', 'sugarbyte-mobile-bank-payments' ),
				'type'        => 'text',
			),
			'client_secret' => array(
				'title'       => esc_html__( 'Client Secret', 'sugarbyte-mobile-bank-payments' ),
				'type'        => 'password',
			),
			'cert_file_upload' => array(
				'title'       => esc_html__( 'Upload Certificate (.crt)', 'sugarbyte-mobile-bank-payments' ),
				'type'        => 'file',
				'description' => esc_html__( 'Upload your signed TLS certificate.', 'sugarbyte-mobile-bank-payments' ),
				'default'     => '',
			),
			'key_file_upload' => array(
				'title'       => esc_html__( 'Upload Key (.key)', 'sugarbyte-mobile-bank-payments' ),
				'type'        => 'file',
				'description' => esc_html__( 'Upload your server key file.', 'sugarbyte-mobile-bank-payments' ),
				'default'     => '',
			),
		);
	}

	/**
	 * Add enctype for file uploads to settings form.
	 */
	public function sugarbyte_cliq_add_multipart_form_data() {
		$screen = get_current_screen();
		if ( $screen && 'woocommerce_page_wc-settings' === $screen->id && isset( $_GET['section'] ) && 'sugarbyte_cliq' === $_GET['section'] ) {
			wp_add_inline_script( 'jquery', "jQuery(document).ready(function($) { jQuery('#mainform').attr('enctype', 'multipart/form-data'); });" );
		}
	}

	/**
	 * Generate HTML for custom file upload field in settings.
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 * @return string
	 */
	public function generate_file_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="file" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
					<?php echo $this->get_description_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php
						$current_file = $this->get_option( $key );
						if ( ! empty( $current_file ) && file_exists( $current_file ) ) {
							echo '<p style="color: green;"><strong>' . esc_html__( 'Currently loaded:', 'sugarbyte-mobile-bank-payments' ) . '</strong> ' . esc_html( basename( $current_file ) ) . '</p>';
						}
					?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle file upload on save settings.
	 *
	 * @param string $key Field key.
	 * @param array  $field Field array.
	 * @param array  $post_data Post data.
	 * @return string|mixed
	 */
	public function get_field_value( $key, $field, $post_data = array() ) {
		if ( 'file' === $this->get_field_type( $field ) ) {
			$field_key = $this->get_field_key( $key );

			// Check nonce to ensure input sanitization and secure access (WooCommerce handles the main settings save nonce).
			if ( isset( $_FILES[ $field_key ] ) && UPLOAD_ERR_OK === $_FILES[ $field_key ]['error'] ) {
				
				// Sanitize file name
				$uploaded_file = sanitize_file_name($_FILES[ $field_key ]['name']);
				$file_content  = file_get_contents( sanitize_text_field( wp_unslash( $_FILES[ $field_key ]['tmp_name'] ) ) );
				
				$upload_dir = wp_upload_dir();
				$cliq_dir   = $upload_dir['basedir'] . '/sugarbyte-cliq-certs';
				
				if ( ! file_exists( $cliq_dir ) ) {
					wp_mkdir_p( $cliq_dir );
					file_put_contents( $cliq_dir . '/.htaccess', 'deny from all' );
					file_put_contents( $cliq_dir . '/index.php', '<?php // silence' );
				}
				
				$ext = ( 'cert_file_upload' === $key ) ? '.crt' : '.key';
				$file_path = $cliq_dir . '/' . $key . $ext;
				
				// Optional: Check mime type or extension to prevent arbitrary uploads
				$file_ext = strtolower(pathinfo($uploaded_file, PATHINFO_EXTENSION));
				if ( ($key === 'cert_file_upload' && $file_ext !== 'crt') || ($key === 'key_file_upload' && $file_ext !== 'key') ) {
					WC_Admin_Settings::add_error( esc_html__( 'Invalid file extension uploaded.', 'sugarbyte-mobile-bank-payments' ) );
					return $this->get_option( $key );
				}

				if ( file_put_contents( $file_path, $file_content ) === false ) {
					WC_Admin_Settings::add_error( esc_html__( 'Failed to write certificate file.', 'sugarbyte-mobile-bank-payments' ) );
					return $this->get_option( $key );
				}
				
				return $file_path; 
			} else {
				return $this->get_option( $key );
			}
		}
		return parent::get_field_value( $key, $field, $post_data );
	}

	/**
	 * Output the frontend checkout fields
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( wp_kses_post( $this->description ) ) );
		}

		echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

		woocommerce_form_field( 'sugarbyte_cliq_alias', array(
			'type'        => 'text',
			'class'       => array( 'sugarbyte-cliq-alias-field form-row-wide' ),
			'label'       => esc_html__( 'Your CliQ Alias', 'sugarbyte-mobile-bank-payments' ),
			'placeholder' => esc_attr__( 'Enter your registered CliQ Alias', 'sugarbyte-mobile-bank-payments' ),
			'required'    => true,
		), '' );

		echo '<div class="clear"></div></fieldset>';
	}

	/**
	 * Validate custom frontend fields
	 */
	public function validate_fields() {
		if ( empty( $_POST['sugarbyte_cliq_alias'] ) ) {
			wc_add_notice( esc_html__( 'Please enter your CliQ Alias.', 'sugarbyte-mobile-bank-payments' ), 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$cliq_alias = isset( $_POST['sugarbyte_cliq_alias'] ) ? sanitize_text_field( wp_unslash( $_POST['sugarbyte_cliq_alias'] ) ) : '';

		// If block checkout, fetch from temporary meta.
		if ( empty( $cliq_alias ) ) {
			$cliq_alias = $order->get_meta( '_sugarbyte_cliq_customer_alias_tmp' );
		}

		if ( empty( $cliq_alias ) ) {
			wc_add_notice( esc_html__( 'CliQ Alias is missing.', 'sugarbyte-mobile-bank-payments' ), 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => ''
			);
		}

		$cert_file = $this->cert_file;
		$key_file  = $this->key_file;

		$api = new sugarbyte_cliq_API(
			$this->api_base_url,
			$this->token_url,
			$this->client_id,
			$this->client_secret,
			$cert_file,
			$key_file
		);

		try {
			$response = $api->create_payment_request(
				$cliq_alias,
				$order->get_total(),
				$order_id
			);

			// translators: %s: CliQ alias
			$note_text = esc_html__( 'Sugarbyte Payment Gateway with CliQ Payment Request successfully sent to alias: %s. Order marked as On-Hold pending payment confirmation.', 'sugarbyte-mobile-bank-payments' );
			$note      = sprintf( $note_text, $cliq_alias );

			if ( ! empty( $response['ObjectId'] ) ) {
				// translators: %s: Bank Object ID
				$object_id_text = esc_html__( 'Bank Object ID: %s', 'sugarbyte-mobile-bank-payments' );
				$note          .= "\n" . sprintf( $object_id_text, sanitize_text_field( $response['ObjectId'] ) );
			}
			
			if ( ! empty( $response['AccountNumber'] ) ) {
				// translators: %s: Account Number
				$acc_num_text = esc_html__( 'Account Number: %s', 'sugarbyte-mobile-bank-payments' );
				$note        .= "\n" . sprintf( $acc_num_text, sanitize_text_field( $response['AccountNumber'] ) );
			}

			// Add note and mark as on-hold.
			$order->update_status( 'on-hold', $note );

			// Save the alias in order meta for reference.
			$order->update_meta_data( '_sugarbyte_cliq_customer_alias', $cliq_alias );
			if ( ! empty( $response['ObjectId'] ) ) {
				$order->update_meta_data( '_sugarbyte_cliq_object_id', sanitize_text_field( $response['ObjectId'] ) );
			}
			$order->save();

			// Reduce stock levels.
			wc_reduce_stock_levels( $order_id );

			// Empty cart.
			WC()->cart->empty_cart();

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);

		} catch ( Exception $e ) {
			$order->add_order_note( 'Sugarbyte Payment Gateway with CliQ API Error: ' . esc_html( $e->getMessage() ) );
			wc_add_notice( esc_html__( 'Payment error: ', 'sugarbyte-mobile-bank-payments' ) . esc_html( $e->getMessage() ), 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => ''
			);
		}
	}
}
