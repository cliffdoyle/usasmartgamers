<?php
/**
 * Template Name: Landing (wide, no byline)
 * Template Post Type: page
 */

get_header();
while ( have_posts() ) :
	the_post();
	$usg_has_hero = has_block( 'usg/hero' );
	?>
	<article <?php post_class( 'page-article page-article--landing' ); ?>>
		<?php if ( ! $usg_has_hero ) : ?>
			<header class="page-header page-header--band">
				<div class="container"><?php usg_breadcrumbs(); ?><h1 class="page-title"><?php the_title(); ?></h1><?php if ( has_excerpt() ) : ?><p class="page-lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?></div>
			</header>
		<?php endif; ?>
		<div class="entry-content container"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;
get_footer();
