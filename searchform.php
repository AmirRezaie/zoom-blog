<?php
/**
 * Search form (live-search enabled).
 *
 * @package ZoomBlog
 */

$zb_id = 'zb-search-' . wp_unique_id();
?>
<form role="search" method="get" class="zb-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-live-search="<?php echo zoomblog_is_on( 'live_search' ) ? '1' : '0'; ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $zb_id ); ?>"><?php esc_html_e( 'جست‌وجو', 'zoomblog' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $zb_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'جست‌وجو در سایت…', 'zoomblog' ); ?>" autocomplete="off">
	<button type="submit" class="zb-btn zb-btn--primary"><?php esc_html_e( 'جست‌وجو', 'zoomblog' ); ?></button>
	<div class="zb-live-results" hidden></div>
</form>
