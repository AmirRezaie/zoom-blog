<?php
/**
 * Site footer.
 *
 * @package ZoomBlog
 */

?>
</main><!-- #zb-content -->

<?php
if ( ! zoomblog_elementor_has_location( 'footer' ) ) :
	$zb_copyright = trim( (string) zoomblog_get_option( 'footer_copyright', '' ) );
	?>
	<footer class="zb-footer" role="contentinfo">
		<div class="zb-container">
			<?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) || is_active_sidebar( 'footer-4' ) ) : ?>
				<div class="zb-footer__cols">
					<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
						<div class="zb-footer__col">
							<?php dynamic_sidebar( 'footer-' . $i ); ?>
						</div>
					<?php endfor; ?>
				</div>
			<?php endif; ?>

			<div class="zb-footer__bottom">
				<?php
				if ( '' !== $zb_copyright ) {
					echo esc_html( $zb_copyright );
				} else {
					printf(
						/* translators: 1: year, 2: site name. */
						esc_html__( '© %1$s — تمام حقوق برای %2$s محفوظ است.', 'zoomblog' ),
						esc_html( zoomblog_to_persian_digits( wp_date( 'Y' ) ) ),
						esc_html( get_bloginfo( 'name' ) )
					);
				}
				?>
				<?php if ( zoomblog_is_on( 'show_footer_menu' ) && has_nav_menu( 'footer' ) ) : ?>
					<nav class="zb-footer__menu" aria-label="<?php esc_attr_e( 'منوی فوتر', 'zoomblog' ); ?>">
						<?php
						wp_nav_menu( array(
							'theme_location' => 'footer',
							'container'      => false,
							'menu_class'     => 'zb-menu',
							'depth'          => 1,
							'fallback_cb'    => false,
						) );
						?>
					</nav>
				<?php endif; ?>
			</div>
		</div>
	</footer>
	<?php
endif;

wp_footer();
?>
</body>
</html>
