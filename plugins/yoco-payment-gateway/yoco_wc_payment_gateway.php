<?php
/*
 * Plugin Name: Yoco Payment Gateway
 * Plugin URI: https://wordpress.org/plugins/yoco-payment-gateway/
 * Description: Take debit and credit card payments on your store.
 * Author: Yoco
 * Author URI: https://www.yoco.com
 * Version: 2.0.12
 * Requires at least: 4.6
 * Tested up to: 5.9
 * WC requires at least: 3.0
 * WC tested up to: 6.1
 */

define('YOCO_PLUGIN_VERSION', '2.0.12');

load_plugin_textdomain( 'yoco_wc_payment_gateway', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
add_action( 'admin_init', function () {
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' )) {
        add_action( 'admin_enqueue_scripts', function () {
            wp_enqueue_script( 'requirements_js', plugins_url( 'assets/js/admin/requirements.js', __FILE__ ) );
            return;
        });
    }
});
add_action( 'plugins_loaded' , 'wc_yoco_gateway_init', 0 );
/**
 * Initialize the gateway.
 *
 * @since 1.0.0
 */
function wc_yoco_gateway_init() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    require_once dirname( __FILE__ ) . '/includes/logging/class-yoco-wc-error.php';
    require_once dirname( __FILE__ ) . '/includes/logging/class-yoco-wc-error-logging.php';
    require_once dirname( __FILE__ ) . '/includes/abstracts/abstract-wc-yoco-payment-gateway.php';
    require_once dirname( __FILE__ ) . '/includes/class-wc-yoco-blackbird-api.php';
    require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-yoco-tokenization.php';
    require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-yoco-card.php';
    require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-yoco-eft.php';
    add_filter( 'woocommerce_payment_gateways', 'woocommerce_yoco_add_gateway' );
}
/**
 * Add the gateway to WooCommerce
 *
 * @since 1.0.0
 */
function woocommerce_yoco_add_gateway( $methods ) {
    $methods[] = WC_Yoco_Card::class;
    $methods[] = WC_Yoco_EFT::class;
    return $methods;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'yoco_thrive_add_plugin_page_settings_link' );
function yoco_thrive_add_plugin_page_settings_link( $links ) {
    $links[] = '<a href="' .
        admin_url( 'admin.php?page=wc-settings&tab=checkout&section=class_yoco_wc_payment_gateway' ) .
        '">' . __( 'Settings' ) . '</a>';
    return $links;
}

/**
 * Migrate Yoco payment gateway options if necessary
 */
function maybe_migrate_yoco_payment_gateway_options() {
    $version_option_key = 'yoco_wc_payment_gateway_version';
    $installed_version = get_option( $version_option_key );
    if ( YOCO_PLUGIN_VERSION !== $installed_version ) {
        update_option( $version_option_key, YOCO_PLUGIN_VERSION );
    }

    if ( version_compare( $installed_version, '2.0.1', '>' ) ) {
        return;
    }

    // update title and description once for card versions 2.0.1 and below
    $gateway_options_key = 'woocommerce_' . WC_Yoco_Card::ID . '_settings';
    $gateway_options = get_option( $gateway_options_key );
    if ( ! is_array ( $gateway_options ) ) {
        return;
    }

    if ( array_key_exists( 'title', $gateway_options )  ) {
        $gateway_options['title'] = 'Yoco – Debit and Credit Card';
    }
    if ( array_key_exists( 'description', $gateway_options )  ) {
        $gateway_options['description'] = 'Pay securely with your debit or credit card via Yoco.';
    }

    update_option( $gateway_options_key, $gateway_options );
}

add_action( 'plugins_loaded', 'maybe_migrate_yoco_payment_gateway_options' );
