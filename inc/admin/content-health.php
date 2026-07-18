<?php
/**
 * Content health tool.
 *
 * Scans published posts and flags common quality issues: missing featured
 * image, thin content, missing excerpt, no internal links, images without
 * alt text, and stale (old) posts. Gives each post a simple score so editors
 * can prioritise fixes.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the submenu.
 */
function zoomblog_content_health_menu() {
	add_submenu_page(
		'zoomblog',
		__( 'سلامت محتوا', 'zoomblog' ),
		__( 'سلامت محتوا', 'zoomblog' ),
		'edit_others_posts',
		'zoomblog-health',
		'zoomblog_render_content_health'
	);
}
add_action( 'admin_menu', 'zoomblog_content_health_menu' );

/**
 * Analyse a single post; return list of issue labels.
 *
 * @param WP_Post $post Post.
 * @return string[]
 */
function zoomblog_health_issues( $post ) {
	$issues  = array();
	$content = $post->post_content;
	$text    = wp_strip_all_tags( strip_shortcodes( $content ) );
	$words   = preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
	$wc      = is_array( $words ) ? count( $words ) : 0;

	if ( ! has_post_thumbnail( $post ) ) {
		$issues[] = __( 'بدون تصویر شاخص', 'zoomblog' );
	}
	if ( $wc < 300 ) {
		$issues[] = __( 'محتوای کوتاه', 'zoomblog' );
	}
	if ( '' === trim( $post->post_excerpt ) ) {
		$issues[] = __( 'بدون خلاصه', 'zoomblog' );
	}
	$home = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! preg_match( '#<a\s[^>]*href=["\'][^"\']*' . preg_quote( $home, '#' ) . '#i', $content )
		&& ! preg_match( '#<a\s[^>]*href=["\']/(?!/)#i', $content ) ) {
		$issues[] = __( 'بدون لینک داخلی', 'zoomblog' );
	}
	if ( preg_match_all( '/<img\b[^>]*>/i', $content, $imgs ) ) {
		foreach ( $imgs[0] as $img ) {
			if ( ! preg_match( '/\balt=["\'][^"\']+["\']/i', $img ) ) {
				$issues[] = __( 'تصویر بدون alt', 'zoomblog' );
				break;
			}
		}
	}
	if ( zoomblog_is_old_post( $post ) ) {
		$issues[] = __( 'قدیمی', 'zoomblog' );
	}
	return $issues;
}

/**
 * Render the tool.
 */
function zoomblog_render_content_health() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$q = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'no_found_rows'  => true,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	$rows    = array();
	$healthy = 0;
	foreach ( $q->posts as $post ) {
		$issues = zoomblog_health_issues( $post );
		$score  = max( 0, 100 - ( count( $issues ) * 18 ) );
		if ( empty( $issues ) ) {
			$healthy++;
		}
		$rows[] = array( 'post' => $post, 'issues' => $issues, 'score' => $score );
	}
	usort( $rows, static function ( $a, $b ) {
		return $a['score'] <=> $b['score'];
	} );
	$total = count( $rows );
	?>
	<div class="wrap zb-admin">
		<h1><?php esc_html_e( 'سلامت محتوا', 'zoomblog' ); ?></h1>
		<p class="zb-health__summary">
			<?php
			printf(
				/* translators: 1: healthy count, 2: total. */
				esc_html__( '%1$s نوشته از %2$s نوشتهٔ بررسی‌شده بدون مشکل است.', 'zoomblog' ),
				esc_html( zoomblog_to_persian_digits( $healthy ) ),
				esc_html( zoomblog_to_persian_digits( $total ) )
			);
			?>
		</p>
		<table class="widefat striped zb-health__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'عنوان', 'zoomblog' ); ?></th>
					<th><?php esc_html_e( 'امتیاز', 'zoomblog' ); ?></th>
					<th><?php esc_html_e( 'مشکلات', 'zoomblog' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><strong><?php echo esc_html( get_the_title( $row['post'] ) ); ?></strong></td>
						<td>
							<span class="zb-score zb-score--<?php echo $row['score'] >= 82 ? 'good' : ( $row['score'] >= 50 ? 'ok' : 'bad' ); ?>">
								<?php echo esc_html( zoomblog_to_persian_digits( $row['score'] ) ); ?>
							</span>
						</td>
						<td>
							<?php if ( empty( $row['issues'] ) ) : ?>
								<span class="zb-ok">✓ <?php esc_html_e( 'سالم', 'zoomblog' ); ?></span>
							<?php else : ?>
								<?php foreach ( $row['issues'] as $issue ) : ?>
									<span class="zb-chip"><?php echo esc_html( $issue ); ?></span>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
						<td><a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $row['post']->ID ) ); ?>"><?php esc_html_e( 'ویرایش', 'zoomblog' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
