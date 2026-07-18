<?php
/**
 * Author box shown at the end of a single post.
 *
 * @package ZoomBlog
 */

if ( ! zoomblog_is_on( 'show_author_box' ) ) {
	return;
}
$zb_author_id = (int) get_the_author_meta( 'ID' );
if ( ! $zb_author_id ) {
	return;
}
$zb_bio = get_the_author_meta( 'description', $zb_author_id );
?>
<div class="zb-authorbox">
	<?php echo get_avatar( $zb_author_id, 144, '', get_the_author(), array( 'class' => 'zb-authorbox__avatar', 'loading' => 'lazy' ) ); ?>
	<div class="zb-authorbox__body">
		<h3 class="zb-authorbox__name"><a href="<?php echo esc_url( get_author_posts_url( $zb_author_id ) ); ?>"><?php echo esc_html( get_the_author() ); ?></a></h3>
		<?php if ( $zb_bio ) : ?>
			<p class="zb-authorbox__bio"><?php echo esc_html( $zb_bio ); ?></p>
		<?php endif; ?>
		<div class="zb-authorbox__foot">
			<a class="zb-btn zb-btn--ghost" href="<?php echo esc_url( get_author_posts_url( $zb_author_id ) ); ?>"><?php esc_html_e( 'همهٔ نوشته‌ها', 'zoomblog' ); ?></a>
			<?php zoomblog_follow_button( $zb_author_id ); ?>
		</div>
	</div>
</div>
