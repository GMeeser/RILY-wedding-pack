<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Yoco_Card class.
 *
 * Handles debit/credit card payments.
 */
class WC_Yoco_Card extends WC_Yoco_Payment_Gateway {

	const ID = 'class_yoco_wc_payment_gateway';

	function __construct() {
		parent::__construct(self::ID);

		$this->method_title = 'Yoco';
		$this->method_description = 'Safe and seamless card payment for your customers, without ever leaving your site.';
		$this->has_fields = false;
		$this->supports = array( 'products' );
		$this->icon_url = plugins_url( '../../assets/images/yoco/yoco-debit-credit-card.png', __FILE__ );

		$this->saved_cards = 'yes' === $this->get_option( 'saved_cards' );
		if ( ! $this->is_payment_methods_page() && $this->saved_cards ) {
			$this->supports[] ='tokenization';
		}

		$this->title = $this->get_option( 'title' );

		$yoco_system_message = class_yoco_wc_error_logging::getYocoSystemMessages();
		if ($yoco_system_message == '') {
			$this->yoco_system_message = '';
		} else {
			$this->yoco_system_message = str_replace('YOCO_SYSTEM_MESSAGE', $yoco_system_message, $this->yoco_system_message);
		}

		$this->description = ($this->yoco_system_message != '') ? $this->get_option( 'description' ). "<br>".$this->yoco_system_message : $this->get_option( 'description' );
		$this->enabled = $this->get_option( 'enabled' );
		$this->yoco_wc_customer_error_msg = (!empty($this->get_option( 'customer_error_message' ))) ? trim($this->get_option( 'customer_error_message' )) : "Payment Gateway Error";

		WC_Yoco_Blackbird_API::set_keys( $this->private_key, $this->publishable_key );

		$this->perform_plugin_checks();
		$this->yoco_logging = new class_yoco_wc_error_logging($this->enabled, $this->testmode, $this->private_key, $this->publishable_key);
		add_action( 'woocommerce_update_options_payment_gateways_' . self::ID, array( $this, 'process_admin_options' ) );

		/**
		 * Pay For Order Receipting Hook
		 */
		add_action( 'woocommerce_receipt_' . self::ID, array(
			$this,
			'pay_for_order'
		) );

		add_action('admin_enqueue_scripts', array( $this,'yoco_admin_load_scripts'));
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );



