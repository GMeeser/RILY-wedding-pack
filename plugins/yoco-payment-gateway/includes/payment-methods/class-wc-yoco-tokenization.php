<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Yoco_Tokenization class.
 *
 * Handles card tokenization.
 */
class WC_Yoco_Tokenization {
	private const YOCO_CUSTOMER_ID_KEY = 'yoco_customer_id';
	public const TOKENIZATION_META_KEY = '_pay_with_payment_method_id';

	/**
	 * Woocommerce Payment Scripts for tokenization
	 *
	 * @param \WC_Order $order 		Order
	 */
	public static function tokenization_payment_scripts( $order ) {
		$current_user     = wp_get_current_user();
		$yoco_customer_id = get_user_meta(
			$current_user->ID,
			self::YOCO_CUSTOMER_ID_KEY,
			true
		);

        $payment_id = self::get_yoco_payment_id_from_order( $order->get_id() );

        if( $payment_id ) {
            do_action('wc_yoco_verify_payment', $order->get_id(), $payment_id );
        } else {
            $order_metadata = WC_Yoco_Card::get_payment_metadata_for_order( $order );
            $response = WC_Yoco_Blackbird_API::initiate_payment(
                WC_Yoco_Card::format_order_total( $order ),
                get_woocommerce_currency(),
                $yoco_customer_id,
                $order_metadata
            );

            if ( is_wp_error( $response ) ) {
                $order->add_order_note(
                    sprintf(
                        __( "%1\$s payment cancelled! Transaction ID: %2\$d\n[%3\$s] %4\$s\n", 'yoco_wc_payment_gateway' ),
                        get_woocommerce_currency() . ' ' . $order->get_total(),
                        $order->get_id(),
                        $response->get_error_code(),
                        $response->get_error_message()
                    )
                );
                wc_add_notice(
                    __(
                        'There was a problem preparing your payment request. Please retry.',
                        'yoco_wc_payment_gateway'
                    ),
                    'error'
                );
                wp_redirect( wc_get_checkout_url() );
                exit;
            }
            $payment_id = $response['id'];
            self::maybe_save_payment_id_to_order($order->get_id(), $payment_id );
        }


		$payment_method_id = $order->get_meta( self::TOKENIZATION_META_KEY, true );

		$yoco_popup_configuration = array(
			'customer'      => $yoco_customer_id,
			'id'            => $payment_id,
			'paymentMethod' => $payment_method_id,
			'paymentType'   => 'CARD',
		);

		/**
		 * Filter the Yoco PopUp Configuration
		 * This excludes the JavaScript callbacks
		 *
		 * @param array     $yoco_popup_configuration Yoco PopUp configuration
		 * @param \WC_Order $order                    Order being charged against
		 */
		$yoco_popup_configuration = apply_filters(
			'wc_yoco_popup_configuration',
			$yoco_popup_configuration,
			$order
		);

		$yoco_params = array_merge(
			array(
				'publicKey'          => WC_Yoco_Blackbird_API::get_public_key(),
				'order_id'           => $order->get_id(),
				'paymentId'          => $payment_id,
				'url'                => get_site_url() . WC_Yoco_Card::WC_API_BB_ENDPOINT,
				'nonce'              => wp_create_nonce( 'nonce_bb_verify_payment' ),
				'checkout_url'       => wc_get_checkout_url(),
				'popUpConfiguration' => $yoco_popup_configuration,
			),
			WC_Yoco_Card::frontend_error_messages()
		);

		WC_Yoco_Card::enqueue_common_payment_scripts();
		wp_register_script(
			'wc_yoco_tokenization',
			plugins_url( '../../assets/js/yoco/yoco-tokenization.js', __FILE__ ),
			array( 'jquery', 'yoco_blackbird_js' ),
			YOCO_PLUGIN_VERSION
		);
		wp_localize_script( 'wc_yoco_tokenization', 'wc_yoco_tokenization_params', $yoco_params );
		wp_enqueue_script( 'wc_yoco_tokenization' );
	}

	/**
	 * Save a payment token (from $_POST) to the current user's profile.
	 */
	public static function save_payment_token() {
		if ( ! (
			isset( $_POST['token'] ) &&
			isset( $_POST['card_expiry_month'] ) &&
			isset( $_POST['card_expiry_year'] ) &&
			isset( $_POST['card_last_4_digits'] ) &&
			isset( $_POST['card_type'] ) &&
			class_exists( 'WC_Payment_Tokens' ) &&
			class_exists( 'WC_Payment_Token_CC' ) &&
			is_user_logged_in()
		) ) {
			return;
		}
		$token              = $_POST['token'];
		$card_expiry_month  = str_pad( $_POST['card_expiry_month'], 2, '0', STR_PAD_LEFT );
		$card_last_4_digits = $_POST['card_last_4_digits'];
		$card_type          = $_POST['card_type'];
		$card_expiry_year   = $_POST['card_expiry_year'];
		if ( strlen( $card_expiry_year ) === 2 ) {
			$card_expiry_year = '20' . $card_expiry_year;
		}

		$current_user        = wp_get_current_user();
		$card_already_exists = false;
		$user_tokens         = WC_Payment_Tokens::get_tokens(
			array(
				'user_id'    => $current_user->ID,
				'gateway_id' => WC_Yoco_Card::ID,
				'type'       => 'CC',
			)
		);
		foreach ( $user_tokens as $user_token ) {
			if (
				( $user_token->get_expiry_month() === $card_expiry_month ) &&
				( $user_token->get_last4() === $card_last_4_digits ) &&
				( $user_token->get_expiry_year() === $card_expiry_year ) &&
				( $user_token->get_card_type() === $card_type )
			) {
				$card_already_exists = true;
				break;
			}
		}
		if ( $card_already_exists ) {
			return;
		}

		$wc_token = new \WC_Payment_Token_CC();
		$wc_token->set_token( $token );
		$wc_token->set_gateway_id( WC_Yoco_Card::ID );
		$wc_token->set_card_type( strtolower( $card_type ) );
		$wc_token->set_last4( $card_last_4_digits );
		$wc_token->set_expiry_month( $card_expiry_month );
		$wc_token->set_expiry_year( $card_expiry_year );
		$wc_token->set_user_id( $current_user->ID );
		$wc_token->save();
	}

