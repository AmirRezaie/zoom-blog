<?php
/**
 * 404 — not found.
 *
 * @package ZoomBlog
 */

get_header();
?>
<div class="zb-container">
	<div class="zb-404">
		<div class="zb-404__code">۴۰۴</div>
		<h1 class="zb-404__title"><?php esc_html_e( 'صفحه پیدا نشد', 'zoomblog' ); ?></h1>
		<p><?php esc_html_e( 'نشانی اشتباه است یا صفحه جابه‌جا شده. با جست‌وجو ادامه دهید:', 'zoomblog' ); ?></p>
		<div class="zb-404__search"><?php get_search_form(); ?></div>
		<?php get_template_part( 'template-parts/none' ); ?>
	</div>
</div>
<style>
.zb-404{ text-align:center; padding:40px 20px; }
.zb-404__code{ font-size:5rem; font-weight:800; color:var(--zb-accent); line-height:1; }
.zb-404__title{ margin:.4em 0; }
.zb-404__search{ max-width:420px; margin:18px auto; }
.zb-404 .zb-none{ padding-top:20px; }
</style>
<?php
get_footer();
