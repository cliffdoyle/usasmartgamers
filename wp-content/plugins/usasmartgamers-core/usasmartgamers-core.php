<?php
/**
 * Plugin Name: USA Smart Gamers Core
 * Description: Data model (operators, offers, toplists, slots, states), Gutenberg blocks, affiliate redirects, geo-targeting and schema for USA Smart Gamers.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Text Domain: usasmartgamers-core
 */

defined( 'ABSPATH' ) || exit;

define( 'USG_CORE_VERSION', '0.1.0' );
define( 'USG_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'USG_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once USG_CORE_DIR . 'includes/rest-health.php';
