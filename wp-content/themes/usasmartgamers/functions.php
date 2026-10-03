<?php
/**
 * USA Smart Gamers theme bootstrap.
 */

defined( 'ABSPATH' ) || exit;

define( 'USG_THEME_VERSION', '0.1.0' );

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
		add_theme_support( 'editor-styles' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'custom-logo', array( 'height' => 60, 'width' => 220, 'flex-width' => true ) );

		register_nav_menus(
			array(
				'primary'        => __( 'Primary (mega-nav)', 'usasmartgamers' ),
				'footer_guides'  => __( 'Footer: State Guides', 'usasmartgamers' ),
				'footer_popular' => __( 'Footer: Popular Pages', 'usasmartgamers' ),
				'footer_about'   => __( 'Footer: About', 'usasmartgamers' ),
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'usg-fonts', 'https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap', array(), null );
		wp_enqueue_style( 'usg-main', get_template_directory_uri() . '/assets/css/main.css', array( 'usg-fonts' ), USG_THEME_VERSION . '-' . usg_build_id() );
	}
);

add_action(
	'wp_head',
	function () {
		echo '<meta name="usg-build" content="' . esc_attr( usg_build_id() ) . '">' . "\n";
	},
	1
);
