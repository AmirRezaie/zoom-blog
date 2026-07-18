<?php
/**
 * Jalali (Persian / Shamsi) calendar.
 *
 * Self-contained Gregorian → Jalali conversion (no external deps) plus a
 * date() style formatter that understands the common format characters and
 * renders Persian month/day names and digits.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert a Gregorian date to Jalali.
 *
 * @param int $gy Gregorian year.
 * @param int $gm Gregorian month (1-12).
 * @param int $gd Gregorian day.
 * @return array{0:int,1:int,2:int} [jy, jm, jd]
 */
function zoomblog_gregorian_to_jalali( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );

	$gy2 = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days = 355666 + ( 365 * $gy ) + ( (int) ( ( $gy2 + 3 ) / 4 ) )
		- ( (int) ( ( $gy2 + 99 ) / 100 ) )
		+ ( (int) ( ( $gy2 + 399 ) / 400 ) )
		+ $gd + $g_d_m[ $gm - 1 ];

	$jy = -1595 + ( 33 * ( (int) ( $days / 12053 ) ) );
	$days %= 12053;

	$jy += 4 * ( (int) ( $days / 1461 ) );
	$days %= 1461;

	if ( $days > 365 ) {
		$jy += (int) ( ( $days - 1 ) / 365 );
		$days = ( $days - 1 ) % 365;
	}

	if ( $days < 186 ) {
		$jm = 1 + (int) ( $days / 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + (int) ( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}

	return array( $jy, $jm, $jd );
}

/**
 * Persian month names.
 *
 * @return array<int,string>
 */
function zoomblog_jalali_months() {
	return array(
		1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
		5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
		9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
	);
}

/**
 * Persian weekday names, keyed by PHP `w` (0 = Sunday).
 *
 * @return array<int,string>
 */
function zoomblog_jalali_weekdays() {
	return array(
		0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه',
		4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه',
	);
}

/**
 * Format a timestamp as a Jalali date string.
 *
 * Supported format chars: Y y m n d j F M l D H G i s a A. Everything else
 * is passed through literally. Digits are rendered in Persian.
 *
 * @param string $format    A date()-style format.
 * @param int    $timestamp Unix timestamp (site local via WP).
 * @param bool   $persian_digits Convert digits to Persian.
 * @return string
 */
function zoomblog_jalali_format( $format, $timestamp = null, $persian_digits = true ) {
	if ( null === $timestamp ) {
		$timestamp = time();
	}

	$gy = (int) wp_date( 'Y', $timestamp );
	$gm = (int) wp_date( 'n', $timestamp );
	$gd = (int) wp_date( 'j', $timestamp );
	$w  = (int) wp_date( 'w', $timestamp );

	list( $jy, $jm, $jd ) = zoomblog_gregorian_to_jalali( $gy, $gm, $gd );

	$months   = zoomblog_jalali_months();
	$weekdays = zoomblog_jalali_weekdays();

	$out    = '';
	$length = strlen( $format );
	for ( $i = 0; $i < $length; $i++ ) {
		$c = $format[ $i ];
		switch ( $c ) {
			case '\\':
				$i++;
				$out .= isset( $format[ $i ] ) ? $format[ $i ] : '';
				break;
			case 'Y':
				$out .= $jy;
				break;
			case 'y':
				$out .= substr( (string) $jy, -2 );
				break;
			case 'm':
				$out .= str_pad( (string) $jm, 2, '0', STR_PAD_LEFT );
				break;
			case 'n':
				$out .= $jm;
				break;
			case 'd':
				$out .= str_pad( (string) $jd, 2, '0', STR_PAD_LEFT );
				break;
			case 'j':
				$out .= $jd;
				break;
			case 'F':
			case 'M':
				$out .= $months[ $jm ];
				break;
			case 'l':
			case 'D':
				$out .= $weekdays[ $w ];
				break;
			case 'H':
			case 'G':
			case 'i':
			case 's':
			case 'a':
			case 'A':
				$out .= wp_date( $c, $timestamp );
				break;
			default:
				$out .= $c;
		}
	}

	if ( $persian_digits ) {
		$out = zoomblog_to_persian_digits( $out );
	}
	return $out;
}

/**
 * Filter WordPress date output to Jalali when enabled.
 *
 * @param string      $the_date The formatted date.
 * @param string      $format   Requested format.
 * @param int|WP_Post $post     Post.
 * @return string
 */
function zoomblog_filter_get_the_date( $the_date, $format, $post = null ) {
	if ( ! zoomblog_is_on( 'jalali_dates' ) ) {
		return $the_date;
	}
	$post = get_post( $post );
	if ( ! $post ) {
		return $the_date;
	}
	$format = $format ? $format : get_option( 'date_format' );
	return zoomblog_jalali_format( $format, get_post_time( 'U', true, $post ) );
}
add_filter( 'get_the_date', 'zoomblog_filter_get_the_date', 10, 3 );

/**
 * Same for get_the_time when a date-ish format is used.
 *
 * @param string      $the_time The formatted time.
 * @param string      $format   Requested format.
 * @param int|WP_Post $post     Post.
 * @return string
 */
function zoomblog_filter_get_the_time( $the_time, $format, $post = null ) {
	if ( ! zoomblog_is_on( 'jalali_dates' ) ) {
		return $the_time;
	}
	$format = $format ? $format : get_option( 'time_format' );
	// Only reformat when the format references date parts.
	if ( ! preg_match( '/[YyFMmnDljS]/', $format ) ) {
		return zoomblog_to_persian_digits( $the_time );
	}
	$post = get_post( $post );
	if ( ! $post ) {
		return $the_time;
	}
	return zoomblog_jalali_format( $format, get_post_time( 'U', true, $post ) );
}
add_filter( 'get_the_time', 'zoomblog_filter_get_the_time', 10, 3 );

/**
 * Human-friendly "x ago" in Persian for recent posts, else Jalali date.
 *
 * @param int|WP_Post|null $post Post.
 * @return string
 */
function zoomblog_relative_or_jalali( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$ts   = get_post_time( 'U', true, $post );
	$diff = time() - $ts;
	if ( $diff < DAY_IN_SECONDS ) {
		$human = human_time_diff( $ts, time() );
		/* translators: %s: human time difference, e.g. "2 hours". */
		return zoomblog_to_persian_digits( sprintf( __( '%s پیش', 'zoomblog' ), $human ) );
	}
	return zoomblog_jalali_format( get_option( 'date_format' ), $ts );
}
