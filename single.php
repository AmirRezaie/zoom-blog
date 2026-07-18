<?php
/**
 * Single post — the premium reading experience.
 *
 * @package ZoomBlog
 */

get_header();

if ( zoomblog_elementor_has_location( 'single' ) ) {
	get_footer();
	return;
}

if ( zoomblog_is_on( 'show_progress_bar' ) ) {
	echo '<div class="zb-progress" aria-hidden="true"><div class="zb-progress__fill"></div></div>';
}

while ( have_posts() ) :
	the_post();
	$zb_author_id = (int) get_the_author_meta( 'ID' );
	$zb_subtitle  = zoomblog_get_subtitle();
	$zb_sources   = zoomblog_get_sources();
	?>
	<div class="zb-container">
		<?php zoomblog_breadcrumb(); ?>

		<div class="zb-layout">
			<div class="zb-primary">
				<article <?php post_class( 'zb-article' ); ?>>

					<header class="zb-article__header">
						<div class="zb-article__badges">
							<?php
							zoomblog_type_badge();
							$zb_cat = zoomblog_primary_term( 'category' );
							if ( $zb_cat ) {
								printf( '<a class="zb-badge" href="%s">%s</a>', esc_url( get_term_link( $zb_cat ) ), esc_html( $zb_cat->name ) );
							}
							?>
						</div>

						<h1 class="zb-article__title"><?php the_title(); ?></h1>
						<?php if ( $zb_subtitle ) : ?>
							<p class="zb-article__subtitle"><?php echo esc_html( $zb_subtitle ); ?></p>
						<?php endif; ?>

						<div class="zb-article__meta">
							<a class="zb-article__author" href="<?php echo esc_url( get_author_posts_url( $zb_author_id ) ); ?>">
								<?php echo get_avatar( $zb_author_id, 80, '', get_the_author() ); ?>
								<span>
									<b><?php the_author(); ?></b>
									<small>
										<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
										<?php if ( zoomblog_is_on( 'show_reading_time' ) ) : ?>
											· <?php echo esc_html( zoomblog_reading_time() ); ?>
										<?php endif; ?>
									</small>
								</span>
							</a>
							<div class="zb-article__actions">
								<?php
								zoomblog_like_button();
								zoomblog_bookmark_button();
								?>
							</div>
						</div>
					</header>

					<?php zoomblog_disclosure_notice(); ?>
					<?php zoomblog_old_post_notice(); ?>

					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="zb-article__figure">
							<?php the_post_thumbnail( 'zoomblog-hero', array( 'fetchpriority' => 'high' ) ); ?>
							<?php if ( wp_get_attachment_caption( get_post_thumbnail_id() ) ) : ?>
								<figcaption><?php echo esc_html( wp_get_attachment_caption( get_post_thumbnail_id() ) ); ?></figcaption>
							<?php endif; ?>
						</figure>
					<?php endif; ?>

					<div class="zb-share zb-article__share"><?php zoomblog_share_bar(); ?></div>

					<?php zoomblog_render_toc(); ?>

					<div class="zb-content">
						<?php
						the_content();
						wp_link_pages( array(
							'before' => '<div class="zb-page-links">' . esc_html__( 'صفحه:', 'zoomblog' ),
							'after'  => '</div>',
						) );
						?>
					</div>

					<?php zoomblog_ad_slot( 'in_content' ); ?>

					<?php if ( $zb_sources ) : ?>
						<div class="zb-sources">
							<h3><?php esc_html_e( 'منابع', 'zoomblog' ); ?></h3>
							<ol>
								<?php foreach ( $zb_sources as $zb_src ) : ?>
									<li>
										<?php if ( $zb_src['url'] ) : ?>
											<a href="<?php echo esc_url( $zb_src['url'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $zb_src['title'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $zb_src['title'] ); ?>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ol>
						</div>
					<?php endif; ?>

					<?php if ( has_tag() ) : ?>
						<div class="zb-tags"><?php the_tags( '<span class="zb-tags__label">' . esc_html__( 'برچسب‌ها:', 'zoomblog' ) . '</span> ', '' ); ?></div>
					<?php endif; ?>

					<?php zoomblog_recommend_widget(); ?>

					<div class="zb-share zb-article__share--bottom"><?php zoomblog_share_bar(); ?></div>

					<?php get_template_part( 'template-parts/author-box' ); ?>

				</article>

				<?php get_template_part( 'template-parts/related' ); ?>

				<?php
				if ( comments_open() || get_comments_number() ) {
					echo '<div class="zb-comments">';
					comments_template();
					echo '</div>';
				}
				?>
			</div>

			<?php get_sidebar(); ?>
		</div>
	</div>

	<?php get_template_part( 'template-parts/reader-controls' ); ?>
	<?php
endwhile;

get_footer();
