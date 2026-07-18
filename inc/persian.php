<?php
/**
 * Persian text engine.
 *
 * Three independent, toggleable passes:
 *   1. Unify — ي/ك (Arabic) → ی/ک (Persian), Arabic digits, tatweel.
 *   2. Half-space — apply ZWNJ (nim-fasele) to common prefixes/suffixes.
 *   3. Near-word — a small correction dictionary (heavier; off by default).
 *
 * Passes run on display filters so stored content is never mutated. Each
 * pass is skipped entirely when its option is off, so the fast path stays
 * fast. Content inside <pre>/<code>/<script>/<style> is protected.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Zero-width non-joiner (نیم‌فاصله). */
define( 'ZOOMBLOG_ZWNJ', "\xE2\x80\x8C" );

/**
 * Unify Arabic characters to their Persian equivalents.
 *
 * @param string $text Input.
 * @return string
 */
function zoomblog_fa_unify( $text ) {
	$map = array(
		// Letters.
		'ي' => 'ی',
		'ك' => 'ک',
		'ﻙ' => 'ک',
		'ﻚ' => 'ک',
		'ﻛ' => 'ک',
		'ﻜ' => 'ک',
		'ة' => 'ه',
		'ۀ' => 'هٔ',
		// Arabic-Indic digits → Persian digits.
		'٠' => '۰', '١' => '۱', '٢' => '۲', '٣' => '۳', '٤' => '۴',
		'٥' => '۵', '٦' => '۶', '٧' => '۷', '٨' => '۸', '٩' => '۹',
		// Punctuation niceties.
		'‏' => '', // RTL mark noise.
	);
	$text = strtr( $text, $map );
	// Remove tatweel/kashida.
	$text = str_replace( 'ـ', '', $text );
	return $text;
}

/**
 * Apply half-space (ZWNJ) to common Persian affixes.
 *
 * @param string $text Input.
 * @return string
 */
function zoomblog_fa_halfspace( $text ) {
	$z = ZOOMBLOG_ZWNJ;

	// Collapse any existing ZWNJ surrounded by spaces to a single ZWNJ.
	$text = preg_replace( '/ *' . $z . ' */u', $z, $text );

	// Prefix: (ن)می + space → (ن)می‌  when followed by a Persian letter.
	$text = preg_replace( '/(^|[\s\x{200C}(«"])(ن?می) (?=[آ-ی])/u', '$1$2' . $z, $text );

	// Suffixes: space + suffix + word-boundary → ZWNJ + suffix.
	$suffixes = array(
		'های', 'هایی', 'هایم', 'هایت', 'هایش', 'هایمان', 'هایتان', 'هایشان',
		'ها', 'تر', 'تری', 'ترین', 'ام', 'ات', 'اش', 'مان', 'تان', 'شان',
		'گر', 'گری', 'وار',
	);
	foreach ( $suffixes as $s ) {
		// Only when the suffix stands as its own token (space before, space/punct/end after).
		$text = preg_replace(
			'/(?<=[آ-ی]) (' . preg_quote( $s, '/' ) . ')(?=[\s\.،,؛;:!؟\?»")\]]|$)/u',
			$z . '$1',
			$text
		);
	}

	return $text;
}

/**
 * Near-word correction dictionary (opt-in, heavier).
 *
 * A conservative list of very common Persian spacing/spelling slips. Kept
 * small on purpose; extend via the `zoomblog_nearword_dictionary` filter.
 *
 * @param string $text Input.
 * @return string
 */
function zoomblog_fa_nearword( $text ) {
	$dict = apply_filters(
		'zoomblog_nearword_dictionary',
		array(
			'می باشد'   => 'است',
			'میباشد'    => 'است',
			'بوسیله'    => 'به‌وسیله',
			'بعبارت'    => 'به‌عبارت',
			'بعنوان'    => 'به‌عنوان',
			'اینکه'     => 'این‌که',
			'آنکه'      => 'آن‌که',
			'همینطور'   => 'همین‌طور',
			'بهمین'     => 'به‌همین',
			'درصورتیکه' => 'در صورتی که',
			'نمیتوان'   => 'نمی‌توان',
		)
	);
	return strtr( $text, $dict );
}

/**
 * Run the enabled passes over a chunk of plain (non-tag) text.
 *
 * @param string $text Text node content.
 * @return string
 */
function zoomblog_fa_apply_passes( $text ) {
	if ( '' === trim( $text ) ) {
		return $text;
	}
	if ( zoomblog_is_on( 'persian_unify' ) ) {
		$text = zoomblog_fa_unify( $text );
	}
	if ( zoomblog_is_on( 'persian_nearword' ) ) {
		$text = zoomblog_fa_nearword( $text );
	}
	if ( zoomblog_is_on( 'persian_halfspace' ) ) {
		$text = zoomblog_fa_halfspace( $text );
	}
	return $text;
}

/**
 * Filter that only touches text nodes, leaving code/markup intact.
 *
 * @param string $content HTML content.
 * @return string
 */
function zoomblog_fa_filter_html( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}
	// Fast exit if no pass is enabled.
	if ( ! zoomblog_is_on( 'persian_unify' )
		&& ! zoomblog_is_on( 'persian_halfspace' )
		&& ! zoomblog_is_on( 'persian_nearword' ) ) {
		return $content;
	}

	// Protect code-ish blocks: split on them and process only the outside parts.
	$parts = preg_split(
		'/(<(?:pre|code|script|style|kbd|samp)[\s\S]*?<\/(?:pre|code|script|style|kbd|samp)>)/i',
		$content,
		-1,
		PREG_SPLIT_DELIM_CAPTURE
	);
	if ( ! is_array( $parts ) ) {
		return $content;
	}

	$out = '';
	foreach ( $parts as $part ) {
		if ( '' === $part ) {
			continue;
		}
		// Protected block — leave as-is.
		if ( '<' === $part[0] && preg_match( '/^<(?:pre|code|script|style|kbd|samp)[\s>]/i', $part ) ) {
			$out .= $part;
			continue;
		}
		// Process text between tags, but not inside tag attributes.
		$tokens = preg_split( '/(<[^>]+>)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $tokens as $tok ) {
			if ( '' === $tok ) {
				continue;
			}
			if ( '<' === $tok[0] ) {
				$out .= $tok; // A tag — untouched.
			} else {
				$out .= zoomblog_fa_apply_passes( $tok );
			}
		}
	}
	return $out;
}

/**
 * Filter for short plain strings (titles, etc.) — no HTML splitting needed.
 *
 * @param string $text Input.
 * @return string
 */
function zoomblog_fa_filter_plain( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return $text;
	}
	return zoomblog_fa_apply_passes( $text );
}

// Display filters (never mutate stored data).
add_filter( 'the_content', 'zoomblog_fa_filter_html', 8 );
add_filter( 'the_excerpt', 'zoomblog_fa_filter_html', 8 );
add_filter( 'the_title', 'zoomblog_fa_filter_plain', 8 );
add_filter( 'widget_text_content', 'zoomblog_fa_filter_html', 8 );
add_filter( 'comment_text', 'zoomblog_fa_filter_html', 8 );

/**
 * Helper: convert Western/Arabic digits in a string to Persian digits.
 * Used by dates and counters regardless of the content passes.
 *
 * @param string|int $value Input.
 * @return string
 */
function zoomblog_to_persian_digits( $value ) {
	$western = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$persian = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	return str_replace( $western, $persian, (string) $value );
}
