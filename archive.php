<?php
/**
 * Generic archive (date, tag, custom taxonomy, CPT).
 *
 * @package ZoomBlog
 */

get_header();
$zb_cols = (int) zoomblog_get_option( 'archive_columns', 3 );

if ( zoomblog_elementor_has_location( 'archive' ) ) {
	get_footer();
	return;
}
?>
<div class="zb-container">
	<?php zoomblog_breadcrumb(); ?>

	<header class="zb-pagehead">
		<?php the_archive_title( '<h1 class="zb-pagehead__title">', '</h1>' ); ?>
		<?php the_archive_description( '<div class="zb-pagehead__desc">', '</div>' ); ?>
	</header>

	<div class="zb-layout">
		<div class="zb-primary">
			<?php if ( have_posts() ) : ?>
				<div class="zb-grid zb-grid--<?php echo esc_attr( $zb_cols ); ?>">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/card' );
					endwhile;
					?>
				</div>
				<?php zoomblog_pagination(); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/none' ); ?>
			<?php endif; ?>
		</div>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php
get_footer();
