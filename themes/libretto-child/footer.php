<?php
/**
 * The template for displaying the footer.
 *
 * @package Libretto
 */
?>

		<footer id="colophon" class="site-footer" role="contentinfo">

			<div class="site-info">
				<?php printf( esc_html__( 'Designed by %s.', 'libretto-child' ), 'Jessica Meeser' ); ?>
				|
				<?php printf( esc_html__( 'Built By by %s.', 'libretto-child' ), 'Grant Meeser & Richard Klement' ); ?>
			</div><!-- .site-info -->

			<?php
			// Prepare social media nav
			if ( has_nav_menu( 'social' ) ) : ?>
				<div id="social">
					<?php wp_nav_menu( array(
						'theme_location' => 'social',
					 	'link_before'    => '<span class="screen-reader-text">',
						'link_after'     => '</span>',
					 	'fallback_cb'    => false,
					 	'depth'          => 1,
					) );
				 	?>
				</div><!-- #social -->
			<?php endif; ?>

		</footer><!-- #colophon -->

		<?php wp_footer(); ?>

	</body>
</html>
