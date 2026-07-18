<?php
/**
 * Asset loading — performance first.
 *
 * Principles:
 *   - Split CSS into components; load a component only where it is used.
 *   - No jQuery on the front-end unless a plugin needs it.
 *   - Local Vazirmatn with font-display:swap and a single controlled preload.
 *   - Defer theme scripts; nothing render-blocking beyond critical CSS.
 *   - Cache/CDN friendly: versioned handles, no per-request inline nonces in
 *     cacheable markup (AJAX nonce is added only when an interaction runs).
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue front-end styles conditionally.
 */
function zoomblog_enqueue_styles() {
	$v   = ZOOMBLOG_VERSION;
	$css = ZOOMBLOG_URI . '/assets/css';

	// Theme stylesheet header (kept tiny).
	wp_enqueue_style( 'zoomblog-style', get_stylesheet_uri(), array(), $v );

	// Fonts + base tokens/layout — always.
	wp_enqueue_style( 'zoomblog-fonts', $css . '/fonts.css', array(), $v );
	wp_enqueue_style( 'zoomblog-base', $css . '/base.css', array( 'zoomblog-fonts' ), $v );

	// Dynamic tokens from options (small, inline).
	wp_add_inline_style( 'zoomblog-base', zoomblog_dynamic_css() );

	// Cards — anywhere a post list appears.
	if ( is_home() || is_front_page() || is_archive() || is_search() || is_page_template( 'template-full-width.php' ) ) {
		wp_enqueue_style( 'zoomblog-cards', $css . '/cards.css', array( 'zoomblog-base' ), $v );
	}

	// Single post styles + reader.
	if ( is_singular() ) {
		wp_enqueue_style( 'zoomblog-single', $css . '/single.css', array( 'zoomblog-base' ), $v );
		if ( is_singular( 'post' ) && zoomblog_is_on( 'show_reader_controls' ) ) {
			wp_enqueue_style( 'zoomblog-reader', $css . '/reader.css', array( 'zoomblog-single' ), $v );
		}
	}

	// Mega menu styles only when enabled and a menu is assigned.
	if ( zoomblog_is_on( 'enable_mega_menu' ) && has_nav_menu( 'mega' ) ) {
		wp_enqueue_style( 'zoomblog-mega', $css . '/mega-menu.css', array( 'zoomblog-base' ), $v );
	}
}
add_action( 'wp_enqueue_scripts', 'zoomblog_enqueue_styles' );

/**
 * Enqueue front-end scripts conditionally. No jQuery dependency.
 */
function zoomblog_enqueue_scripts() {
	$v  = ZOOMBLOG_VERSION;
	$js = ZOOMBLOG_URI . '/assets/js';

	// Core: theme switch, sticky header, mobile menu, progress bar. ~small.
	wp_enqueue_script( 'zoomblog-theme', $js . '/theme.js', array(), $v, true );

	// Reader controls + TOC on single posts.
	if ( is_singular( 'post' ) ) {
		if ( zoomblog_is_on( 'show_reader_controls' ) || zoomblog_is_on( 'show_toc' ) ) {
			wp_enqueue_script( 'zoomblog-reader', $js . '/reader.js', array(), $v, true );
		}
	}

	// Interactions (like/bookmark/history/follow/recommend) — only if any on.
	$interactions_on = zoomblog_is_on( 'enable_like' ) || zoomblog_is_on( 'enable_bookmark' )
		|| zoomblog_is_on( 'enable_history' ) || zoomblog_is_on( 'enable_follow' )
		|| zoomblog_is_on( 'enable_recommend' );
	if ( $interactions_on ) {
		wp_enqueue_script( 'zoomblog-interactions', $js . '/interactions.js', array(), $v, true );
		wp_localize_script( 'zoomblog-interactions', 'zoomblogData', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'zoomblog_nonce' ),
			'restBase' => esc_url_raw( rest_url() ),
			'i18n'     => array(
				'saved'    => __( 'ذخیره شد', 'zoomblog' ),
				'save'     => __( 'ذخیره', 'zoomblog' ),
				'liked'    => __( 'پسندیدید', 'zoomblog' ),
				'like'     => __( 'پسندیدن', 'zoomblog' ),
				'follow'   => __( 'دنبال‌کردن', 'zoomblog' ),
				'following'=> __( 'دنبال می‌کنید', 'zoomblog' ),
				'empty'    => __( 'موردی نیست.', 'zoomblog' ),
			),
			'features' => array(
				'like'      => zoomblog_is_on( 'enable_like' ),
				'bookmark'  => zoomblog_is_on( 'enable_bookmark' ),
				'history'   => zoomblog_is_on( 'enable_history' ),
				'follow'    => zoomblog_is_on( 'enable_follow' ),
				'recommend' => zoomblog_is_on( 'enable_recommend' ),
			),
		) );
	}

	// Live search — only when enabled and a search UI is present on the page.
	if ( zoomblog_is_on( 'live_search' ) ) {
		wp_enqueue_script( 'zoomblog-search', $js . '/live-search.js', array(), $v, true );
		wp_localize_script( 'zoomblog-search', 'zoomblogSearch', array(
			'restUrl' => esc_url_raw( rest_url( 'zoomblog/v1/search' ) ),
			'minChars'=> (int) zoomblog_get_option( 'live_search_min', 2 ),
			'i18n'    => array(
				'noResults' => __( 'نتیجه‌ای یافت نشد', 'zoomblog' ),
				'searching' => __( 'در حال جست‌وجو…', 'zoomblog' ),
			),
		) );
	}

	// Threaded comments only where comments are shown.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'zoomblog_enqueue_scripts' );

