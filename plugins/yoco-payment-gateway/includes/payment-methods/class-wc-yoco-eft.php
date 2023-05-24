<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Yoco_EFT class.
 *
 * Handles EFT payments.
 */
class WC_Yoco_EFT extends WC_Yoco_Payment_Gateway {

	const ID = 'yoco_eft';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( self::ID );

		$this->method_title = __( 'Yoco EFT', 'yoco_wc_payment_gateway' );
		/* translators: Payment method title */
		$this->method_description = sprintf( __( 'Secure and instant electronic funds transfer via your customers\' internet banking.<br/>Set your general Yoco payment gateway settings <a href="%s">here</a>.', 'yoco_wc_payment_gateway' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=class_yoco_wc_payment_gateway' ) );
		$this->has_fields         = false;
		$this->supports           = array( 'products' );
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );
		$this->icon_url    = plugins_url( '../../assets/images/yoco/yoco-eft.png', __FILE__ );

		/**
		 * Pay For Order Receipting Hook
		 */
		add_action(
			'woocommerce_receipt_' . self::ID,
			array(
				$this,
				'pay_for_order',
			)
		);
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Woocommerce Init Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable Yoco EFT',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'       => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default'     => 'Yoco EFT',
			),

			'description' => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'This controls the description which the user sees during checkout.',
				'default'     => 'Pay securely with Electronic Funds Transfers by Yoco.',
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

        $order_id = $order->get_id();
        $payment_id = $this->get_yoco_payment_id_from_order($order_id);

        if ( $payment_id ) {
            do_action('wc_yoco_verify_payment', $order_id, $payment_id );
        } else {
            $response   = $this->initiate_blackbird_payment( $order );
            $payment_id = $response['id'];
            $this->maybe_save_payment_id_to_order($order_id, $payment_id );
        }


		$eft_popup_configuration = array(
			'paymentId' => $payment_id,
		);

		$yoco_eft_params = array_merge(
			array(
				'publicKey'                => $this->publishable_key,
				'order_id'                 => $order->get_id(),
				'payment_verification_url' => get_site_url() . self::WC_API_BB_ENDPOINT,
				'nonce'                    => wp_create_nonce( 'nonce_bb_verify_payment' ),
				'checkout_url'             => wc_get_checkout_url(),
				'popUpConfiguration'       => $eft_popup_configuration,
			),
			self::frontend_error_messages()
		);

		self::enqueue_common_payment_scripts();
		wp_register_script(
			'wc_yoco_eft',
			plugins_url( '../../assets/js/yoco/yoco-eft.js', __FILE__ ),
			array( 'jquery', 'yoco_blackbird_js' ),
			YOCO_PLUGIN_VERSION
		);
		wp_localize_script( 'wc_yoco_eft', 'wc_yoco_eft_params', $yoco_eft_params );
		wp_enqueue_script( 'wc_yoco_eft' );
	}

	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		parent::process_payment( $order );
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}
}
