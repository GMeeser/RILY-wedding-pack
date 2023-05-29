<?php
/**
 * This class contains all the function needed to add RSVP, +1 and family groups as options to users.
 */
class RSVP {
	/**
	 * Registers all the required hooks.
	 *
	 * @return void
	 */
	public function init() : void {
		add_filter( 'manage_users_columns', [ $this, 'add_column_to_user_table' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'display_data_for_user_table' ], 10, 3 );
		add_action( 'show_user_profile', [ $this, 'add_rsvp_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'edit_user_profile', [ $this, 'add_rsvp_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'show_user_profile', [ $this, 'add_plus_one_permission_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'edit_user_profile', [ $this, 'add_plus_one_permission_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'show_user_profile', [ $this, 'add_plus_one_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'edit_user_profile', [ $this, 'add_plus_one_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'show_user_profile', [ $this, 'add_rsvp_id_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'edit_user_profile', [ $this, 'add_rsvp_id_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'show_user_profile', [ $this, 'add_family_group_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'edit_user_profile', [ $this, 'add_family_group_to_user_settings_wp_admin' ], 10, 1 );
		add_action( 'profile_update', [ $this, 'update_user_rsvp' ], 10, 1 );
	}

	/**
	 * Adds extra columns to the users table in wp-admin
	 *
	 * @param array $column
	 * @return array
	 */
	public function add_column_to_user_table( array $column ) : array {
		$column['rsvp']     = __( 'RSVP', 'libretto-child' );
		$column['plus_one'] = __( 'Has +1', 'libretto-child' );
		return $column;
	}

	/**
	 * Displays the data within the newly created columns in the user table of wp-admin
	 *
	 * @param  mixed $val the val to display
	 * @param string $column_name
	 * @param int $user_id
	 * @return mixed
	 */
	public function display_data_for_user_table( $val, $column_name, $user_id ) {
		switch ( $column_name ) {
			case 'rsvp' :
				if ( ! self::has_RSVPed( $user_id ) ) {
					return __( 'No Response', 'libretto-child' );
				}
				return self::is_going( $user_id) ? __( 'Yes', 'libretto-child' ) : __( 'No', 'libretto-child' );
			case 'plus_one':
				$yes = sprintf(
					'<a href="%s" >%s</a>',
					get_admin_url( null, 'user-edit.php?user_id=' . get_user_meta( $user_id, 'plus_one', true ) ),
					__( 'Yes', 'libretto-child' )
				);
				return self::has_plus_one( $user_id ) ? $yes : __( 'No', 'libretto-child' ) ;
		}
		return $val;
	}

	/**
	 * sets the RSVP status for a user. if no user is provided the current user is used.
	 *
	 * @param boolean $going
	 * @param integer|null $user_id
	 * @return void
	 */
	public static function set_response( bool $going, ?int $user_id ) : void {
		$user_id = $user_id ?? get_current_user_id();
		if ( empty( $user_id ) ) {
			return;
		}

		if ( $going ) {
			update_user_meta( $user_id, 'rsvp', 'yes' );
		} else {
			update_user_meta( $user_id, 'rsvp', 'no' );
		}
	}

	/**
	 * returns weather the user has responded to the RSVP
	 * If no user is provided the current user is checked.
	 *
	 * @param integer|null $user_id
	 * @return boolean
	 */
	public static function has_RSVPed( ?int $user_id ) : bool {
		$user_id = $user_id ?? get_current_user_id();
		if ( empty( $user_id ) ) {
			return false;
		}

		return empty( get_user_meta($user_id, 'rsvp', true ) ) ? false : true;
	}

	/**
	 * Returns true if the user has RSVPed yes, if the user has not responded or RSVPed no false is returned.
	 * If no user is provided the current user is checked.
	 *
	 * @param integer|null $user_id
	 * @return boolean|null
	 */
	public static function is_going( ?int $user_id ) : ?bool {
		$user_id = $user_id ?? get_current_user_id();
		if ( empty( $user_id ) ) {
			return null;
		}
		return 'no' === get_user_meta( $user_id, 'rsvp', true ) ? false : true;
	}

	/**
	 * Set weather the user may have a +1
	 * If no user is provided the current user is checked.
	 *
	 * @param boolean $permission
	 * @param integer|null $user_id
	 * @return void
	 */
	public static function Allow_plus_one( bool $permission, ?int $user_id ) : void {
		$user_id = $user_id ?? get_current_user_id();
		if ( empty( $user_id ) ) {
			return;
		}

		if ( $permission ) {
			update_user_meta( $user_id, 'plus_one_permission', 'yes' );
		} else {
			update_user_meta( $user_id, 'plus_one_permission', 'no' );
		}
	}

	/**
	 * Returns true only if the user has a plus one added
	 * If no user is provided the current user is checked.
	 *
	 * @param integer|null $user_id
	 * @return boolean
	 */
	public static function has_plus_one( ?int $user_id ) : bool {
		$user_id = $user_id ?? get_current_user_id();

		if ( ! empty( get_user_meta( $user_id, 'plus_one', true ) ) ) {
			$plus_one_user = get_user_by( 'id', get_user_meta( $user_id, 'plus_one', true ) );
			if ( empty( $plus_one_user ) ) {
				return false;
			}
			return true;
		}

		return false;
	}

	/**
	 * Returns true is the user is allowed to bring a plus one.
	 * If no user is provided the current user is checked.
	 *
	 * @param integer|null $user_id
	 * @return boolean
	 */
	public static function can_have_plus_one( ?int $user_id ) : bool {
		$user_id = $user_id ?? get_current_user_id();

		if ( ! empty( get_user_meta( $user_id, 'plus_one_permission', true ) ) ) {
			if ( 'yes' === get_user_meta( $user_id, 'plus_one_permission', true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Adds the plus one user to the users account.
	 * If no user is provided the current user is checked.
	 *
	 * @param integer $plus_one_id
	 * @param integer|null $user_id
	 * @return void
	 */
	public static function add_plus_one( int $plus_one_id, ?int $user_id ) : void {
		$user_id = $user_id ?? get_current_user_id();
		if ( ! self::can_have_plus_one( $user_id ) ) {
			return;
		}
		// Link +1 users
		update_user_meta( $user_id, 'plus_one', $plus_one_id );
		update_user_meta( $plus_one_id, 'plus_one', $user_id );
		// Add +1 to family group.
		$family_group = self::get_family_group( $user_id );
		array_push( $family_group, $plus_one_id );
		self::set_family_group( $family_group, $user_id );
	}

	/**
	 * Returns the RSVP id. This ID is attached to the rsvp link to directly link to this users
	 * family group RSVP response form.
	 * If no user is provided the current user is checked.
	 *
	 * @param integer|null $user_id
	 * @return string|null
	 */
	public static function get_rsvp_id( ?int $user_id ) : ?string {
		$user_id = $user_id ?? get_current_user_id();

		if ( ! empty( get_user_meta( $user_id, 'rsvp_id', true ) ) ) {
			return get_user_meta( $user_id, 'rsvp_id', true );
		}

		return null;
	}

	/**
	 * Sets the RSVP id provided to the users account.
	 * If no user is provided the current user is used.
	 *
	 * @param string $rsvp_id
	 * @param integer|null $user_id
	 * @return void
	 */
	public static function set_rsvp_id( string $rsvp_id, ?int $user_id ) : void {
		$user_id = $user_id ?? get_current_user_id();

		update_user_meta( $user_id, 'rsvp_id', $rsvp_id );
	}

	/**
	 * Returns a user given the rsvp id
	 *
	 * @param string $rsvp_id
	 * @return WP_User|null
	 */
	public static function get_user_from_rsvp_id( string $rsvp_id ) : ?WP_User {
		$users = get_users(
			[
				'meta_key'   => 'rsvp_id',
				'meta_value' => $rsvp_id,
			]
		);
		if ( empty( $users ) ) {
			return null;
		} else {
			return $users[0];
		}
	}

	/**
	 * Returns an array of users that are linked into a family group.
	 * If no user is provided the current user is checked.
	 *
	 * @param integer|null $user_id
	 * @return array
	 */
	public static function get_family_group( ?int $user_id ) : array {
		$user_id = $user_id ?? get_current_user_id();
		$output  = [];

		if ( ! empty( get_user_meta( $user_id, 'family_group', true ) ) ) {
			$family_group = get_user_meta( $user_id, 'family_group', true );
			foreach ( $family_group as $member ) {
				if ( $member === $user_id ) {
					continue;
				}
				$output[] = $member;
			}
		}

		return array_unique( $output );
	}

	/**
	 * Sets the family group for the user provided, and every user in the family group.
	 * If no user is provided the current user is checked.
	 *
	 * @param array $family_group
	 * @param integer|null $user_id
	 * @return void
	 */
	public static function set_family_group( array $family_group, ?int $user_id ) : void {
		$user_id = $user_id ?? get_current_user_id();
		array_push( $family_group, $user_id );
		foreach ( $family_group as $member ) {
			update_user_meta( $member, 'family_group', $family_group );
		}
	}

	/**
	 * Adds the RSVP radio boxes to the user settings page in wp-admin
	 *
	 * @param WP_User $user
	 * @return void
	 */
	public function add_rsvp_to_user_settings_wp_admin( WP_User $user ) : void {
		$value = get_user_meta( $user->ID, 'rsvp', true );
		?>
		<table class="form-table" role="presentation">
			<tr class="user-url-wrap">
				<th><label for="rsvp"><?php _e( 'RSVP:', 'libretto-child' ); ?></label></th>
				<td>
					<table>
						<tr>
							<td>
								<label for="rsvp_yes">
									<input type="radio" name="rsvp" id="rsvp_yes" value="yes" <?php checked( 'yes', $value ); ?> />
									<?php _e( 'Yes', 'libretto-child' ); ?>
								</label>
							</td>
							<td>
								<label for="rsvp_no">
									<input type="radio" name="rsvp" id="rsvp_no" value="no" <?php checked( 'no', $value ); ?> />
									<?php _e( 'No', 'libretto-child' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Adds the +1 permission radio boxes to the wp-admin user settings page.
	 *
	 * @param WP_User $user
	 * @return void
	 */
	public function add_plus_one_permission_to_user_settings_wp_admin( WP_User $user ) {
		$value = self::can_have_plus_one( $user->ID ) ? 'yes' : 'no';
		?>
		<table class="form-table" role="presentation">
			<tr class="user-url-wrap">
				<th><label for="rsvp"><?php _e( 'Can Have +1:', 'libretto-child' ); ?></label></th>
				<td>
					<table>
						<tr>
							<td>
								<label for="plus_one_permission_yes">
									<input type="radio" name="plus_one_permission" id="plus_one_permission_yes" value="yes" <?php checked( 'yes', $value ); ?> />
									<?php _e( 'Yes', 'libretto-child' ); ?>
								</label>
							</td>
							<td>
								<label for="plus_one_permission_no">
									<input type="radio" name="plus_one_permission" id="plus_one_permission_no" value="no" <?php checked( 'no', $value ); ?> />
									<?php _e( 'No', 'libretto-child' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Adds the family group multi-select to the wp-admin user settings page.
	 *
	 * @param WP_User $user
	 * @return void
	 */
	public function add_family_group_to_user_settings_wp_admin( WP_User $user ) {
		$family_group = self::get_family_group( $user->ID );
		?>
		<table class="form-table" role="presentation">
			<tr class="user-url-wrap">
				<th><label for="rsvp"><?php _e( 'Family Group:', 'libretto-child' ); ?></label></th>
				<td>
					<table>
						<tr>
							<td>
								<label for="plus_one_permission_yes">
									<select name="family_group[]" id="family_group" multiple class="regular-text" >
										<?php foreach ( get_users() as $other_user ) : ?>
										<?php if ( $other_user->ID === $user->ID ) { continue; } ?>
										<option value=<?php echo esc_attr( $other_user->ID ); selected( true, in_array( $other_user->ID, $family_group, false )  )?> ><?php echo esc_attr( $other_user->display_name ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Adds the plus one indicator to the wp-admin user settings page.
	 *
	 * @param WP_User $user
	 * @return void
	 */
	public function add_plus_one_to_user_settings_wp_admin( WP_User $user ) {
		$value = get_user_meta( $user->ID, 'plus_one', true );
		$plus_one_user = get_user_by( 'id', $value );
		if ( empty( $plus_one_user ) ) {
			$user_name = __( 'NONE', 'libretto-child' );
		} else {
			$user_name = sprintf(
				'<a href="%s" >%s</a>',
				get_admin_url( null, 'user-edit.php?user_id=' . get_user_meta( $user->ID, 'plus_one', true ) ),
				$plus_one_user->display_name
			);
		}
		?>
		<table class="form-table" role="presentation">
			<tr class="user-url-wrap">
				<th><label for="rsvp"><?php _e( '+1 Details:', 'libretto-child' ); ?></label></th>
				<td>
					<table>
						<tr>
							<td>
								<label for="plus_one">
									<?php echo ( $user_name ); ?>
								</label>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Adds the RSVP Id text box to the wp-admin user settings page.
	 *
	 * @param WP_User $user
	 * @return void
	 */
	public function add_rsvp_id_to_user_settings_wp_admin( WP_User $user ) {
		$value = self::get_rsvp_id( $user->ID );
		?>
		<table class="form-table" role="presentation">
			<tr class="user-url-wrap">
				<th><label for="rsvp"><?php _e( 'RSVP ID', 'libretto-child' ); ?></label></th>
				<td>
					<table>
						<tr>
							<td>
								<label for="rsvp_id">
									<input type="text" name="rsvp_id" id="rsvp_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
								</label>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Hooks on the update user hook, and checks if the newly added options from the settings page are present.
	 * if so they are checked and used to update the user.
	 *
	 * @param integer $user_id
	 * @return void
	 */
	public function update_user_rsvp( int $user_id ) : void {
		if ( empty( $_REQUEST['rsvp'] ) && empty( $_REQUEST['plus_one_permission'] ) && empty( $_REQUEST['rsvp_id'] ) ) {
			return;
		}

		switch ( $_REQUEST['rsvp'] ) {
			case 'yes':
				self::set_response( true, $user_id );
				break;
			case 'no':
				self::set_response( false, $user_id );
				break;
		}

		switch ( $_REQUEST['plus_one_permission'] ) {
			case 'yes':
				self::Allow_plus_one( true, $user_id );
				break;
			case 'no':
				self::Allow_plus_one( false, $user_id );
				break;
		}

		if ( ! empty( $_REQUEST['family_group'] ) ) {
			$data = [];
			foreach ( $_REQUEST['family_group'] as $member ) {
				if ( is_integer( intval( $member ) ) ) {
					$data[] = $member;
				}
			}
			self::set_family_group( $data, $user_id );
		}

		if ( ! empty( $_REQUEST['rsvp_id'] ) ) {
			self::set_rsvp_id( sanitize_text_field( $_REQUEST['rsvp_id'] ), $user_id );
		}
	}
}
