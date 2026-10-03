<?php
/**
 * Homepage: fully block-built (hero block supplies the H1).
 */

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="entry-content container home-content"><?php the_content(); ?></div>
	<?php
endwhile;
get_footer();
