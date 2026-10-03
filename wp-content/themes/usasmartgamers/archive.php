<?php
/**
 * Archives: news categories, Insights, dates.
 */

get_header();
?>
<header class="page-header page-header--band">
	<div class="container">
		<?php usg_breadcrumbs(); ?>
		<h1 class="page-title">
			<?php
			if ( is_post_type_archive( 'usg_blog' ) ) {
				esc_html_e( 'Insights', 'usasmartgamers' );
			} elseif ( is_category() ) {
				single_cat_title();
			} else {
				echo wp_kses_post( get_the_archive_title() );
			}
			?>
		</h1>
		<?php if ( get_the_archive_description() ) : ?><div class="page-lead"><?php echo wp_kses_post( get_the_archive_description() ); ?></div><?php endif; ?>
	</div>
</header>
<div class="container archive-content">
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
	<?php else : ?>
		<p class="usg-notice"><?php esc_html_e( 'Nothing published here yet.', 'usasmartgamers' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
