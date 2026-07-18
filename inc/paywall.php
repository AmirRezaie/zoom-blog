<?php
/**
 * Lightweight paywall.
 *
 * A post is gated when it carries the `_zoomblog_premium` meta (checkbox in
 * the tidy post sidebar) and the global paywall option is on. Guests see the
 * first N paragraphs and a call-to-action; logged-in users with the
 * `read_premium` capability (or admins/editors) see everything.
 *
 * This is intentionally simple and membership-plugin friendly: the
 * `zoomblog_user_can_read_premium` filter lets any plugin grant access.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is the given post gated?
 *
 * @param int|WP_Post|null $post Post.
 * @return bool
 */
function zoomblog_is_premium( $post = null ) {
	if ( ! zoomblog_is_on( 'paywall_enable' ) ) {
		return false;
	}
	$post = get_post( $post );
	return $post ? (bool) get_post_meta( $post->ID, '_zoomblog_premium', true ) : false;
}

/**
 * Can the current user read premium content?
 *
 * @return bool
 */
function zoomblog_can_read_premium() {
	$can = is_user_logged_in() && ( current_user_can( 'read_premium' ) || current_user_can( 'edit_others_posts' ) );
	return (bool) apply_filters( 'zoomblog_user_can_read_premium', $can );
}

/**
 * Gate the content on single premium posts.
 *
 * @param string $content Post content.
 * @return string
 */
function zoomblog_paywall_content( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ! zoomblog_is_premium() || zoomblog_can_read_premium() ) {
		return $content;
	}

	$free = max( 1, (int) zoomblog_get_option( 'paywall_free_paras', 4 ) );
	$parts = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$out   = '';
	$paras = 0;
	if ( is_array( $parts ) ) {
		for ( $i = 0; $i < count( $parts ); $i++ ) {
			$out .= $parts[ $i ];
			if ( isset( $parts[ $i ] ) && '</p>' === strtolower( $parts[ $i ] ) ) {
				$paras++;
				if ( $paras >= $free ) {
					break;
				}
			}
		}
	} else {
		$out = wp_trim_words( $content, 80, '…' );
	}

	$msg = trim( (string) zoomblog_get_option( 'paywall_message', '' ) );
	if ( '' === $msg ) {
		$msg = __( 'ادامهٔ این نوشته ویژهٔ کاربران است.', 'zoomblog' );
	}

	$cta = '<div class="zb-paywall" role="note">';
	$cta .= '<div class="zb-paywall__fade" aria-hidden="true"></div>';
	$cta .= '<div class="zb-paywall__box">';
	$cta .= '<span class="zb-paywall__icon" aria-hidden="true">🔒</span>';
	$cta .= '<p class="zb-paywall__msg">' . esc_html( $msg ) . '</p>';
	if ( ! is_user_logged_in() ) {
		$cta .= '<a class="zb-btn zb-btn--primary" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'ورود / اشتراک', 'zoomblog' ) . '</a>';
	}
	$cta .= '</div></div>';

	return $out . $cta;
}
add_filter( 'the_content', 'zoomblog_paywall_content', 20 );

/**
 * Hide full content from feeds for premium posts.
 *
 * @param string $content Feed content.
 * @return string
 */
function zoomblog_paywall_feed( $content ) {
	if ( zoomblog_is_premium() ) {
		return wpautop( esc_html__( 'این نوشته ویژهٔ کاربران است. برای مطالعه به سایت مراجعه کنید.', 'zoomblog' ) );
	}
	return $content;
}
add_filter( 'the_content_feed', 'zoomblog_paywall_feed' );
add_filter( 'the_excerpt_rss', 'zoomblog_paywall_feed' );
