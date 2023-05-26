<?php
$message = $args['message'];
if ( 1 == $message ) {
	$message = __( 'RSVP submitted successfully.', 'libretto-child' );
	$prompt  = __( 'To login please reset your password <a href="/my-account/lost-password"/>here</a>', 'libretto-child' );
} else {
	$prompt  = __( 'Please try again', 'libretto-child' );
}
?>
<div>
	<h2><?php echo esc_attr( $message ); ?> </h2>
	<p><?php echo $prompt; ?></p>
</div>
