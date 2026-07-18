<?php
/**
 * Template tags — presentational helpers used across templates & parts.
 *
 * Every echoing function here escapes its own output.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Breadcrumb. Defers to Yoast / Rank Math when available, otherwise builds
 * a lightweight one. Prints nothing on the front page.
 */
function zoomblog_breadcrumb() {
	if ( is_front_page() ) {
		return;
	}

	// Prefer SEO plugin breadcrumbs (they add their own schema).
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		yoast_breadcrumb( '<nav class="zb-breadcrumb" aria-label="' . esc_attr__( 'مسیر', 'zoomblog' ) . '">', '</nav>' );
		return;
	}
	if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
		echo '<nav class="zb-breadcrumb" aria-label="' . esc_attr__( 'مسیر', 'zoomblog' ) . '">';
		rank_math_the_breadcrumbs();
		echo '</nav>';
		return;
	}

	$items   = array();
	$items[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'خانه', 'zoomblog' ) . '</a>';

	if ( is_singular( 'post' ) ) {
		$term = zoomblog_primary_term( 'category' );
		if ( $term ) {
			$items[] = '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
		}
		$items[] = '<span aria-current="page">' . esc_html( get_the_title() ) . '</span>';
	} elseif ( is_singular() ) {
		$items[] = '<span aria-current="page">' . esc_html( get_the_title() ) . '</span>';
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$items[] = '<span aria-current="page">' . esc_html( single_term_title( '', false ) ) . '</span>';
	} elseif ( is_author() ) {
		$items[] = '<span aria-current="page">' . esc_html( get_the_author() ) . '</span>';
	} elseif ( is_search() ) {
		$items[] = '<span aria-current="page">' . esc_html__( 'جست‌وجو', 'zoomblog' ) . '</span>';
	} elseif ( is_archive() ) {
		$items[] = '<span aria-current="page">' . esc_html( get_the_archive_title() ) . '</span>';
	}

	echo '<nav class="zb-breadcrumb" aria-label="' . esc_attr__( 'مسیر', 'zoomblog' ) . '"><ol>';
	foreach ( $items as $item ) {
		echo '<li>' . wp_kses_post( $item ) . '</li>';
	}
	echo '</ol></nav>';
}

/**
 * The content-type badge (part of the trust layer).
 *
 * @param int|WP_Post|null $post Post.
 */
function zoomblog_type_badge( $post = null ) {
	$type = zoomblog_content_type( $post );
	printf(
		'<span class="zb-badge zb-badge--%1$s">%2$s</span>',
		esc_attr( $type['key'] ),
		esc_html( $type['label'] )
	);
}

/**
 * Compact post meta row for cards (author, date, reading time, type).
 *
 * @param array $args Toggle which pieces to show.
 */
function zoomblog_card_meta( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'author'  => true,
		'date'    => true,
		'reading' => true,
		'views'   => false,
		'type'    => true,
	) );

	echo '<div class="zb-meta">';
	if ( $args['type'] ) {
		zoomblog_type_badge();
	}
	if ( $args['author'] ) {
		printf(
			'<a class="zb-meta__author" href="%s">%s</a>',
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			esc_html( get_the_author() )
		);
	}
	if ( $args['date'] ) {
		printf(
			'<time class="zb-meta__date" datetime="%s">%s</time>',
			esc_attr( get_the_date( 'c' ) ),
			esc_html( zoomblog_relative_or_jalali() )
		);
	}
	if ( $args['reading'] && zoomblog_is_on( 'show_reading_time' ) ) {
		printf( '<span class="zb-meta__reading">%s</span>', esc_html( zoomblog_reading_time() ) );
	}
	if ( $args['views'] ) {
		printf(
			'<span class="zb-meta__views">%s %s</span>',
			esc_html( zoomblog_format_count( zoomblog_views( get_the_ID() ) ) ),
			esc_html__( 'بازدید', 'zoomblog' )
		);
	}
	echo '</div>';
}

