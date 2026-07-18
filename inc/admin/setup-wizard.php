<?php
/**
 * Six-step install wizard.
 *
 * Steps: 1) server status · 2) essential plugins · 3) site type ·
 * 4) import demo · 5) logo/color/font · 6) build homepage & finish.
 *
 * Phase-1 scope: every step is a working screen. Demo import registers the
 * chosen demo and seeds starter content/menus; richer per-demo XML importers
 * plug in via the `zoomblog_import_demo` action.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the wizard submenu.
 */
function zoomblog_wizard_menu() {
	add_submenu_page(
		'zoomblog',
		__( 'نصب سریع', 'zoomblog' ),
		__( 'نصب سریع', 'zoomblog' ),
		'manage_options',
		'zoomblog-setup',
		'zoomblog_render_wizard'
	);
}
add_action( 'admin_menu', 'zoomblog_wizard_menu' );

/**
 * The available demos.
 *
 * @return array<string,array{title:string,desc:string}>
 */
function zoomblog_demos() {
	return array(
		'personal'  => array( 'title' => __( 'بلاگ شخصی و مینیمال', 'zoomblog' ), 'desc' => __( 'ساده، سبک و متن‌محور.', 'zoomblog' ) ),
		'tech'      => array( 'title' => __( 'مجلهٔ تکنولوژی', 'zoomblog' ), 'desc' => __( 'الهام‌گرفته از زومیت.', 'zoomblog' ) ),
		'news'      => array( 'title' => __( 'سایت خبری', 'zoomblog' ), 'desc' => __( 'خبر فوری و جریان سریع.', 'zoomblog' ) ),
		'lifestyle' => array( 'title' => __( 'سبک زندگی', 'zoomblog' ), 'desc' => __( 'تصویری و گرم.', 'zoomblog' ) ),
		'review'    => array( 'title' => __( 'نقد و بررسی', 'zoomblog' ), 'desc' => __( 'امتیاز و جدول مشخصات.', 'zoomblog' ) ),
		'podcast'   => array( 'title' => __( 'پادکست و ویدیو', 'zoomblog' ), 'desc' => __( 'رسانه‌محور.', 'zoomblog' ) ),
	);
}

/**
 * Recommended plugins for step 2.
 *
 * @return array<string,array{name:string,slug:string,why:string}>
 */
function zoomblog_wizard_plugins() {
	return array(
		'elementor' => array( 'name' => 'Elementor', 'slug' => 'elementor', 'why' => __( 'صفحه‌ساز اصلی.', 'zoomblog' ) ),
		'rank-math' => array( 'name' => 'Rank Math SEO', 'slug' => 'seo-by-rank-math', 'why' => __( 'سئوی حرفه‌ای.', 'zoomblog' ) ),
	);
}

/**
 * Persist a wizard step submission.
 */
function zoomblog_wizard_handle() {
	if ( empty( $_POST['zoomblog_wizard_nonce'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zoomblog_wizard_nonce'] ) ), 'zoomblog_wizard' ) ) {
		return;
	}
	$step  = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;
	$clean = array();

	if ( 3 === $step && isset( $_POST['demo'] ) ) {
		$demo = sanitize_key( wp_unslash( $_POST['demo'] ) );
		if ( array_key_exists( $demo, zoomblog_demos() ) ) {
			update_option( 'zoomblog_active_demo', $demo );
		}
	}

	if ( 4 === $step ) {
		$demo = get_option( 'zoomblog_active_demo', 'tech' );
		zoomblog_seed_demo_content( $demo );
		/**
		 * Let a demo-content pack do a full import.
		 *
		 * @param string $demo Demo slug.
		 */
		do_action( 'zoomblog_import_demo', $demo );
		update_option( 'zoomblog_demo_imported', $demo );
	}

	if ( 5 === $step ) {
		if ( isset( $_POST['accent_color'] ) ) {
			$clean['accent_color'] = sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) ) ?: '#2f6bff';
		}
		if ( isset( $_POST['font_family'] ) ) {
			$font = sanitize_key( wp_unslash( $_POST['font_family'] ) );
			$clean['font_family'] = in_array( $font, array( 'vazirmatn', 'system' ), true ) ? $font : 'vazirmatn';
		}
		if ( isset( $_POST['default_mode'] ) ) {
			$mode = sanitize_key( wp_unslash( $_POST['default_mode'] ) );
			$clean['default_mode'] = in_array( $mode, array( 'light', 'dark', 'auto' ), true ) ? $mode : 'auto';
		}
		if ( ! empty( $clean ) ) {
			zoomblog_update_options( $clean );
		}
		if ( ! empty( $_POST['custom_logo'] ) ) {
			set_theme_mod( 'custom_logo', absint( wp_unslash( $_POST['custom_logo'] ) ) );
		}
	}

	if ( 6 === $step ) {
		zoomblog_wizard_build_homepage();
		update_option( 'zoomblog_wizard_done', 1 );
	}

	$next = min( 6, $step + 1 );
	wp_safe_redirect( admin_url( 'admin.php?page=zoomblog-setup&step=' . $next ) );
	exit;
}
add_action( 'admin_init', 'zoomblog_wizard_handle' );

