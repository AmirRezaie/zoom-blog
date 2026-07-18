
	&lt;/main&gt;

	&lt;footer class="site-footer"&gt;
		&lt;div class="container"&gt;
			&lt;div class="site-info"&gt;
				&lt;?php
				printf(
					/* translators: %s: WordPress. */
					esc_html__( 'Proudly powered by %s.', 'zoomblog' ),
					'WordPress'
				);
				?&gt;
			&lt;/div&gt;
		&lt;/div&gt;
	&lt;/footer&gt;

	&lt;?php wp_footer(); ?&gt;
&lt;/body&gt;
&lt;/html&gt;
