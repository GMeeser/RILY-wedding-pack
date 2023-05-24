<?php
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles', 1, 0 );
function my_theme_enqueue_styles() {
	$parent_handle = 'libretto-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.
	$theme         = wp_get_theme();
	wp_enqueue_style( $parent_handle,
		get_template_directory_uri() . '/style.css',
		array(),  // If the parent theme code has a dependency, copy it to here.
		$theme->parent()->get( 'Version' )
	);
	wp_enqueue_style( 'libretto-child-style',
		get_stylesheet_uri(),
		array( $parent_handle ),
		$theme->get( 'Version' ) // This only works if you have Version defined in the style header.
	);
}

add_filter('wp_is_application_passwords_available', '__return_false');

require_once 'includes/bootstrap.php';