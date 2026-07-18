
&lt;?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ZOOMBLOG_VERSION', '1.0.0' );
define( 'ZOOMBLOG_DIR', get_template_directory() );
define( 'ZOOMBLOG_URI', get_template_directory_uri() );

// Enqueue scripts and styles
function zoomblog_enqueue_scripts() {
	// Main stylesheet
	wp_enqueue_style( 'zoomblog-style', get_stylesheet_uri(), array(), ZOOMBLOG_VERSION );
	
	// Theme main styles
	wp_enqueue_style( 'zoomblog-main', ZOOMBLOG_URI . '/assets/css/main.css', array(), ZOOMBLOG_VERSION );
	
	// Main script
	wp_enqueue_script( 'zoomblog-script', ZOOMBLOG_URI . '/assets/js/main.js', array(), ZOOMBLOG_VERSION, true );
	
	// Localize script
	wp_localize_script( 'zoomblog-script', 'zoomblogData', array(
		'ajaxUrl' =&gt; admin_url( 'admin-ajax.php' ),
		'nonce' =&gt; wp_create_nonce( 'zoomblog_nonce' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'zoomblog_enqueue_scripts' );

// Theme setup
function zoomblog_setup() {
	// Text domain
	load_theme_textdomain( 'zoomblog', ZOOMBLOG_DIR . '/languages' );
	
	// Title tag
	add_theme_support( 'title-tag' );
	
	// Post thumbnails
	add_theme_support( 'post-thumbnails' );
	
	// Menus
	register_nav_menus( array(
		'primary' =&gt; __( 'Primary Menu', 'zoomblog' ),
		'footer' =&gt; __( 'Footer Menu', 'zoomblog' ),
	) );
	
	// HTML5 support
	add_theme_support( 'html5', array(
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	) );
}
add_action( 'after_setup_theme', 'zoomblog_setup' );

// Include required files
require_once ZOOMBLOG_DIR . '/inc/helpers.php';
require_once ZOOMBLOG_DIR . '/inc/customizer.php';
require_once ZOOMBLOG_DIR . '/inc/elementor-compatibility.php';

// AJAX handler for reading history
add_action( 'wp_ajax_nopriv_zoomblog_get_reading_history', 'zoomblog_get_reading_history_callback' );
add_action( 'wp_ajax_zoomblog_get_reading_history', 'zoomblog_get_reading_history_callback' );

function zoomblog_get_reading_history_callback() {
	check_ajax_referer( 'zoomblog_nonce', 'nonce' );
	
	$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', json_decode( $_POST['post_ids'] ) ) : array();
	
	$html = zoomblog_get_reading_history_html( $post_ids );
	
	wp_send_json_success( array( 'html' =&gt; $html ) );
}

// AJAX handler for getting bookmarks
add_action( 'wp_ajax_nopriv_zoomblog_get_bookmarks', 'zoomblog_get_bookmarks_callback' );
add_action( 'wp_ajax_zoomblog_get_bookmarks', 'zoomblog_get_bookmarks_callback' );

function zoomblog_get_bookmarks_callback() {
	check_ajax_referer( 'zoomblog_nonce', 'nonce' );
	
	$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', json_decode( $_POST['post_ids'] ) ) : array();
	
	$html = zoomblog_get_bookmarks_html( $post_ids );
	
	wp_send_json_success( array( 'html' =&gt; $html ) );
}
