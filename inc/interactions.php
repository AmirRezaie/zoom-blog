<?php
/**
 * Reader interactions: like, view count, recommend, follow, and the
 * live-search REST endpoint. Bookmarks & reading history are primarily
 * client-side (localStorage); logged-in users also get server sync.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Increment the view counter once per visitor per post (cookie de-dupe).
 */
function zoomblog_count_view() {
	if ( is_admin() || ! is_singular( array( 'post', 'zb_podcast', 'zb_video', 'zb_review' ) ) || ! is_main_query() ) {
		return;
	}
	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}
	$cookie = 'zb_viewed_' . $post_id;
	if ( isset( $_COOKIE[ $cookie ] ) ) {
		return;
	}
	zoomblog_views( $post_id, true );
	if ( ! headers_sent() ) {
		setcookie( $cookie, '1', time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
	}
}
add_action( 'wp', 'zoomblog_count_view' );

/**
 * Verify the AJAX nonce, or die with an error.
 */
function zoomblog_verify_ajax() {
	if ( ! check_ajax_referer( 'zoomblog_nonce', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'درخواست نامعتبر است.', 'zoomblog' ) ), 400 );
	}
}

/**
 * AJAX: like / unlike a post.
 */
function zoomblog_ajax_like() {
	zoomblog_verify_ajax();
	if ( ! zoomblog_is_on( 'enable_like' ) ) {
		wp_send_json_error( array(), 403 );
	}
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$dir     = isset( $_POST['dir'] ) && 'down' === $_POST['dir'] ? -1 : 1;
	if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) {
		wp_send_json_error( array(), 404 );
	}
	$count = max( 0, zoomblog_likes( $post_id ) + $dir );
	update_post_meta( $post_id, '_zoomblog_likes', $count );
	wp_send_json_success( array(
		'count'     => $count,
		'formatted' => zoomblog_format_count( $count ),
	) );
}
add_action( 'wp_ajax_zoomblog_like', 'zoomblog_ajax_like' );
add_action( 'wp_ajax_nopriv_zoomblog_like', 'zoomblog_ajax_like' );

/**
 * AJAX: recommend vote (cookie de-dupe per post).
 */
function zoomblog_ajax_recommend() {
	zoomblog_verify_ajax();
	if ( ! zoomblog_is_on( 'enable_recommend' ) ) {
		wp_send_json_error( array(), 403 );
	}
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$vote    = isset( $_POST['vote'] ) && 'down' === $_POST['vote'] ? 'down' : 'up';
	if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) {
		wp_send_json_error( array(), 404 );
	}
	$cookie = 'zb_rec_' . $post_id;
	if ( isset( $_COOKIE[ $cookie ] ) ) {
		wp_send_json_error( array( 'message' => __( 'رأی شما ثبت شده است.', 'zoomblog' ) ), 409 );
	}
	$meta = 'up' === $vote ? '_zoomblog_recommend_up' : '_zoomblog_recommend_down';
	update_post_meta( $post_id, $meta, (int) get_post_meta( $post_id, $meta, true ) + 1 );
	if ( ! headers_sent() ) {
		setcookie( $cookie, $vote, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
	}
	wp_send_json_success( zoomblog_recommend( $post_id ) );
}
add_action( 'wp_ajax_zoomblog_recommend', 'zoomblog_ajax_recommend' );
add_action( 'wp_ajax_nopriv_zoomblog_recommend', 'zoomblog_ajax_recommend' );

/**
 * AJAX: follow / unfollow an author (logged-in users persisted to user meta).
 */
function zoomblog_ajax_follow() {
	zoomblog_verify_ajax();
	if ( ! zoomblog_is_on( 'enable_follow' ) ) {
		wp_send_json_error( array(), 403 );
	}
	$author_id = isset( $_POST['author_id'] ) ? absint( $_POST['author_id'] ) : 0;
	if ( ! $author_id || ! get_user_by( 'id', $author_id ) ) {
		wp_send_json_error( array(), 404 );
	}
	if ( ! is_user_logged_in() ) {
		// Guests keep follow state client-side; report ok so the UI toggles.
		wp_send_json_success( array( 'guest' => true ) );
	}
	$uid  = get_current_user_id();
	$list = (array) get_user_meta( $uid, '_zoomblog_following', true );
	if ( in_array( $author_id, $list, true ) ) {
		$list = array_diff( $list, array( $author_id ) );
		$state = 'unfollowed';
	} else {
		$list[] = $author_id;
		$state = 'followed';
	}
	update_user_meta( $uid, '_zoomblog_following', array_values( array_unique( array_map( 'intval', $list ) ) ) );
	wp_send_json_success( array( 'state' => $state ) );
}
add_action( 'wp_ajax_zoomblog_follow', 'zoomblog_ajax_follow' );
add_action( 'wp_ajax_nopriv_zoomblog_follow', 'zoomblog_ajax_follow' );

