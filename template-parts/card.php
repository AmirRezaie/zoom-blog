<?php
/**
 * Post card.
 *
 * Accepts $args:
 *   - variant: '' | 'row' | 'mobile-row'
 *   - size:    image size (default zoomblog-card)
 *   - excerpt: bool (default per option)
 *   - views:   bool
 *
 * @package ZoomBlog
 */

$zb_variant = isset( $args['variant'] ) ? $args['variant'] : '';
$zb_size    = isset( $args['size'] ) ? $args['size'] : 'zoomblog-card';
$zb_excerpt = isset( $args['excerpt'] ) ? (bool) $args['excerpt'] : zoomblog_is_on( 'show_excerpt' );
$zb_views   = ! empty( $args['views'] );

$zb_classes = array( 'zb-card' );
if ( 'row' === $zb_variant ) {
	$zb_classes[] = 'zb-card--row';
}
$zb_classes[] = 'zb-card--mobile-row'; // Denser layout on phones (see cards.css).

$zb_type = zoomblog_content_type();
$zb_cat  = zoomblog_primary_term( 'category' );
$zb_icons = array( 'podcast' => '🎙', 'video' => '▶' );
?>
<article <?php post_class( $zb_classes ); ?>>
	<a class="zb-card__media" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>" style="--zb-card-ratio:<?php echo esc_attr( zoomblog_card_ratio_css() ); ?>">
		<?php
		echo zoomblog_media_or_placeholder( null, $zb_size, array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $zb_cat ) {
			printf( '<span class="zb-card__cat">%s</span>', esc_html( $zb_cat->name ) );
		}
		if ( isset( $zb_icons[ $zb_type['key'] ] ) ) {
			printf( '<span class="zb-card__typeicon" aria-hidden="true">%s</span>', esc_html( $zb_icons[ $zb_type['key'] ] ) );
		}
		?>
	</a>
	<div class="zb-card__body">
		<h3 class="zb-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( $zb_excerpt ) : ?>
			<p class="zb-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), (int) zoomblog_get_option( 'excerpt_length', 24 ), '…' ) ); ?></p>
		<?php endif; ?>
		<?php zoomblog_card_meta( array( 'views' => $zb_views, 'type' => false ) ); ?>
	</div>
</article>
