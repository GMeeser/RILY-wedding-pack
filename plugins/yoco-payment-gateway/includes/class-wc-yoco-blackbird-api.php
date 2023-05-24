<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Blackbird_API class.
 *
 */

class WC_Yoco_Blackbird_API {

    const BASE_URL  = 'https://payments-online.yoco.com';
	const PAYMENTS  = 'payments';
	const CUSTOMERS = 'customers';

	const ALLOWED_HTTP_STATUS    = array( 200, 201, 202 );
	const HTTP_STATUS_EXCLUSIONS = array( 400, 401 );

	private const KEY_PREFIX_LEN = 8;

	private static $secret_key;
	private static $public_key;

	/**
	 * Set API Keys.
	 *
	 * @param string $secret_key
	 */
	public static function set_keys( $secret_key, $public_key ) {
		self::$secret_key = $secret_key;
		self::$public_key = $public_key;
	}

	/**
	 * Returns the public key
	 *
	 * @return string the public key
	 */
	public static function get_public_key() {
		return self::$public_key;
	}

	/**
	 * Returns the redacted secret key
	 *
	 * @return string redacted secret key
	 */
	public static function get_redacted_secret_key() {
		// keep prefix, replace rest with asterisks
		$asterisks = strlen( self::$secret_key ) - self::KEY_PREFIX_LEN;
		return substr( self::$secret_key, 0, self::KEY_PREFIX_LEN ) . str_repeat( '*', $asterisks );
	}