/**
 * "Old post" trust warning.
 *
 * @param int|WP_Post|null $post Post.
 */
function zoomblog_old_post_notice( $post = null ) {
	if ( ! zoomblog_is_old_post( $post ) ) {
		return;
	}
	$months = (int) zoomblog_get_option( 'old_post_months', 18 );
	echo '<div class="zb-old-notice" role="note">';
	echo '<span class="zb-old-notice__icon" aria-hidden="true">⏳</span>';
	printf(
		/* translators: %s: date. */
		esc_html__( 'این نوشته در %s منتشر شده و ممکن است بخشی از اطلاعات آن به‌روز نباشد.', 'zoomblog' ),
		esc_html( get_the_date() )
	);
	echo '</div>';
	unset( $months );
}

/**
 * Sponsored / disclosure notice.
 *
 * @param int|WP_Post|null $post Post.
 */
function zoomblog_disclosure_notice( $post = null ) {
	$type = zoomblog_content_type( $post );
	if ( 'sponsored' !== $type['key'] ) {
		return;
	}
	echo '<div class="zb-disclosure" role="note">';
	echo esc_html__( 'این محتوا رپورتاژ آگهی است و دیدگاه تحریریه محسوب نمی‌شود.', 'zoomblog' );
	echo '</div>';
}

/**
 * Non-intrusive share bar.
 *
 * @param int|WP_Post|null $post Post.
 */
function zoomblog_share_bar( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$url   = rawurlencode( get_permalink( $post ) );
	$title = rawurlencode( get_the_title( $post ) );
	$svcs  = (array) zoomblog_get_option( 'share_services', array( 'telegram', 'twitter', 'whatsapp', 'copy' ) );

	$map = array(
		'telegram' => array( 'https://t.me/share/url?url=' . $url . '&text=' . $title, 'تلگرام' ),
		'twitter'  => array( 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title, 'ایکس' ),
		'whatsapp' => array( 'https://api.whatsapp.com/send?text=' . $title . '%20' . $url, 'واتساپ' ),
		'linkedin' => array( 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url, 'لینکدین' ),
	);

	echo '<div class="zb-share" aria-label="' . esc_attr__( 'هم‌رسانی', 'zoomblog' ) . '">';
	foreach ( $svcs as $svc ) {
		if ( 'copy' === $svc ) {
			printf(
				'<button type="button" class="zb-share__btn zb-share__copy" data-url="%s" aria-label="%s">%s</button>',
				esc_url( get_permalink( $post ) ),
				esc_attr__( 'کپی پیوند', 'zoomblog' ),
				esc_html__( 'کپی', 'zoomblog' )
			);
			continue;
		}
		if ( isset( $map[ $svc ] ) ) {
			printf(
				'<a class="zb-share__btn zb-share__%1$s" href="%2$s" target="_blank" rel="noopener nofollow" aria-label="%3$s">%3$s</a>',
				esc_attr( $svc ),
				esc_url( $map[ $svc ][0] ),
				esc_attr( $map[ $svc ][1] )
			);
		}
	}
	echo '</div>';
}

/**
 * Like button.
 *
 * @param int|WP_Post|null $post Post.
 */
function zoomblog_like_button( $post = null ) {
	if ( ! zoomblog_is_on( 'enable_like' ) ) {
		return;
	}
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	printf(
		'<button type="button" class="zb-like" data-post-id="%1$d" aria-pressed="false"><span class="zb-like__icon" aria-hidden="true">♥</span> <span class="zb-like__count">%2$s</span></button>',
		(int) $post->ID,
		esc_html( zoomblog_format_count( zoomblog_likes( $post->ID ) ) )
	);
}

/**
 * Bookmark button (client-side; syncs to server for logged-in users).
 *
 * @param int|WP_Post|null $post Post.
 */
