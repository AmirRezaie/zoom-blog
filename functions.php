<?php
/**
 * ZoomBlog functions and definitions — bootstrap.
 *
 * This file only wires the theme together. Real logic lives in /inc.
 * Everything is namespaced with the `zoomblog_` prefix and the `zoomblog`
 * text domain.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ZOOMBLOG_VERSION', '1.0.0' );
define( 'ZOOMBLOG_DIR', get_template_directory() );
define( 'ZOOMBLOG_URI', get_template_directory_uri() );
define( 'ZOOMBLOG_INC', ZOOMBLOG_DIR . '/inc' );

/**
 * Require a theme include once, safely.
 *
 * @param string $rel Relative path inside the theme (no leading slash).
 */
function zoomblog_require( $rel ) {
	$path = ZOOMBLOG_DIR . '/' . ltrim( $rel, '/' );
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

/*
 * Core (always loaded — kept small and fast).
 */
zoomblog_require( 'inc/options.php' );        // Central settings store + defaults.
zoomblog_require( 'inc/setup.php' );          // Theme supports, menus, image sizes.
zoomblog_require( 'inc/enqueue.php' );         // Conditional asset + font loading.
zoomblog_require( 'inc/persian.php' );        // ی/ک unify, half-space, near-word.
zoomblog_require( 'inc/jalali.php' );         // Gregorian → Jalali converter.
zoomblog_require( 'inc/helpers.php' );        // Reading time, dates, small utils.
zoomblog_require( 'inc/template-tags.php' );  // Breadcrumb, meta, trust badge, cards.
zoomblog_require( 'inc/interactions.php' );   // Like / view / bookmark / follow AJAX.
zoomblog_require( 'inc/cpt.php' );            // Optional custom post types.
zoomblog_require( 'inc/seo.php' );            // Yoast / Rank Math aware SEO + schema.
zoomblog_require( 'inc/paywall.php' );        // Lightweight paywall gate.

/*
 * Editor / builder integrations.
 */
zoomblog_require( 'inc/elementor.php' );      // Elementor + Elementor Pro locations.

/*
 * Admin-only (loaded only in wp-admin).
 */
if ( is_admin() ) {
	zoomblog_require( 'inc/admin/settings-panel.php' );  // Searchable grouped settings.
	zoomblog_require( 'inc/admin/post-sidebar.php' );    // Tidy per-post sidebar.
	zoomblog_require( 'inc/admin/content-health.php' );  // Content health checker.
	zoomblog_require( 'inc/admin/editorial.php' );       // Editorial statistics dashboard.
	zoomblog_require( 'inc/admin/social-card.php' );     // Social card generator.
	zoomblog_require( 'inc/admin/setup-wizard.php' );    // 6-step install wizard.
}
