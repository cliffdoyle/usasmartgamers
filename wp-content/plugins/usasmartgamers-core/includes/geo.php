<?php
/**
 * Visitor location: state-picker cookie > Cloudflare visitor-location headers > unknown.
 */

defined( 'ABSPATH' ) || exit;

function usg_detect_state(): ?string {
	static $state = false;
	if ( false !== $state ) {
		return $state;
	}
	$state  = null;
	$cookie = isset( $_COOKIE['usg_state'] ) ? strtoupper( sanitize_key( wp_unslash( $_COOKIE['usg_state'] ) ) ) : '';
	if ( 'INTL' === $cookie || isset( usg_states()[ $cookie ] ) ) {
		$state = $cookie;
		return $state;
	}
	$country = isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ) : '';
	if ( $country && ! in_array( $country, array( 'XX', 'T1' ), true ) ) {
		if ( 'US' !== $country ) {
			$state = 'INTL';
			return $state;
		}
		$region = isset( $_SERVER['HTTP_CF_REGION_CODE'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['HTTP_CF_REGION_CODE'] ) ) ) : '';
		if ( isset( usg_states()[ $region ] ) ) {
			$state = $region;
		}
	}
	return $state;
}

/**
 * Header state picker markup.
 */
function usg_state_picker( string $class = '' ): string {
	$h = '<label class="usg-state-picker ' . esc_attr( $class ) . '">' . usg_icon( 'pin' ) . '<span class="screen-reader-text">' . esc_html__( 'Your state', 'usasmartgamers-core' ) . '</span><select data-usg-state-picker>';
	$h .= '<option value="">' . esc_html__( 'Select state', 'usasmartgamers-core' ) . '</option>';
	foreach ( usg_states() as $code => $name ) {
		$h .= '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
	}
	$h .= '<option value="INTL">' . esc_html__( 'Outside the US', 'usasmartgamers-core' ) . '</option></select></label>';
	return $h;
}
