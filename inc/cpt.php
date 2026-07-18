<?php
/**
 * Optional custom post types: Podcast, Video, Review.
 *
 * Each is registered only when its option is enabled, so a plain blog stays
 * lean. All share the `category` and `post_tag` taxonomies with posts so
 * archives and the personalized feed work uniformly.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the enabled CPTs.
 */
function zoomblog_register_cpts() {
	$common = array(
		'public'       => true,
		'has_archive'  => true,
		'show_in_rest' => true,
		'menu_position'=> 6,
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments', 'custom-fields' ),
		'taxonomies'   => array( 'category', 'post_tag' ),
		'rewrite'      => array( 'with_front' => false ),
	);

	if ( zoomblog_is_on( 'cpt_podcast' ) ) {
		register_post_type( 'zb_podcast', array_merge( $common, array(
			'labels'    => zoomblog_cpt_labels( 'پادکست', 'پادکست‌ها' ),
			'menu_icon' => 'dashicons-microphone',
			'rewrite'   => array( 'slug' => 'podcast', 'with_front' => false ),
		) ) );
	}

	if ( zoomblog_is_on( 'cpt_video' ) ) {
		register_post_type( 'zb_video', array_merge( $common, array(
			'labels'    => zoomblog_cpt_labels( 'ویدیو', 'ویدیوها' ),
			'menu_icon' => 'dashicons-video-alt3',
			'rewrite'   => array( 'slug' => 'video', 'with_front' => false ),
		) ) );
	}

	if ( zoomblog_is_on( 'cpt_review' ) ) {
		register_post_type( 'zb_review', array_merge( $common, array(
			'labels'    => zoomblog_cpt_labels( 'نقد و بررسی', 'نقد و بررسی‌ها' ),
			'menu_icon' => 'dashicons-star-half',
			'rewrite'   => array( 'slug' => 'review', 'with_front' => false ),
		) ) );
	}
}
add_action( 'init', 'zoomblog_register_cpts' );

/**
 * Build a labels array for a CPT.
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return array<string,string>
 */
function zoomblog_cpt_labels( $singular, $plural ) {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		/* translators: %s: singular label. */
		'add_new_item'       => sprintf( __( 'افزودن %s', 'zoomblog' ), $singular ),
		/* translators: %s: singular label. */
		'edit_item'          => sprintf( __( 'ویرایش %s', 'zoomblog' ), $singular ),
		'all_items'          => $plural,
		'search_items'       => __( 'جست‌وجو', 'zoomblog' ),
		'not_found'          => __( 'موردی یافت نشد.', 'zoomblog' ),
	);
}

/**
 * Include enabled CPTs in the main blog/archive/feed queries.
 *
 * @param WP_Query $query Query.
 */
function zoomblog_cpt_in_queries( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_home() || $query->is_feed() || ( $query->is_category() && ! $query->is_post_type_archive() ) ) {
		$types = array( 'post' );
		foreach ( array( 'podcast', 'video', 'review' ) as $t ) {
			if ( zoomblog_is_on( 'cpt_' . $t ) ) {
				$types[] = 'zb_' . $t;
			}
		}
		if ( count( $types ) > 1 ) {
			$query->set( 'post_type', $types );
		}
	}
}
add_action( 'pre_get_posts', 'zoomblog_cpt_in_queries' );

/**
 * Flush rewrite rules once after a CPT option changes.
 */
function zoomblog_maybe_flush_rewrites() {
	if ( get_option( 'zoomblog_flush_rewrites' ) ) {
		flush_rewrite_rules();
		delete_option( 'zoomblog_flush_rewrites' );
	}
}
add_action( 'init', 'zoomblog_maybe_flush_rewrites', 99 );