	/**
	 * Extracts and saves the payment token from the request.
	 *
	 * @param \WC_Order $order   Order
	 * @param array $request    Associative array containing payment request information.
	 */
	public static function use_token_from_request( $order, array $request ) {
		if ( ! class_exists( 'WC_Payment_Tokens' ) ) {
			return;
		}

		$payment_method    = ! is_null( $request['payment_method'] ) ? $request['payment_method'] : null;
		$token_request_key = 'wc-' . $payment_method . '-payment-token';
		if (
			! isset( $request[ $token_request_key ] ) ||
			'new' === $request[ $token_request_key ]
			) {
			return;
		}

		$token = WC_Payment_Tokens::get( wc_clean( $request[ $token_request_key ] ) );

		// If the token doesn't belong to this gateway or the current user it's invalid.
		if ( ! $token || $payment_method !== $token->get_gateway_id() || $token->get_user_id() !== get_current_user_id() ) {
			return;
		}

		$order->add_meta_data(
			self::TOKENIZATION_META_KEY,
			$token->get_token(),
			true
		);
		$order->save();
	}

	/**
	 * Checks if payment is via saved payment source.
	 *
	 * @return bool
	 */
	public static function is_using_saved_payment_method() {
		$payment_method = WC_Yoco_Card::ID;
		return (
			isset( $_POST[ 'wc-' . $payment_method . '-payment-token' ] ) &&
			'new' !== $_POST[ 'wc-' . $payment_method . '-payment-token' ]
		);
	}

	/**
	 * Retrieves the user's Blacbird customer ID, creating the profile if the
	 * user does not have one.
	 *
	 * @return null|string The customer ID
	 */
	public static function get_customer_yoco_id( $user_id, $order ) {
		$customer_yoco_id = get_user_meta(
			$user_id,
			self::YOCO_CUSTOMER_ID_KEY,
			true
		);
		if ( $customer_yoco_id ) {
			return $customer_yoco_id;
		}

		$order_data              = $order->get_data();
		$create_customer_request = WC_Yoco_Blackbird_API::create_customer(
			sprintf(
				'%s %s',
				$order_data['billing']['first_name'],
				$order_data['billing']['last_name']
			),
			'',
			'',
			array(
				'customerFirstName' => $order_data['billing']['first_name'],
				'customerLastName'  => $order_data['billing']['last_name'],
				'customerEmail'     => $order_data['billing']['email'],
			)
		);

		if ( is_wp_error( $create_customer_request ) ) {
			$order->add_order_note(
				sprintf(
					__( "Yoco customer profile creation cancelled.\n[%1\$s] %2\$s\n", 'yoco_wc_payment_gateway' ),
					$create_customer_request->get_error_code(),
					$create_customer_request->get_error_message()
				)
			);
			return null;
		}
		$customer_yoco_id = $create_customer_request['id'];
		update_user_meta(
			$user_id,
			self::YOCO_CUSTOMER_ID_KEY,
			$customer_yoco_id
		);
		return $customer_yoco_id;
	}

	/**
	 * Removes tokenization meta from an order.
	 *
	 * This is useful for instances where a customer decides to
	 * use a new payment method after having selected to use a
	 * saved payment method. By removing the meta, the order is
	 * no longer fulfilled with the saved payment method.
	 *
	 * @param \WC_Order $order Order
	 */
	public static function maybe_delete_tokenization_meta( $order ) {
		if ( $order->meta_exists( WC_Yoco_Tokenization::TOKENIZATION_META_KEY ) ) {
			$order->delete_meta_data( WC_Yoco_Tokenization::TOKENIZATION_META_KEY );
		}
	}

	/**
	 * Is the order marked to be paid for with a saved payment method?
	 *
	 * @param \WC_Order $order Order to check
	 * @return bool
	 */
	public static function order_must_use_saved_payment_method( $order ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$current_user = wp_get_current_user();
		return (
			get_user_meta(
				$current_user->ID,
				self::YOCO_CUSTOMER_ID_KEY,
				true
			) &&
			$order->meta_exists( WC_Yoco_Tokenization::TOKENIZATION_META_KEY )
		);
	}

    public static function maybe_save_payment_id_to_order($order_id, $payment_id) {

        update_post_meta( $order_id,'_yoco_payment_id', $payment_id);
    }

    public static function get_yoco_payment_id_from_order( $order_id ) {
        $order = wc_get_order( $order_id);
        if( ! is_a($order, 'WC_Order')) return false;

        return get_post_meta( $order_id, '_yoco_payment_id', true);
    }
}