		add_action('woocommerce_thankyou', array($this, 'auto_complete_virtual_orders'));
	}

	/**
	 * Woocommerce Init Form Fields
	 */
	public function init_form_fields(){
		add_action( 'woocommerce_api_plugin_health_check', array($this,'plugin_health_check'));

		$this->form_fields = array(
			'enabled' => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable Yoco Gateway',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default'     => 'Yoco – Debit and Credit Card',
			),

			'description' => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'This controls the description which the user sees during checkout.',
				'default'     => 'Pay securely with your debit or credit card via Yoco.',
			),

			'customer_error_message' => array(
				'title'       => 'Customer Error Message',
				'type'        => 'textarea',
				'description' => 'What the client sees when a payment processing error occurs',
				'default'     => 'Your order could not be processed by Yoco - please try again later',
			),

			'mode' => array(
				'title'       => 'Mode',
				'label'       => 'Mode',
				'type'        => 'select',
				'description' => 'Test mode allow you to test the plugin without processing money, make sure you set the plugin to live mode and click on "Save changes" for real customers to use it',
				'default'     => 'Test',
				'options' => array(
					'live' => 'Live',
					'test' => 'Test'
				)
			),

			'live_public_key' => array(
			  'title'       => 'Live Public Key',
			  'type'        => 'password',
			  'description' => 'Live Public Key',
			),
			'live_secret_key' => array(
				'title'       => 'Live Secret Key',
				'type'        => 'password',
				'description' => 'Live Secret Key',
			),

			'test_public_key' => array(
				'title'       => 'Test Public Key',
				'type'        => 'text',
				'description' => 'Test Public Key',
			),
			'test_secret_key' => array(
				'title'       => 'Test Secret Key',
				'type'        => 'text',
				'description' => 'Test Secret Key',
			),
			'saved_cards' => array(
				'title'       => 'Saved Cards',
				'label'       => 'Enable Payment via Saved Cards',
				'type'        => 'checkbox',
				'description' => 'If enabled, users will be able to pay with a saved card during checkout. Card details are saved on Yoco servers, not on your store.',
				'default'     => 'no',
			),
		);
	}

	/**
	 * Woocommerce Payment Scripts
	 */
	public function payment_scripts() {
		$order = $this->get_order_for_payment_scripts_to_enqueue();
		if ( ! $order ) {
			return;
		}

		// check if this is an order marked to paid with a payment method
		if ( 
			$this->saved_cards && 
			WC_Yoco_Tokenization::order_must_use_saved_payment_method( $order ) 
		) {
			return WC_Yoco_Tokenization::tokenization_payment_scripts( $order );
		}

		// proceed below with customer not using a saved payment method

		$order_data = $order->get_data();
		$order_description = self::get_description_for_order( $order );
		$customer_yoco_id = null;

		// if the saved cards option is enabled, create a Yoco customer profile
		if ( $this->saved_cards && is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$customer_yoco_id = WC_Yoco_Tokenization::get_customer_yoco_id( $current_user->ID, $order );
		}
        $order_id = $order->get_id();
        $payment_id = $this->get_yoco_payment_id_from_order($order_id);

        if ( $payment_id ) {
            do_action('wc_yoco_verify_payment', $order_id, $payment_id );
        } else {
            $response = $this->initiate_blackbird_payment($order);
            $payment_id = $response['id'];
            $this->maybe_save_payment_id_to_order($order_id, $payment_id );
        }

		$yoco_popup_configuration = array(
		  'amountInCents' => self::format_order_total( $order ),
		  'currency' => 'ZAR',
		  'description' => $order_description,
		  'id' => $payment_id,
		  'email' => $order_data['billing']['email'],
		  'firstName' => $order_data['billing']['first_name'],
		  'lastName' => $order_data['billing']['last_name'],
		  'name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		);

		if ( $customer_yoco_id && $this->saved_cards ) {
			$yoco_popup_configuration['customer'] = $customer_yoco_id;
			$yoco_popup_configuration['showSaveCardCheckbox'] = true;
			$yoco_popup_configuration['paymentMethodDetails'] = array(
				'save'      => true,
				'usable'    => 'online',
			);
		}

		/**
		 * Filter the Yoco PopUp Configuration
		 * This excludes the JavaScript callbacks
		 *
		 * @param array    $yoco_popup_configuration Yoco PopUp configuration
		 * @param WC_Order $order                    Order being charged against
		 */
		$yoco_popup_configuration = apply_filters(
		  'wc_yoco_popup_configuration',
		  $yoco_popup_configuration,
		  $order
		);

		$yoco_params = array_merge(
			array(
				'publicKey' => $this->publishable_key,
				'order_id' => $order->get_id(),
				'paymentId' => $payment_id,
				'url' => get_site_url() . self::WC_API_BB_ENDPOINT,
				'nonce' => wp_create_nonce('nonce_bb_verify_payment'),
				'checkout_url' => wc_get_checkout_url(),
				'popUpConfiguration' => $yoco_popup_configuration
			),
			self::frontend_error_messages()
		);

		self::enqueue_common_payment_scripts();
		self::enqueue_yoco_sdk_script();

		wp_localize_script( 'woocommerce_yoco', 'yoco_params', $yoco_params );
		wp_enqueue_script( 'woocommerce_yoco' );
	}

	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		parent::process_payment( $order );

		if ( ! WC_Yoco_Tokenization::is_using_saved_payment_method() || ! is_user_logged_in() ) {
			WC_Yoco_Tokenization::maybe_delete_tokenization_meta( $order );
			return array(
				'result' => 'success',
				'redirect' => $order->get_checkout_payment_url( true ),
			);
		}
		WC_Yoco_Tokenization::use_token_from_request( $order, $_POST );
		return array(
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		);
	}

}
