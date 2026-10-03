<?php
/**
 * Front page placeholder until the Phase 4 homepage template is built.
 */

get_header();
?>
<section class="hero">
	<div class="container">
		<h1><?php esc_html_e( 'Smarter Online Gambling in the USA', 'usasmartgamers' ); ?></h1>
		<p><?php esc_html_e( 'Honest reviews, the best legal bonuses, and expert guides for US players. Launching soon.', 'usasmartgamers' ); ?></p>
	</div>
</section>
<div class="container content">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</div>
<?php
get_footer();
