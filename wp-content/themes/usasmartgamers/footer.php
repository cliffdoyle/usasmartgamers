</main>
<?php usg_sticky_footer_cta(); ?>
<footer class="site-footer">
	<div class="container">
		<div class="site-footer__top">
			<div class="site-footer__brand">
				<?php usg_theme_logo(); ?>
				<p><?php echo esc_html( usg_core_active() ? (string) usg_option( 'disclosure' ) : '' ); ?></p>
				<?php
				if ( usg_core_active() ) {
					$usg_social = '';
					foreach ( array( 'social_x' => 'twitter', 'social_facebook' => 'facebook', 'social_instagram' => 'instagram', 'social_youtube' => 'youtube', 'social_tiktok' => 'tiktok', 'social_reddit' => 'reddit' ) as $usg_k => $usg_i ) {
						$usg_u = usg_option( $usg_k );
						if ( $usg_u ) {
							$usg_social .= '<a href="' . esc_url( $usg_u ) . '" rel="noopener" target="_blank" aria-label="' . esc_attr( ucfirst( $usg_i ) ) . '">' . usg_icon( $usg_i ) . '</a>';
						}
					}
					if ( $usg_social ) {
						echo '<div class="site-footer__social">' . $usg_social . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
				}
				?>
			</div>
			<?php
			foreach ( array( 'footer_guides', 'footer_popular', 'footer_about' ) as $usg_loc ) {
				if ( has_nav_menu( $usg_loc ) ) {
					$usg_menu = wp_get_nav_menu_object( get_nav_menu_locations()[ $usg_loc ] );
					echo '<div class="site-footer__col"><h2>' . esc_html( $usg_menu ? $usg_menu->name : '' ) . '</h2>';
					wp_nav_menu( array( 'theme_location' => $usg_loc, 'container' => false, 'depth' => 1 ) );
					echo '</div>';
				}
			}
			?>
		</div>
		<div class="site-footer__rg">
			<span class="site-footer__21">21+</span>
			<p><?php echo esc_html( usg_core_active() ? (string) usg_option( 'rg_text' ) : '' ); ?> <a href="https://www.ncpgambling.org/help-treatment/" rel="noopener" target="_blank">ncpgambling.org</a></p>
		</div>
		<p class="site-footer__copy">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'usasmartgamers' ); ?></p>
	</div>
</footer>
<button type="button" class="to-top" data-to-top hidden aria-label="<?php esc_attr_e( 'Back to top', 'usasmartgamers' ); ?>"><?php echo usg_theme_icon( 'up' ); // phpcs:ignore ?></button>
<?php wp_footer(); ?>
</body>
</html>
