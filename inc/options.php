<?php
/**
 * Central options store.
 *
 * A single wp_option (`zoomblog_options`) holds every theme setting so the
 * front-end stays fast (one DB read, autoloaded) and the admin panel has one
 * source of truth. Grouping metadata drives the searchable settings screen.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default values for every setting.
 *
 * @return array<string,mixed>
 */
function zoomblog_default_options() {
	$defaults = array(
		// Brand identity (Zoomit-style red).
		'accent_color'        => '#eb2027',
		'accent_color_2'      => '#ff5a5f',
		'container_width'     => 1200,
		'logo_max_height'     => 40,

		// Color & typography.
		'default_mode'        => 'auto',   // light | dark | auto.
		'enable_sepia'        => true,
		'font_family'         => 'vazirmatn', // vazirmatn | system.
		'base_font_size'      => 17,
		'base_line_height'    => 1.85,
		'preload_font'        => true,

		// Header & navigation.
		'sticky_header'       => true,
		'enable_mega_menu'    => true,
		'show_search_in_header' => true,

		// Footer.
		'footer_copyright'    => '',
		'show_footer_menu'    => true,

		// Blog / archive.
		'card_ratio'          => '16-9', // 16-9 | 4-3 | 1-1.
		'archive_columns'     => 3,
		'show_excerpt'        => true,
		'excerpt_length'      => 24,

		// Single post.
		'show_progress_bar'   => true,
		'show_reading_time'   => true,
		'show_toc'            => true,
		'toc_min_headings'    => 3,
		'show_related'        => true,
		'related_count'       => 3,
		'show_author_box'     => true,
		'show_reader_controls' => true,
		'old_post_months'     => 18,      // Warn if older than N months (0 = off).

		// Search.
		'live_search'         => true,
		'live_search_min'     => 2,

		// Social.
		'social_twitter'      => '',
		'social_instagram'    => '',
		'social_telegram'     => '',
		'social_youtube'      => '',
		'social_linkedin'     => '',
		'share_services'      => array( 'telegram', 'twitter', 'whatsapp', 'copy' ),

		// Ads.
		'ad_header'           => '',
		'ad_in_content'       => '',
		'ad_sidebar'          => '',
		'ad_reserve_space'    => true,   // Reserve height to prevent CLS.

		// Performance.
		'remove_jquery'       => true,
		'lazyload'            => true,
		'defer_scripts'       => true,
		'disable_emoji'       => true,
		'disable_embeds'      => false,

		// Accessibility.
		'focus_outline'       => true,
		'reduce_motion_respect' => true,

		// Interactions.
		'enable_like'         => true,
		'enable_bookmark'     => true,
		'enable_history'      => true,
		'enable_follow'       => true,
		'enable_recommend'    => true,   // Recommend percentage.

		// Persian engine.
		'persian_unify'       => true,   // ی/ي and ک/ك.
		'persian_halfspace'   => true,   // nim-fasele normalization.
		'persian_nearword'    => false,  // near-word suggestion (heavier).
		'jalali_dates'        => true,

		// Post types (optional).
		'cpt_podcast'         => false,
		'cpt_video'           => false,
		'cpt_review'          => false,

		// Paywall.
		'paywall_enable'      => false,
		'paywall_free_paras'  => 4,
		'paywall_message'     => '',
	);

	return apply_filters( 'zoomblog_default_options', $defaults );
}

/**
 * Get all options merged with defaults.
 *
 * @return array<string,mixed>
 */
function zoomblog_get_options() {
	static $cache = null;
	if ( null === $cache ) {
		$saved  = get_option( 'zoomblog_options', array() );
		$saved  = is_array( $saved ) ? $saved : array();
		$cache  = wp_parse_args( $saved, zoomblog_default_options() );
	}
	return $cache;
}

/**
 * Get a single option value.
 *
 * @param string $key      Option key.
 * @param mixed  $fallback Value if key missing.
 * @return mixed
 */
function zoomblog_get_option( $key, $fallback = null ) {
	$options = zoomblog_get_options();
	if ( array_key_exists( $key, $options ) ) {
		return $options[ $key ];
	}
	return $fallback;
}

/**
 * Convenience boolean reader.
 *
 * @param string $key Option key.
 * @return bool
 */
function zoomblog_is_on( $key ) {
	return (bool) zoomblog_get_option( $key, false );
}

/**
 * Persist a full options array (used by the settings panel + wizard).
 *
 * @param array<string,mixed> $values Sanitized values.
 */
function zoomblog_update_options( array $values ) {
	$current = get_option( 'zoomblog_options', array() );
	$current = is_array( $current ) ? $current : array();
	update_option( 'zoomblog_options', array_merge( $current, $values ) );
}

