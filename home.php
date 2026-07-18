<?php
/**
 * Blog home / front page (magazine layout).
 *
 * Sections: hero → editor's picks → latest (+ sticky sidebar with trending &
 * newsletter) → topic sections. Ads reserve space to avoid layout shift.
 *
 * @package ZoomBlog
 */

get_header();
$GLOBALS['zoomblog_shown'] = array();
?>
<div class="zb-container">

	<?php
	// Only decorate the first page with the hero.
	if ( ! is_paged() ) {
		get_template_part( 'template-parts/hero' );
	}
	$zb_shown = isset( $GLOBALS['zoomblog_shown'] ) ? (array) $GLOBALS['zoomblog_shown'] : array();
	?>

	<div class="zb-layout">
		<div class="zb-primary">

			<?php if ( ! is_paged() ) : ?>
				<?php
				// Editor's picks strip.
				$zb_picks = new WP_Query( array(
					'posts_per_page'      => 3,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'post__not_in'        => $zb_shown,
					'meta_key'            => '_zoomblog_editor_pick',
					'meta_value'          => '1',
				) );
				if ( $zb_picks->have_posts() ) :
					?>
					<section class="zb-section">
						<div class="zb-section__head">
							<h2 class="zb-section__title"><?php esc_html_e( 'انتخاب سردبیر', 'zoomblog' ); ?></h2>
						</div>
						<div class="zb-grid zb-grid--3">
							<?php
							while ( $zb_picks->have_posts() ) :
								$zb_picks->the_post();
								$zb_shown[] = get_the_ID();
								get_template_part( 'template-parts/card' );
							endwhile;
							wp_reset_postdata();
							?>
						</div>
					</section>
				<?php endif; ?>
			<?php endif; ?>

			<section class="zb-section">
				<div class="zb-section__head">
					<h2 class="zb-section__title"><?php esc_html_e( 'تازه‌ترین‌ها', 'zoomblog' ); ?></h2>
				</div>

				<?php if ( have_posts() ) : ?>
					<div class="zb-grid zb-grid--2">
						<?php
						while ( have_posts() ) :
							the_post();
							if ( in_array( get_the_ID(), $zb_shown, true ) && ! is_paged() ) {
								continue;
							}
							get_template_part( 'template-parts/card' );
						endwhile;
						?>
					</div>
					<?php zoomblog_pagination(); ?>
				<?php else : ?>
					<?php get_template_part( 'template-parts/none' ); ?>
				<?php endif; ?>
			</section>

		</div>

		<aside class="zb-sidebar" role="complementary">
			<?php
			// Trending (by views).
			$zb_trend = new WP_Query( array(
				'posts_per_page'      => 6,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'meta_key'            => '_zoomblog_views',
				'orderby'             => 'meta_value_num',
				'order'               => 'DESC',
			) );
			if ( $zb_trend->have_posts() ) :
				?>
				<div class="widget">
					<h3 class="widget-title"><?php esc_html_e( 'پرطرفدارها', 'zoomblog' ); ?></h3>
					<div class="zb-trending">
						<?php
						$zb_rank = 0;
						while ( $zb_trend->have_posts() ) :
							$zb_trend->the_post();
							$zb_rank++;
							?>
							<div class="zb-trending__item">
								<span class="zb-trending__rank"><?php echo esc_html( zoomblog_to_persian_digits( $zb_rank ) ); ?></span>
								<div class="zb-trending__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
							</div>
						<?php endwhile; wp_reset_postdata(); ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="widget zb-newsletter">
				<h3><?php esc_html_e( 'خبرنامه', 'zoomblog' ); ?></h3>
				<p><?php esc_html_e( 'مهم‌ترین مطالب هفته در ایمیل شما.', 'zoomblog' ); ?></p>
				<form onsubmit="return false;">
					<input type="email" class="zb-input" placeholder="<?php esc_attr_e( 'ایمیل شما', 'zoomblog' ); ?>" required>
					<button type="submit" class="zb-btn zb-btn--primary"><?php esc_html_e( 'عضویت', 'zoomblog' ); ?></button>
				</form>
			</div>

			<?php
			dynamic_sidebar( 'sidebar-main' );
			zoomblog_ad_slot( 'sidebar' );
			?>
		</aside>
	</div>

	<?php
	// Topic sections — top categories, one row each.
	if ( ! is_paged() ) {
		$zb_topics = get_terms( array(
			'taxonomy'   => 'category',
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 3,
			'hide_empty' => true,
		) );
		if ( $zb_topics && ! is_wp_error( $zb_topics ) ) {
			foreach ( $zb_topics as $zb_topic ) {
				$zb_tq = new WP_Query( array(
					'cat'                 => $zb_topic->term_id,
					'posts_per_page'      => 4,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				) );
				if ( ! $zb_tq->have_posts() ) {
					continue;
				}
				echo '<section class="zb-section">';
				echo '<div class="zb-section__head">';
				printf( '<h2 class="zb-section__title">%s</h2>', esc_html( $zb_topic->name ) );
				printf( '<a class="zb-section__more" href="%s">%s</a>', esc_url( get_term_link( $zb_topic ) ), esc_html__( 'مشاهدهٔ همه', 'zoomblog' ) );
				echo '</div>';
				echo '<div class="zb-grid zb-grid--4">';
				while ( $zb_tq->have_posts() ) {
					$zb_tq->the_post();
					get_template_part( 'template-parts/card', null, array( 'excerpt' => false ) );
				}
				wp_reset_postdata();
				echo '</div></section>';
			}
		}
	}
	?>
</div>
<?php
get_footer();