/**
 * Seed a few starter categories/menu so a demo has structure.
 *
 * @param string $demo Demo slug.
 */
function zoomblog_seed_demo_content( $demo ) {
	$cats = array(
		'tech'      => array( 'موبایل', 'لپ‌تاپ', 'هوش مصنوعی', 'بازی' ),
		'news'      => array( 'ایران', 'جهان', 'اقتصاد', 'ورزش' ),
		'lifestyle' => array( 'سلامت', 'سفر', 'آشپزی', 'مد' ),
		'review'    => array( 'گوشی', 'خودرو', 'گجت', 'نرم‌افزار' ),
		'podcast'   => array( 'اپیزودها', 'مصاحبه', 'ویدیو' ),
		'personal'  => array( 'یادداشت', 'تجربه' ),
	);
	$list = $cats[ $demo ] ?? $cats['tech'];
	foreach ( $list as $name ) {
		if ( ! term_exists( $name, 'category' ) ) {
			wp_insert_term( $name, 'category' );
		}
	}

	// Seed a handful of sample posts so the homepage looks complete.
	zoomblog_seed_demo_posts( $demo );
}

/**
 * Sample post body with a few headings (so the TOC has something to show).
 *
 * @param string $lead Intro sentence.
 * @return string
 */
function zoomblog_demo_body( $lead ) {
	return
		'<p>' . esc_html( $lead ) . ' این متن نمونه است و می‌توانید آن را با محتوای واقعی خود جایگزین کنید.</p>' .
		'<h2>مقدمه</h2>' .
		'<p>فناوری و رسانه با سرعت زیادی در حال تغییر هستند و درک درست آن‌ها به تصمیم‌گیری بهتر کمک می‌کند. در ادامه مهم‌ترین نکته‌ها را مرور می‌کنیم.</p>' .
		'<blockquote>کیفیت محتوا مهم‌تر از کمیت آن است؛ یک نوشتهٔ دقیق ارزش ده نوشتهٔ سطحی را دارد.</blockquote>' .
		'<h2>نکته‌های کلیدی</h2>' .
		'<p>ابتدا باید نیاز واقعی خود را بشناسیم، سپس گزینه‌های موجود را با هم مقایسه کنیم و در نهایت انتخاب آگاهانه داشته باشیم. این مسیر ساده اما اثرگذار است.</p>' .
		'<h3>جمع‌بندی</h3>' .
		'<p>در مجموع، انتخاب درست به اولویت‌های شما بستگی دارد. امیدواریم این راهنما برایتان مفید بوده باشد و در ادامه سراغ نمونه‌های بیشتری برویم.</p>';
}

/**
 * Create sample posts once (idempotent via an option flag).
 *
 * @param string $demo Demo slug.
 */
