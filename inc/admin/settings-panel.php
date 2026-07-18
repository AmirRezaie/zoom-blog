<?php
/**
 * Searchable, grouped settings panel.
 *
 * Renders from zoomblog_settings_schema(): groups in a sidebar, fields in
 * cards, a live search box that filters fields, per-section reset, and mobile
 * overrides handled by the same store. Saves everything into one option.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the top-level ZoomBlog menu + the settings screen.
 */
function zoomblog_admin_menu() {
	add_menu_page(
		__( 'زومبلاگ', 'zoomblog' ),
		__( 'زومبلاگ', 'zoomblog' ),
		'manage_options',
		'zoomblog',
		'zoomblog_render_settings_page',
		'dashicons-superhero',
		59
	);
	add_submenu_page( 'zoomblog', __( 'تنظیمات', 'zoomblog' ), __( 'تنظیمات', 'zoomblog' ), 'manage_options', 'zoomblog', 'zoomblog_render_settings_page' );
}
add_action( 'admin_menu', 'zoomblog_admin_menu', 5 );

/**
 * Handle a settings save (POST) before the page renders.
 */
function zoomblog_handle_settings_save() {
	if ( empty( $_POST['zoomblog_settings_nonce'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zoomblog_settings_nonce'] ) ), 'zoomblog_save_settings' ) ) {
		return;
	}

	// Reset a single section?
	if ( ! empty( $_POST['zoomblog_reset_group'] ) ) {
		$group   = sanitize_key( wp_unslash( $_POST['zoomblog_reset_group'] ) );
		$schema  = zoomblog_settings_schema();
		$current = get_option( 'zoomblog_options', array() );
		$current = is_array( $current ) ? $current : array();
		$defaults = zoomblog_default_options();
		if ( isset( $schema[ $group ] ) ) {
			foreach ( array_keys( $schema[ $group ]['fields'] ) as $key ) {
				if ( isset( $defaults[ $key ] ) ) {
					$current[ $key ] = $defaults[ $key ];
				}
			}
			update_option( 'zoomblog_options', $current );
			add_settings_error( 'zoomblog', 'reset', __( 'این بخش به حالت پیش‌فرض بازگشت.', 'zoomblog' ), 'updated' );
		}
		return;
	}

	// Create sample content on demand (idempotent).
	if ( ! empty( $_POST['zoomblog_seed_samples'] ) ) {
		if ( function_exists( 'zoomblog_seed_demo_content' ) ) {
			zoomblog_seed_demo_content( get_option( 'zoomblog_active_demo', 'tech' ) );
			add_settings_error( 'zoomblog', 'seeded', __( 'دسته‌ها و نوشته‌های نمونه ساخته شدند. صفحهٔ اصلی را ببینید.', 'zoomblog' ), 'updated' );
		}
		return;
	}

	$incoming = isset( $_POST['zoomblog_options'] ) ? (array) wp_unslash( $_POST['zoomblog_options'] ) : array();
	$clean    = zoomblog_sanitize_settings( $incoming );

	// Detect CPT changes to flush rewrites once.
	$before = zoomblog_get_options();
	foreach ( array( 'cpt_podcast', 'cpt_video', 'cpt_review' ) as $cpt ) {
		if ( (int) ( $before[ $cpt ] ?? 0 ) !== (int) ( $clean[ $cpt ] ?? 0 ) ) {
			update_option( 'zoomblog_flush_rewrites', 1 );
			break;
		}
	}

	zoomblog_update_options( $clean );
	add_settings_error( 'zoomblog', 'saved', __( 'تنظیمات ذخیره شد.', 'zoomblog' ), 'updated' );
}
add_action( 'load-toplevel_page_zoomblog', 'zoomblog_handle_settings_save' );

/**
 * Sanitize an incoming settings array against the schema + defaults.
 *
 * @param array<string,mixed> $incoming Raw POST values.
 * @return array<string,mixed>
 */
function zoomblog_sanitize_settings( $incoming ) {
	$schema   = zoomblog_settings_schema();
	$defaults = zoomblog_default_options();
	$clean    = array();

	foreach ( $schema as $group ) {
		foreach ( $group['fields'] as $key => $field ) {
			$type = $field['type'];
			switch ( $type ) {
				case 'toggle':
					$clean[ $key ] = ! empty( $incoming[ $key ] );
					break;
				case 'color':
					$clean[ $key ] = isset( $incoming[ $key ] ) ? ( sanitize_hex_color( $incoming[ $key ] ) ?: $defaults[ $key ] ) : $defaults[ $key ];
					break;
				case 'number':
					$clean[ $key ] = isset( $incoming[ $key ] ) ? floatval( $incoming[ $key ] ) : $defaults[ $key ];
					break;
				case 'select':
					$choices = array_map( 'strval', array_keys( $field['choices'] ) );
					$val     = isset( $incoming[ $key ] ) ? (string) $incoming[ $key ] : '';
					$clean[ $key ] = in_array( $val, $choices, true ) ? $val : $defaults[ $key ];
					break;
				case 'textarea':
					$clean[ $key ] = isset( $incoming[ $key ] ) ? wp_kses_post( $incoming[ $key ] ) : '';
					break;
				case 'text':
				default:
					$clean[ $key ] = isset( $incoming[ $key ] ) ? sanitize_text_field( $incoming[ $key ] ) : '';
			}
		}
	}
	return $clean;
}

/**
 * Render one field control.
 *
 * @param string $key   Field key.
 * @param array  $field Field schema.
 * @param mixed  $value Current value.
 */
function zoomblog_render_field( $key, $field, $value ) {
	$name  = 'zoomblog_options[' . esc_attr( $key ) . ']';
	$id    = 'zb-field-' . esc_attr( $key );
	$label = esc_html( $field['label'] );
	$desc  = isset( $field['desc'] ) ? esc_html( $field['desc'] ) : '';

	echo '<div class="zb-field" data-search="' . esc_attr( mb_strtolower( $field['label'] . ' ' . ( $field['desc'] ?? '' ) ) ) . '">';
	echo '<div class="zb-field__label"><label for="' . $id . '">' . $label . '</label>';
	if ( $desc ) {
		echo '<span class="zb-field__desc">' . $desc . '</span>';
	}
	echo '</div>';
	echo '<div class="zb-field__control">';

	switch ( $field['type'] ) {
		case 'toggle':
			printf(
				'<label class="zb-switch"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s data-zb-preview="%4$s"><span class="zb-switch__track"></span></label>',
				$id, esc_attr( $name ), checked( (bool) $value, true, false ), esc_attr( $key )
			);
			break;
		case 'color':
			printf(
				'<input type="text" class="zb-color" id="%1$s" name="%2$s" value="%3$s" data-zb-preview="%4$s">',
				$id, esc_attr( $name ), esc_attr( $value ), esc_attr( $key )
			);
			break;
		case 'number':
			printf(
				'<input type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s" data-zb-preview="%7$s">',
				$id, esc_attr( $name ), esc_attr( $value ),
				esc_attr( $field['min'] ?? '' ), esc_attr( $field['max'] ?? '' ), esc_attr( $field['step'] ?? '1' ), esc_attr( $key )
			);
			break;
		case 'select':
			printf( '<select id="%1$s" name="%2$s" data-zb-preview="%3$s">', $id, esc_attr( $name ), esc_attr( $key ) );
			foreach ( $field['choices'] as $ck => $cl ) {
				printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $ck ), selected( (string) $value, (string) $ck, false ), esc_html( $cl ) );
			}
			echo '</select>';
			break;
		case 'textarea':
			printf( '<textarea id="%1$s" name="%2$s" rows="4">%3$s</textarea>', $id, esc_attr( $name ), esc_textarea( $value ) );
			break;
		default:
			printf( '<input type="text" id="%1$s" name="%2$s" value="%3$s">', $id, esc_attr( $name ), esc_attr( $value ) );
	}

	echo '</div></div>';
}

