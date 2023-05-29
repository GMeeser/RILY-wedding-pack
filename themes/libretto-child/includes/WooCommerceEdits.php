<?php

class WooCommerceEdits {
	public function init() : void {
		add_filter( 'woocommerce_customer_meta_fields', [ $this, 'remove_shipping_address'], 10, 1 );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'clean_up_my_account_menu' ], 10, 1 );
		// delete_user_meta( 1, 'rsvp' );
	}

	/**
	 * Remove the un-needed fields from the user admin
	 * 
	 * @param array $fields the WC fields array used to generate the inputs on the page
	 * @return array
	 */
	public function remove_shipping_address( array $fields ) : array {
		unset( $fields['shipping'] );
		unset( $fields['billing']['fields']['billing_company'] );
		unset( $fields['billing']['fields']['billing_first_name'] );
		unset( $fields['billing']['fields']['billing_last_name'] );
		$fields['billing']['title'] = __( 'Address', 'libretto-child' );
		return $fields;
	}

	/**
	 * remove un-needed menu items from the my-account page.
	 *
	 * @param array $items
	 * @return array
	 */
	public function clean_up_my_account_menu( array $items ) : array {
		unset( $items['downloads'] );
		unset( $items['payment-methods'] );
		unset( $items['edit-address'] );

		return $items;
	}
}