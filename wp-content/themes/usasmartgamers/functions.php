<?php
/**
 * USA Smart Gamers theme bootstrap. Presentation only — data/blocks come from the usasmartgamers-core plugin.
 */

defined( 'ABSPATH' ) || exit;

define( 'USG_THEME_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/walker-mega.php';
require_once get_template_directory() . '/inc/template-tags.php';

/**
 * Build identifier written by CI (build-version.txt = git SHA). Used for cache busting and smoke tests.
 */
function usg_build_id(): string {
	static $id = null;
	if ( null === $id ) {
		$file = get_template_directory() . '/build-version.txt';
		$id   = is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : 'dev';
	}
	return $id;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support( 'custom-logo', array( 'height' => 60, 'width' => 240, 'flex-width' => true, 'flex-height' => true ) );
		add_editor_style( 'assets/css/main.css' );

		register_nav_menus(
			array(
				'primary'        => __( 'Primary (mega-nav)', 'usasmartgamers' ),
				'footer_guides'  => __( 'Footer: State guides', 'usasmartgamers' ),
				'footer_popular' => __( 'Footer: Popular pages', 'usasmartgamers' ),
				'footer_about'   => __( 'Footer: About', 'usasmartgamers' ),
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$ver = USG_THEME_VERSION . '-' . usg_build_id();
		wp_enqueue_style( 'usg-fonts', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap', array(), null );
		wp_enqueue_style( 'usg-main', get_template_directory_uri() . '/assets/css/main.css', array( 'usg-fonts' ), $ver );
		wp_enqueue_script( 'usg-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), $ver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_dequeue_style( 'classic-theme-styles' );
	}
);

add_action(
	'wp_head',
	function () {
		echo '<meta name="usg-build" content="' . esc_attr( usg_build_id() ) . '">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		if ( ! has_site_icon() ) {
			$img = get_template_directory_uri() . '/assets/img/';
			echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( $img . 'logo-icon.svg' ) . '">' . "\n";
			if ( file_exists( get_template_directory() . '/assets/img/apple-touch-icon.png' ) ) {
				echo '<link rel="apple-touch-icon" href="' . esc_url( $img . 'apple-touch-icon.png' ) . '">' . "\n";
				echo '<link rel="icon" type="image/png" sizes="180x180" href="' . esc_url( $img . 'apple-touch-icon.png' ) . '">' . "\n";
			}
			echo '<meta name="theme-color" content="#0f2a4a">' . "\n";
		}
	},
	1
);

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_page_template( 'page-templates/landing.php' ) || is_front_page() ) {
			$classes[] = 'usg-wide';
		}
		return $classes;
	}
);

add_filter( 'excerpt_length', fn() => 28 );
add_filter( 'excerpt_more', fn() => '…' );
