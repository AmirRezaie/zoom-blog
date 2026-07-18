<?php
/**
 * Site header.
 *
 * @package ZoomBlog
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php
	// Set the reading theme as early as possible to avoid a flash.
	$zb_mode = esc_js( zoomblog_get_option( 'default_mode', 'auto' ) );
	?>
	<script>
	(function () {
		try {
			var saved = localStorage.getItem('zb-theme');
			var def = '<?php echo $zb_mode; ?>';
			var t = saved || def;
			if (t === 'auto') { t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
			document.documentElement.setAttribute('data-theme', t);
		} catch (e) {}
	})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#zb-content"><?php esc_html_e( 'پرش به محتوا', 'zoomblog' ); ?></a>

<?php
// Let Elementor Pro fully own the header if a template is assigned.
if ( ! zoomblog_elementor_has_location( 'header' ) ) :
	?>
	<header class="zb-header" role="banner">
		<div class="zb-container">
			<div class="zb-header__bar">

				<div class="zb-brand">
					<?php
					if ( has_custom_logo() ) {
						the_custom_logo();
					} else {
						printf(
							'<a href="%1$s" rel="home">%2$s</a>',
							esc_url( home_url( '/' ) ),
							esc_html( get_bloginfo( 'name' ) )
						);
					}
					?>
				</div>

				<nav class="zb-header__nav" aria-label="<?php esc_attr_e( 'منوی اصلی', 'zoomblog' ); ?>">
					<?php
					if ( zoomblog_is_on( 'enable_mega_menu' ) && has_nav_menu( 'mega' ) ) {
						get_template_part( 'template-parts/mega-menu' );
					} elseif ( has_nav_menu( 'primary' ) ) {
						wp_nav_menu( array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'zb-menu',
							'depth'          => 2,
						) );
					}
					?>
				</nav>

				<div class="zb-header__tools">
					<?php if ( zoomblog_is_on( 'show_search_in_header' ) ) : ?>
						<button type="button" class="zb-iconbtn zb-search-toggle" aria-label="<?php esc_attr_e( 'جست‌وجو', 'zoomblog' ); ?>" aria-expanded="false">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
						</button>
					<?php endif; ?>

					<button type="button" class="zb-iconbtn zb-theme-toggle" aria-label="<?php esc_attr_e( 'تغییر حالت روشن/تیره', 'zoomblog' ); ?>">
						<svg class="zb-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2m-3.5-7.5-1.5 1.5m-9 9-1.5 1.5m0-12 1.5 1.5m9 9 1.5 1.5"/></svg>
						<svg class="zb-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" hidden><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
					</button>

					<button type="button" class="zb-iconbtn zb-burger" aria-label="<?php esc_attr_e( 'منو', 'zoomblog' ); ?>" aria-expanded="false" aria-controls="zb-drawer">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
					</button>
				</div>

			</div>

			<?php if ( zoomblog_is_on( 'show_search_in_header' ) ) : ?>
				<div class="zb-header__search" hidden>
					<?php get_search_form(); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php get_template_part( 'template-parts/catbar' ); ?>
		<?php get_template_part( 'template-parts/breaking' ); ?>
	</header>

	<!-- Mobile drawer -->
	<div class="zb-drawer" id="zb-drawer" hidden>
		<?php
		wp_nav_menu( array(
			'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
			'container'      => false,
			'menu_class'     => 'zb-drawer__menu',
			'depth'          => 2,
			'fallback_cb'    => false,
		) );
		?>
	</div>
	<?php
endif;

// Header ad slot.
if ( ! is_singular() ) {
	echo '<div class="zb-container">';
	zoomblog_ad_slot( 'header' );
	echo '</div>';
}
?>

<main id="zb-content" class="zb-main" role="main">
