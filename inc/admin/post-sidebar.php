<?php
/**
 * Tidy per-post sidebar.
 *
 * Instead of several large metaboxes, one compact panel in the editor's side
 * column holds every ZoomBlog per-post field: subtitle, content type,
 * editorial flags, sources, and the premium (paywall) switch. Works in both
 * the block editor (side panel via meta box) and the classic editor.
 *
 * @package ZoomBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The per-post fields definition.
 *
 * @return array<string,array<string,mixed>>
 */
function zoomblog_post_fields() {
	return array(
		'_zoomblog_subtitle'     => array( 'type' => 'text', 'label' => __( 'زیرعنوان', 'zoomblog' ) ),
		'_zoomblog_content_type' => array( 'type' => 'select', 'label' => __( 'نوع محتوا', 'zoomblog' ), 'choices' => zoomblog_content_type_choices() ),
		'_zoomblog_editor_pick'  => array( 'type' => 'checkbox', 'label' => __( 'انتخاب سردبیر', 'zoomblog' ) ),
		'_zoomblog_breaking'     => array( 'type' => 'checkbox', 'label' => __( 'خبر فوری', 'zoomblog' ) ),
		'_zoomblog_hide_toc'     => array( 'type' => 'checkbox', 'label' => __( 'مخفی‌کردن فهرست مطالب', 'zoomblog' ) ),
		'_zoomblog_premium'      => array( 'type' => 'checkbox', 'label' => __( 'محتوای ویژه (پی‌وال)', 'zoomblog' ) ),
		'_zoomblog_sources'      => array( 'type' => 'textarea', 'label' => __( 'منابع (هر خط: عنوان|نشانی)', 'zoomblog' ) ),
	);
}

/**
 * Register the meta box on supported post types.
 */
function zoomblog_add_post_metabox() {
	$types = array( 'post' );
	foreach ( array( 'podcast', 'video', 'review' ) as $t ) {
		if ( zoomblog_is_on( 'cpt_' . $t ) ) {
			$types[] = 'zb_' . $t;
		}
	}
	foreach ( $types as $type ) {
		add_meta_box(
			'zoomblog_post_panel',
			__( 'گزینه‌های زومبلاگ', 'zoomblog' ),
			'zoomblog_render_post_metabox',
			$type,
			'side',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'zoomblog_add_post_metabox' );

/**
 * Render the compact panel.
 *
 * @param WP_Post $post Current post.
 */
function zoomblog_render_post_metabox( $post ) {
	wp_nonce_field( 'zoomblog_save_post_meta', 'zoomblog_post_meta_nonce' );
	echo '<div class="zb-postpanel">';
	foreach ( zoomblog_post_fields() as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		$id    = 'zb' . esc_attr( $key );
		echo '<p class="zb-postpanel__row zb-postpanel__row--' . esc_attr( $field['type'] ) . '">';
		switch ( $field['type'] ) {
			case 'checkbox':
				printf(
					'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
					$id, esc_attr( $key ), checked( $value, '1', false ), esc_html( $field['label'] )
				);
				break;
			case 'select':
				printf( '<label for="%1$s">%2$s</label><select id="%1$s" name="%3$s">', $id, esc_html( $field['label'] ), esc_attr( $key ) );
				foreach ( $field['choices'] as $ck => $cl ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $ck ), selected( $value, $ck, false ), esc_html( $cl ) );
				}
				echo '</select>';
				break;
			case 'textarea':
				printf( '<label for="%1$s">%2$s</label><textarea id="%1$s" name="%3$s" rows="3" style="width:100%%">%4$s</textarea>', $id, esc_html( $field['label'] ), esc_attr( $key ), esc_textarea( $value ) );
				break;
			default:
				printf( '<label for="%1$s">%2$s</label><input type="text" id="%1$s" name="%3$s" value="%4$s" style="width:100%%">', $id, esc_html( $field['label'] ), esc_attr( $key ), esc_attr( $value ) );
		}
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Save the panel fields.
 *
 * @param int $post_id Post ID.
 */
function zoomblog_save_post_meta( $post_id ) {
	if ( ! isset( $_POST['zoomblog_post_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zoomblog_post_meta_nonce'] ) ), 'zoomblog_save_post_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( zoomblog_post_fields() as $key => $field ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				if ( ! empty( $_POST[ $key ] ) ) {
					update_post_meta( $post_id, $key, '1' );
				} else {
					delete_post_meta( $post_id, $key );
				}
				break;
			case 'select':
				$choices = array_map( 'strval', array_keys( $field['choices'] ) );
				$val     = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
				if ( in_array( $val, $choices, true ) ) {
					update_post_meta( $post_id, $key, $val );
				}
				break;
			case 'textarea':
				update_post_meta( $post_id, $key, sanitize_textarea_field( wp_unslash( $_POST[ $key ] ?? '' ) ) );
				break;
			default:
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) ) );
		}
	}
}
add_action( 'save_post', 'zoomblog_save_post_meta' );

/**
 * Helper: get a post's subtitle.
 *
 * @param int|WP_Post|null $post Post.
 * @return string
 */
function zoomblog_get_subtitle( $post = null ) {
	$post = get_post( $post );
	return $post ? (string) get_post_meta( $post->ID, '_zoomblog_subtitle', true ) : '';
}

/**
 * Helper: parsed sources list [ [title,url], ... ].
 *
 * @param int|WP_Post|null $post Post.
 * @return array<int,array{title:string,url:string}>
 */
function zoomblog_get_sources( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$raw = (string) get_post_meta( $post->ID, '_zoomblog_sources', true );
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[] = array(
			'title' => $parts[0],
			'url'   => isset( $parts[1] ) ? esc_url_raw( $parts[1] ) : '',
		);
	}
	return $out;
}
