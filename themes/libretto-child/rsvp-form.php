<?php
$family = [];
foreach ( $args['user_ids'] as $user_id ) {
	$user = get_user_by( 'id', $user_id );
	if ( ! empty( $user ) ) {
		$family[] = $user;
	}
}
?>
<form method="POST" name="rsvp_form" id="rsvp_form" >
	<table>
		<?php foreach ( $family as $member ) : ?>
		<tr>
			<?php $value = RSVP::is_going( $member->ID ); ?>
			<td colspan="1" class="float-container">
				<div class="col-3" >
					<label class="name" id="name_<?php echo esc_attr( $member->ID ); ?>" ><?php echo esc_attr( $member->display_name ); ?></label>
					<?php if ( 1 === preg_match( '/@[a-zA-Z0-9-.]*\.local/', $member->user_email ) ) : ?>
					<input type="email" id="email_<?php echo esc_attr( $member->ID ); ?>" name="email_<?php echo esc_attr( $member->ID ); ?>" placeholder="<?php _e( 'Email Address', 'libretto-child' ); ?>" required/>
					<?php endif; ?>
				</div>
				<div class="col-3" >
					<label for="rsvp_<?php echo esc_attr( $member->ID ); ?>_yes" class="radio" >
						<input type="radio" name="rsvp_<?php echo esc_attr( $member->ID ); ?>" id="rsvp_<?php echo esc_attr( $member->ID ); ?>_yes" value="yes" <?php checked( 'yes', $value ); ?> required />
						<?php _e( 'Yes', 'libretto-child' ); ?>
					</label>
					<label for="rsvp_<?php echo esc_attr( $member->ID ); ?>_no"  class="radio" >
						<input type="radio" name="rsvp_<?php echo esc_attr( $member->ID ); ?>" id="rsvp_<?php echo esc_attr( $member->ID ); ?>_no" value="no" <?php checked( 'no', $value ); ?> required />
						<?php _e( 'No', 'libretto-child' ); ?>
					</label>
				</div>
				<div class="col-3" >
					<?php if ( RSVP::can_have_plus_one( $member->ID ) && ! RSVP::has_plus_one( $member->ID) ) : ?>
					<button type="button" onclick="add_plus_one_form( this, <?php echo esc_attr( $member->ID ); ?>)" id="add_plus_one_<?php echo esc_attr( $member->ID ); ?>"><?php _e( 'Add +1', 'libretto-child' ); ?></button>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php foreach ( $family as $member ) : ?>
		<?php if ( ! RSVP::can_have_plus_one( $member->ID ) ) continue; ?>
	<div id="add_plus_one_form_<?php echo esc_attr( $member->ID ); ?>" class="hidden" >
		<h3>+1 For <?php echo esc_attr( $member->display_name ); ?></h3>
		<div class="float-container">
			<div class="col-3" >
				<input type="text" id="plus_one_first_name_<?php echo esc_attr( $member->ID ); ?>" name="plus_one_first_name_<?php echo esc_attr( $member->ID ); ?>" placeholder="<?php _e( 'First Name', 'libretto-child' ); ?>" />
			</div>
			<div class="col-3" >
				<input type="text" id="plus_one_last_name_<?php echo esc_attr( $member->ID ); ?>" name="plus_one_last_name_<?php echo esc_attr( $member->ID ); ?>" placeholder="<?php _e( 'Last Name', 'libretto-child' ); ?>" />
			</div>
			<div class="col-3" >
				<input type="email" id="plus_one_email_<?php echo esc_attr( $member->ID ); ?>" name="plus_one_email_<?php echo esc_attr( $member->ID ); ?>" placeholder="<?php _e( 'Email Address', 'libretto-child' ); ?>" />
			</div>
		</div>
	</div>
	<?php endforeach; ?>
	<div class="buttons">
		<button type="submit" ><?php _e( 'Submit', 'libretto-child' ); ?></button>
	</div>
</form>