/**
 * AJAX: render a list of posts by IDs (used by bookmarks / history panels).
 */
function zoomblog_ajax_render_list() {
	zoomblog_verify_ajax();
	$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) json_decode( wp_unslash( $_POST['ids'] ), true ) ) : array();
	$ids = array_filter( $ids );
	if ( empty( $ids ) ) {
		wp_send_json_success( array( 'html' => '<p class="zb-empty">' . esc_html__( 'موردی نیست.', 'zoomblog' ) . '</p>' ) );
	}
	$q = new WP_Query( array(
		'post_type'           => 'any',
		'post_status'         => 'publish',
		'post__in'            => array_slice( $ids, 0, 30 ),
		'orderby'             => 'post__in',
		'posts_per_page'      => 30,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	ob_start();
	if ( $q->have_posts() ) {
		echo '<ul class="zb-minilist">';
		while ( $q->have_posts() ) {
			$q->the_post();
			echo '<li class="zb-minilist__item">';
			echo '<a href="' . esc_url( get_permalink() ) . '">';
			$thumb = zoomblog_thumbnail( null, 'zoomblog-thumb' );
			if ( $thumb ) {
				echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '<span class="zb-minilist__title">' . esc_html( get_the_title() ) . '</span>';
			echo '</a>';
			echo '<button type="button" class="zb-minilist__remove" data-post-id="' . get_the_ID() . '" aria-label="' . esc_attr__( 'حذف', 'zoomblog' ) . '">×</button>';
			echo '</li>';
		}
		echo '</ul>';
		wp_reset_postdata();
	} else {
		echo '<p class="zb-empty">' . esc_html__( 'موردی نیست.', 'zoomblog' ) . '</p>';
	}
	wp_send_json_success( array( 'html' => ob_get_clean() ) );
}
add_action( 'wp_ajax_zoomblog_render_list', 'zoomblog_ajax_render_list' );
add_action( 'wp_ajax_nopriv_zoomblog_render_list', 'zoomblog_ajax_render_list' );

/**
 * Register the live-search REST route.
 */
function zoomblog_register_rest() {
	register_rest_route( 'zoomblog/v1', '/search', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => 'zoomblog_rest_search',
		'args'                => array(
			'q'        => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'cat'      => array( 'sanitize_callback' => 'absint' ),
			'author'   => array( 'sanitize_callback' => 'absint' ),
		),
	) );
}
add_action( 'rest_api_init', 'zoomblog_register_rest' );

/**
 * REST: live search results (title, url, thumb, category, type).
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function zoomblog_rest_search( $req ) {
	$term = trim( (string) $req->get_param( 'q' ) );
	if ( mb_strlen( $term ) < (int) zoomblog_get_option( 'live_search_min', 2 ) ) {
		return rest_ensure_response( array( 'results' => array() ) );
	}

	$args = array(
		's'                   => $term,
		'post_type'           => array( 'post' ),
		'post_status'         => 'publish',
		'posts_per_page'      => 7,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	);
	$cat = (int) $req->get_param( 'cat' );
	if ( $cat ) {
		$args['cat'] = $cat;
	}
	$author = (int) $req->get_param( 'author' );
	if ( $author ) {
		$args['author'] = $author;
	}

	$q       = new WP_Query( $args );
	$results = array();
	foreach ( $q->posts as $post ) {
		$type = zoomblog_content_type( $post );
		$cat_term = zoomblog_primary_term( 'category', $post );
		$results[] = array(
			'title'    => get_the_title( $post ),
			'url'      => get_permalink( $post ),
			'thumb'    => get_the_post_thumbnail_url( $post, 'zoomblog-thumb' ),
			'category' => $cat_term ? $cat_term->name : '',
			'type'     => $type['label'],
			'date'     => zoomblog_jalali_format( 'j F Y', get_post_time( 'U', true, $post ) ),
		);
	}
	return rest_ensure_response( array( 'results' => $results ) );
}
