
&lt;?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Elementor compatibility
function zoomblog_elementor_support() {
	add_theme_support( 'elementor' );
}
add_action( 'after_setup_theme', 'zoomblog_elementor_support' );
