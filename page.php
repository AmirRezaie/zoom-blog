<?php
/**
 * Single page.
 *
 * @package ZoomBlog
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="zb-container">
		<?php zoomblog_breadcrumb(); ?>
		<div class="zb-layout zb-layout--full">
			<article <?php post_class( 'zb-article' ); ?>>
				<header class="zb-article__header">
					<h1 class="zb-article__title"><?php the_title(); ?></h1>
				</header>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="zb-article__figure"><?php the_post_thumbnail( 'zoomblog-hero' ); ?></figure>
				<?php endif; ?>
				<div class="zb-content">
					<?php
					the_content();
					wp_link_pages( array( 'before' => '<div class="zb-page-links">', 'after' => '</div>' ) );
					?>
				</div>
				<?php
				if ( comments_open() || get_comments_number() ) {
					echo '<div class="zb-comments">';
					comments_template();
					echo '</div>';
				}
				?>
			</article>
		</div>
	</div>
	<?php
endwhile;

get_footer();
