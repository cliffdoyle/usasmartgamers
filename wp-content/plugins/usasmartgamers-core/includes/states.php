<?php
/**
 * US states data.
 */

defined( 'ABSPATH' ) || exit;

function usg_states(): array {
	return array(
		'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
		'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'District of Columbia', 'FL' => 'Florida',
		'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana',
		'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
		'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
		'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire',
		'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota',
		'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
		'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
		'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin',
		'WY' => 'Wyoming',
	);
}

function usg_state_name( ?string $code ): string {
	if ( 'INTL' === $code ) {
		return __( 'Outside the US', 'usasmartgamers-core' );
	}
	$s = usg_states();
	return $code && isset( $s[ $code ] ) ? $s[ $code ] : '';
}

/**
 * Tile-grid map positions: code => [row, col].
 */
function usg_state_tiles(): array {
	return array(
		'AK' => array( 0, 0 ), 'ME' => array( 0, 11 ),
		'VT' => array( 1, 10 ), 'NH' => array( 1, 11 ),
		'WA' => array( 2, 1 ), 'ID' => array( 2, 2 ), 'MT' => array( 2, 3 ), 'ND' => array( 2, 4 ), 'MN' => array( 2, 5 ), 'IL' => array( 2, 6 ), 'WI' => array( 2, 7 ), 'MI' => array( 2, 8 ), 'NY' => array( 2, 9 ), 'RI' => array( 2, 10 ), 'MA' => array( 2, 11 ),
		'OR' => array( 3, 1 ), 'NV' => array( 3, 2 ), 'WY' => array( 3, 3 ), 'SD' => array( 3, 4 ), 'IA' => array( 3, 5 ), 'IN' => array( 3, 6 ), 'OH' => array( 3, 7 ), 'PA' => array( 3, 8 ), 'NJ' => array( 3, 9 ), 'CT' => array( 3, 10 ),
		'CA' => array( 4, 1 ), 'UT' => array( 4, 2 ), 'CO' => array( 4, 3 ), 'NE' => array( 4, 4 ), 'MO' => array( 4, 5 ), 'KY' => array( 4, 6 ), 'WV' => array( 4, 7 ), 'VA' => array( 4, 8 ), 'MD' => array( 4, 9 ), 'DE' => array( 4, 10 ),
		'AZ' => array( 5, 2 ), 'NM' => array( 5, 3 ), 'KS' => array( 5, 4 ), 'AR' => array( 5, 5 ), 'TN' => array( 5, 6 ), 'NC' => array( 5, 7 ), 'SC' => array( 5, 8 ), 'DC' => array( 5, 9 ),
		'OK' => array( 6, 4 ), 'LA' => array( 6, 5 ), 'MS' => array( 6, 6 ), 'AL' => array( 6, 7 ), 'GA' => array( 6, 8 ),
		'HI' => array( 7, 0 ), 'TX' => array( 7, 4 ), 'FL' => array( 7, 9 ),
	);
}

function usg_state_statuses(): array {
	return array(
		'casino' => array(
			'legal'    => __( 'Legal & live', 'usasmartgamers-core' ),
			'pending'  => __( 'Bill under consideration', 'usasmartgamers-core' ),
			'unlikely' => __( 'Not legal / unlikely soon', 'usasmartgamers-core' ),
		),
		'sports' => array(
			'legal'   => __( 'Online betting legal', 'usasmartgamers-core' ),
			'retail'  => __( 'Retail only', 'usasmartgamers-core' ),
			'pending' => __( 'Pending', 'usasmartgamers-core' ),
			'no'      => __( 'Not legal', 'usasmartgamers-core' ),
		),
		'sweeps' => array(
			'allowed'    => __( 'Sweepstakes available', 'usasmartgamers-core' ),
			'restricted' => __( 'Restricted', 'usasmartgamers-core' ),
		),
	);
}

/**
 * State term (taxonomy usg_state, slug = lowercase code) for a code.
 */
function usg_state_term( string $code ) {
	$t = get_term_by( 'slug', strtolower( $code ), 'usg_state' );
	return $t instanceof WP_Term ? $t : null;
}
