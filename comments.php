<?php
/**
 * Comments template.
 *
 * @package ZoomBlog
 */

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="zb-comments__inner">
	<?php if ( have_comments() ) : ?>
		<h2 class="zb-comments__title">
			<?php
			$zb_count = get_comments_number();
			printf(
				/* translators: %s: comment count. */
				esc_html( _n( '%s دیدگاه', '%s دیدگاه', $zb_count, 'zoomblog' ) ),
				esc_html( zoomblog_to_persian_digits( number_format_i18n( $zb_count ) ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments( array(
				'style'      => 'ol',
				'short_ping' => true,
				'avatar_size'=> 48,
			) );
			?>
		</ol>

		<?php the_comments_pagination( array(
			'prev_text' => esc_html__( 'قبلی', 'zoomblog' ),
			'next_text' => esc_html__( 'بعدی', 'zoomblog' ),
		) ); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="zb-comments__closed"><?php esc_html_e( 'دیدگاه‌ها بسته شده‌اند.', 'zoomblog' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form( array(
		'title_reply'         => esc_html__( 'دیدگاه خود را بنویسید', 'zoomblog' ),
		'label_submit'        => esc_html__( 'ارسال دیدگاه', 'zoomblog' ),
		'class_submit'        => 'zb-btn zb-btn--primary',
		'comment_notes_before'=> '',
	) );
	?>
</div>
