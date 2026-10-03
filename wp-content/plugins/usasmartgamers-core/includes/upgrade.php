<?php
/**
 * Custom tables and versioned upgrades (runs on deploy since code ships via rsync, not activation).
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function () {
		if ( get_option( 'usg_db_version' ) === USG_DB_VERSION ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$wpdb->prefix}usg_clicks (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created datetime NOT NULL,
				operator_id bigint(20) unsigned NOT NULL DEFAULT 0,
				offer_id bigint(20) unsigned NOT NULL DEFAULT 0,
				tag varchar(64) NOT NULL DEFAULT '',
				page varchar(255) NOT NULL DEFAULT '',
				state varchar(8) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY operator_created (operator_id,created),
				KEY created (created)
			) $c;"
		);
		dbDelta(
			"CREATE TABLE {$wpdb->prefix}usg_coins (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				amount int(11) NOT NULL,
				action varchar(32) NOT NULL,
				ref varchar(64) NOT NULL DEFAULT '',
				note varchar(255) NOT NULL DEFAULT '',
				created datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY user_created (user_id,created),
				KEY user_action (user_id,action,created)
			) $c;"
		);
		$defaults = array(
			'disclosure'      => 'Affiliate disclosure: USA Smart Gamers is an independent review site. We may earn a commission when you sign up with an operator through our links — this never affects our ratings or the order of our lists. We only recommend operators that are licensed in the states where they operate.',
			'rg_text'         => 'Gambling should be fun. If you or someone you know has a gambling problem, call 1-800-GAMBLER. 21+ only — all content on this site is intended for audiences 21 and older.',
			'offer_rg'        => '21+. New customers only. Terms apply. Gambling problem? Call 1-800-GAMBLER.',
			'how_we_test'     => 'Every operator we list is tested hands-on by our team: we open real accounts, deposit our own money, claim the welcome offer, play the games, request withdrawals and contact customer support. Scores are set by the editorial team and are never influenced by commercial relationships.',
			'intl_message'    => 'It looks like you are browsing from outside the United States. The offers on this page are only available to players located in eligible US states.',
			'rewards_enabled' => '1',
		);
		$current = get_option( 'usg_settings', array() );
		update_option( 'usg_settings', array_merge( $defaults, is_array( $current ) ? array_filter( $current, fn( $v ) => '' !== $v ) : array() ) );
		update_option( 'usg_db_version', USG_DB_VERSION );
	}
);
