
&lt;?php
if ( ! defined( 'ABSPATH' ) ) exit;

function zoomblog_customize_register( $wp_customize ) {
	// Add Dark/Light mode setting
	$wp_customize-&gt;add_setting( 'zoomblog_dark_mode', array(
		'default' =&gt; 'light',
		'sanitize_callback' =&gt; 'sanitize_text_field',
	) );

	$wp_customize-&gt;add_control( 'zoomblog_dark_mode', array(
		'type' =&gt; 'select',
		'section' =&gt; 'title_tagline',
		'label' =&gt; __( 'Dark Mode', 'zoomblog' ),
		'choices' =&gt; array(
			'light' =&gt; __( 'Light', 'zoomblog' ),
			'dark' =&gt; __( 'Dark', 'zoomblog' ),
			'auto' =&gt; __( 'Auto', 'zoomblog' ),
		),
	) );
}
add_action( 'customize_register', 'zoomblog_customize_register' );
