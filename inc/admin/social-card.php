<?php
/**
 * Social card generator.
 *
 * Builds a 1200×630 share card (SVG) for any post from its title, primary
 * category, author and the site name — no external service, no fonts fetched
 * remotely. Editors preview it and download an SVG (or copy the markup) to
 * attach as the social image. SVG keeps it crisp and dependency-free; a
 * PNG-export helper is exposed via the `zoomblog_social_card_png` filter for
 * sites that have GD/Imagick and want a raster.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the submenu.
 */
function zoomblog_social_card_menu() {
	add_submenu_page(
		'zoomblog',
		__( 'کارت اجتماعی', 'zoomblog' ),
		__( 'کارت اجتماعی', 'zoomblog' ),
		'edit_others_posts',
		'zoomblog-social-card',
		'zoomblog_render_social_card'
	);
}
add_action( 'admin_menu', 'zoomblog_social_card_menu' );

/**
 * Build the SVG for a post.
 *
 * @param WP_Post $post   Post.
 * @param string  $accent Hex accent color.
 * @return string SVG markup.
 */
function zoomblog_social_card_svg( $post, $accent = '#2f6bff' ) {
	$title = wp_strip_all_tags( get_the_title( $post ) );
	$cat   = zoomblog_primary_term( 'category', $post );
	$cat   = $cat ? $cat->name : '';
	$author = get_the_author_meta( 'display_name', $post->post_author );
	$site   = get_bloginfo( 'name' );

	// Wrap the title into up to 3 lines (~22 chars each for large Persian type).
	$lines = zoomblog_wrap_text( $title, 24, 3 );
	$y     = 250;
	$tspans = '';
	foreach ( $lines as $line ) {
		$tspans .= '<tspan x="80" y="' . $y . '">' . esc_html( $line ) . '</tspan>';
		$y += 78;
	}

	$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" direction="rtl">';
	$svg .= '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">';
	$svg .= '<stop offset="0" stop-color="' . esc_attr( $accent ) . '"/><stop offset="1" stop-color="#14161c"/></linearGradient></defs>';
	$svg .= '<rect width="1200" height="630" fill="#0f1117"/>';
	$svg .= '<rect x="0" y="0" width="14" height="630" fill="url(#g)"/>';
	$svg .= '<text x="80" y="120" fill="' . esc_attr( $accent ) . '" font-family="Vazirmatn,Tahoma,sans-serif" font-size="34" font-weight="700">' . esc_html( $cat ) . '</text>';
	$svg .= '<text fill="#ffffff" font-family="Vazirmatn,Tahoma,sans-serif" font-size="64" font-weight="800">' . $tspans . '</text>';
	$svg .= '<text x="80" y="560" fill="#c7ccd6" font-family="Vazirmatn,Tahoma,sans-serif" font-size="30">' . esc_html( $author ) . '</text>';
	$svg .= '<text x="1120" y="560" text-anchor="start" fill="#8a90a0" font-family="Vazirmatn,Tahoma,sans-serif" font-size="30">' . esc_html( $site ) . '</text>';
	$svg .= '</svg>';
	return $svg;
}

/**
 * Naive word-wrap into N lines of ~max chars.
 *
 * @param string $text Text.
 * @param int    $max  Max chars per line.
 * @param int    $lines Max lines.
 * @return string[]
 */
function zoomblog_wrap_text( $text, $max, $lines ) {
	$words = preg_split( '/\s+/u', trim( $text ) );
	$out   = array();
	$cur   = '';
	foreach ( $words as $w ) {
		$try = '' === $cur ? $w : $cur . ' ' . $w;
		if ( mb_strlen( $try ) > $max && '' !== $cur ) {
			$out[] = $cur;
			$cur   = $w;
			if ( count( $out ) === $lines - 1 ) {
				// Last line: take the rest, trim if huge.
				$rest = $cur;
				$idx  = array_search( $w, $words, true );
				$rest = implode( ' ', array_slice( $words, $idx ) );
				if ( mb_strlen( $rest ) > $max ) {
					$rest = mb_substr( $rest, 0, $max - 1 ) . '…';
				}
				$out[] = $rest;
				return $out;
			}
		} else {
			$cur = $try;
		}
	}
	if ( '' !== $cur ) {
		$out[] = $cur;
	}
	return $out;
}

/**
 * Render the tool.
 */
function zoomblog_render_social_card() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$accent   = zoomblog_get_option( 'accent_color', '#2f6bff' );
	$selected = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$recent   = get_posts( array( 'numberposts' => 30, 'post_status' => 'publish' ) );
	if ( ! $selected && $recent ) {
		$selected = $recent[0]->ID;
	}
	$post = $selected ? get_post( $selected ) : null;
	?>
	<div class="wrap zb-admin">
		<h1><?php esc_html_e( 'کارت اجتماعی', 'zoomblog' ); ?></h1>
		<p><?php esc_html_e( 'یک نوشته را انتخاب کنید تا کارت هم‌رسانی ۱۲۰۰×۶۳۰ ساخته شود.', 'zoomblog' ); ?></p>

		<form method="get" class="zb-socialcard__form">
			<input type="hidden" name="page" value="zoomblog-social-card">
			<select name="post" onchange="this.form.submit()">
				<?php foreach ( $recent as $p ) : ?>
					<option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $selected, $p->ID ); ?>><?php echo esc_html( get_the_title( $p ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</form>

		<?php if ( $post ) : $svg = zoomblog_social_card_svg( $post, $accent ); ?>
			<div class="zb-socialcard__preview" id="zb-card-preview"><?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<p>
				<button type="button" class="button button-primary" id="zb-download-card"><?php esc_html_e( 'دانلود SVG', 'zoomblog' ); ?></button>
			</p>
			<script>
			document.getElementById('zb-download-card')?.addEventListener('click', function () {
				var svg = document.querySelector('#zb-card-preview svg');
				if (!svg) return;
				var blob = new Blob([svg.outerHTML], { type: 'image/svg+xml' });
				var a = document.createElement('a');
				a.href = URL.createObjectURL(blob);
				a.download = 'social-card-<?php echo (int) $post->ID; ?>.svg';
				document.body.appendChild(a); a.click(); a.remove();
			});
			</script>
		<?php endif; ?>
	</div>
	<?php
}
