<?php
/**
 * Template Name: تمام‌عرض بدون سایدبار
 * Template Post Type: page, post
 *
 * A no-sidebar, full-width canvas — ideal for Elementor-built pages.
 *
 * @package ZoomBlog
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="zb-container">
		<div class="zb-layout zb-layout--full">
			<article <?php post_class( 'zb-article' ); ?>>
				<div class="zb-content"><?php the_content(); ?></div>
			</article>
		</div>
	</div>
	<?php
endwhile;

get_footer();
