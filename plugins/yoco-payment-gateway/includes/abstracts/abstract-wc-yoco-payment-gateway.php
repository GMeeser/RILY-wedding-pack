<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class WC_Yoco_Payment_Gateway extends WC_Payment_Gateway {
	const BLACKBIRD_POPUP_SDK_ENDPOINT = 'https://js.yoco.com/sdk/v2/blackbird-web-sdk.js';
	const WC_API_BB_ENDPOINT = '/?wc-api=ajax_bb_verify_payment';
	const WC_API_ADMIN_ENDPOINT = '/?wc-api=plugin_health_check';
	const YOCO_ALLOWED_CURRENCY = 'ZAR';

	public $yoco_wc_customer_error_msg;
	public $yoco_logging;
	public $yoco_system_message = '<br><span style="color: #ff0000 !important; font-size: smaller !important;">YOCO_SYSTEM_MESSAGE</span>';

	/**
	 * Merchant's secret key
	 *
	 * @var string
	 */
	public $private_key;

	/**
	 * Merchant's public key
	 *
	 * @var string
	 */
	public $publishable_key;

	/**
	 * Is the merchant in test mode?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Payment method's icon URL
	 *
	 * @var string
	 */
	public $icon_url;

	/**
	 * Constructor
	 */
	public function __construct($payment_method) {
        $this->id = $payment_method;
		$this->init_form_fields();
		$this->init_settings();
		$this->get_merchant_keys();

        add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'handle_custom_query_var' ) , 10, 2 );
        add_action('wc_yoco_verify_payment', array( $this, 'bb_verify_payment') , 10, 2 );
        /**
         * Ajax payment verify
         */
        add_action(
            'woocommerce_api_ajax_bb_verify_payment',
            array( $this, 'ajax_bb_verify_payment' )
        );

	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		parent::payment_fields();
		if ( in_array( 'tokenization', $this->supports ) && is_user_logged_in() ) {
			$this->tokenization_script();
			$this->saved_payment_methods();
		}
	}

	/**
	 * Get title function.
	 *
	 * @return string
	 */
	public function get_title() {
		// show the title with an icon on the checkout page alone
		if ( ! is_checkout() ) {
			return parent::get_title();
		}

		$logo_url = plugins_url( '../../assets/images/yoco/yoco.svg', __FILE__ );
        $img = '<img src="' . $logo_url . '" style="height: 1.4em;margin-left: 0px;margin-right: 0.3em;display: inline;float: none;" class="' . $this->id . '-payment-method-title-icon" alt="Yoco logo" />';
		$title = '<span style="display: inline-flex;align-items: center;vertical-align: middle;">' . $img . parent::get_title() . '</span>';
		return apply_filters( 'woocommerce_gateway_title', $title, $this->id );
	}

	/**
	 * Get icon function.
	 *
	 * @return string
	 */
	public function get_icon() {
        $icon = '<img src="' . $this->icon_url . '" style="max-height:26px;" class="' . $this->id . '-payment-method-icon" alt="Yoco payment option brands" />';
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	public function admin_options() {
		if ( $this->is_currency_valid_for_use() ) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error">
				<p>
					<strong><?php esc_html_e( 'Gateway disabled', 'yoco_wc_payment_gateway' ); ?></strong>: <?php _e( 'Currency not supported by by Yoco plugin, you can change the currency of the store <a href="/wp-admin/admin.php?page=wc-settings">here</a>', 'yoco_wc_payment_gateway' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * @return array
	 *
	 * Perform Plugin Health Check
	 */
	public function perform_plugin_checks() {
		$health = array( 'SSL' => false, 'KEYS' => false, 'CURRENCY' => false );

		if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
			$this->enabled = 'no';

		} else {
			$health['KEYS'] = true;
		}

		if ( ! $this->testmode && ! is_ssl() ) {
			$this->enabled = 'no';
		} else {
			$health['SSL'] = true;
		}


		if ( get_woocommerce_currency() !== static::YOCO_ALLOWED_CURRENCY ) {
			$this->enabled = 'no';
		} else {
			$health['CURRENCY'] = true;
		}

		return $health;
	}

	/**
	 * Yoco Admin Load Scripts
	 */

	static function is__payments_admin_page() {
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
		$section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';

		return ( $tab === 'checkout' && $section === 'class_yoco_wc_payment_gateway' );
	}

	public function yoco_admin_load_scripts() {
		wp_enqueue_style(
			'admin_styles',
			plugins_url( '../../assets/css/admin/admin.css', __FILE__ ),
			[],
			YOCO_PLUGIN_VERSION
		);
		if ( static::is__payments_admin_page() ) {
			wp_enqueue_script(
				'admin_js',
				plugins_url( '../../assets/js/admin/admin.js', __FILE__ )
			);
		}
		wp_localize_script( 'admin_js', 'yoco_params', array(
			'url' => get_site_url().static::WC_API_ADMIN_ENDPOINT,
			'nonce' => wp_create_nonce( 'nonce_yoco_admin' ),
		));
	}

	/**
	 * Woocommerce Currency Validator
	 * @return bool
	 */
	public function is_currency_valid_for_use() {
		return in_array(
			get_woocommerce_currency(),
			apply_filters(
				'woocommerce_yoco_supported_currencies',
				array( 'ZAR')
			),
			true
		);
	}

	/**
	 * Returns true when viewing payment methods page.
	 *
	 * @return bool
	 */
	public function is_payment_methods_page() {
		global $wp;
		$page_id = wc_get_page_id( 'myaccount' );
		return ( $page_id && is_page( $page_id ) && ( isset( $wp->query_vars['payment-methods'] ) ) );
	}

	public static function format_order_total($order) {
		return absint( wc_format_decimal( ( $order->get_total() * 100 ), wc_get_price_decimals() ) );
	}

	/**
	 * Enqueue common payment scripts
	 */
	public static function enqueue_common_payment_scripts() {
		wp_enqueue_script( 'yoco_blackbird_js', static::BLACKBIRD_POPUP_SDK_ENDPOINT );
		wp_enqueue_style(
			'customer_styles',
			plugins_url( '../../assets/css/customer/customer.css', __FILE__ ),
			[],
			YOCO_PLUGIN_VERSION
		);
		wp_enqueue_style(
			'orderpay_styles',
			plugins_url( '../../assets/css/frontend/orderpay.css', __FILE__ ),
			[],
			YOCO_PLUGIN_VERSION
		);
	}

	/**
	 * Enqueue Yoco SDK
	 */
	public static function enqueue_yoco_sdk_script() {
		wp_register_script(
			'woocommerce_yoco',
			plugins_url( '../../assets/js/yoco/yoco.js', __FILE__ ),
			array( 'jquery', 'yoco_blackbird_js' ),
			YOCO_PLUGIN_VERSION
		);
	}

	/**
	 * Text for frontend error messages
	 *
	 * @return array
	 */
	public static function frontend_error_messages() {
		return array(
			'frontendResourcesError' => __(
				'Your browser failed to load some resources for payment. Please retry.',
				'yoco_wc_payment_gateway'
			),
			'frontendNetworkError' => __(
				'Your browser failed to connect to our website for payment completion. Please retry.',
				'yoco_wc_payment_gateway'
			),
			'frontendErrorAction' => __(
				'Retry',
				'yoco_wc_payment_gateway'
			),
		);
	}

	/**
	 * Get Order ID from Order-Pay endpoint
	 *
	 * @return string|null
	 */
	public function get_order_id_order_pay_yoco() {
		global $wp;
		$order_id = absint( $wp->query_vars['order-pay'] );
		if ( empty( $order_id ) || $order_id == 0 ) {
			return null;
		}
		return $order_id;
	}

	/**
	 * Retrieve order if this is a valid checkout we enqueue payment scripts for
	 * 
	 * @return WC_Order
	 */
	public function get_order_for_payment_scripts_to_enqueue() {
		if ( ! is_wc_endpoint_url( 'order-pay' ) || isset( $_GET['pay_for_order'] ) ) {
			return false;
		}

		if ( 'no' === $this->enabled ) {
			return false;
		}

		if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
			return false;
		}

		if ( ! $this->testmode && ! is_ssl() ) {
			return false;
		}

		$order_id = $this->get_order_id_order_pay_yoco();
		if ( $order_id === null ) {
			return false;
		}

		$order = wc_get_order( $order_id );
		$is_yoco_order = $order->get_payment_method() === $this->id;
		if ( ! $is_yoco_order ) {
			return false;
		}

		if ( $order->is_paid() ) {
			wp_redirect( $order->get_checkout_order_received_url() );
			exit;
		}
		if ( $order->get_status() !== 'pending' && $order->get_status() !== 'failed' ) {
			return false;
		}

		return $order;
	}

	/**
	 * Initiates a Blackbird payment and handles any errors.
	 * 
	 * @param WC_Order $order The order to be paid
	 * 
	 * @return array
	 */
	public function initiate_blackbird_payment( $order ) {
		$order_metadata = static::get_payment_metadata_for_order( $order );
		$response = WC_Yoco_Blackbird_API::initiate_payment(
			static::format_order_total( $order ),
			get_woocommerce_currency(),
			null,
			$order_metadata
		);

		if ( is_wp_error( $response ) ) {
			$order->add_order_note( sprintf(
				__( "%s payment cancelled! Transaction ID: %d\n[%s] %s\n", 'yoco_wc_payment_gateway' ),
				get_woocommerce_currency().' '.$order->get_total(),
				$order->get_id(),
				$response->get_error_code(),
				$response->get_error_message()
			));
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

		return $response;
	}

	/**
	 * Generate order metadata.
	 *
	 * @param WC_Order $order Order
	 *
	 * @return array
	 */
	public static function get_payment_metadata_for_order( $order ) {
		$order_description = static::get_description_for_order( $order );
		$order_data = $order->get_data();
		return array(
			'billNote'          => $order_description,
			'productType'       => "wc_plugin",
			'customerFirstName' => $order_data['billing']['first_name'],
			'customerLastName'  => $order_data['billing']['last_name']
		);
	}

	/**
	 * Generate order description.
	 *
	 * @param WC_Order $order Order
	 *
	 * @return string
	 */
	public static function get_description_for_order( $order ) {
		$order_data = $order->get_data();
		return sprintf(
			/* translators: %1$s: Order ID, %2$ Billing first name, %3$s  Billing last name, %4$s Billing email address*/
			__( 'order %1$s from %2$s %3$s (%4$s)', 'yoco_wc_payment_gateway' ),
			$order->get_id(),
			$order_data['billing']['first_name'],
			$order_data['billing']['last_name'],
			$order_data['billing']['email']
		);
	}

	/**
	 * @param $order
	 * Process a successful payment and redirect to order received
	 */
	public function process_success($order) {
	  global $woocommerce;
	  $order->add_order_note(
		sprintf(
		  __(
			"%s %s was successfully processed through Yoco, you can see this payment in Yoco transaction history (on the Yoco app or on the desktop portal)\n",
			'yoco_wc_payment_gateway'
		  ),
		  get_woocommerce_currency(),
		  $order->get_total()
	  ));
	  $order->payment_complete();
	  $order->add_order_note(
		__(
		  'Order status updated for payment completion.',
		  'yoco_wc_payment_gateway'
		)
	  );
	  $woocommerce->cart->empty_cart();
	  wp_send_json( array(
		'success' => true,
		'redirect' => $order->get_checkout_order_received_url(),
	  ));
      exit;
	}

	/***
	 * @param $order
	 * @param $message
	 *
	 * Process a failed payment and redirect to checkout url
	 */
	public function process_failure($order, $message) {
		if ( empty( $message ) ) {
		  $message = __(
			'Could not connect to Yoco server.',
			'yoco_wc_payment_gateway'
		  );
		}
		// Cancel Order
		$order->update_status(
		  'failed',
		  sprintf(
			__( "%s payment cancelled! Transaction ID: %d\n%s\n", 'yoco_wc_payment_gateway' ),
			get_woocommerce_currency().' '.$order->get_total(),
			$order->get_id(),
			$message
		  )
		);
		// Add WC Notice
		wc_add_notice( $this->yoco_wc_customer_error_msg, 'error' );
		$this->set_wc_admin_notice( 'Yoco Payment Gateway Error [Order# '.$order->get_id().']: '.$message );
		// Redirect to Cart Page
		wp_send_json( array(
		  'success' => false,
		  'redirect' => wc_get_checkout_url(),
		));
		exit;
	}

	/**
	 * Process a failed payment from Yoco Endpoint and redirect to checkout url
	 *
	 * @param WC_Order $order           Order being charged against
	 * @param string   $error_code      Yoco charge API error code
	 * @param string   $error_message   Yoco charge API error message
	 * @param string   $display_message Yoco charge API error display message
	 */
	public function process_yoco_failure($order, $error_code, $error_message, $display_message) {
		// Cancel Order
		$order->update_status(
		  'failed',
		  sprintf(
			__( "%s payment cancelled! Transaction ID: %d\nError Code: %s\nError Message: %s\n", 'yoco_wc_payment_gateway' ),
			get_woocommerce_currency().' '.$order->get_total(),
			$order->get_id(),
			$error_code,
			$error_message
		  )
		);
		// Add WC Notice
		wc_add_notice( $display_message, 'error' );
		$this->set_wc_admin_notice('Yoco Payment Gateway Error [Order# '.$order->get_id().']: '.$error_message);
		class_yoco_wc_error::save_yoco_customer_order_error($order->get_id(), $error_code, $error_message);
		// Redirect to Cart Page
		wp_send_json( array(
		  'success' => false,
		  'redirect' => wc_get_checkout_url(),
		));
        exit;
	}

	/**
	 * @param $message
	 * Displays a Woocommerce Admin Notice to the backend
	 */
	public function set_wc_admin_notice($message) {
		$html = __("<h2 class='yoco_pg_admin_notice'>$message</h2>", 'yoco_payment_gateway');
		WC_Admin_Notices::add_custom_notice('yoco_payment_gateway', $html);
	}

	/**
	 * @param $order_id
	 * @return bool
	 *
	 * Pay For Yoco Order
	 */
	public function pay_for_order( $order_id ) {
		return true;
	}

	/**
	 * Ajax request handler for verifying a Blackbird payment ID.
	 */
	public function ajax_bb_verify_payment() {
		global $woocommerce;
		check_ajax_referer( 'nonce_bb_verify_payment', 'nonce' );
		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['order_id'] ) ) {
			$error_message = __( 'Invalid request', 'yoco_wc_payment_gateway' );
			wp_send_json( array(
			  'success' => false,
			  'redirect' => wc_get_checkout_url(),
			));
		}

		$payment_id = sanitize_text_field( $_POST['id'] ); // we need to verify that the payment id matches the one in record
        $payment_type = sanitize_text_field( $_POST['paymentType'] ?? $this->id );
		$order_id = intval( $_POST['order_id'] );
        $order = wc_get_order( $order_id );
        if( !$this->validate_cart_hash( $order_id, $payment_id, $payment_type )) {
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

		if ( $order->is_paid() ) {
			$woocommerce->cart->empty_cart();
			wp_send_json( array(
			  'success' => false,
			  'redirect' => $order->get_checkout_order_received_url(),
			));
		}

        do_action('wc_yoco_verify_payment', $order_id, $payment_id );
	}

    /**
     * Compare the cart hash of the current order with the cart hash to the associated with the payment id
     * @param $order_id
     * @param $payment_id
     * @return bool
     */
    public function validate_cart_hash($order_id, $payment_id, $payment_type): bool
    {
        if ( empty($payment_id) ) {
            return false;
        }
        //get the order
        $order = wc_get_order($order_id);
        $previous_orders = wc_get_orders( array(
            "yoco_payment_id"   => $payment_id,
            'payment_type'      => $payment_type
        ) );

        if( 1 > count($previous_orders) ) {
            return false; // no previous orders to match
        }
        if( 1 < count($previous_orders) ) {
            return false; // we have more than one record with the same payment id. so something is really messed up
        }
        $previous_order = wc_get_order( $previous_orders[0]->get_id() );

        return $order->has_cart_hash( $previous_order->get_cart_hash() );

    }

	public function bb_verify_payment($order_id, $payment_id) {
        $order = wc_get_order($order_id);
        $yoco_result = WC_Yoco_Blackbird_API::get_payment( $payment_id );
        // check for errors
        if (!is_array($yoco_result) ) {
            $this->process_failure( $order, strval($yoco_result) );
        }

        if( array_key_exists('errorCode', $yoco_result) ) {
            $this->process_yoco_failure(
                $order,
                $yoco_result['errorCode'],
                $yoco_result['errorMessage'],
                $yoco_result['displayMessage']
            );
        }

        if( ! array_key_exists('status', $yoco_result) ) {
            $this->process_failure(
                $order,
                __( 'No status defined on token charge.', 'yoco_wc_payment_gateway' )
            );
        }

        if( 'pending' === $yoco_result['status'] or 'processing' === $yoco_result['status'] ) {
            wc_add_notice(
                    __('Your payment is being processed. Please wait','yoco_wc_payment_gateway'),
                'info'
            );
            wp_redirect( $order->get_view_order_url() );
            exit;
        }
        if( 'succeeded' === $yoco_result['status'] ) {
            WC_Yoco_Tokenization::save_payment_token();
            $this->process_success( $order );
        }

        // draft mode should be handled by the calling method
        if ( "draft" === $yoco_result["status"] ) {
            return $yoco_result;
        }
	}

	/**
	 * Ajax Admin Plugin Health Check
	 */
	public function plugin_health_check() {
		check_ajax_referer( 'nonce_yoco_admin', 'nonce' );
		$health = $this->perform_plugin_checks( true );
		wp_send_json( $health );
		wp_die();
	}

	public function auto_complete_virtual_orders($order_id) {
		if ( ! $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( $order->get_payment_method() === static::ID ) {
			$items = $order->get_items();
			if ( count( $items ) < 1 ) {
				return;
			}

			foreach ( $items as $item ) {
				if ( $item['variation_id'] != '0' ) {
					$product = wc_get_product( $item['variation_id'] );
				} else {
					$product = new WC_Product( $item['product_id'] );
				}
				if ( $product->is_virtual() === false ) {
					return;
				}
			}
			$order->update_status( 'completed' );
		}
	}

	/**
	 * Retrieve the merchant's public and secret keys, as well as the mode.
	 */
	public function get_merchant_keys() {
		$settings = get_option( 'woocommerce_class_yoco_wc_payment_gateway_settings' );
		$this->testmode = ( ! empty( $settings['mode'] ) && 'test' === $settings['mode'] ) ? true : false;
		$prefix = ( $this->testmode ) ? 'test' : 'live';
		$publishable_key_id 	= $prefix . '_public_key';
		$private_key_id     	= $prefix . '_secret_key';
		$this->private_key     	= ! empty( $settings[$private_key_id] ) ? $settings[$private_key_id] : '';
		$this->publishable_key 	= ! empty( $settings[$publishable_key_id] ) ? $settings[$publishable_key_id] : '';
	}

	/**
	 * Process Payment.
	 *
	 * @param WC_Order $order Order.
	 * @return array
	 */
	public function process_payment( $order ) {
		// ensure logo is not included in the title saved with the order
		$order->set_payment_method_title( parent::get_title() );
		$order->save();
	}

    /**
     * @param $order_id Int
     * @param $payment_id String
     * @return void
     */
    public function maybe_save_payment_id_to_order($order_id, $payment_id) {

        update_post_meta( $order_id,'_yoco_payment_id', $payment_id);
    }


    /**
     * @param $order_id
     * @return Boolean|String
     */
    public function get_yoco_payment_id_from_order( $order_id ) {
        $order = wc_get_order( $order_id);
        if( ! is_a($order, 'WC_Order')) return false;

        return get_post_meta( $order_id, '_yoco_payment_id', true);
    }

    public function handle_custom_query_var( $query, $query_vars ) {
        if ( ! empty( $query_vars['yoco_payment_id'] ) ) {
            $query['meta_query'][] = array(
                    array( "key" => "_payment_method", "value" =>$query_vars['payment_type']),
                    array( "key" => "_yoco_payment_id", "value" => esc_attr( $query_vars['yoco_payment_id'] ) )
            );
        }

        return $query;
    }

}
