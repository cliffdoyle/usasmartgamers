</main>
<footer class="site-footer">
	<div class="container">
		<p class="site-footer__disclosure">
			<?php esc_html_e( 'Affiliate Disclosure: USA Smart Gamers may earn a commission when you sign up with operators through our links. This never affects our ratings. We only cover legally licensed operators.', 'usasmartgamers' ); ?>
		</p>
		<div class="site-footer__columns">
			<?php
			foreach ( array( 'footer_guides', 'footer_popular', 'footer_about' ) as $usg_location ) {
				wp_nav_menu(
					array(
						'theme_location' => $usg_location,
						'container'      => 'div',
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
			}
			?>
		</div>
		<p class="site-footer__rg">
			<?php esc_html_e( 'Gamble responsibly. If you or someone you know has a gambling problem, call 1-800-GAMBLER. 21+ only — all content is intended for audiences 21 years and older.', 'usasmartgamers' ); ?>
		</p>
		<p class="site-footer__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