function zoomblog_bookmark_button( $post = null ) {
	if ( ! zoomblog_is_on( 'enable_bookmark' ) ) {
		return;
	}
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	printf(
		'<button type="button" class="zb-bookmark" data-post-id="%1$d" aria-pressed="false" aria-label="%2$s"><span aria-hidden="true">🔖</span></button>',
		(int) $post->ID,
		esc_attr__( 'ذخیره', 'zoomblog' )
	);
}

/**
 * Follow-author button.
 *
 * @param int $author_id Author user ID.
 */
function zoomblog_follow_button( $author_id ) {
	if ( ! zoomblog_is_on( 'enable_follow' ) ) {
		return;
	}
	$author_id = (int) $author_id;
	if ( ! $author_id ) {
		return;
	}
	printf(
		'<button type="button" class="zb-follow" data-author-id="%1$d" aria-pressed="false">%2$s</button>',
		$author_id,
		esc_html__( 'دنبال‌کردن', 'zoomblog' )
	);
}

/**
 * Recommend widget ("do you recommend this?").
 *
 * @param int|WP_Post|null $post Post.
 */
function zoomblog_recommend_widget( $post = null ) {
	if ( ! zoomblog_is_on( 'enable_recommend' ) ) {
		return;
	}
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$data = zoomblog_recommend( $post->ID );
	echo '<div class="zb-recommend" data-post-id="' . (int) $post->ID . '">';
	echo '<div class="zb-recommend__head">';
	echo '<strong>' . esc_html__( 'این نوشته را پیشنهاد می‌کنید؟', 'zoomblog' ) . '</strong>';
	printf(
		'<span class="zb-recommend__stat"><b class="zb-recommend__percent">%s٪</b> <small>%s رأی</small></span>',
		esc_html( zoomblog_to_persian_digits( $data['percent'] ) ),
		esc_html( zoomblog_to_persian_digits( $data['votes'] ) )
	);
	echo '</div>';
	echo '<div class="zb-recommend__bar"><span style="width:' . (int) $data['percent'] . '%"></span></div>';
	echo '<div class="zb-recommend__actions">';
	echo '<button type="button" class="zb-recommend__btn" data-vote="up">' . esc_html__( 'بله', 'zoomblog' ) . '</button>';
	echo '<button type="button" class="zb-recommend__btn" data-vote="down">' . esc_html__( 'خیر', 'zoomblog' ) . '</button>';
	echo '</div>';
	echo '</div>';
}

/**
 * Extract H2/H3 headings from content with deterministic anchor slugs.
 *
 * The slug algorithm here is shared by the id-injector and the renderer, so
 * anchors always match regardless of which runs first.
 *
 * @param string $content Post content HTML.
 * @return array<int,array{level:int,text:string,slug:string,raw:string,attrs:string,inner:string}>
 */
function zoomblog_extract_headings( $content ) {
	if ( ! preg_match_all( '/<h([23])(.*?)>(.*?)<\/h\1>/is', $content, $m, PREG_SET_ORDER ) ) {
		return array();
	}
	$out  = array();
	$used = array();
	foreach ( $m as $heading ) {
		$text = trim( wp_strip_all_tags( $heading[3] ) );
		if ( '' === $text ) {
			continue;
		}
		// Honour an existing id.
		if ( preg_match( '/id=["\']([^"\']+)["\']/', $heading[2], $idm ) ) {
			$slug = $idm[1];
		} else {
			$slug = sanitize_title( $text );
			$slug = 'zb-' . ( '' === $slug ? 'section' : $slug );
			if ( isset( $used[ $slug ] ) ) {
				$used[ $slug ]++;
				$slug .= '-' . $used[ $slug ];
			} else {
				$used[ $slug ] = 1;
			}
		}
		$out[] = array(
			'level' => (int) $heading[1],
			'text'  => $text,
			'slug'  => $slug,
			'raw'   => $heading[0],
			'attrs' => $heading[2],
			'inner' => $heading[3],
		);
	}
	return $out;
}

