<?php
/**
 * Fallback template for all views.
 */

get_header();
?>
<div class="container content">
	<?php if ( have_posts() ) : ?>
		<?php if ( ! is_singular() ) : ?>
			<h1 class="page-title"><?php echo wp_kses_post( get_the_archive_title() ?: get_bloginfo( 'name' ) ); ?></h1>
		<?php endif; ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class(); ?>>
				<?php if ( is_singular() ) : ?>
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<div class="entry-content"><?php the_content(); ?></div>
				<?php else : ?>
					<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="entry-summary"><?php the_excerpt(); ?></div>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<h1 class="page-title"><?php esc_html_e( 'Nothing found', 'usasmartgamers' ); ?></h1>
	<?php endif; ?>
</div>
<?php
get_footer();
