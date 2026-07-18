<?php
/**
 * SEO layer — cooperative with Yoast and Rank Math.
 *
 * When either plugin is active it owns titles, meta, Open Graph and schema,
 * and this file stays out of the way. Only when neither is present does the
 * theme add minimal, correct fallbacks (description, Open Graph, Article
 * JSON-LD) so a fresh install is still shareable and indexable.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is an SEO plugin managing meta already?
 *
 * @return bool
 */
function zoomblog_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || class_exists( 'WPSEO_Options' );
}

/**
 * Output minimal head meta only when no SEO plugin is present.
 */
function zoomblog_fallback_head_meta() {
	if ( zoomblog_seo_plugin_active() ) {
		return;
	}

	$desc = '';
	$img  = '';
	$type = 'website';
	$url  = home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) );

	if ( is_singular() ) {
		$post = get_queried_object();
		$type = 'article';
		$url  = get_permalink( $post );
		$desc = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '…' );
		if ( has_post_thumbnail( $post ) ) {
			$img = get_the_post_thumbnail_url( $post, 'zoomblog-hero' );
		}
	} else {
		$desc = get_bloginfo( 'description' );
	}

	$desc = trim( wp_strip_all_tags( $desc ) );

	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( wp_get_document_title() ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	if ( $desc ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	if ( $img ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $img ) );
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	} else {
		echo '<meta name="twitter:card" content="summary">' . "\n";
	}
	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( 'fa_IR' ) );
}
add_action( 'wp_head', 'zoomblog_fallback_head_meta', 5 );

/**
 * Minimal Article JSON-LD for single posts (only when no SEO plugin).
 */
function zoomblog_fallback_schema() {
	if ( zoomblog_seo_plugin_active() || ! is_singular( array( 'post', 'zb_podcast', 'zb_video', 'zb_review' ) ) ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post ) {
		return;
	}

	$data = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'Article',
		'headline'      => wp_strip_all_tags( get_the_title( $post ) ),
		'datePublished' => get_post_time( 'c', true, $post ),
		'dateModified'  => get_post_modified_time( 'c', true, $post ),
		'author'        => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', $post->post_author ),
			'url'   => get_author_posts_url( $post->post_author ),
		),
		'publisher'     => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
		),
		'mainEntityOfPage' => get_permalink( $post ),
	);
	if ( has_post_thumbnail( $post ) ) {
		$data['image'] = get_the_post_thumbnail_url( $post, 'zoomblog-hero' );
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'zoomblog_fallback_schema', 20 );
