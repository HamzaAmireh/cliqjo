<?php
/**
 * Sugarbyte Payment Gateway with CliQ API Client Wrapper
 *
 * @package AlEtihadCliq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class sugarbyte_cliq_API {
	
	private $base_url;
	private $token_url;
	private $client_id;
	private $client_secret;

	private $cert_file;
	private $key_file;

	public function __construct( $base_url, $token_url, $client_id, $client_secret, $cert_file = '', $key_file = '' ) {
		$this->base_url      = trailingslashit( sanitize_url( $base_url ) );
		$this->token_url     = sanitize_url( $token_url );
		$this->client_id     = sanitize_text_field( $client_id );
		$this->client_secret = sanitize_text_field( $client_secret );
		$this->cert_file     = sanitize_text_field( $cert_file );
		$this->key_file      = sanitize_text_field( $key_file );
	}

	public function sugarbyte_cliq_set_curl_certs_callback( $handle, $r, $url ) {
		// Only apply certs if it's our API url.
		if ( strpos( $url, 'bankaletihad.com' ) !== false || strpos( $url, 'finto.io' ) !== false ) {
			if ( ! empty( $this->cert_file ) && file_exists( $this->cert_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
				curl_setopt( $handle, CURLOPT_SSLCERT, $this->cert_file );
			}
			if ( ! empty( $this->key_file ) && file_exists( $this->key_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
				curl_setopt( $handle, CURLOPT_SSLKEY, $this->key_file );
			}
		}
	}

	/**
	 * Fetch Access Token from Identity Provider
	 */
	public function get_access_token() {
		$auth = base64_encode( $this->client_id . ':' . $this->client_secret );
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . $auth,
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'body'    => array(
				'grant_type'    => 'client_credentials',
				'client_id'     => $this->client_id,
				'scope'         => 'openid identity cliqpayment'
			),
			'timeout'   => 30,
			'sslverify' => ( false === strpos( $this->token_url, 'sandbox' ) ),
		);

		add_action( 'http_api_curl', array( $this, 'sugarbyte_cliq_set_curl_certs_callback' ), 10, 3 );
		$response = wp_remote_post( $this->token_url, $args );
		remove_action( 'http_api_curl', array( $this, 'sugarbyte_cliq_set_curl_certs_callback' ), 10 );

		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['access_token'] ) ) {
			throw new Exception( esc_html__( 'Failed to retrieve token from IDP.', 'sugarbyte-mobile-bank-payments' ) );
		}

		return sanitize_text_field( $data['access_token'] );
	}

	/**
	 * Create a Payment Request
	 */
	public function create_payment_request( $alias, $amount, $external_id ) {
		$token = $this->get_access_token();

		$endpoint = $this->base_url . 'request';

		$payload = array(
			'Alias'                 => sanitize_text_field( $alias ),
			'Amount'                => round( (float) $amount, 3 ), // CliQ often expects accurate decimals.
			'ExternalTransactionId' => sanitize_text_field( (string) $external_id ),
		);

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'      => wp_json_encode( $payload ),
			'timeout'   => 30,
			'sslverify' => ( false === strpos( $endpoint, 'sandbox' ) ),
		);

		add_action( 'http_api_curl', array( $this, 'sugarbyte_cliq_set_curl_certs_callback' ), 10, 3 );
		$response = wp_remote_post( $endpoint, $args );
		remove_action( 'http_api_curl', array( $this, 'sugarbyte_cliq_set_curl_certs_callback' ), 10 );

		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code !== 200 ) {
			// translators: %s: HTTP status code
			$fallback_msg  = esc_html__( 'Unknown error from expected API endpoints. Status: %s', 'sugarbyte-mobile-bank-payments' );
			$error_message = isset( $data['Message'] ) ? sanitize_text_field( $data['Message'] ) : sprintf( $fallback_msg, absint( $status_code ) );
			throw new Exception( esc_html( $error_message ) );
		}

		return $data;
	}
}
