<?php
/**
 * Homepage hero — one large featured post + a stacked side list.
 *
 * Prefers editor's picks (`_zoomblog_editor_pick`), falls back to latest.
 * Exposes the used IDs in $GLOBALS['zoomblog_shown'] so later sections can
 * avoid duplicates.
 *
 * @package ZoomBlog
 */

$zb_pick_args = array(
	'posts_per_page'      => 4,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'meta_key'            => '_zoomblog_editor_pick',
	'meta_value'          => '1',
);
$zb_hero = new WP_Query( $zb_pick_args );
if ( ! $zb_hero->have_posts() ) {
	wp_reset_postdata();
	$zb_hero = new WP_Query( array(
		'posts_per_page'      => 4,
		'ignore_sticky_posts' => false,
		'no_found_rows'       => true,
	) );
}
if ( ! $zb_hero->have_posts() ) {
	wp_reset_postdata();
	return;
}

$zb_posts = $zb_hero->posts;
$zb_main  = array_shift( $zb_posts );

$GLOBALS['zoomblog_shown'] = array_merge(
	array( $zb_main->ID ),
	wp_list_pluck( $zb_posts, 'ID' )
);
?>
<section class="zb-hero">
	<a class="zb-hero__main" href="<?php echo esc_url( get_permalink( $zb_main ) ); ?>">
		<?php echo zoomblog_media_or_placeholder( $zb_main, 'zoomblog-hero', array( 'fetchpriority' => 'high' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="zb-hero__overlay">
			<?php
			$zb_cat = zoomblog_primary_term( 'category', $zb_main );
			if ( $zb_cat ) {
				printf( '<span class="zb-badge">%s</span>', esc_html( $zb_cat->name ) );
			}
			?>
			<h2 class="zb-card__title"><?php echo esc_html( get_the_title( $zb_main ) ); ?></h2>
			<div class="zb-meta">
				<span class="zb-meta__author"><?php echo esc_html( get_the_author_meta( 'display_name', $zb_main->post_author ) ); ?></span>
				<time class="zb-meta__date"><?php echo esc_html( zoomblog_relative_or_jalali( $zb_main ) ); ?></time>
			</div>
		</div>
	</a>

	<div class="zb-hero__side">
		<?php foreach ( $zb_posts as $zb_sp ) : ?>
			<article class="zb-card zb-card--row">
				<a class="zb-card__media" href="<?php echo esc_url( get_permalink( $zb_sp ) ); ?>">
					<?php echo zoomblog_media_or_placeholder( $zb_sp, 'zoomblog-card-sm', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<div class="zb-card__body">
					<h3 class="zb-card__title"><a href="<?php echo esc_url( get_permalink( $zb_sp ) ); ?>"><?php echo esc_html( get_the_title( $zb_sp ) ); ?></a></h3>
					<div class="zb-meta">
						<time class="zb-meta__date"><?php echo esc_html( zoomblog_relative_or_jalali( $zb_sp ) ); ?></time>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
<?php
wp_reset_postdata();
