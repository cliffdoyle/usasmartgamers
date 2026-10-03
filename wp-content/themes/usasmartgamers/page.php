<?php
/**
 * Evergreen page: hubs, reviews, state pages, guides.
 */

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'page-article' ); ?>>
		<header class="page-header container container--narrow">
			<?php usg_breadcrumbs(); ?>
			<h1 class="page-title"><?php the_title(); ?></h1>
			<?php usg_byline(); ?>
		</header>
		<div class="entry-content container container--narrow">
			<?php the_content(); ?>
		</div>
		<div class="container container--narrow"><?php usg_author_box(); ?></div>
	</article>
	<?php
endwhile;
get_footer();
