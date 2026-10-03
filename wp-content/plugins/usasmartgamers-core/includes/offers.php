<?php
/**
 * Operator / offer / toplist domain logic.
 *
 * $state semantics: null = unknown visitor (national default list), 'INTL' = outside the US, 'NJ' etc. = US state.
 */

defined( 'ABSPATH' ) || exit;

function usg_operator_available( int $op_id, ?string $state ): bool {
	$mode   = usg_meta( $op_id, 'availability', 'listed' );
	$states = (array) usg_meta( $op_id, 'states', array() );
	if ( 'INTL' === $state ) {
		return 'international' === $mode;
	}
	if ( 'international' === $mode ) {
		return false;
	}
	if ( null === $state ) {
		return true;
	}
	return 'all_except' === $mode ? ! in_array( $state, $states, true ) : in_array( $state, $states, true );
}

function usg_offer_valid( int $offer_id, ?string $state ): bool {
	if ( ! $offer_id || 'publish' !== get_post_status( $offer_id ) ) {
		return false;
	}
	$today = current_time( 'Y-m-d' );
	$start = usg_meta( $offer_id, 'start' );
	$end   = usg_meta( $offer_id, 'end' );
	if ( ( $start && $start > $today ) || ( $end && $end < $today ) ) {
		return false;
	}
	$mode = usg_meta( $offer_id, 'availability', 'inherit' );
	if ( 'inherit' === $mode ) {
		return true;
	}
	// Nationwide default lists must never show state-restricted offers.
	if ( null === $state ) {
		return 'listed' !== $mode;
	}
	if ( 'INTL' === $state ) {
		return false;
	}
	$states = (array) usg_meta( $offer_id, 'states', array() );
	return 'all_except' === $mode ? ! in_array( $state, $states, true ) : in_array( $state, $states, true );
}

function usg_operator_offers( int $op_id ): array {
	static $cache = array();
	if ( ! isset( $cache[ $op_id ] ) ) {
		$cache[ $op_id ] = get_posts(
			array(
				'post_type'      => 'usg_offer',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'meta_key'       => '_usg_operator', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $op_id, // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'no_found_rows'  => true,
			)
		);
	}
	return $cache[ $op_id ];
}

function usg_best_offer( int $op_id, ?string $state ): int {
	foreach ( usg_operator_offers( $op_id ) as $offer_id ) {
		if ( usg_offer_valid( (int) $offer_id, $state ) ) {
			return (int) $offer_id;
		}
	}
	return 0;
}

function usg_claim_url( int $op_id, string $tag = '', int $offer_id = 0 ): string {
	$args = array_filter( array( 'tag' => $tag, 'o' => $offer_id ?: null ) );
	return add_query_arg( $args, home_url( '/claim/' . get_post_field( 'post_name', $op_id ) . '/' ) );
}

/**
 * Display data for an operator + offer.
 */
function usg_offer_view( int $op_id, int $offer_id = 0 ): array {
	$review = (int) usg_meta( $op_id, 'review_page' );
	return array(
		'operator' => $op_id,
		'offer'    => $offer_id,
		'name'     => html_entity_decode( get_the_title( $op_id ), ENT_QUOTES, 'UTF-8' ),
		'rating'   => (float) usg_meta( $op_id, 'rating', 0 ),
		'headline' => $offer_id ? usg_tokens( (string) usg_meta( $offer_id, 'headline', get_the_title( $offer_id ) ) ) : (string) usg_meta( $op_id, 'tagline' ),
		'bullets'  => $offer_id ? usg_lines( usg_meta( $offer_id, 'bullets' ) ) : array(),
		'code'     => $offer_id ? (string) usg_meta( $offer_id, 'promo_code' ) : '',
		'terms'    => $offer_id ? (string) usg_meta( $offer_id, 'terms', usg_meta( $op_id, 'terms' ) ) : (string) usg_meta( $op_id, 'terms' ),
		'cta'      => $offer_id ? (string) usg_meta( $offer_id, 'cta_label', __( 'Claim offer', 'usasmartgamers-core' ) ) : __( 'Visit site', 'usasmartgamers-core' ),
		'exclusive'=> $offer_id && usg_meta( $offer_id, 'exclusive' ),
		'review'   => $review && 'publish' === get_post_status( $review ) ? get_permalink( $review ) : '',
		'pill'     => (string) usg_meta( $op_id, 'pill' ),
	);
}

function usg_toplist_items( int $toplist_id, ?string $state, int $limit = 0 ): array {
	$items = (array) usg_meta( $toplist_id, 'items', array() );
	$geo   = '1' === (string) usg_meta( $toplist_id, 'geo', '' );
	$scope = $geo ? $state : null;
	$out   = array();
	foreach ( $items as $it ) {
		$op = (int) ( $it['operator'] ?? 0 );
		if ( ! $op || 'publish' !== get_post_status( $op ) ) {
			continue;
		}
		if ( $geo && ! usg_operator_available( $op, $state ) ) {
			continue;
		}
		$offer = (int) ( $it['offer'] ?? 0 );
		if ( $offer && ( (int) usg_meta( $offer, 'operator' ) !== $op || ! usg_offer_valid( $offer, $scope ) ) ) {
			$offer = 0;
		}
		if ( ! $offer ) {
			$offer = usg_best_offer( $op, $scope );
		}
		$view = usg_offer_view( $op, $offer );
		if ( ! empty( $it['pill'] ) ) {
			$view['pill'] = $it['pill'];
		}
		$out[] = $view;
		if ( $limit && count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}
