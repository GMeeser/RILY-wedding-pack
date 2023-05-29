<?php

class WordPressEdits {
	public function init() : void {
		add_filter( 'init', [ $this, 'force_login'], 10, 1);
		add_filter( 'init', [ $this, 'add_rsvp_page'], 10, 1);
	}

	/**
	 * Force users to login to view the site except for the my-account pages ( for login and forgot password )
	 * and the RSVP page
	 *
	 * @return void
	 */
	public function force_login() {
		$exception_pages = [
			'/^\/my-account\/.*/',
			'/^\/rsvp[\/\?]{1}.*/',
		];

		if ( is_user_logged_in() ) {
			return;
		}

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			foreach ( $exception_pages as $exception ) {
				if ( 1 === preg_match( $exception, $_SERVER['REQUEST_URI'] ) ) {
					return;
				}
			}
		}

		wp_safe_redirect( '/my-account/' );
		die();
	}

	public function add_rsvp_page() : void {
		$check_page_exist = get_page_by_path('rsvp', 'OBJECT', 'page');
		// Check if the page already exists
		if ( empty( $check_page_exist ) ) {
			$page_id = wp_insert_post(
				[
					'comment_status' => 'close',
					'ping_status'    => 'close',
					'post_author'    => 1,
					'post_title'     => __( 'RSVP', 'libretto-child' ),
					'post_name'      => 'rsvp',
					'post_status'    => 'publish',
					'post_content'   => '[rsvp_form /]',
					'post_type'      => 'page',
				]
			);
		}
	}
}