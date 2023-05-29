<?php

class RSVPShortCode {
	public function init() : void {
		add_shortcode( 'rsvp_form', [ $this, 'rsvp_form_renders' ] );
	}

	public function rsvp_form_renders( $attr ) {
		$family_group = [];
		if ( empty( $_REQUEST['id'] ) && is_user_logged_in() ) {
			$user = get_user_by( 'id', get_current_user_id() );
		} elseif ( ! empty( $_REQUEST['id'] ) ) {
			$user = RSVP::get_user_from_rsvp_id( $_REQUEST['id'] );
		} else {
			return __( 'Invalid Link, please try again', 'libretto-child' );
		}

		if ( ! empty( $user ) ) {
			$family_group = RSVP::get_family_group( $user->ID );
			array_push( $family_group, $user->ID );
		}

		foreach ( $family_group as $member ) {
			if ( ! empty( $_REQUEST[ 'rsvp_' . $member ] ) ) {
				$message = $this->process_form_post();
				ob_start();
				locate_template(
					'rsvp-form-submitted.php',
					true,
					true,
					[
						'message' => $message,
					]
				);
				return ob_get_clean();
			}
		}

		$family_group = array_unique( $family_group );

		if ( empty( $family_group ) ) {
			return;
		}

		wp_enqueue_script(
			'rsvp-form-script',
			get_stylesheet_directory_uri() . '/javascript/rsvp_form.js',
			[],
			1,
			true
		);

		ob_start();
		locate_template(
			'rsvp-form.php',
			true,
			true,
			[
				'user_ids' => $family_group
			]
		);
		return ob_get_clean();
	}

	public function process_form_post() {
		if ( empty( $_REQUEST['id'] ) && is_user_logged_in() ) {
			$user = get_user_by( 'id', get_current_user_id() );
		} elseif ( ! empty( $_REQUEST['id'] ) ) {
			$user = RSVP::get_user_from_rsvp_id( $_REQUEST['id'] );
		} else {
			return __( 'Invalid request', 'libretto-child' );
		}

		if ( ! empty( $user ) ) {
			$family_group = RSVP::get_family_group( $user->ID );
			array_push( $family_group, $user->ID );
		}
		if ( empty( $family_group ) ) {
			return __( 'Invalid request', 'libretto-child' );
		}

		// Check all emails addresses first
		foreach ( $family_group as $member_id ) {
			if ( ! empty( $_REQUEST[ 'email_' . $member_id ] ) ) {
				$email = sanitize_email( $_REQUEST[ 'email_' . $member_id ] );

				if ( email_exists( $email ) ) {
					return sprintf(
						'A user with the email %s already exists. Please try again.',
						$email
					);
				}

				if ( ! empty( $_REQUEST[ 'plus_one_email_' . $member_id ] ) ) {
					$email      = sanitize_email( $_REQUEST[ 'plus_one_email_' . $member_id ] );
					if ( email_exists( $email ) ) {
						return sprintf(
							'A user with the email %s already exists. Please try again.',
							$email
						);
					}
				}
			}
		}

		foreach ( $family_group as $member_id ) {
			// Add RSVP for user
			if ( ! empty( $_REQUEST[ 'rsvp_' . $member_id ] ) ) {
				if ( 'yes' === $_REQUEST[ 'rsvp_' . $member_id ] ) {
					RSVP::set_response( true, $member_id );
				} else {
					RSVP::set_response( false, $member_id );
				}
			}

			if ( RSVP::can_have_plus_one( $member_id ) ) {
				if ( ! empty( $_REQUEST[ 'plus_one_first_name_' . $member_id ] ) && ! empty( $_REQUEST[ 'plus_one_last_name_' . $member_id ] ) && ! empty( $_REQUEST[ 'plus_one_email_' . $member_id ] ) ) {
					$username   = sanitize_email( $_REQUEST[ 'plus_one_email_' . $member_id ] );
					$email      = sanitize_email( $_REQUEST[ 'plus_one_email_' . $member_id ] );
					$first_name = sanitize_text_field( $_REQUEST[ 'plus_one_first_name_' . $member_id ] );
					$last_name  = sanitize_text_field( $_REQUEST[ 'plus_one_last_name_' . $member_id ] );
					if ( email_exists( $email ) ) {
						return sprintf(
							'A user with the email %s already exists. Please try again.',
							$email
						);
					}

					$new_user_id = wp_create_user(
						$username,
						wp_generate_password( 12, true, true ),
						$email
					);

					wp_update_user(
						[
							'ID'           => $new_user_id,
							'display_name' => $first_name . ' ' . $last_name,
						]
					);

					update_user_meta( $new_user_id, 'first_name', $first_name );
					update_user_meta( $new_user_id, 'last_name', $last_name );

					RSVP::add_plus_one( $new_user_id, $member_id );
					RSVP::set_response( true, $new_user_id );
				}
			}

			if ( ! empty( $_REQUEST[ 'email_' . $member_id ] ) ) {
				$email = sanitize_email( $_REQUEST[ 'email_' . $member_id ] );

				if ( email_exists( $email ) ) {
					return sprintf(
						'A user with the email %s already exists. Please try again.',
						$email
					);
				}

				wp_update_user(
					[
						'ID'         => $member_id,
						'user_email' => $email,
					]
				);
			}

		}

		return true;
	}
}