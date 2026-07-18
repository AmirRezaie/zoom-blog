<?php
/**
 * Small reusable helpers: reading time, counters, content type, excerpts.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estimate reading time in minutes for a post (Persian-aware word counting).
 *
 * @param int|WP_Post|null $post Post.
 * @return int Minutes (min 1).
 */
function zoomblog_reading_minutes( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}
	$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	// Count words by whitespace — works for Persian since words are space-separated.
	$words = preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
	$count = is_array( $words ) ? count( $words ) : 0;
	// ~220 wpm is a good Persian reading pace.
	$minutes = (int) ceil( $count / 220 );
	return max( 1, $minutes );
}

/**
 * Formatted reading time string, e.g. "۵ دقیقه مطالعه".
 *
 * @param int|WP_Post|null $post Post.
 * @return string
 */
function zoomblog_reading_time( $post = null ) {
	$m = zoomblog_reading_minutes( $post );
	/* translators: %s: number of minutes. */
	return zoomblog_to_persian_digits( sprintf( _n( '%s دقیقه مطالعه', '%s دقیقه مطالعه', $m, 'zoomblog' ), $m ) );
}

/**
 * Get and (optionally) increment the view counter for a post.
 *
 * @param int  $post_id Post ID.
 * @param bool $inc     Increment.
 * @return int
 */
function zoomblog_views( $post_id, $inc = false ) {
	$key   = '_zoomblog_views';
	$count = (int) get_post_meta( $post_id, $key, true );
	if ( $inc ) {
		$count++;
		update_post_meta( $post_id, $key, $count );
	}
	return $count;
}

/**
 * Get the like counter for a post.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function zoomblog_likes( $post_id ) {
	return (int) get_post_meta( $post_id, '_zoomblog_likes', true );
}

/**
 * Recommend percentage (positive / total votes).
 *
 * @param int $post_id Post ID.
 * @return array{percent:int,votes:int}
 */
function zoomblog_recommend( $post_id ) {
	$up   = (int) get_post_meta( $post_id, '_zoomblog_recommend_up', true );
	$down = (int) get_post_meta( $post_id, '_zoomblog_recommend_down', true );
	$total = $up + $down;
	$pct   = $total > 0 ? (int) round( ( $up / $total ) * 100 ) : 0;
	return array( 'percent' => $pct, 'votes' => $total );
}

/**
 * Human view count with Persian digits (e.g. "۱٫۲هزار").
 *
 * @param int $count Raw count.
 * @return string
 */
function zoomblog_format_count( $count ) {
	$count = (int) $count;
	if ( $count >= 1000000 ) {
		return zoomblog_to_persian_digits( round( $count / 1000000, 1 ) ) . 'م';
	}
	if ( $count >= 1000 ) {
		return zoomblog_to_persian_digits( round( $count / 1000, 1 ) ) . 'هزار';
	}
	return zoomblog_to_persian_digits( $count );
}

/**
 * Determine the editorial "content type" of a post.
 *
 * Uses a `_zoomblog_content_type` meta if set, otherwise the post type, so
 * cards and the trust layer can label news / analysis / opinion / sponsored,
 * plus podcast / video.
 *
 * @param int|WP_Post|null $post Post.
 * @return array{key:string,label:string}
 */
function zoomblog_content_type( $post = null ) {
	$post = get_post( $post );
	$types = zoomblog_content_type_choices();

	$key = $post ? get_post_meta( $post->ID, '_zoomblog_content_type', true ) : '';

	if ( ! $key && $post ) {
		if ( 'zb_podcast' === $post->post_type ) {
			$key = 'podcast';
		} elseif ( 'zb_video' === $post->post_type ) {
			$key = 'video';
		} elseif ( 'zb_review' === $post->post_type ) {
			$key = 'review';
		} else {
			$key = 'article';
		}
	}
	$key = $key ? $key : 'article';
	$label = isset( $types[ $key ] ) ? $types[ $key ] : $types['article'];
	return array( 'key' => $key, 'label' => $label );
}

/**
 * The editorial content-type vocabulary.
 *
 * @return array<string,string>
 */
function zoomblog_content_type_choices() {
	return apply_filters( 'zoomblog_content_type_choices', array(
		'article'   => __( 'مقاله', 'zoomblog' ),
		'news'      => __( 'خبر', 'zoomblog' ),
		'analysis'  => __( 'تحلیل', 'zoomblog' ),
		'opinion'   => __( 'دیدگاه', 'zoomblog' ),
		'review'    => __( 'نقد و بررسی', 'zoomblog' ),
		'sponsored' => __( 'رپورتاژ آگهی', 'zoomblog' ),
		'podcast'   => __( 'پادکست', 'zoomblog' ),
		'video'     => __( 'ویدیو', 'zoomblog' ),
	) );
}

