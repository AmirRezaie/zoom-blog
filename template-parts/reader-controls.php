<?php
/**
 * Reader Control Center.
 *
 * A floating panel that lets the reader tune font size, line spacing, reading
 * column width, theme (light/dark/sepia), hide the sidebar, and enter focus
 * mode. Preferences persist in localStorage (handled by reader.js). "Continue
 * reading" is rendered by reader.js from stored scroll positions.
 *
 * @package ZoomBlog
 */

if ( ! zoomblog_is_on( 'show_reader_controls' ) ) {
	return;
}
?>
<button type="button" class="zb-reader-fab" aria-label="<?php esc_attr_e( 'تنظیمات مطالعه', 'zoomblog' ); ?>" aria-expanded="false" aria-controls="zb-reader-panel">Aa</button>

<div class="zb-reader-panel" id="zb-reader-panel" role="dialog" aria-label="<?php esc_attr_e( 'مرکز کنترل خواننده', 'zoomblog' ); ?>">
	<h4><?php esc_html_e( 'تنظیمات مطالعه', 'zoomblog' ); ?></h4>

	<div class="zb-reader-row">
		<span><?php esc_html_e( 'اندازهٔ متن', 'zoomblog' ); ?></span>
		<div class="zb-seg" data-reader="size">
			<button type="button" data-val="15"><?php esc_html_e( 'ک', 'zoomblog' ); ?></button>
			<button type="button" data-val="17" class="is-active"><?php esc_html_e( 'م', 'zoomblog' ); ?></button>
			<button type="button" data-val="20"><?php esc_html_e( 'ب', 'zoomblog' ); ?></button>
		</div>
	</div>

	<div class="zb-reader-row">
		<span><?php esc_html_e( 'فاصلهٔ خطوط', 'zoomblog' ); ?></span>
		<div class="zb-seg" data-reader="lh">
			<button type="button" data-val="1.6"><?php esc_html_e( 'کم', 'zoomblog' ); ?></button>
			<button type="button" data-val="1.85" class="is-active"><?php esc_html_e( 'متوسط', 'zoomblog' ); ?></button>
			<button type="button" data-val="2.1"><?php esc_html_e( 'زیاد', 'zoomblog' ); ?></button>
		</div>
	</div>

	<div class="zb-reader-row">
		<span><?php esc_html_e( 'عرض ستون', 'zoomblog' ); ?></span>
		<div class="zb-seg" data-reader="width">
			<button type="button" data-val="640"><?php esc_html_e( 'باریک', 'zoomblog' ); ?></button>
			<button type="button" data-val="100%" class="is-active"><?php esc_html_e( 'معمول', 'zoomblog' ); ?></button>
		</div>
	</div>

	<div class="zb-reader-row">
		<span><?php esc_html_e( 'حالت', 'zoomblog' ); ?></span>
		<div class="zb-theme-swatches" data-reader="theme">
			<button type="button" class="zb-swatch-light" data-val="light" aria-label="<?php esc_attr_e( 'روشن', 'zoomblog' ); ?>"></button>
			<button type="button" class="zb-swatch-dark" data-val="dark" aria-label="<?php esc_attr_e( 'تیره', 'zoomblog' ); ?>"></button>
			<?php if ( zoomblog_is_on( 'enable_sepia' ) ) : ?>
				<button type="button" class="zb-swatch-sepia" data-val="sepia" aria-label="<?php esc_attr_e( 'سپیا', 'zoomblog' ); ?>"></button>
			<?php endif; ?>
		</div>
	</div>

	<div class="zb-reader-row">
		<label class="zb-reader-toggle"><input type="checkbox" data-reader="hide-sidebar"> <?php esc_html_e( 'پنهان‌کردن سایدبار', 'zoomblog' ); ?></label>
	</div>
	<div class="zb-reader-row">
		<label class="zb-reader-toggle"><input type="checkbox" data-reader="focus"> <?php esc_html_e( 'حالت تمرکز', 'zoomblog' ); ?></label>
	</div>
</div>
