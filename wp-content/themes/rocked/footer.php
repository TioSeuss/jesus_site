<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package Rocked
 */
?>

			</div>
		</div>
	</div>

	<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
		<?php get_sidebar('footer'); ?>
	<?php endif; ?>

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="site-info container">
			<a href="<?php echo esc_url( __( 'http://wordpress.org/', 'rocked' ) ); ?>"><?php printf( esc_html__( 'Proudly powered by %s', 'rocked' ), 'WordPress' ); ?></a>
			<span class="sep"> | </span>
			<?php printf( esc_html__( 'Theme: %2$s by %1$s.', 'rocked' ), 'aThemes', '<a href="http://athemes.com/theme/rocked" rel="designer">Rocked</a>' ); ?>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->
<a class="go-top">
	<i class="fa fa-angle-up"></i>
</a>

<?php wp_footer(); ?>

</body>
</html>
