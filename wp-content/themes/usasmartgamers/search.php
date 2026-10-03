<?php
get_header();
?>
<header class="page-header page-header--band">
	<div class="container">
		<?php usg_breadcrumbs(); ?>
		<h1 class="page-title"><?php echo esc_html( sprintf( __( 'Search results for “%s”', 'usasmartgamers' ), get_search_query() ) ); ?></h1>
		<?php get_search_form(); ?>
	</div>
</header>
<div class="container container--narrow archive-content">
	<?php if ( have_posts() ) : ?>
		<ul class="search-results">
			<?php
			while ( have_posts() ) :
				the_post();
				$usg_type = get_post_type_object( get_post_type() );
				?>
				<li><span class="kicker"><?php echo esc_html( 'usg_slot' === get_post_type() ? 'Slot' : ( 'page' === get_post_type() ? 'Guide' : $usg_type->labels->singular_name ) ); ?></span><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p><?php echo esc_html( get_the_excerpt() ); ?></p></li>
			<?php endwhile; ?>
		</ul>
		<?php usg_pagination(); ?>
	<?php else : ?>
		<p class="usg-notice"><?php esc_html_e( 'No results. Try a different search term.', 'usasmartgamers' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