function zoomblog_seed_demo_posts( $demo ) {
	if ( get_option( 'zoomblog_sample_posts_seeded' ) ) {
		return;
	}

	$terms   = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
	$cat_ids = ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'term_id' ) : array();
	if ( empty( $cat_ids ) ) {
		$cat_ids = array( (int) get_option( 'default_category' ) );
	}

	$author = get_current_user_id();

	// title, excerpt/lead, content type, editor pick, breaking.
	$samples = array(
		array( 'هوش مصنوعی چگونه آیندهٔ کار را دگرگون می‌کند', 'نگاهی به تأثیر هوش مصنوعی بر مشاغل و مهارت‌های آینده.', 'analysis', true, false ),
		array( 'بررسی کامل پرچم‌دار تازه‌ی بازار', 'نقد و بررسی سخت‌افزار، دوربین و باتری این گوشی پرچم‌دار.', 'review', true, false ),
		array( 'بهترین لپ‌تاپ‌ها برای برنامه‌نویسی در ۱۴۰۵', 'راهنمای انتخاب لپ‌تاپ مناسب توسعه‌دهندگان با هر بودجه.', 'article', true, false ),
		array( 'راهنمای خرید هدفون بی‌سیم', 'هر آنچه پیش از خرید هدفون بی‌سیم باید بدانید.', 'article', true, false ),
		array( 'خبر فوری: عرضهٔ نسل تازهٔ پردازنده‌ها', 'نسل جدید پردازنده‌ها با جهش چشمگیر در کارایی معرفی شد.', 'news', false, true ),
		array( 'ده افزونهٔ ضروری برای افزایش بهره‌وری', 'فهرستی از ابزارهایی که جریان کاری شما را سریع‌تر می‌کنند.', 'article', false, false ),
		array( 'چرا حریم خصوصی داده‌ها اهمیت دارد', 'دیدگاهی دربارهٔ ارزش داده‌های شخصی در دنیای امروز.', 'opinion', false, false ),
		array( 'مقایسهٔ سرویس‌های ابری محبوب', 'کدام سرویس ابری برای پروژهٔ شما مناسب‌تر است؟', 'analysis', false, false ),
	);

	$i = 0;
	foreach ( $samples as $s ) {
		list( $title, $lead, $type, $pick, $breaking ) = $s;

		$post_id = wp_insert_post( array(
			'post_title'    => $title,
			'post_content'  => zoomblog_demo_body( $lead ),
			'post_excerpt'  => $lead,
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'post_author'   => $author,
			'post_category' => array( $cat_ids[ $i % count( $cat_ids ) ] ),
			'post_date'     => gmdate( 'Y-m-d H:i:s', time() - ( $i * 6 * HOUR_IN_SECONDS ) ),
		), true );

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_zoomblog_content_type', $type );
			update_post_meta( $post_id, '_zoomblog_views', wp_rand( 400, 9000 ) );
			update_post_meta( $post_id, '_zoomblog_likes', wp_rand( 5, 240 ) );
			update_post_meta( $post_id, '_zoomblog_recommend_up', wp_rand( 20, 120 ) );
			update_post_meta( $post_id, '_zoomblog_recommend_down', wp_rand( 2, 30 ) );
			if ( $pick ) {
				update_post_meta( $post_id, '_zoomblog_editor_pick', '1' );
			}
			if ( $breaking ) {
				update_post_meta( $post_id, '_zoomblog_breaking', '1' );
			}
			update_post_meta( $post_id, '_zoomblog_sample', '1' );
		}
		$i++;
	}

	update_option( 'zoomblog_sample_posts_seeded', 1 );
}

/**
 * Ensure a front page exists and is set.
 */
function zoomblog_wizard_build_homepage() {
	$front_id = (int) get_option( 'page_on_front' );
	if ( ! $front_id || 'page' !== get_post_type( $front_id ) ) {
		$front_id = wp_insert_post( array(
			'post_title'   => __( 'خانه', 'zoomblog' ),
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		) );
	}
	$blog_id = (int) get_option( 'page_for_posts' );
	if ( ! $blog_id ) {
		$blog_id = wp_insert_post( array(
			'post_title'  => __( 'مطالب', 'zoomblog' ),
			'post_status' => 'publish',
			'post_type'   => 'page',
		) );
	}
	update_option( 'show_on_front', 'posts' ); // Home template drives the front page grid.
}

/**
 * Server status checks for step 1.
 *
 * @return array<int,array{label:string,ok:bool,value:string}>
 */