/**
 * Is this post older than the configured threshold?
 *
 * @param int|WP_Post|null $post Post.
 * @return bool
 */
function zoomblog_is_old_post( $post = null ) {
	$months = (int) zoomblog_get_option( 'old_post_months', 18 );
	if ( $months <= 0 ) {
		return false;
	}
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	$age = time() - get_post_time( 'U', true, $post );
	return $age > ( $months * MONTH_IN_SECONDS );
}

/**
 * Safe featured image with explicit dimensions + srcset (prevents CLS).
 *
 * @param int|WP_Post|null $post Post.
 * @param string           $size Image size.
 * @param array            $attr Extra attributes.
 * @return string HTML or ''.
 */
function zoomblog_thumbnail( $post = null, $size = 'zoomblog-card', $attr = array() ) {
	$post = get_post( $post );
	if ( ! $post || ! has_post_thumbnail( $post ) ) {
		return '';
	}
	$attr = wp_parse_args( $attr, array( 'decoding' => 'async' ) );
	return get_the_post_thumbnail( $post, $size, $attr );
}

/**
 * Featured image, or a graceful gradient placeholder with the title initial.
 *
 * Keeps cards/hero from showing an empty box when a post has no thumbnail.
 * The hue is derived deterministically from the title so each post keeps a
 * stable colour.
 *
 * @param int|WP_Post|null $post Post.
 * @param string           $size Image size.
 * @param array            $attr Extra <img> attributes.
 * @return string HTML.
 */
function zoomblog_media_or_placeholder( $post = null, $size = 'zoomblog-card', $attr = array() ) {
	$thumb = zoomblog_thumbnail( $post, $size, $attr );
	if ( $thumb ) {
		return $thumb;
	}
	$post  = get_post( $post );
	$title = $post ? trim( wp_strip_all_tags( get_the_title( $post ) ) ) : '';
	$initial = '' !== $title ? mb_substr( $title, 0, 1 ) : '#';
	$hue   = '' !== $title ? ( (int) sprintf( '%u', crc32( $title ) ) % 360 ) : 210;
	return sprintf(
		'<span class="zb-placeholder" style="--zb-ph-hue:%1$d;" aria-hidden="true"><span class="zb-placeholder__letter">%2$s</span></span>',
		$hue,
		esc_html( $initial )
	);
}

/**
 * Get the primary term (Yoast/Rank Math primary aware) for a taxonomy.
 *
 * @param string           $taxonomy Taxonomy.
 * @param int|WP_Post|null $post     Post.
 * @return WP_Term|null
 */
function zoomblog_primary_term( $taxonomy = 'category', $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}

	// Yoast primary term.
	$primary_id = (int) get_post_meta( $post->ID, '_yoast_wpseo_primary_' . $taxonomy, true );
	if ( $primary_id ) {
		$term = get_term( $primary_id, $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
	}

	// Rank Math primary term.
	$rm_id = (int) get_post_meta( $post->ID, 'rank_math_primary_' . $taxonomy, true );
	if ( $rm_id ) {
		$term = get_term( $rm_id, $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
	}

	$terms = get_the_terms( $post, $taxonomy );
	if ( $terms && ! is_wp_error( $terms ) ) {
		return $terms[0];
	}
	return null;
}

/**
 * Get a post's subtitle (set in the tidy post sidebar).
 *
 * Lives here (not the admin file) because the front-end needs it.
 *
 * @param int|WP_Post|null $post Post.
 * @return string
 */
function zoomblog_get_subtitle( $post = null ) {
	$post = get_post( $post );
	return $post ? (string) get_post_meta( $post->ID, '_zoomblog_subtitle', true ) : '';
}

/**
 * Parsed sources list [ ['title'=>, 'url'=>], ... ] from the post meta.
 *
 * @param int|WP_Post|null $post Post.
 * @return array<int,array{title:string,url:string}>
 */
function zoomblog_get_sources( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$raw = (string) get_post_meta( $post->ID, '_zoomblog_sources', true );
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[] = array(
			'title' => $parts[0],
			'url'   => isset( $parts[1] ) ? esc_url_raw( $parts[1] ) : '',
		);
	}
	return $out;
}

/**
 * CSS aspect-ratio value from the card_ratio option.
 *
 * @return string
 */
function zoomblog_card_ratio_css() {
	switch ( zoomblog_get_option( 'card_ratio', '16-9' ) ) {
		case '4-3':
			return '4 / 3';
		case '1-1':
			return '1 / 1';
		default:
			return '16 / 9';
	}
}
