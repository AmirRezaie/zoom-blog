<?php
/**
 * Professional author page.
 *
 * @package ZoomBlog
 */

get_header();
$zb_cols   = (int) zoomblog_get_option( 'archive_columns', 3 );
$zb_author = get_queried_object();
$zb_id     = (int) $zb_author->ID;

// Aggregate stats.
$zb_post_count = count_user_posts( $zb_id, 'post' );
$zb_total_views = 0;
$zb_stat_q = new WP_Query( array(
	'author'         => $zb_id,
	'posts_per_page' => 200,
	'fields'         => 'ids',
	'no_found_rows'  => true,
) );
foreach ( $zb_stat_q->posts as $zb_pid ) {
	$zb_total_views += (int) get_post_meta( $zb_pid, '_zoomblog_views', true );
}
?>
<div class="zb-container">
	<?php zoomblog_breadcrumb(); ?>

	<header class="zb-authorpage">
		<?php echo get_avatar( $zb_id, 160, '', $zb_author->display_name, array( 'class' => 'zb-authorpage__avatar' ) ); ?>
		<div class="zb-authorpage__body">
			<h1 class="zb-authorpage__name"><?php echo esc_html( $zb_author->display_name ); ?></h1>
			<?php if ( $zb_author->description ) : ?>
				<p class="zb-authorpage__bio"><?php echo esc_html( $zb_author->description ); ?></p>
			<?php endif; ?>
			<div class="zb-authorpage__stats">
				<span><b><?php echo esc_html( zoomblog_to_persian_digits( $zb_post_count ) ); ?></b> <?php esc_html_e( 'نوشته', 'zoomblog' ); ?></span>
				<span><b><?php echo esc_html( zoomblog_format_count( $zb_total_views ) ); ?></b> <?php esc_html_e( 'بازدید', 'zoomblog' ); ?></span>
			</div>
			<div class="zb-authorpage__actions">
				<?php
				zoomblog_follow_button( $zb_id );
				$zb_url = $zb_author->user_url;
				if ( $zb_url ) {
					printf( '<a class="zb-btn zb-btn--ghost" href="%s" rel="nofollow" target="_blank">%s</a>', esc_url( $zb_url ), esc_html__( 'وب‌سایت', 'zoomblog' ) );
				}
				?>
			</div>
		</div>
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
<style>
.zb-authorpage{ display:flex; gap:22px; align-items:center; padding:28px 0 22px; border-bottom:1px solid var(--zb-line); margin-bottom:28px; flex-wrap:wrap; }
.zb-authorpage__avatar{ width:120px; height:120px; border-radius:50%; }
.zb-authorpage__name{ margin:0 0 6px; }
.zb-authorpage__bio{ color:var(--zb-muted); max-width:64ch; margin:0 0 12px; }
.zb-authorpage__stats{ display:flex; gap:20px; margin-bottom:14px; color:var(--zb-muted); font-size:.9rem; }
.zb-authorpage__stats b{ color:var(--zb-ink); font-size:1.1rem; }
.zb-authorpage__actions{ display:flex; gap:10px; }
</style>
<?php
get_footer();
