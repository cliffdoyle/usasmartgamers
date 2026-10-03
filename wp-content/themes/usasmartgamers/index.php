<?php
/**
 * Fallback template.
 */

get_header();
?>
<div class="container archive-content">
	<?php usg_breadcrumbs(); ?>
	<?php if ( have_posts() ) : ?>
		<div class="usg-cards usg-cards--grid">
			<?php
			while ( have_posts() ) :
				the_post();
				echo function_exists( 'usg_post_card' ) ? usg_post_card( get_post() ) : '<h2><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>'; // phpcs:ignore
			endwhile;
			?>
		</div>
		<?php usg_pagination(); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