/**
 * Render the settings page.
 */
function zoomblog_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$schema  = zoomblog_settings_schema();
	$options = zoomblog_get_options();
	$groups  = array_keys( $schema );
	$active  = $groups[0];
	?>
	<div class="wrap zb-admin" id="zb-settings">
		<h1 class="zb-admin__title"><?php esc_html_e( 'تنظیمات زومبلاگ', 'zoomblog' ); ?></h1>
		<?php settings_errors( 'zoomblog' ); ?>

		<div class="zb-admin__searchbar">
			<input type="search" id="zb-search" placeholder="<?php esc_attr_e( 'جست‌وجو در تنظیمات…', 'zoomblog' ); ?>">
		</div>

		<form method="post" action="" class="zb-admin__layout">
			<?php wp_nonce_field( 'zoomblog_save_settings', 'zoomblog_settings_nonce' ); ?>

			<nav class="zb-admin__nav" aria-label="<?php esc_attr_e( 'گروه‌های تنظیمات', 'zoomblog' ); ?>">
				<ul>
					<?php foreach ( $schema as $gkey => $group ) : ?>
						<li>
							<button type="button" class="zb-navitem <?php echo $gkey === $active ? 'is-active' : ''; ?>" data-group="<?php echo esc_attr( $gkey ); ?>">
								<span class="dashicons dashicons-<?php echo esc_attr( $group['icon'] ); ?>"></span>
								<?php echo esc_html( $group['title'] ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<div class="zb-admin__panels">
				<?php foreach ( $schema as $gkey => $group ) : ?>
					<section class="zb-panel <?php echo $gkey === $active ? 'is-active' : ''; ?>" data-group="<?php echo esc_attr( $gkey ); ?>">
						<header class="zb-panel__head">
							<h2><?php echo esc_html( $group['title'] ); ?></h2>
							<button type="submit" name="zoomblog_reset_group" value="<?php echo esc_attr( $gkey ); ?>" class="button-link zb-panel__reset" onclick="return confirm('<?php echo esc_js( __( 'این بخش به پیش‌فرض بازگردد؟', 'zoomblog' ) ); ?>');">
								<?php esc_html_e( 'بازنشانی این بخش', 'zoomblog' ); ?>
							</button>
						</header>
						<div class="zb-panel__fields">
							<?php
							foreach ( $group['fields'] as $key => $field ) {
								zoomblog_render_field( $key, $field, $options[ $key ] ?? '' );
							}
							?>
						</div>
					</section>
				<?php endforeach; ?>

				<p class="zb-admin__actions">
					<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'ذخیرهٔ تنظیمات', 'zoomblog' ); ?></button>
					<button type="submit" name="zoomblog_seed_samples" value="1" class="button"><?php esc_html_e( 'ساخت محتوای نمونه', 'zoomblog' ); ?></button>
					<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php esc_html_e( 'مشاهدهٔ سایت', 'zoomblog' ); ?></a>
				</p>
				<p class="zb-admin__noresults" hidden><?php esc_html_e( 'موردی برای این جست‌وجو پیدا نشد.', 'zoomblog' ); ?></p>
			</div>
		</form>
	</div>
	<?php
}
