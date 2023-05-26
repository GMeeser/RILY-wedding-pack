<?php

class WordPressEdits {
	public function init() : void {
		add_filter( 'init', [ $this, 'force_login'], 10, 1);
	}

	public function force_login() {
		$exception_pages = [
			'/^\/my-account\/.*/',
			'/^\/rsvp[\/\?]{1}.*/',
		];

		if ( is_user_logged_in() ) {
			return;
		}

		foreach ( $exception_pages as $exception ) {
			if ( 1 === preg_match( $exception, $_SERVER['REQUEST_URI'] ) ) {
				return;
			}
		}

		wp_redirect( '/my-account/' );
	}
}