	/**
	 * Makes a POST request to Blackbird (retries up to 3 times)
	 *
	 * @param  string   $path   Request path
	 * @param  string   $method Request method
	 * @param  array    $body   Request payload
	 * @param  bool     $retry  Is the method allowed to retry after the first failure?
	 * @return mixed|WP_Error
	 */
	private static function request( $path, $method = 'POST', $body = array(), $retry = true ) {
        $validateKeys = self::validate_keys();
        if ($validateKeys !== true) {
            return $validateKeys;
        }
		$args = array(
			'method'    => $method,
			'sslverify' => true,
			'headers'   => array(
				'Authorization' => 'Bearer ' . self::$secret_key,
				'Cache-Control' => 'no-cache',
				'Content-Type'  => 'application/json',
			),
			'body'      => 'POST' === $method ? json_encode( $body ) : array(),
			'timeout'   => 20,
		);

		$url = self::BASE_URL . '/' . $path;

		$retries = 0;
		while ( $retries < 3 ) {
			$response = wp_remote_post( $url, $args );
			if ( ! $retry ||
					( ! is_wp_error( $response ) && self::is_response_code_final( $response ) )
			) {
				break;
			}

			error_log( "Error response on attempt # {$retries}" );
			self::log_request_error( $path, $url, $args, $response, false );

			$retries++;
			sleep( $retries * 2 );
		}

		self::log_request_error( $path, $url, $args, $response, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body      = wp_remote_retrieve_body( $response );
		$json_body = json_decode( $body, true );
		if ( null === $json_body ) {
			return new WP_Error( -1, $body );
		}

		if ( array_key_exists( 'code', $json_body ) && array_key_exists( 'description', $json_body ) ) {
			return new WP_Error( $json_body['code'], $json_body['description'] );
		}
		return $json_body;
	}

	private static function sanitize_args( $request_args ) {
		$auth_header                              = $request_args['headers']['Authorization'];
		$request_args['headers']['Authorization'] =
			substr( $auth_header, 0, strlen( 'Bearer ' ) + self::KEY_PREFIX_LEN );
		return $request_args;
	}

	private static function log_request_error( $path, $url, $args, $response, $remote = false ) {
		$safe_args = self::sanitize_args( $args );

		$code = '-1';
		$msg  = 'Error connecting: ';
		if ( ! is_wp_error( $response ) ) {
			$code    = wp_remote_retrieve_response_code( $response );
			$msg     = 'Request result: ';
			$details = 'status: ' . print_r( $response['response'], true ) . PHP_EOL
			. 'headers: ' . print_r( $response['headers'], true ) . PHP_EOL
			. 'body: ' . print_r( $response['body'], true ) . PHP_EOL;
		} else {
			$details = 'error_code:' . $response->get_error_code() . PHP_EOL
				. 'error_message:' . $response->get_error_message() . PHP_EOL;
		}

		$error_message =
			$msg . PHP_EOL
			. 'secret_key: ' . self::get_redacted_secret_key() . PHP_EOL
			. 'public_key: ' . self::get_public_key() . PHP_EOL
			. 'url: ' . print_r( $url, true ) . PHP_EOL
			. 'request: ' . print_r( $safe_args, true ) . PHP_EOL
			. $details . PHP_EOL;

		// clean up the path
		if ( strpos( $path, '/' ) !== false ) {
			$path = strtoupper( substr( $path, 0, strpos( $path, '/' ) ) );
		}

		error_log( $error_message );

		if ( $remote ) {
			class_yoco_wc_error_logging::logError( $path, $code, $error_message, $args );
		}
	}

	/**
	 * Determine if a request's response code warrants a retry.
	 * @param  array|WP_Error $request WordPress request
	 * @return bool
	 */
	private static function is_response_code_final( $request ) {
		return in_array(
			wp_remote_retrieve_response_code( $request ),
			array_merge(
				self::ALLOWED_HTTP_STATUS,
				self::HTTP_STATUS_EXCLUSIONS
			),
			true
		);
	}

	/**
	 * Creates a Blackbird payment (retries up to 3 times)
	 *
	 * @param  string $amount    Payment amount in cents
	 * @param  array  $currency  Payment currenct
	 * @param  array  $cutomer   Payment's customer id
	 * @param  array  $metadata  Payment metadata
	 *
	 * @return mixed|WP_Error
	 */
	public static function initiate_payment( $amount, $currency, $customer = null, $metadata = array() ) {
		$body = array(
			'amount'   => $amount,
			'currency' => $currency,
			'metadata' => $metadata,
		);
		if ( $customer ) {
			$body['customer'] = $customer;
			$body['offline']  = false;
		}

		$response = self::request( 'payments', 'POST', $body );

		// an `id` must be present if the request succeeds
		if (
			( ! is_wp_error( $response ) && is_array( $response ) ) &&
			! array_key_exists( 'id', $response )
		) {
			return self::wp_error_for_response( $response );
		}

		return $response;
	}

	public static function get_payment( $id ) {
		return self::request( "payments/{$id}", 'GET', array() );
	}

	/**
	 * Creates a customer profile on Blackbird
	 *
	 * @param string $name      Customer name
	 * @param string $email     Customer email
	 * @param string $phone     Customer phone
	 * @param array  $metadata  Customer metadata
	 *
	 * @return mixed|WP_Error
	 */
	public static function create_customer( $name = '', $email = '', $phone = '', $metadata = array() ) {
		$body = array(
			'name'     => $name,
			'metadata' => json_encode( $metadata ),
		);
		if ( $email ) {
			$body['email'] = $email;
		}
		if ( $email ) {
			$body['phone'] = $phone;
		}
		$response = self::request( 'customers', 'POST', $body );

		// an `id` must be present if the request succeeds
		if (
			( ! is_wp_error( $response ) && is_array( $response ) ) &&
			! array_key_exists( 'id', $response )
		) {
			return self::wp_error_for_response( $response );
		}

		return $response;
	}

	/**
	 * Returns environment targeted by the keys ("test" / "live" / "mixed")
	 *
	 * @return string
	 */
	public static function key_environment() {
		if (
			preg_match( '/_live_/', self::$secret_key ) &&
			preg_match( '/_live_/', self::$public_key )
		) {
			return 'live';
		} elseif (
			preg_match( '/_test_/', self::$secret_key ) &&
			preg_match( '/_test_/', self::$public_key )
		) {
			return 'test';
		} else {
			return 'mixed';
		}
	}

	/**
	 * Validate the API keys are in the correct format and have matching target environments (live or test).
	 *
	 * @throws ApiKeyException If there is an error with the Api Keys
	 */
	private static function validate_keys() {
		if ( ! self::keys_look_correct() ) {
			$error_message = implode( "\n", self::get_key_errors() );
			$error         = array(
				'errorType'      => 'invalid_request_error',
				'errorCode'      => 'wrong_api_key',
				'errorMessage'   => 'The provided Api Keys are not valid.' . $error_message,
				'displayMessage' => 'There is a configuration error. Please contact support.',
			);
			$error_obj     =  $error;

            error_log($error_message);

			return $error_obj;
		}
		return true;
	}

	/**
	 * Simple check to confirm API keys look valid
	 *
	 * @return bool
	 */
	public static function keys_look_correct() {
		return (
				preg_match( '/^sk_test_/', self::$secret_key )
				&& preg_match( '/^pk_test_/', self::$public_key )
			)
			|| (
				preg_match( '/^sk_live_/', self::$secret_key )
				&& preg_match( '/^pk_live_/', self::$public_key )
			);
	}

	/**
	 * Returns any formatting errors found in the API keys
	 *
	 * @return array
	 */
	public static function get_key_errors() {
		$errors = array();
		if (
			! preg_match( '/^sk_test_/', self::$secret_key ) &&
			! preg_match( '/^sk_live_/', self::$secret_key ) ) {
			$errors[] = 'Secret key prefix is incorrect.';
		}
		if (
			! preg_match( '/^pk_test_/', self::$public_key ) &&
			! preg_match( '/^pk_live_/', self::$public_key ) ) {
			$errors[] = 'Public key prefix is incorrect.';
		}

		if ( strlen( self::$secret_key ) !== ( 8 + 28 ) ) {
			$errors[] = 'Secret key length is incorrect.';
		}
		if ( strlen( self::$public_key ) !== ( 8 + 20 ) ) {
			$errors[] = 'Public key length is incorrect.';
		}

		$key_mismatch = (
			preg_match( '/^sk_test/', self::$secret_key ) &&
			preg_match( '/^pk_live/', self::$public_key )
		) ||
		(
			preg_match( '/^sk_live/', self::$secret_key ) &&
			preg_match( '/^pk_test/', self::$public_key )
		);
		if ( $key_mismatch ) {
			$errors[] = 'Mixing test and live keys.';
		}

		return $errors;
	}

	/**
	 * Creates a WP_Error object for a wp_remote_post response
	 *
	 * @param array $response The json_decoded, non-error wp_remote_post response
	 * @return \WP_Error
	 */
	static function wp_error_for_response( $response ) {
		$error_code    = -1;
		$error_message = '';

		if ( is_string( $response ) ) {
			$error_message = $response;
		}
		if ( is_array( $response ) && array_key_exists( 'status', $response ) ) {
			$error_code = $response['status'];
		}
		if ( is_array( $response ) && array_key_exists( 'message', $response ) ) {
			$error_message = $response['message'];
		}
        if ( is_array( $response ) && array_key_exists( 'errorCode', $response ) ) {
            $error_code = $response['errorCode'];
        }
        if ( is_array( $response ) && array_key_exists( 'errorMessage', $response ) ) {
            $error_message = $response['errorMessage'];
        }
		return new WP_Error( $error_code, $error_message );
	}

    /**
     * Get merchant details
     * @param $id
     * @return mixed|WP_Error
     */
    public static function get_merchant_detail() {
        $response = self::request( "merchants/me", 'GET', array() );
        if (
            ( ! is_wp_error( $response ) && is_array( $response ) && !isset($response['merchantId']))
        ) {
            return self::wp_error_for_response( $response );
        }
        return $response;
    }
}
