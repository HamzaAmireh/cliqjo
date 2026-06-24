<?php
/**
 * WooCommerce Gateway extension for Bank Al Etihad CliQ
 *
 * @package AlEtihadCliq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AL_Etihad_Cliq_WC_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'al_etihad_cliq';
		$this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name.
		$this->has_fields         = true; // We need a custom field for the Alias.
		$this->method_title       = esc_html__( 'Bank Al Etihad CliQ', 'al-etihad-cliq' );
		$this->method_description = esc_html__( 'Allows payments via Bank Al Etihad CliQ instant payment system.', 'al-etihad-cliq' );

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
		add_action( 'admin_footer', array( $this, 'al_etihad_cliq_add_multipart_form_data' ) );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => esc_html__( 'Enable/Disable', 'al-etihad-cliq' ),
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Enable Bank Al Etihad CliQ Payment', 'al-etihad-cliq' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => esc_html__( 'Title', 'al-etihad-cliq' ),
				'type'        => 'text',
				'description' => esc_html__( 'This controls the title which the user sees during checkout.', 'al-etihad-cliq' ),
				'default'     => esc_html__( 'CliQ Payment', 'al-etihad-cliq' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => esc_html__( 'Description', 'al-etihad-cliq' ),
				'type'        => 'textarea',
				'description' => esc_html__( 'This controls the description which the user sees during checkout.', 'al-etihad-cliq' ),
				'default'     => esc_html__( 'Pay easily using your CliQ Alias. You will receive a payment request on your mobile banking app.', 'al-etihad-cliq' ),
			),
			'api_base_url' => array(
				'title'       => esc_html__( 'API Base URL', 'al-etihad-cliq' ),
				'type'        => 'text',
				'description' => esc_html__( 'The base URL for the Bank Al Etihad payment API.', 'al-etihad-cliq' ),
				'default'     => 'https://api.developer.bankaletihad.com/api/v1/partner/cliq/payment',
				'desc_tip'    => true,
			),
			'token_url' => array(
				'title'       => esc_html__( 'Token Endpoint URL', 'al-etihad-cliq' ),
				'type'        => 'text',
				'description' => esc_html__( 'The identity provider URL to fetch the OAuth token.', 'al-etihad-cliq' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'client_id' => array(
				'title'       => esc_html__( 'Client ID', 'al-etihad-cliq' ),
				'type'        => 'text',
			),
			'client_secret' => array(
				'title'       => esc_html__( 'Client Secret', 'al-etihad-cliq' ),
				'type'        => 'password',
			),
			'cert_file_upload' => array(
				'title'       => esc_html__( 'Upload Certificate (.crt)', 'al-etihad-cliq' ),
				'type'        => 'file',
				'description' => esc_html__( 'Upload your signed TLS certificate.', 'al-etihad-cliq' ),
				'default'     => '',
			),
			'key_file_upload' => array(
				'title'       => esc_html__( 'Upload Key (.key)', 'al-etihad-cliq' ),
				'type'        => 'file',
				'description' => esc_html__( 'Upload your server key file.', 'al-etihad-cliq' ),
				'default'     => '',
			),
		);
	}

	/**
	 * Add enctype for file uploads to settings form.
	 */
	public function al_etihad_cliq_add_multipart_form_data() {
		$screen = get_current_screen();
		if ( $screen && 'woocommerce_page_wc-settings' === $screen->id && isset( $_GET['section'] ) && 'al_etihad_cliq' === $_GET['section'] ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('#mainform').attr('enctype', 'multipart/form-data');
				});
			</script>
			<?php
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
							echo '<p style="color: green;"><strong>' . esc_html__( 'Currently loaded:', 'al-etihad-cliq' ) . '</strong> ' . esc_html( basename( $current_file ) ) . '</p>';
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
				$cliq_dir   = $upload_dir['basedir'] . '/al-etihad-cliq-certs';
				
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
					WC_Admin_Settings::add_error( esc_html__( 'Invalid file extension uploaded.', 'al-etihad-cliq' ) );
					return $this->get_option( $key );
				}

				if ( file_put_contents( $file_path, $file_content ) === false ) {
					WC_Admin_Settings::add_error( esc_html__( 'Failed to write certificate file.', 'al-etihad-cliq' ) );
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
			echo wpautop( wp_kses_post( $this->description ) );
		}

		echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

		woocommerce_form_field( 'al_etihad_cliq_alias', array(
			'type'        => 'text',
			'class'       => array( 'al-etihad-cliq-alias-field form-row-wide' ),
			'label'       => esc_html__( 'Your CliQ Alias', 'al-etihad-cliq' ),
			'placeholder' => esc_attr__( 'Enter your registered CliQ Alias', 'al-etihad-cliq' ),
			'required'    => true,
		), '' );

		echo '<div class="clear"></div></fieldset>';
	}

	/**
	 * Validate custom frontend fields
	 */
	public function validate_fields() {
		if ( empty( $_POST['al_etihad_cliq_alias'] ) ) {
			wc_add_notice( esc_html__( 'Please enter your CliQ Alias.', 'al-etihad-cliq' ), 'error' );
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

		$cliq_alias = isset( $_POST['al_etihad_cliq_alias'] ) ? sanitize_text_field( wp_unslash( $_POST['al_etihad_cliq_alias'] ) ) : '';

		// If block checkout, fetch from temporary meta.
		if ( empty( $cliq_alias ) ) {
			$cliq_alias = $order->get_meta( '_al_etihad_cliq_customer_alias_tmp' );
		}

		if ( empty( $cliq_alias ) ) {
			wc_add_notice( esc_html__( 'CliQ Alias is missing.', 'al-etihad-cliq' ), 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => ''
			);
		}

		$cert_file = $this->cert_file;
		$key_file  = $this->key_file;

		$api = new AL_Etihad_Cliq_API(
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

			// Payment Request successfully created.
			$note = sprintf(
				/* translators: %s: CliQ alias */
				esc_html__( 'Bank Al Etihad CliQ Payment Request successfully sent to alias: %s. Order marked as On-Hold pending payment confirmation.', 'al-etihad-cliq' ),
				$cliq_alias
			);

			if ( ! empty( $response['ObjectId'] ) ) {
				$note .= "\n" . sprintf( esc_html__( 'Bank Object ID: %s', 'al-etihad-cliq' ), sanitize_text_field( $response['ObjectId'] ) );
			}
			
			if ( ! empty( $response['AccountNumber'] ) ) {
				$note .= "\n" . sprintf( esc_html__( 'Account Number: %s', 'al-etihad-cliq' ), sanitize_text_field( $response['AccountNumber'] ) );
			}

			// Add note and mark as on-hold.
			$order->update_status( 'on-hold', $note );

			// Save the alias in order meta for reference.
			$order->update_meta_data( '_al_etihad_cliq_customer_alias', $cliq_alias );
			if ( ! empty( $response['ObjectId'] ) ) {
				$order->update_meta_data( '_al_etihad_cliq_object_id', sanitize_text_field( $response['ObjectId'] ) );
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
			$order->add_order_note( 'Bank Al Etihad CliQ API Error: ' . esc_html( $e->getMessage() ) );
			wc_add_notice( esc_html__( 'Payment error: ', 'al-etihad-cliq' ) . esc_html( $e->getMessage() ), 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => ''
			);
		}
	}
}
