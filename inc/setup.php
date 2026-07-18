<?php
/**
 * Theme setup: supports, menus, image sizes, sidebars.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core theme supports.
 */
function zoomblog_setup() {
	load_theme_textdomain( 'zoomblog', ZOOMBLOG_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 240,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets',
	) );
	add_theme_support( 'custom-background' );
	add_theme_support( 'custom-header' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	// Load the local webfont + editor typography inside the block editor.
	add_editor_style( array( 'assets/css/fonts.css', 'assets/css/editor.css' ) );

	// Menus.
	register_nav_menus( array(
		'primary' => __( 'منوی اصلی', 'zoomblog' ),
		'mega'    => __( 'مگا منو', 'zoomblog' ),
		'footer'  => __( 'منوی فوتر', 'zoomblog' ),
		'mobile'  => __( 'منوی موبایل', 'zoomblog' ),
	) );

	// Image sizes tuned for cards, hero and thumbnails.
	set_post_thumbnail_size( 800, 450, true );
	add_image_size( 'zoomblog-card', 640, 360, true );
	add_image_size( 'zoomblog-card-sm', 400, 225, true );
	add_image_size( 'zoomblog-hero', 1280, 720, true );
	add_image_size( 'zoomblog-thumb', 120, 120, true );
}
add_action( 'after_setup_theme', 'zoomblog_setup' );

/**
 * Human-readable labels for the custom image sizes (media picker).
 *
 * @param array<string,string> $sizes Existing sizes.
 * @return array<string,string>
 */
function zoomblog_image_size_names( $sizes ) {
	return array_merge( $sizes, array(
		'zoomblog-hero' => __( 'زومبلاگ — هیرو', 'zoomblog' ),
		'zoomblog-card' => __( 'زومبلاگ — کارت', 'zoomblog' ),
	) );
}
add_filter( 'image_size_names_choose', 'zoomblog_image_size_names' );

/**
 * Content width for embeds / wide alignment.
 */
function zoomblog_content_width() {
	$GLOBALS['content_width'] = (int) apply_filters( 'zoomblog_content_width', 1120 );
}
add_action( 'after_setup_theme', 'zoomblog_content_width', 0 );

/**
 * Register widget areas.
 */
function zoomblog_widgets_init() {
	$defaults = array(
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	);

	register_sidebar( array_merge( $defaults, array(
		'name' => __( 'سایدبار اصلی', 'zoomblog' ),
		'id'   => 'sidebar-main',
		'description' => __( 'ستون کناری صفحهٔ نوشته و آرشیو.', 'zoomblog' ),
	) ) );

	for ( $i = 1; $i <= 4; $i++ ) {
		register_sidebar( array_merge( $defaults, array(
			/* translators: %d: footer column number. */
			'name' => sprintf( __( 'فوتر — ستون %d', 'zoomblog' ), $i ),
			'id'   => 'footer-' . $i,
		) ) );
	}
}
add_action( 'widgets_init', 'zoomblog_widgets_init' );

/**
 * Apply the configured excerpt length.
 *
 * @param int $length Default length.
 * @return int
 */
function zoomblog_excerpt_length( $length ) {
	return (int) zoomblog_get_option( 'excerpt_length', 24 );
}
add_filter( 'excerpt_length', 'zoomblog_excerpt_length' );

/**
 * Persian ellipsis for excerpts.
 *
 * @return string
 */
function zoomblog_excerpt_more() {
	return ' …';
}
add_filter( 'excerpt_more', 'zoomblog_excerpt_more' );

/**
 * Add useful body classes for theming.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function zoomblog_body_classes( $classes ) {
	$classes[] = 'zb';
	$classes[] = 'zb-mode-' . sanitize_html_class( zoomblog_get_option( 'default_mode', 'auto' ) );
	if ( zoomblog_is_on( 'sticky_header' ) ) {
		$classes[] = 'zb-sticky-header';
	}
	if ( is_singular( 'post' ) && zoomblog_is_on( 'show_reader_controls' ) ) {
		$classes[] = 'zb-has-reader';
	}
	return $classes;
}
add_filter( 'body_class', 'zoomblog_body_classes' );

/**
 * Pingback header on singular views.
 */
function zoomblog_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'zoomblog_pingback_header' );
