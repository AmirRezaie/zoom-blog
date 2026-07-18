<?php
/**
 * Search results with filters (category · author · date) and recent searches.
 *
 * @package ZoomBlog
 */

get_header();
$zb_cols  = (int) zoomblog_get_option( 'archive_columns', 3 );
$zb_query = get_search_query();
?>
<div class="zb-container">
	<?php zoomblog_breadcrumb(); ?>

	<header class="zb-pagehead">
		<h1 class="zb-pagehead__title">
			<?php
			/* translators: %s: search query. */
			printf( esc_html__( 'نتایج جست‌وجو برای «%s»', 'zoomblog' ), '<span>' . esc_html( $zb_query ) . '</span>' );
			?>
		</h1>
		<div class="zb-pagehead__desc"><?php get_search_form(); ?></div>
	</header>

	<div class="zb-searchfilters">
		<form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="zb-searchfilters__form">
			<input type="hidden" name="s" value="<?php echo esc_attr( $zb_query ); ?>">
			<?php
			wp_dropdown_categories( array(
				'show_option_all' => __( 'همهٔ دسته‌ها', 'zoomblog' ),
				'name'            => 'cat',
				'selected'        => isset( $_GET['cat'] ) ? (int) $_GET['cat'] : 0, // phpcs:ignore WordPress.Security.NonceVerification
				'orderby'         => 'name',
				'hide_empty'      => true,
				'class'           => 'zb-input',
			) );
			wp_dropdown_users( array(
				'show_option_all' => __( 'همهٔ نویسندگان', 'zoomblog' ),
				'name'            => 'author',
				'selected'        => isset( $_GET['author'] ) ? (int) $_GET['author'] : 0, // phpcs:ignore WordPress.Security.NonceVerification
				'who'             => 'authors',
				'class'           => 'zb-input',
			) );
			?>
			<button type="submit" class="zb-btn zb-btn--primary"><?php esc_html_e( 'فیلتر', 'zoomblog' ); ?></button>
		</form>
		<div class="zb-recent-searches" data-recent-searches hidden>
			<span><?php esc_html_e( 'جست‌وجوهای اخیر:', 'zoomblog' ); ?></span>
			<div class="zb-recent-searches__list"></div>
		</div>
	</div>

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
<style>
.zb-searchfilters{ margin-bottom:24px; }
.zb-searchfilters__form{ display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
.zb-searchfilters select{ max-width:220px; }
.zb-recent-searches{ margin-top:12px; font-size:.85rem; color:var(--zb-muted); display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.zb-recent-searches__list{ display:flex; gap:6px; flex-wrap:wrap; }
</style>
<?php
get_footer();
