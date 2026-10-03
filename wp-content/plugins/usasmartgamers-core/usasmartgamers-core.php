<?php
/**
 * Plugin Name: USA Smart Gamers Core
 * Description: Data model (operators, offers, toplists, slots, states), Gutenberg blocks, affiliate redirects, geo-targeting, user reviews, accounts, rewards and schema for USA Smart Gamers.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Text Domain: usasmartgamers-core
 */

defined( 'ABSPATH' ) || exit;

define( 'USG_CORE_VERSION', '1.0.0' );
define( 'USG_DB_VERSION', '3' );
define( 'USG_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'USG_CORE_URL', plugin_dir_url( __FILE__ ) );

foreach ( array(
	'helpers',
	'states',
	'fields',
	'cpt',
	'settings',
	'upgrade',
	'offers',
	'geo',
	'redirects',
	'schema',
	'blocks',
	'render-offers',
	'render-content',
	'render-tools',
	'reviews',
	'accounts',
	'rewards',
	'contact',
	'rest',
	'rest-health',
	'seo',
	'admin',
) as $usg_file ) {
	require_once USG_CORE_DIR . 'includes/' . $usg_file . '.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once USG_CORE_DIR . 'includes/cli.php';
}
