<?php
/**
 * Editorial statistics dashboard.
 *
 * A quick pulse of the newsroom: totals, this month's output, top posts by
 * views and likes, and most active authors. Read-only and cheap to compute.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the submenu.
 */
function zoomblog_editorial_menu() {
	add_submenu_page(
		'zoomblog',
		__( 'آمار تحریریه', 'zoomblog' ),
		__( 'آمار تحریریه', 'zoomblog' ),
		'edit_others_posts',
		'zoomblog-editorial',
		'zoomblog_render_editorial'
	);
}
add_action( 'admin_menu', 'zoomblog_editorial_menu' );

/**
 * Render the dashboard.
 */
function zoomblog_render_editorial() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}

	$counts     = wp_count_posts( 'post' );
	$published  = (int) $counts->publish;
	$drafts     = (int) $counts->draft + (int) $counts->pending;

	$month_q = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'date_query'     => array( array( 'after' => '30 days ago' ) ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	$this_month = (int) $month_q->found_posts;

	$top_views = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 5,
		'meta_key'       => '_zoomblog_views',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	) );

	$top_likes = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 5,
		'meta_key'       => '_zoomblog_likes',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	) );

	$authors = get_users( array( 'who' => 'authors', 'number' => 8, 'fields' => array( 'ID', 'display_name' ) ) );
	?>
	<div class="wrap zb-admin">
		<h1><?php esc_html_e( 'آمار تحریریه', 'zoomblog' ); ?></h1>

		<div class="zb-stats">
			<div class="zb-stat"><span class="zb-stat__num"><?php echo esc_html( zoomblog_to_persian_digits( $published ) ); ?></span><span class="zb-stat__label"><?php esc_html_e( 'نوشتهٔ منتشرشده', 'zoomblog' ); ?></span></div>
			<div class="zb-stat"><span class="zb-stat__num"><?php echo esc_html( zoomblog_to_persian_digits( $this_month ) ); ?></span><span class="zb-stat__label"><?php esc_html_e( 'در ۳۰ روز اخیر', 'zoomblog' ); ?></span></div>
			<div class="zb-stat"><span class="zb-stat__num"><?php echo esc_html( zoomblog_to_persian_digits( $drafts ) ); ?></span><span class="zb-stat__label"><?php esc_html_e( 'پیش‌نویس/در انتظار', 'zoomblog' ); ?></span></div>
			<div class="zb-stat"><span class="zb-stat__num"><?php echo esc_html( zoomblog_to_persian_digits( count( $authors ) ) ); ?></span><span class="zb-stat__label"><?php esc_html_e( 'نویسنده', 'zoomblog' ); ?></span></div>
		</div>

		<div class="zb-cols">
			<div class="zb-col">
				<h2><?php esc_html_e( 'پربازدیدترین‌ها', 'zoomblog' ); ?></h2>
				<?php zoomblog_editorial_list( $top_views, '_zoomblog_views', __( 'بازدید', 'zoomblog' ) ); ?>
			</div>
			<div class="zb-col">
				<h2><?php esc_html_e( 'پرپسندترین‌ها', 'zoomblog' ); ?></h2>
				<?php zoomblog_editorial_list( $top_likes, '_zoomblog_likes', __( 'پسند', 'zoomblog' ) ); ?>
			</div>
		</div>

		<h2><?php esc_html_e( 'فعال‌ترین نویسندگان', 'zoomblog' ); ?></h2>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'نویسنده', 'zoomblog' ); ?></th><th><?php esc_html_e( 'تعداد نوشته', 'zoomblog' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $authors as $author ) : ?>
					<tr>
						<td><?php echo esc_html( $author->display_name ); ?></td>
						<td><?php echo esc_html( zoomblog_to_persian_digits( count_user_posts( $author->ID, 'post' ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render a small ranked list.
 *
 * @param WP_Query $query     Query.
 * @param string   $meta_key  Meta key for the value column.
 * @param string   $unit      Unit label.
 */
function zoomblog_editorial_list( $query, $meta_key, $unit ) {
	if ( ! $query->have_posts() ) {
		echo '<p>' . esc_html__( 'داده‌ای نیست.', 'zoomblog' ) . '</p>';
		return;
	}
	echo '<ol class="zb-ranklist">';
	foreach ( $query->posts as $post ) {
		$val = (int) get_post_meta( $post->ID, $meta_key, true );
		printf(
			'<li><a href="%1$s">%2$s</a> <span>%3$s %4$s</span></li>',
			esc_url( get_edit_post_link( $post->ID ) ),
			esc_html( get_the_title( $post ) ),
			esc_html( zoomblog_format_count( $val ) ),
			esc_html( $unit )
		);
	}
	echo '</ol>';
}
