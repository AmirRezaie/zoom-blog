
&lt;!DOCTYPE html&gt;
&lt;html &lt;?php language_attributes(); ?&gt;&gt;
&lt;head&gt;
	&lt;meta charset="&lt;?php bloginfo( 'charset' ); ?&gt;"&gt;
	&lt;meta name="viewport" content="width=device-width, initial-scale=1"&gt;
	&lt;?php wp_head(); ?&gt;
&lt;/head&gt;
&lt;body &lt;?php body_class(); ?&gt;&gt;
	&lt;?php wp_body_open(); ?&gt;

	&lt;header class="site-header"&gt;
		&lt;div class="container"&gt;
			&lt;div class="site-branding"&gt;
				&lt;?php the_custom_logo(); ?&gt;
				&lt;?php if ( is_front_page() &amp;&amp; is_home() ) : ?&gt;
					&lt;h1 class="site-title"&gt;&lt;a href="&lt;?php echo esc_url( home_url( '/' ) ); ?&gt;" rel="home"&gt;&lt;?php bloginfo( 'name' ); ?&gt;&lt;/a&gt;&lt;/h1&gt;
				&lt;?php else : ?&gt;
					&lt;p class="site-title"&gt;&lt;a href="&lt;?php echo esc_url( home_url( '/' ) ); ?&gt;" rel="home"&gt;&lt;?php bloginfo( 'name' ); ?&gt;&lt;/a&gt;&lt;/p&gt;
				&lt;?php endif; ?&gt;
			&lt;/div&gt;

			&lt;nav class="main-navigation"&gt;
				&lt;?php
				wp_nav_menu( array(
					'theme_location' =&gt; 'primary',
					'menu_id'        =&gt; 'primary-menu',
				) );
				?&gt;
			&lt;/nav&gt;
		&lt;/div&gt;
	&lt;/header&gt;

	&lt;main class="site-main"&gt;
