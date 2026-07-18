<?php
/**
 * "Nothing found" state with topic suggestions.
 *
 * @package ZoomBlog
 */

?>
<div class="zb-none">
	<div class="zb-none__art" aria-hidden="true">🔍</div>
	<h2 class="zb-none__title">
		<?php
		if ( is_search() ) {
			esc_html_e( 'نتیجه‌ای پیدا نشد', 'zoomblog' );
		} else {
			esc_html_e( 'هنوز نوشته‌ای نیست', 'zoomblog' );
		}
		?>
	</h2>
	<p class="zb-none__text"><?php esc_html_e( 'شاید یکی از موضوع‌های پرطرفدار زیر کمک کند:', 'zoomblog' ); ?></p>

	<?php
	$zb_topics = get_terms( array(
		'taxonomy'   => 'category',
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 8,
		'hide_empty' => true,
	) );
	if ( $zb_topics && ! is_wp_error( $zb_topics ) ) :
		?>
		<div class="zb-none__topics">
			<?php foreach ( $zb_topics as $zb_topic ) : ?>
				<a class="zb-tab" href="<?php echo esc_url( get_term_link( $zb_topic ) ); ?>"><?php echo esc_html( $zb_topic->name ); ?></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="zb-none__search"><?php get_search_form(); ?></div>
</div>
<style>
.zb-none{ text-align:center; padding:60px 20px; max-width:640px; margin-inline:auto; }
.zb-none__art{ font-size:3rem; }
.zb-none__topics{ display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin:18px 0; }
.zb-none__search{ max-width:420px; margin-inline:auto; }
</style>
