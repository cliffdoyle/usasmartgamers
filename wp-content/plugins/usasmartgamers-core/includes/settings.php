<?php
/**
 * Site settings (Settings → USA Smart Gamers).
 */

defined( 'ABSPATH' ) || exit;

function usg_settings_fields(): array {
	return array(
		__( 'Compliance & trust', 'usasmartgamers-core' ) => array(
			array( 'key' => 'disclosure', 'label' => __( 'Affiliate disclosure (footer)', 'usasmartgamers-core' ), 'type' => 'textarea' ),
			array( 'key' => 'rg_text', 'label' => __( 'Responsible gambling line (footer)', 'usasmartgamers-core' ), 'type' => 'textarea' ),
			array( 'key' => 'offer_rg', 'label' => __( 'Short RG line under every offer', 'usasmartgamers-core' ), 'type' => 'text' ),
			array( 'key' => 'how_we_test', 'label' => __( '"How we test" text', 'usasmartgamers-core' ), 'type' => 'textarea' ),
			array( 'key' => 'intl_message', 'label' => __( 'Message for visitors outside the US', 'usasmartgamers-core' ), 'type' => 'textarea' ),
		),
		__( 'Monetisation', 'usasmartgamers-core' ) => array(
			array( 'key' => 'default_sticky_operator', 'label' => __( 'Default sticky footer offer (operator)', 'usasmartgamers-core' ), 'type' => 'post', 'post_type' => 'usg_operator' ),
		),
		__( 'Rewards', 'usasmartgamers-core' ) => array(
			array( 'key' => 'rewards_enabled', 'label' => __( 'Enable Smart Rewards (coins)', 'usasmartgamers-core' ), 'type' => 'checkbox' ),
		),
		__( 'Analytics & social', 'usasmartgamers-core' ) => array(
			array( 'key' => 'gtm_id', 'label' => __( 'Google Tag Manager ID (GTM-XXXX) — loads only after cookie consent', 'usasmartgamers-core' ), 'type' => 'text' ),
			array( 'key' => 'social_x', 'label' => 'X URL', 'type' => 'url' ),
			array( 'key' => 'social_facebook', 'label' => 'Facebook URL', 'type' => 'url' ),
			array( 'key' => 'social_instagram', 'label' => 'Instagram URL', 'type' => 'url' ),
			array( 'key' => 'social_youtube', 'label' => 'YouTube URL', 'type' => 'url' ),
			array( 'key' => 'social_tiktok', 'label' => 'TikTok URL', 'type' => 'url' ),
			array( 'key' => 'social_reddit', 'label' => 'Reddit URL', 'type' => 'url' ),
		),
	);
}

add_action(
	'admin_init',
	function () {
		register_setting(
			'usg_settings',
			'usg_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => function ( $input ) {
					$out = array();
					foreach ( usg_settings_fields() as $fields ) {
						foreach ( $fields as $f ) {
							$out[ $f['key'] ] = usg_field_sanitize( $f, $input[ $f['key'] ] ?? '' );
						}
					}
					return $out;
				},
			)
		);
	}
);

add_action(
	'admin_menu',
	function () {
		add_options_page(
			'USA Smart Gamers',
			'USA Smart Gamers',
			'manage_options',
			'usg-settings',
			function () {
				$opts = get_option( 'usg_settings', array() );
				echo '<div class="wrap"><h1>USA Smart Gamers</h1><form method="post" action="options.php">';
				settings_fields( 'usg_settings' );
				foreach ( usg_settings_fields() as $section => $fields ) {
					echo '<h2>' . esc_html( $section ) . '</h2><table class="form-table">';
					foreach ( $fields as $f ) {
						echo '<tr><th>' . esc_html( $f['label'] ) . '</th><td>' . usg_field_input( $f, $opts[ $f['key'] ] ?? '', 'usg_settings[' . $f['key'] . ']' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					echo '</table>';
				}
				submit_button();
				echo '</form></div>';
			}
		);
	}
);
