
&lt;?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Convert Gregorian date to Persian (Jalali)
function zoomblog_get_persian_date( $date = null ) {
	if ( ! $date ) {
		$date = get_the_date( 'Y-m-d' );
	}

	// For now, we'll use a simple implementation; in production, use a proper Jalali library
	return $date;
}

// Persian text normalization (ya/ke, half space, etc.)
function zoomblog_normalize_persian_text( $text ) {
	$text = str_replace( 'ي', 'ی', $text );
	$text = str_replace( 'ك', 'ک', $text );
	$text = str_replace( '‌', ' ', $text ); // Replace half space with normal space temporarily if needed
	return $text;
}
add_filter( 'the_content', 'zoomblog_normalize_persian_text' );
add_filter( 'the_title', 'zoomblog_normalize_persian_text' );

// Estimated reading time
function zoomblog_get_reading_time( $content = null ) {
	if ( ! $content ) {
		$content = get_the_content();
	}
	$word_count = str_word_count( strip_tags( $content ) );
	// Average Persian reading speed: ~200 words per minute
	$reading_time = ceil( $word_count / 200 );
	return $reading_time . ' ' . __( 'minute', 'zoomblog' ) . ( $reading_time == 1 ? '' : 's' );
}

// Get reading history HTML from post IDs
function zoomblog_get_reading_history_html( $post_ids ) {
	if ( empty( $post_ids ) ) {
		return '';
	}
	
	$args = array(
		'post_type'      =&gt; 'post',
		'post_status'    =&gt; 'publish',
		'posts_per_page' =&gt; 10,
		'post__in'       =&gt; $post_ids,
		'orderby'        =&gt; 'post__in'
	);
	
	$history_query = new WP_Query( $args );
	
	$html = '&lt;div class="reading-history"&gt;';
	$html .= '&lt;h3&gt;' . __( 'Reading History', 'zoomblog' ) . '&lt;/h3&gt;';
	
	if ( $history_query-&gt;have_posts() ) {
		$html .= '&lt;ul class="history-list"&gt;';
		while ( $history_query-&gt;have_posts() ) {
			$history_query-&gt;the_post();
			$html .= '&lt;li class="history-item"&gt;';
			$html .= '&lt;a href="' . get_permalink() . '"&gt;';
			if ( has_post_thumbnail() ) {
				$html .= get_the_post_thumbnail( 'thumbnail' );
			}
			$html .= '&lt;span class="history-title"&gt;' . get_the_title() . '&lt;/span&gt;';
			$html .= '&lt;/a&gt;';
			$html .= '&lt;/li&gt;';
		}
		wp_reset_postdata();
		$html .= '&lt;/ul&gt;';
	} else {
		$html .= '&lt;p&gt;' . __( 'No reading history yet.', 'zoomblog' ) . '&lt;/p&gt;';
	}
	
	$html .= '&lt;/div&gt;';
	
	return $html;
}

// Get bookmark button
function zoomblog_get_bookmark_button( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	
	$button_text = __( 'Bookmark', 'zoomblog' );
	$button_class = 'bookmark-button';
	
	return '&lt;button class="' . $button_class . '" data-post-id="' . $post_id . '"&gt;' . $button_text . '&lt;/button&gt;';
}

// Get bookmarks HTML from post IDs
function zoomblog_get_bookmarks_html( $post_ids ) {
	if ( empty( $post_ids ) ) {
		return '';
	}
	
	$args = array(
		'post_type'      =&gt; 'post',
		'post_status'    =&gt; 'publish',
		'posts_per_page' =&gt; -1,
		'post__in'       =&gt; $post_ids,
		'orderby'        =&gt; 'post__in'
	);
	
	$bookmarks_query = new WP_Query( $args );
	
	$html = '&lt;div class="bookmarks-list"&gt;';
	$html .= '&lt;h3&gt;' . __( 'Bookmarks', 'zoomblog' ) . '&lt;/h3&gt;';
	
	if ( $bookmarks_query-&gt;have_posts() ) {
		$html .= '&lt;ul class="bookmarks-items"&gt;';
		while ( $bookmarks_query-&gt;have_posts() ) {
			$bookmarks_query-&gt;the_post();
			$html .= '&lt;li class="bookmark-item" data-post-id="' . get_the_ID() . '"&gt;';
			$html .= '&lt;a href="' . get_permalink() . '"&gt;';
			if ( has_post_thumbnail() ) {
				$html .= get_the_post_thumbnail( 'thumbnail' );
			}
			$html .= '&lt;span class="bookmark-title"&gt;' . get_the_title() . '&lt;/span&gt;';
			$html .= '&lt;/a&gt;';
			$html .= '&lt;button class="remove-bookmark" data-post-id="' . get_the_ID() . '"&gt;' . __( 'Remove', 'zoomblog' ) . '&lt;/button&gt;';
			$html .= '&lt;/li&gt;';
		}
		wp_reset_postdata();
		$html .= '&lt;/ul&gt;';
	} else {
		$html .= '&lt;p&gt;' . __( 'No bookmarks yet.', 'zoomblog' ) . '&lt;/p&gt;';
	}
	
	$html .= '&lt;/div&gt;';
	
	return $html;
}