/**
 * The settings-panel schema: groups → fields.
 *
 * Each field: type, label (fa), desc (fa short), and type-specific keys.
 * This drives the searchable admin UI and keeps labels in one place.
 *
 * @return array<string,array<string,mixed>>
 */
function zoomblog_settings_schema() {
	$schema = array(
		'brand' => array(
			'title'  => 'هویت برند',
			'icon'   => 'admin-appearance',
			'fields' => array(
				'accent_color'    => array( 'type' => 'color', 'label' => 'رنگ اصلی', 'desc' => 'رنگ برند برای دکمه‌ها و لینک‌ها.' ),
				'accent_color_2'  => array( 'type' => 'color', 'label' => 'رنگ تأکید', 'desc' => 'برای برچسب‌ها و نکات ویژه.' ),
				'container_width' => array( 'type' => 'number', 'label' => 'عرض محتوا (px)', 'desc' => 'حداکثر عرض ستون اصلی.', 'min' => 960, 'max' => 1440 ),
				'logo_max_height' => array( 'type' => 'number', 'label' => 'ارتفاع لوگو (px)', 'desc' => 'ارتفاع لوگو در هدر.', 'min' => 20, 'max' => 120 ),
			),
		),
		'typography' => array(
			'title'  => 'رنگ و تایپوگرافی',
			'icon'   => 'editor-textcolor',
			'fields' => array(
				'default_mode'     => array( 'type' => 'select', 'label' => 'حالت پیش‌فرض', 'desc' => 'روشن، تیره یا خودکار بر اساس سیستم.', 'choices' => array( 'light' => 'روشن', 'dark' => 'تیره', 'auto' => 'خودکار' ) ),
				'enable_sepia'     => array( 'type' => 'toggle', 'label' => 'حالت سپیا', 'desc' => 'گزینهٔ مطالعهٔ کاغذی برای کاربر.' ),
				'font_family'      => array( 'type' => 'select', 'label' => 'قلم', 'desc' => 'وزیرمتن محلی یا قلم سیستم.', 'choices' => array( 'vazirmatn' => 'وزیرمتن (محلی)', 'system' => 'قلم سیستم' ) ),
				'base_font_size'   => array( 'type' => 'number', 'label' => 'اندازهٔ متن (px)', 'desc' => 'اندازهٔ پایهٔ متن مقاله.', 'min' => 14, 'max' => 22 ),
				'base_line_height' => array( 'type' => 'number', 'label' => 'فاصلهٔ خطوط', 'desc' => 'ارتفاع خط مناسب فارسی.', 'min' => 1.4, 'max' => 2.2, 'step' => 0.05 ),
				'preload_font'     => array( 'type' => 'toggle', 'label' => 'پیش‌بارگذاری قلم', 'desc' => 'بارگذاری کنترل‌شدهٔ قلم اصلی.' ),
			),
		),
		'header' => array(
			'title'  => 'هدر و ناوبری',
			'icon'   => 'menu',
			'fields' => array(
				'sticky_header'         => array( 'type' => 'toggle', 'label' => 'هدر چسبان', 'desc' => 'هدر هنگام اسکرول ثابت بماند.' ),
				'enable_mega_menu'      => array( 'type' => 'toggle', 'label' => 'مگا منو', 'desc' => 'منوی بزرگ چنددسته‌ای.' ),
				'show_search_in_header' => array( 'type' => 'toggle', 'label' => 'جست‌وجو در هدر', 'desc' => 'نمایش دکمهٔ جست‌وجو در هدر.' ),
			),
		),
		'footer' => array(
			'title'  => 'فوتر',
			'icon'   => 'align-center',
			'fields' => array(
				'footer_copyright' => array( 'type' => 'text', 'label' => 'متن کپی‌رایت', 'desc' => 'خالی بگذارید تا خودکار ساخته شود.' ),
				'show_footer_menu' => array( 'type' => 'toggle', 'label' => 'منوی فوتر', 'desc' => 'نمایش منوی پایین سایت.' ),
			),
		),
		'blog' => array(
			'title'  => 'بلاگ و آرشیو',
			'icon'   => 'grid-view',
			'fields' => array(
				'card_ratio'      => array( 'type' => 'select', 'label' => 'نسبت تصویر کارت', 'desc' => 'نسبت ابعاد تصویر بندانگشتی.', 'choices' => array( '16-9' => '۱۶:۹', '4-3' => '۴:۳', '1-1' => '۱:۱' ) ),
				'archive_columns' => array( 'type' => 'select', 'label' => 'ستون‌های آرشیو', 'desc' => 'تعداد ستون شبکهٔ نوشته‌ها.', 'choices' => array( 2 => '۲', 3 => '۳', 4 => '۴' ) ),
				'show_excerpt'    => array( 'type' => 'toggle', 'label' => 'نمایش خلاصه', 'desc' => 'نمایش چکیده در کارت‌ها.' ),
				'excerpt_length'  => array( 'type' => 'number', 'label' => 'طول خلاصه (کلمه)', 'desc' => 'تعداد کلمات چکیده.', 'min' => 8, 'max' => 60 ),
			),
		),
		'single' => array(
			'title'  => 'صفحهٔ نوشته',
			'icon'   => 'media-text',
			'fields' => array(
				'show_progress_bar'    => array( 'type' => 'toggle', 'label' => 'نوار پیشرفت', 'desc' => 'نوار پیشرفت مطالعه در بالای صفحه.' ),
				'show_reading_time'    => array( 'type' => 'toggle', 'label' => 'زمان مطالعه', 'desc' => 'نمایش زمان تقریبی مطالعه.' ),
				'show_toc'             => array( 'type' => 'toggle', 'label' => 'فهرست مطالب', 'desc' => 'فهرست خودکار از سرتیترها.' ),
				'toc_min_headings'     => array( 'type' => 'number', 'label' => 'حداقل سرتیتر برای فهرست', 'desc' => 'فهرست فقط از این تعداد به بالا ساخته شود.', 'min' => 2, 'max' => 8 ),
				'show_related'         => array( 'type' => 'toggle', 'label' => 'نوشته‌های مرتبط', 'desc' => 'نمایش نوشته‌های مرتبط در پایان.' ),
				'related_count'        => array( 'type' => 'number', 'label' => 'تعداد مرتبط‌ها', 'desc' => 'چند نوشتهٔ مرتبط نمایش داده شود.', 'min' => 2, 'max' => 6 ),
				'show_author_box'      => array( 'type' => 'toggle', 'label' => 'جعبهٔ نویسنده', 'desc' => 'کارت معرفی نویسنده در پایان.' ),
				'show_reader_controls' => array( 'type' => 'toggle', 'label' => 'مرکز کنترل خواننده', 'desc' => 'تنظیمات مطالعهٔ شخصی خواننده.' ),
				'old_post_months'      => array( 'type' => 'number', 'label' => 'هشدار نوشتهٔ قدیمی (ماه)', 'desc' => 'هشدار برای نوشته‌های قدیمی‌تر از این مقدار. صفر = خاموش.', 'min' => 0, 'max' => 60 ),
			),
		),
		'search' => array(
			'title'  => 'جست‌وجو',
			'icon'   => 'search',
			'fields' => array(
				'live_search'     => array( 'type' => 'toggle', 'label' => 'جست‌وجوی زنده', 'desc' => 'نمایش نتایج حین تایپ.' ),
				'live_search_min' => array( 'type' => 'number', 'label' => 'حداقل کاراکتر', 'desc' => 'شروع جست‌وجو بعد از این تعداد حرف.', 'min' => 1, 'max' => 5 ),
			),
		),
		'social' => array(
			'title'  => 'شبکه‌های اجتماعی',
			'icon'   => 'share',
			'fields' => array(
				'social_telegram'  => array( 'type' => 'text', 'label' => 'تلگرام', 'desc' => 'نشانی کانال یا صفحه.' ),
				'social_instagram' => array( 'type' => 'text', 'label' => 'اینستاگرام', 'desc' => 'نشانی صفحه.' ),
				'social_twitter'   => array( 'type' => 'text', 'label' => 'ایکس / توییتر', 'desc' => 'نشانی صفحه.' ),
				'social_youtube'   => array( 'type' => 'text', 'label' => 'یوتیوب', 'desc' => 'نشانی کانال.' ),
				'social_linkedin'  => array( 'type' => 'text', 'label' => 'لینکدین', 'desc' => 'نشانی صفحه.' ),
			),
		),
		'ads' => array(
			'title'  => 'تبلیغات',
			'icon'   => 'megaphone',
			'fields' => array(
				'ad_header'        => array( 'type' => 'textarea', 'label' => 'بنر هدر', 'desc' => 'کد HTML بنر زیر هدر.' ),
				'ad_in_content'    => array( 'type' => 'textarea', 'label' => 'تبلیغ میان‌متن', 'desc' => 'کد HTML داخل مقاله.' ),
				'ad_sidebar'       => array( 'type' => 'textarea', 'label' => 'تبلیغ سایدبار', 'desc' => 'کد HTML ستون کناری.' ),
				'ad_reserve_space' => array( 'type' => 'toggle', 'label' => 'رزرو فضای تبلیغ', 'desc' => 'جلوگیری از پرش صفحه (CLS).' ),
			),
		),
		'performance' => array(
			'title'  => 'کارایی',
			'icon'   => 'performance',
			'fields' => array(
				'remove_jquery'  => array( 'type' => 'toggle', 'label' => 'حذف jQuery در فرانت', 'desc' => 'اگر افزونه‌ای نیاز نداشته باشد.' ),
				'lazyload'       => array( 'type' => 'toggle', 'label' => 'بارگذاری تنبل', 'desc' => 'تصاویر پایین صفحه با تأخیر.' ),
				'defer_scripts'  => array( 'type' => 'toggle', 'label' => 'defer اسکریپت‌ها', 'desc' => 'به تعویق انداختن جاوااسکریپت تم.' ),
				'disable_emoji'  => array( 'type' => 'toggle', 'label' => 'حذف اسکریپت ایموجی', 'desc' => 'حذف emoji وردپرس.' ),
				'disable_embeds' => array( 'type' => 'toggle', 'label' => 'حذف wp-embed', 'desc' => 'اگر به embed داخلی نیاز ندارید.' ),
			),
		),
		'accessibility' => array(
			'title'  => 'دسترس‌پذیری',
			'icon'   => 'universal-access',
			'fields' => array(
				'focus_outline'         => array( 'type' => 'toggle', 'label' => 'حلقهٔ فوکوس', 'desc' => 'نمایش قاب فوکوس برای کیبورد.' ),
				'reduce_motion_respect' => array( 'type' => 'toggle', 'label' => 'احترام به کاهش حرکت', 'desc' => 'کاهش انیمیشن‌ها طبق تنظیم سیستم.' ),
			),
		),
		'interactions' => array(
			'title'  => 'تعامل‌ها',
			'icon'   => 'heart',
			'fields' => array(
				'enable_like'      => array( 'type' => 'toggle', 'label' => 'پسند (لایک)', 'desc' => 'دکمهٔ پسند نوشته‌ها.' ),
				'enable_bookmark'  => array( 'type' => 'toggle', 'label' => 'نشان‌ک (بوکمارک)', 'desc' => 'ذخیرهٔ نوشته توسط خواننده.' ),
				'enable_history'   => array( 'type' => 'toggle', 'label' => 'تاریخچهٔ مطالعه', 'desc' => 'یادآوری نوشته‌های خوانده‌شده.' ),
				'enable_follow'    => array( 'type' => 'toggle', 'label' => 'دنبال‌کردن نویسنده', 'desc' => 'دنبال‌کردن نویسنده یا دسته.' ),
				'enable_recommend' => array( 'type' => 'toggle', 'label' => 'درصد پیشنهاد', 'desc' => 'رأی «پیشنهاد می‌کنم» خوانندگان.' ),
			),
		),
		'persian' => array(
			'title'  => 'فارسی‌ساز',
			'icon'   => 'translation',
			'fields' => array(
				'persian_unify'     => array( 'type' => 'toggle', 'label' => 'یکسان‌سازی ی/ک', 'desc' => 'تبدیل ي/ك عربی به ی/ک فارسی.' ),
				'persian_halfspace' => array( 'type' => 'toggle', 'label' => 'مدیریت نیم‌فاصله', 'desc' => 'اصلاح نیم‌فاصله در پیشوند/پسوندها.' ),
				'persian_nearword'  => array( 'type' => 'toggle', 'label' => 'پیشنهاد واژهٔ نزدیک', 'desc' => 'پیشنهاد املای نزدیک (سنگین‌تر).' ),
				'jalali_dates'      => array( 'type' => 'toggle', 'label' => 'تاریخ جلالی', 'desc' => 'نمایش تاریخ شمسی.' ),
			),
		),
		'cpt' => array(
			'title'  => 'انواع محتوا',
			'icon'   => 'admin-post',
			'fields' => array(
				'cpt_podcast' => array( 'type' => 'toggle', 'label' => 'پادکست', 'desc' => 'فعال‌سازی نوع محتوای پادکست.' ),
				'cpt_video'   => array( 'type' => 'toggle', 'label' => 'ویدیو', 'desc' => 'فعال‌سازی نوع محتوای ویدیو.' ),
				'cpt_review'  => array( 'type' => 'toggle', 'label' => 'بررسی محصول', 'desc' => 'فعال‌سازی نوع محتوای نقد و بررسی.' ),
			),
		),
		'paywall' => array(
			'title'  => 'محتوای ویژه (پی‌وال)',
			'icon'   => 'lock',
			'fields' => array(
				'paywall_enable'     => array( 'type' => 'toggle', 'label' => 'فعال‌سازی پی‌وال', 'desc' => 'محدودکردن بخشی از نوشته‌های ویژه.' ),
				'paywall_free_paras' => array( 'type' => 'number', 'label' => 'پاراگراف رایگان', 'desc' => 'چند پاراگراف پیش از قفل نمایش داده شود.', 'min' => 1, 'max' => 15 ),
				'paywall_message'    => array( 'type' => 'textarea', 'label' => 'پیام پی‌وال', 'desc' => 'متن نمایش‌داده‌شده روی بخش قفل‌شده.' ),
			),
		),
	);

	return apply_filters( 'zoomblog_settings_schema', $schema );
}