function zoomblog_server_checks() {
	$uploads = wp_get_upload_dir();
	return array(
		array( 'label' => __( 'نسخهٔ PHP (۷.۴+)', 'zoomblog' ), 'ok' => version_compare( PHP_VERSION, '7.4', '>=' ), 'value' => PHP_VERSION ),
		array( 'label' => __( 'نسخهٔ وردپرس (۶.۲+)', 'zoomblog' ), 'ok' => version_compare( get_bloginfo( 'version' ), '6.2', '>=' ), 'value' => get_bloginfo( 'version' ) ),
		array( 'label' => __( 'حافظهٔ PHP (۱۲۸M+)', 'zoomblog' ), 'ok' => zoomblog_bytes( WP_MEMORY_LIMIT ) >= 134217728, 'value' => WP_MEMORY_LIMIT ),
		array( 'label' => __( 'افزونهٔ GD/تصویر', 'zoomblog' ), 'ok' => extension_loaded( 'gd' ) || extension_loaded( 'imagick' ), 'value' => extension_loaded( 'gd' ) ? 'GD' : ( extension_loaded( 'imagick' ) ? 'Imagick' : '—' ) ),
		array( 'label' => __( 'قابلیت نوشتن در uploads', 'zoomblog' ), 'ok' => wp_is_writable( $uploads['basedir'] ), 'value' => wp_is_writable( $uploads['basedir'] ) ? __( 'بله', 'zoomblog' ) : __( 'خیر', 'zoomblog' ) ),
	);
}

/**
 * Parse a shorthand byte value like "128M".
 *
 * @param string $val Shorthand.
 * @return int
 */
function zoomblog_bytes( $val ) {
	$val  = trim( (string) $val );
	if ( '' === $val ) {
		return 0;
	}
	$unit = strtolower( $val[ strlen( $val ) - 1 ] );
	$num  = (int) $val;
	switch ( $unit ) {
		case 'g':
			$num *= 1024;
			// no break.
		case 'm':
			$num *= 1024;
			// no break.
		case 'k':
			$num *= 1024;
	}
	return $num;
}

/**
 * Render the wizard.
 */
