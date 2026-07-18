<?php
/**
 * Breaking-news ticker (optional).
 *
 * Shows posts flagged with the `_zoomblog_breaking` meta from the tidy post
 * sidebar. Renders nothing when there are none, so it is zero-cost by default.
 * Only appears on the front page to stay unobtrusive.
 *
 * @package ZoomBlog
 */

if ( ! is_front_page() && ! is_home() ) {
	return;
}

$zb_breaking = get_posts( array(
	'numberposts' => 6,
	'meta_key'    => '_zoomblog_breaking',
	'meta_value'  => '1',
	'post_status' => 'publish',
) );

if ( empty( $zb_breaking ) ) {
	return;
}
?>
<div class="zb-container">
	<div class="zb-breaking" role="region" aria-label="<?php esc_attr_e( 'اخبار فوری', 'zoomblog' ); ?>">
		<span class="zb-breaking__label"><?php esc_html_e( 'فوری', 'zoomblog' ); ?></span>
		<div class="zb-breaking__viewport" style="overflow:hidden;flex:1">
			<div class="zb-breaking__track">
				<?php foreach ( array_merge( $zb_breaking, $zb_breaking ) as $zb_bp ) : ?>
					<a href="<?php echo esc_url( get_permalink( $zb_bp ) ); ?>"><?php echo esc_html( get_the_title( $zb_bp ) ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>
