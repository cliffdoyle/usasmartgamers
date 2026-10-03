<?php
get_header();
?>
<section class="container container--narrow notfound">
	<p class="notfound__code">404</p>
	<h1 class="page-title"><?php esc_html_e( 'That page has cashed out', 'usasmartgamers' ); ?></h1>
	<p><?php esc_html_e( 'The page you were looking for has moved or no longer exists. Try a search or head to one of our most popular guides.', 'usasmartgamers' ); ?></p>
	<?php get_search_form(); ?>
	<p class="notfound__links"><a href="<?php echo esc_url( home_url( '/online-casinos/' ) ); ?>">Online casinos</a> · <a href="<?php echo esc_url( home_url( '/sweepstakes-casinos/' ) ); ?>">Sweepstakes casinos</a> · <a href="<?php echo esc_url( home_url( '/slots/' ) ); ?>">Slots</a> · <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News</a></p>
</section>
<?php
get_footer();