function zoomblog_render_wizard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$step  = isset( $_GET['step'] ) ? max( 1, min( 6, absint( $_GET['step'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
	$steps = array(
		1 => __( 'بررسی سرور', 'zoomblog' ),
		2 => __( 'افزونه‌ها', 'zoomblog' ),
		3 => __( 'نوع سایت', 'zoomblog' ),
		4 => __( 'دموی نمونه', 'zoomblog' ),
		5 => __( 'لوگو، رنگ، قلم', 'zoomblog' ),
		6 => __( 'ساخت خانه و پایان', 'zoomblog' ),
	);
	?>
	<div class="wrap zb-admin zb-wizard">
		<h1><?php esc_html_e( 'نصب سریع زومبلاگ', 'zoomblog' ); ?></h1>

		<ol class="zb-wizard__steps">
			<?php foreach ( $steps as $n => $label ) : ?>
				<li class="<?php echo $n === $step ? 'is-active' : ( $n < $step ? 'is-done' : '' ); ?>">
					<span class="zb-wizard__num"><?php echo esc_html( zoomblog_to_persian_digits( $n ) ); ?></span>
					<span class="zb-wizard__label"><?php echo esc_html( $label ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>

		<form method="post" class="zb-wizard__body">
			<?php wp_nonce_field( 'zoomblog_wizard', 'zoomblog_wizard_nonce' ); ?>
			<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>">

			<?php
			switch ( $step ) {
				case 1:
					echo '<h2>' . esc_html__( 'وضعیت سرور', 'zoomblog' ) . '</h2><table class="widefat striped"><tbody>';
					foreach ( zoomblog_server_checks() as $check ) {
						printf(
							'<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td></tr>',
							esc_html( $check['label'] ),
							esc_html( $check['value'] ),
							$check['ok'] ? '<span class="zb-ok">✓</span>' : '<span class="zb-bad">✕</span>'
						);
					}
					echo '</tbody></table>';
					break;

				case 2:
					echo '<h2>' . esc_html__( 'افزونه‌های پیشنهادی', 'zoomblog' ) . '</h2><div class="zb-plugins">';
					foreach ( zoomblog_wizard_plugins() as $plugin ) {
						$active = is_plugin_active( $plugin['slug'] . '/' . $plugin['slug'] . '.php' );
						$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin['slug'] ), 'install-plugin_' . $plugin['slug'] );
						echo '<div class="zb-plugin"><strong>' . esc_html( $plugin['name'] ) . '</strong><span>' . esc_html( $plugin['why'] ) . '</span>';
						if ( $active ) {
							echo '<em class="zb-ok">' . esc_html__( 'فعال', 'zoomblog' ) . '</em>';
						} else {
							echo '<a class="button" href="' . esc_url( $install_url ) . '">' . esc_html__( 'نصب', 'zoomblog' ) . '</a>';
						}
						echo '</div>';
					}
					echo '</div>';
					break;

				case 3:
					$active_demo = get_option( 'zoomblog_active_demo', '' );
					echo '<h2>' . esc_html__( 'نوع سایت را انتخاب کنید', 'zoomblog' ) . '</h2><div class="zb-demos">';
					foreach ( zoomblog_demos() as $slug => $demo ) {
						printf(
							'<label class="zb-demo %4$s"><input type="radio" name="demo" value="%1$s" %5$s><strong>%2$s</strong><span>%3$s</span></label>',
							esc_attr( $slug ),
							esc_html( $demo['title'] ),
							esc_html( $demo['desc'] ),
							$active_demo === $slug ? 'is-selected' : '',
							checked( $active_demo, $slug, false )
						);
					}
					echo '</div>';
					break;

				case 4:
					$demo = get_option( 'zoomblog_active_demo', 'tech' );
					$demos = zoomblog_demos();
					echo '<h2>' . esc_html__( 'ورود محتوای نمونه', 'zoomblog' ) . '</h2>';
					echo '<p>' . sprintf(
						/* translators: %s: demo title. */
						esc_html__( 'دستهٔ «%s» انتخاب شده است. با ادامه، دسته‌بندی‌ها و صفحات پایه ساخته می‌شوند.', 'zoomblog' ),
						esc_html( $demos[ $demo ]['title'] ?? $demo )
					) . '</p>';
					break;

				case 5:
					$opts = zoomblog_get_options();
					echo '<h2>' . esc_html__( 'ظاهر پایه', 'zoomblog' ) . '</h2>';
					echo '<p class="zb-field"><label>' . esc_html__( 'رنگ اصلی', 'zoomblog' ) . '</label><input type="text" class="zb-color" name="accent_color" value="' . esc_attr( $opts['accent_color'] ) . '"></p>';
					echo '<p class="zb-field"><label>' . esc_html__( 'قلم', 'zoomblog' ) . '</label><select name="font_family"><option value="vazirmatn"' . selected( $opts['font_family'], 'vazirmatn', false ) . '>وزیرمتن</option><option value="system"' . selected( $opts['font_family'], 'system', false ) . '>سیستم</option></select></p>';
					echo '<p class="zb-field"><label>' . esc_html__( 'حالت پیش‌فرض', 'zoomblog' ) . '</label><select name="default_mode"><option value="auto"' . selected( $opts['default_mode'], 'auto', false ) . '>خودکار</option><option value="light"' . selected( $opts['default_mode'], 'light', false ) . '>روشن</option><option value="dark"' . selected( $opts['default_mode'], 'dark', false ) . '>تیره</option></select></p>';
					echo '<p class="description">' . esc_html__( 'لوگو را می‌توانید از سفارشی‌ساز یا تنظیمات وردپرس بارگذاری کنید.', 'zoomblog' ) . '</p>';
					break;

				case 6:
					echo '<h2>' . esc_html__( 'ساخت صفحهٔ خانه', 'zoomblog' ) . '</h2>';
					echo '<p>' . esc_html__( 'با کلیک روی «پایان»، صفحهٔ خانه آماده و پیکربندی نهایی انجام می‌شود.', 'zoomblog' ) . '</p>';
					break;
			}
			?>

			<div class="zb-wizard__nav">
				<?php if ( $step > 1 ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=zoomblog-setup&step=' . ( $step - 1 ) ) ); ?>"><?php esc_html_e( 'قبلی', 'zoomblog' ); ?></a>
				<?php endif; ?>
				<button type="submit" class="button button-primary">
					<?php echo $step === 6 ? esc_html__( 'پایان', 'zoomblog' ) : esc_html__( 'بعدی', 'zoomblog' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Nudge admins to run the wizard once.
 */
function zoomblog_wizard_notice() {
	if ( get_option( 'zoomblog_wizard_done' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && false !== strpos( $screen->id, 'zoomblog-setup' ) ) {
		return;
	}
	echo '<div class="notice notice-info is-dismissible"><p>';
	echo esc_html__( 'به زومبلاگ خوش آمدید! برای راه‌اندازی سریع، ویزارد نصب را اجرا کنید.', 'zoomblog' );
	echo ' <a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=zoomblog-setup' ) ) . '">' . esc_html__( 'شروع', 'zoomblog' ) . '</a>';
	echo '</p></div>';
}
add_action( 'admin_notices', 'zoomblog_wizard_notice' );
