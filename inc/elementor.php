<?php
/**
 * Elementor & Elementor Pro compatibility.
 *
 * - Declares theme support so Pro's Theme Builder can fully take over
 *   header/footer/single/archive when the user wants it.
 * - Registers a "ZoomBlog" widget category.
 * - Adds a content-width hint and locations support.
 *
 * The theme's own PHP templates remain the default; Elementor overrides them
 * only where the user builds a template, so both worlds coexist.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Elementor Pro theme locations (header, footer, single, archive).
 *
 * @param object $manager Elementor locations manager.
 */
function zoomblog_elementor_locations( $manager ) {
	if ( is_object( $manager ) && method_exists( $manager, 'register_all_core_location' ) ) {
		$manager->register_all_core_location();
	}
}
add_action( 'elementor/theme/register_locations', 'zoomblog_elementor_locations' );

/**
 * Add a dedicated widget category for future ZoomBlog widgets.
 *
 * @param object $elements_manager Elementor elements manager.
 */
function zoomblog_elementor_category( $elements_manager ) {
	if ( is_object( $elements_manager ) && method_exists( $elements_manager, 'add_category' ) ) {
		$elements_manager->add_category( 'zoomblog', array(
			'title' => __( 'زومبلاگ', 'zoomblog' ),
			'icon'  => 'fa fa-plug',
		) );
	}
}
add_action( 'elementor/elements/categories_registered', 'zoomblog_elementor_category' );

/**
 * Tell Elementor the theme handles its own content wrapper so full-width
 * canvas templates line up with our container.
 */
function zoomblog_elementor_theme_support() {
	// Only meaningful when Elementor is active.
	if ( did_action( 'elementor/loaded' ) ) {
		add_theme_support( 'elementor' );
	}
}
add_action( 'after_setup_theme', 'zoomblog_elementor_theme_support', 20 );

/**
 * Does an Elementor Pro template take over this location?
 *
 * Templates use this to skip their own header/footer/content when the Theme
 * Builder is driving that location.
 *
 * @param string $location Location slug.
 * @return bool
 */
function zoomblog_elementor_has_location( $location ) {
	if ( function_exists( 'elementor_theme_do_location' ) ) {
		// elementor_theme_do_location echoes + returns true if it rendered.
		return elementor_theme_do_location( $location );
	}
	return false;
}
