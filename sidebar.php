<?php
/**
 * Main sidebar.
 *
 * @package ZoomBlog
 */

if ( ! is_active_sidebar( 'sidebar-main' ) && ! zoomblog_get_option( 'ad_sidebar' ) ) {
	return;
}
?>
<aside class="zb-sidebar" role="complementary">
	<?php
	dynamic_sidebar( 'sidebar-main' );
	zoomblog_ad_slot( 'sidebar' );
	?>
</aside>
