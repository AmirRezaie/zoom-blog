
&lt;?php get_header(); ?&gt;

&lt;div class="container"&gt;
	&lt;?php if ( have_posts() ) : ?&gt;
		&lt;?php
		$first_post = true;
		?&gt;

		&lt;div class="posts-grid"&gt;
			&lt;?php while ( have_posts() ) : the_post(); ?&gt;
				&lt;article id="post-&lt;?php the_ID(); ?&gt;" &lt;?php post_class( $first_post ? 'first-post' : '' ); ?&gt;&gt;
					&lt;?php if ( has_post_thumbnail() ) : ?&gt;
						&lt;a href="&lt;?php the_permalink(); ?&gt;"&gt;
							&lt;?php the_post_thumbnail( $first_post ? 'large' : 'medium' ); ?&gt;
						&lt;/a&gt;
					&lt;?php endif; ?&gt;

					&lt;h2&gt;&lt;a href="&lt;?php the_permalink(); ?&gt;"&gt;&lt;?php the_title(); ?&gt;&lt;/a&gt;&lt;/h2&gt;

					&lt;div class="post-meta"&gt;
						&lt;span class="author"&gt;&lt;?php the_author(); ?&gt;&lt;/span&gt;
						&lt;span class="date"&gt;&lt;?php echo get_the_date(); ?&gt;&lt;/span&gt;
						&lt;span class="reading-time"&gt;&lt;?php echo zoomblog_get_reading_time(); ?&gt;&lt;/span&gt;
						&lt;?php echo zoomblog_get_bookmark_button(); ?&gt;
					&lt;/div&gt;

					&lt;?php the_excerpt(); ?&gt;
				&lt;/article&gt;
				&lt;?php
				$first_post = false;
				?&gt;
			&lt;?php endwhile; ?&gt;
		&lt;/div&gt;
	&lt;?php else : ?&gt;
		&lt;p&gt;&lt;?php esc_html_e( 'No posts found.', 'zoomblog' ); ?&gt;&lt;/p&gt;
	&lt;?php endif; ?&gt;
&lt;/div&gt;

&lt;?php get_footer(); ?&gt;
