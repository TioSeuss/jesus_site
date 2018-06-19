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
			<p>
				<?php _e('© 2018 Valyrian Coffee, All Rights Reserved'); ?>
			</p>
		</div> <!--.site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->
<a class="go-top">
	<i class="fa fa-angle-up"></i>
</a>

<?php wp_footer(); ?>

</body>
</html>
