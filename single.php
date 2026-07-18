
&lt;?php get_header(); ?&gt;

&lt;div class="reading-progress-bar"&gt;&lt;/div&gt;

&lt;div class="container"&gt;
	&lt;?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?&gt;
		&lt;article id="post-&lt;?php the_ID(); ?&gt;" &lt;?php post_class(); ?&gt;&gt;
			&lt;h1&gt;&lt;?php the_title(); ?&gt;&lt;/h1&gt;

			&lt;div class="post-meta"&gt;
				&lt;span class="author"&gt;&lt;?php the_author(); ?&gt;&lt;/span&gt;
				&lt;span class="date"&gt;&lt;?php echo get_the_date(); ?&gt;&lt;/span&gt;
				&lt;span class="reading-time"&gt;&lt;?php echo zoomblog_get_reading_time(); ?&gt;&lt;/span&gt;
				&lt;?php echo zoomblog_get_bookmark_button(); ?&gt;
			&lt;/div&gt;

			&lt;?php if ( has_post_thumbnail() ) : ?&gt;
				&lt;?php the_post_thumbnail( 'large' ); ?&gt;
			&lt;?php endif; ?&gt;

			&lt;div class="post-content"&gt;
				&lt;?php the_content(); ?&gt;
			&lt;/div&gt;

			&lt;?php if ( comments_open() || get_comments_number() ) : ?&gt;
				&lt;?php comments_template(); ?&gt;
			&lt;?php endif; ?&gt;
		&lt;/article&gt;
	&lt;?php endwhile; endif; ?&gt;
&lt;/div&gt;

&lt;?php get_footer(); ?&gt;
