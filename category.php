<?php
/**
 * Category archive — with a hero heading and sibling/child category tabs.
 *
 * @package ZoomBlog
 */

get_header();
$zb_cols = (int) zoomblog_get_option( 'archive_columns', 3 );
$zb_cat  = get_queried_object();

if ( zoomblog_elementor_has_location( 'archive' ) ) {
	get_footer();
	return;
}

// Tabs: child categories, else siblings.
$zb_children = get_terms( array( 'taxonomy' => 'category', 'parent' => $zb_cat->term_id, 'hide_empty' => true ) );
if ( empty( $zb_children ) || is_wp_error( $zb_children ) ) {
	$zb_children = get_terms( array( 'taxonomy' => 'category', 'parent' => (int) $zb_cat->parent, 'hide_empty' => true, 'exclude' => array( $zb_cat->term_id ) ) );
}
?>
<div class="zb-container">
	<?php zoomblog_breadcrumb(); ?>

	<header class="zb-pagehead">
		<h1 class="zb-pagehead__title"><?php single_cat_title(); ?></h1>
		<?php if ( category_description() ) : ?>
			<div class="zb-pagehead__desc"><?php echo wp_kses_post( category_description() ); ?></div>
		<?php endif; ?>
	</header>

	<?php if ( ! empty( $zb_children ) && ! is_wp_error( $zb_children ) ) : ?>
		<nav class="zb-tabs" aria-label="<?php esc_attr_e( 'زیرشاخه‌ها', 'zoomblog' ); ?>">
			<?php foreach ( $zb_children as $zb_child ) : ?>
				<a class="zb-tab" href="<?php echo esc_url( get_term_link( $zb_child ) ); ?>"><?php echo esc_html( $zb_child->name ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

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
