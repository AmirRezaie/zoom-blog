<?php
/**
 * Category bar — a horizontal strip of top categories under the header,
 * Zoomit-style. Falls back to nothing when there are no populated categories.
 *
 * @package ZoomBlog
 */

$zb_cats = get_categories( array(
	'number'     => 12,
	'orderby'    => 'count',
	'order'      => 'DESC',
	'hide_empty' => true,
) );

if ( empty( $zb_cats ) ) {
	return;
}
?>
<div class="zb-catbar">
	<div class="zb-container">
		<nav class="zb-catbar__nav" aria-label="<?php esc_attr_e( 'دسته‌بندی‌ها', 'zoomblog' ); ?>">
			<a class="zb-catbar__home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'صفحهٔ اصلی', 'zoomblog' ); ?></a>
			<?php foreach ( $zb_cats as $zb_c ) : ?>
				<a href="<?php echo esc_url( get_category_link( $zb_c ) ); ?>"><?php echo esc_html( $zb_c->name ); ?></a>
			<?php endforeach; ?>
		</nav>
	</div>
</div>