/**
 * Should this post show a TOC?
 *
 * @param string $content Content.
 * @return array Headings (empty if TOC should not render).
 */
function zoomblog_toc_items( $content = null ) {
	if ( ! zoomblog_is_on( 'show_toc' ) ) {
		return array();
	}
	if ( get_post_meta( get_the_ID(), '_zoomblog_hide_toc', true ) ) {
		return array();
	}
	if ( null === $content ) {
		$content = get_the_content();
	}
	$items = zoomblog_extract_headings( $content );
	$min   = (int) zoomblog_get_option( 'toc_min_headings', 3 );
	return count( $items ) >= $min ? $items : array();
}

/**
 * Inject anchor ids into the post content so the TOC links resolve.
 *
 * @param string $content Post content HTML.
 * @return string
 */
function zoomblog_inject_toc_ids( $content ) {
	if ( ! is_singular( 'post' ) || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	$items = zoomblog_toc_items( $content );
	if ( empty( $items ) ) {
		return $content;
	}
	foreach ( $items as $item ) {
		if ( false === stripos( $item['attrs'], 'id=' ) ) {
			$new = '<h' . $item['level'] . $item['attrs'] . ' id="' . esc_attr( $item['slug'] ) . '">' . $item['inner'] . '</h' . $item['level'] . '>';
			$content = str_replace( $item['raw'], $new, $content );
		}
	}
	return $content;
}
add_filter( 'the_content', 'zoomblog_inject_toc_ids', 7 );

/**
 * Render the TOC (rebuilt deterministically from the current post content).
 */
function zoomblog_render_toc() {
	$toc = zoomblog_toc_items();
	if ( empty( $toc ) ) {
		return;
	}
	echo '<nav class="zb-toc" aria-label="' . esc_attr__( 'فهرست مطالب', 'zoomblog' ) . '">';
	echo '<button type="button" class="zb-toc__toggle" aria-expanded="true">' . esc_html__( 'فهرست مطالب', 'zoomblog' ) . '</button>';
	echo '<ol class="zb-toc__list">';
	foreach ( $toc as $item ) {
		printf(
			'<li class="zb-toc__item zb-toc__item--h%1$d"><a href="#%2$s">%3$s</a></li>',
			(int) $item['level'],
			esc_attr( $item['slug'] ),
			esc_html( $item['text'] )
		);
	}
	echo '</ol></nav>';
}

/**
 * Ad slot. Reserves height (option) to avoid layout shift.
 *
 * @param string $slot One of header|in_content|sidebar.
 */
function zoomblog_ad_slot( $slot ) {
	$map = array(
		'header'     => 'ad_header',
		'in_content' => 'ad_in_content',
		'sidebar'    => 'ad_sidebar',
	);
	if ( ! isset( $map[ $slot ] ) ) {
		return;
	}
	$code = trim( (string) zoomblog_get_option( $map[ $slot ], '' ) );
	if ( '' === $code ) {
		return;
	}
	$reserve = zoomblog_is_on( 'ad_reserve_space' ) ? ' zb-ad--reserve' : '';
	echo '<div class="zb-ad zb-ad--' . esc_attr( $slot ) . $reserve . '">';
	echo '<span class="zb-ad__label">' . esc_html__( 'تبلیغات', 'zoomblog' ) . '</span>';
	// Ad code is admin-provided; allow scripts/iframes.
	echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';
}

/**
 * Pagination for archives.
 */
function zoomblog_pagination() {
	the_posts_pagination( array(
		'mid_size'           => 1,
		'prev_text'          => esc_html__( 'قبلی', 'zoomblog' ),
		'next_text'          => esc_html__( 'بعدی', 'zoomblog' ),
		'screen_reader_text' => esc_html__( 'ناوبری نوشته‌ها', 'zoomblog' ),
		'class'              => 'zb-pagination',
	) );
}
