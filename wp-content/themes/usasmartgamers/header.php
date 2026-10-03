<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'usasmartgamers' ); ?></a>
<header class="site-header" data-site-header>
	<div class="container site-header__bar">
		<div class="site-branding"><?php usg_theme_logo(); ?></div>
		<button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'usasmartgamers' ); ?></span>
			<span class="nav-toggle__lines" aria-hidden="true"><span></span><span></span><span></span></span>
		</button>
		<nav id="site-nav" class="mega" aria-label="<?php esc_attr_e( 'Primary', 'usasmartgamers' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'mega__list',
					'fallback_cb'    => false,
					'depth'          => 3,
					'walker'         => new USG_Mega_Walker(),
				)
			);
			?>
			<div class="mega__mobile-extra">
				<?php get_search_form(); ?>
			</div>
		</nav>
		<div class="site-header__actions">
			<?php
			if ( usg_core_active() ) {
				echo usg_state_picker( 'site-header__state' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
			<button type="button" class="icon-btn" data-search-toggle aria-expanded="false" aria-controls="header-search"><?php echo usg_theme_icon( 'search' ); // phpcs:ignore ?><span class="screen-reader-text"><?php esc_html_e( 'Search', 'usasmartgamers' ); ?></span></button>
			<?php
			if ( usg_core_active() ) {
				echo usg_header_account(); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
	</div>
	<div id="header-search" class="header-search" hidden><div class="container"><?php get_search_form(); ?></div></div>
</header>
<?php if ( usg_toc_enabled() ) : ?>
<nav class="toc-bar" aria-label="<?php esc_attr_e( 'On this page', 'usasmartgamers' ); ?>" data-toc hidden><div class="container"><ul class="toc-bar__list"></ul></div></nav>
<?php endif; ?>
<main id="main" class="site-main">
