<?php
class RSVP {
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
        add_action( 'profile_update', [ $this, 'update_user_rsvp' ], 10, 1 );
    }

    public function add_column_to_user_table( $column ) {
        $column['rsvp'] = __( 'RSVP', 'libretto-child' );
        $column['plus_one'] = __( 'Has +1', 'libretto-child' );
        return $column;
    }
    
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

    public static function RSVP( bool $going, ?int $user_id ) : void {
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

    public static function has_RSVPed( ?int $user_id ) : bool {
        $user_id = $user_id ?? get_current_user_id();
        if ( empty( $user_id ) ) {
            return false;
        }

        return empty( get_user_meta($user_id, 'rsvp', true ) ) ? false : true;
    }

    public static function is_going( ?int $user_id ) : ?bool {
        $user_id = $user_id ?? get_current_user_id();
        if ( empty( $user_id ) ) {
            return null;
        }
        
        return 'no' === get_user_meta( $user_id, 'rsvp', true ) ? false : true;
    }

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

    public static function has_plus_one( ?int $user_id ) : bool {
        $user_id = $user_id ?? get_current_user_id();
        
        if ( ! self::can_have_plus_one( $user_id ) ) {
            return false;
        }

        if ( ! empty( get_user_meta( $user_id, 'plus_one', true ) ) ) {
            return true;
        }

        return false;
    }

    public static function can_have_plus_one( ?int $user_id ) : bool {
        $user_id = $user_id ?? get_current_user_id();
        
        if ( ! empty( get_user_meta( $user_id, 'plus_one_permission', true ) ) ) {
            return true;
        }

        return false;
    }

    public static function add_plus_one( int $plus_one_id, ?int $user_id ) : void {
        $user_id = $user_id ?? get_current_user_id();
        if ( ! self::can_have_plus_one( $user_id ) ) {
            return;
        }

        update_user_meta( $user_id, 'plus_one', $plus_one_id );
        update_user_meta( $plus_one_id, 'plus_one', $user_id );
    }

    public static function get_rsvp_id( ?int $user_id ) : ?string {
        $user_id = $user_id ?? get_current_user_id();
        
        if ( ! empty( get_user_meta( $user_id, 'rsvp_id', true ) ) ) {
            return get_user_meta( $user_id, 'rsvp_id', true );
        }

        return null;
    }

    public static function set_rsvp_id( string $rsvp_id, ?int $user_id ) : void {
        $user_id = $user_id ?? get_current_user_id();
        
        update_user_meta( $user_id, 'rsvp_id', $rsvp_id );
    }

    public static function get_user_from_rsvp_id( string $rsvp_id ) : ?WP_User {
        $users = get_users(
            [
                'meta_key'   => 'rsvp_id',
                'meta_value' => $rsvp_id
            ]
        );
        if ( empty( $users ) ) {
            return null;
        } else {
            return $user[0];
        }
    }

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
                                   <input type="text" name="rsvp_id" id="rsvp_id" value="<?php echo esc_attr( $value ); ?>" />
                               </label>
                           </td>
                       </tr>
                   </table>
               </td>
           </tr>
       </table>
   <?php
   }

    public function update_user_rsvp( int $user_id ) : void {
        if ( empty( $_REQUEST['rsvp'] ) && empty( $_REQUEST['plus_one_permission'] ) && empty( $_REQUEST['rsvp_id'] ) ) {
            return;
        }

        switch ( $_REQUEST['rsvp'] ) {
            case 'yes':
                self::RSVP( true, $user_id );
                break;
            case 'no':
                self::RSVP( false, $user_id );
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

        if ( ! empty( $_REQUEST['rsvp_id'] ) ) {
            self::set_rsvp_id( sanitize_text_field( $_REQUEST['rsvp_id'] ), $user_id );
        }
    }
}