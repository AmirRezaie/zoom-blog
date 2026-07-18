<?php
/**
 * Related posts (by shared category, newest first, excluding current).
 *
 * @package ZoomBlog
 */

if ( ! zoomblog_is_on( 'show_related' ) ) {
	return;
}

$zb_count = (int) zoomblog_get_option( 'related_count', 3 );
$zb_cats  = wp_get_post_categories( get_the_ID() );

$zb_args = array(
	'post_type'           => 'post',
	'posts_per_page'      => $zb_count,
	'post__not_in'        => array( get_the_ID() ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'orderby'             => 'date',
);
if ( $zb_cats ) {
	$zb_args['category__in'] = $zb_cats;
}

$zb_related = new WP_Query( $zb_args );
if ( ! $zb_related->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="zb-related zb-section">
	<div class="zb-section__head">
		<h2 class="zb-section__title"><?php esc_html_e( 'نوشته‌های مرتبط', 'zoomblog' ); ?></h2>
	</div>
	<div class="zb-grid zb-grid--<?php echo esc_attr( min( 3, max( 2, $zb_count ) ) ); ?>">
		<?php
		while ( $zb_related->have_posts() ) :
			$zb_related->the_post();
			get_template_part( 'template-parts/card', null, array( 'excerpt' => false ) );
		endwhile;
		wp_reset_postdata();
		?>
	</div>
</section>