/**
 * Build the dynamic CSS variables block from options.
 *
 * @return string
 */
function zoomblog_dynamic_css() {
	$accent   = sanitize_hex_color( zoomblog_get_option( 'accent_color', '#2f6bff' ) );
	$accent2  = sanitize_hex_color( zoomblog_get_option( 'accent_color_2', '#ff4d6d' ) );
	$width    = (int) zoomblog_get_option( 'container_width', 1200 );
	$fontsize = (int) zoomblog_get_option( 'base_font_size', 17 );
	$lh       = (float) zoomblog_get_option( 'base_line_height', 1.85 );
	$logoH    = (int) zoomblog_get_option( 'logo_max_height', 40 );

	$css  = ':root{';
	$css .= '--zb-accent:' . $accent . ';';
	$css .= '--zb-accent-2:' . $accent2 . ';';
	$css .= '--zb-container:' . $width . 'px;';
	$css .= '--zb-font-size:' . $fontsize . 'px;';
	$css .= '--zb-line-height:' . $lh . ';';
	$css .= '--zb-logo-height:' . $logoH . 'px;';
	$css .= '}';
	return $css;
}

/**
 * Preload the primary font (controlled) and add resource hints.
 */
function zoomblog_head_preloads() {
	if ( 'vazirmatn' === zoomblog_get_option( 'font_family', 'vazirmatn' ) && zoomblog_is_on( 'preload_font' ) ) {
		$font = ZOOMBLOG_URI . '/assets/fonts/vazirmatn/Vazirmatn-Variable.woff2';
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( $font )
		);
	}
}
add_action( 'wp_head', 'zoomblog_head_preloads', 1 );

/**
 * Give the hero/featured image high fetch priority and eager loading, while
 * everything below the fold keeps WordPress' native lazy loading.
 *
 * @param string $html    The post thumbnail HTML.
 * @param int    $post_id Post ID.
 * @param int    $thumb_id Thumbnail attachment ID.
 * @param string $size    Requested size.
 * @return string
 */
function zoomblog_hero_fetchpriority( $html, $post_id, $thumb_id, $size ) {
	if ( in_array( $size, array( 'zoomblog-hero', 'post-thumbnail' ), true ) && ( is_singular() || is_front_page() ) && is_main_query() ) {
		if ( false === strpos( $html, 'fetchpriority' ) ) {
			$html = str_replace( '<img ', '<img fetchpriority="high" ', $html );
			$html = str_replace( ' loading="lazy"', '', $html );
		}
	}
	return $html;
}
add_filter( 'post_thumbnail_html', 'zoomblog_hero_fetchpriority', 10, 4 );

/**
 * Defer theme scripts (keeps HTML non-blocking).
 *
 * @param string $tag    Script tag.
 * @param string $handle Handle.
 * @return string
 */
function zoomblog_defer_scripts( $tag, $handle ) {
	if ( ! zoomblog_is_on( 'defer_scripts' ) ) {
		return $tag;
	}
	$deferred = array( 'zoomblog-theme', 'zoomblog-reader', 'zoomblog-interactions', 'zoomblog-search' );
	if ( in_array( $handle, $deferred, true ) && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'zoomblog_defer_scripts', 10, 2 );

/**
 * Drop jQuery from the front-end when unused.
 */
function zoomblog_maybe_dequeue_jquery() {
	if ( is_admin() || ! zoomblog_is_on( 'remove_jquery' ) ) {
		return;
	}
	if ( apply_filters( 'zoomblog_keep_jquery', false ) ) {
		return;
	}
	// Only remove if nothing else declared a dependency on it.
	wp_dequeue_script( 'jquery' );
}
add_action( 'wp_enqueue_scripts', 'zoomblog_maybe_dequeue_jquery', 100 );

/**
 * Trim WordPress head bloat per options.
 */
function zoomblog_clean_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );

	if ( zoomblog_is_on( 'disable_emoji' ) ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		add_filter( 'tiny_mce_plugins', function ( $plugins ) {
			return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
		} );
	}

	if ( zoomblog_is_on( 'disable_embeds' ) ) {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	}
}
add_action( 'init', 'zoomblog_clean_head' );

/**
 * Allow WebP (and AVIF) uploads for responsive images.
 *
 * @param array<string,string> $mimes Allowed mimes.
 * @return array<string,string>
 */
function zoomblog_allow_modern_image_mimes( $mimes ) {
	$mimes['webp'] = 'image/webp';
	$mimes['avif'] = 'image/avif';
	return $mimes;
}
add_filter( 'upload_mimes', 'zoomblog_allow_modern_image_mimes' );

/**
 * Admin styles for the settings panel + editorial screens.
 *
 * @param string $hook Current admin page.
 */
function zoomblog_admin_assets( $hook ) {
	// Load only on our screens and the post editor.
	$is_ours = ( false !== strpos( $hook, 'zoomblog' ) );
	$is_post = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
	if ( ! $is_ours && ! $is_post ) {
		return;
	}
	wp_enqueue_style( 'zoomblog-admin', ZOOMBLOG_URI . '/assets/css/admin.css', array(), ZOOMBLOG_VERSION );
	if ( $is_ours ) {
		wp_enqueue_script( 'zoomblog-admin', ZOOMBLOG_URI . '/assets/js/admin.js', array( 'wp-color-picker' ), ZOOMBLOG_VERSION, true );
		wp_enqueue_style( 'wp-color-picker' );
	}
}
add_action( 'admin_enqueue_scripts', 'zoomblog_admin_assets' );
