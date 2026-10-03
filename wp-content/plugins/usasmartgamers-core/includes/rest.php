<?php
/**
 * REST API: /wp-json/usg/v1/*
 */

defined( 'ABSPATH' ) || exit;

function usg_rest_nocache( $data, int $status = 200 ): WP_REST_Response {
	$r = new WP_REST_Response( $data, $status );
	$r->header( 'Cache-Control', 'no-store, private' );
	return $r;
}

add_action(
	'rest_api_init',
	function () {
		$member = fn() => is_user_logged_in();

		register_rest_route(
			'usg/v1',
			'/geo-block',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $req ) {
					$block = sanitize_key( (string) $req->get_param( 'block' ) );
					if ( 'toplist' !== $block ) {
						return usg_rest_nocache( array( 'error' => 'unsupported' ), 400 );
					}
					$attrs = usg_block_clean_attrs( 'toplist', (array) $req->get_param( 'attrs' ) );
					$state = usg_detect_state();
					$attrs['__state'] = $state;
					return usg_rest_nocache(
						array(
							'state' => $state,
							'label' => usg_state_name( $state ),
							'html'  => usg_block_toplist( $attrs ),
						)
					);
				},
			)
		);

		register_rest_route(
			'usg/v1',
			'/finder',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					$out = array();
					foreach ( get_posts( array( 'post_type' => 'usg_operator', 'post_status' => 'publish', 'posts_per_page' => 300, 'fields' => 'ids' ) ) as $op ) {
						$v      = usg_offer_view( (int) $op, usg_best_offer( (int) $op, null ) );
						$pays   = get_the_terms( $op, 'usg_payment' );
						$out[]  = array(
							'id'        => $op,
							'name'      => $v['name'],
							'vertical'  => usg_meta( $op, 'vertical', 'casino' ),
							'rating'    => $v['rating'],
							'headline'  => $v['headline'],
							'code'      => $v['code'],
							'review'    => $v['review'],
							'claim'     => usg_claim_url( (int) $op, 'finder', $v['offer'] ),
							'logo'      => usg_operator_logo( (int) $op, 'usg-logo--sm' ),
							'mode'      => usg_meta( $op, 'availability', 'listed' ),
							'states'    => array_values( (array) usg_meta( $op, 'states', array() ) ),
							'payments'  => $pays && ! is_wp_error( $pays ) ? wp_list_pluck( $pays, 'slug' ) : array(),
							'payout'    => usg_meta( $op, 'payout_speed' ),
							'minDep'    => usg_meta( $op, 'min_deposit' ),
						);
					}
					$r = new WP_REST_Response( $out );
					$r->header( 'Cache-Control', 'public, max-age=300' );
					return $r;
				},
			)
		);

		register_rest_route(
			'usg/v1',
			'/reviews',
			array(
				'methods'             => 'POST',
				'permission_callback' => $member,
				'callback'            => function ( WP_REST_Request $req ) {
					$res = usg_submit_review( get_current_user_id(), (int) $req->get_param( 'post_id' ), (array) $req->get_json_params() );
					if ( is_wp_error( $res ) ) {
						return usg_rest_nocache( array( 'message' => $res->get_error_message() ), 400 );
					}
					return usg_rest_nocache( array( 'message' => __( 'Thanks! Your review is pending moderation — coins are added once it is approved.', 'usasmartgamers-core' ) ) );
				},
			)
		);

		register_rest_route(
			'usg/v1',
			'/redeem',
			array(
				'methods'             => 'POST',
				'permission_callback' => $member,
				'callback'            => function ( WP_REST_Request $req ) {
					$res = usg_redeem( get_current_user_id(), (int) $req->get_param( 'reward' ) );
					if ( is_wp_error( $res ) ) {
						return usg_rest_nocache( array( 'message' => $res->get_error_message() ), 400 );
					}
					return usg_rest_nocache( array( 'balance' => $res, 'message' => __( 'Redeemed! We will email you when your reward is on its way.', 'usasmartgamers-core' ) ) );
				},
			)
		);

		register_rest_route(
			'usg/v1',
			'/task/slot-demo',
			array(
				'methods'             => 'POST',
				'permission_callback' => $member,
				'callback'            => function ( WP_REST_Request $req ) {
					$slot = (int) $req->get_param( 'slot' );
					if ( 'usg_slot' !== get_post_type( $slot ) ) {
						return usg_rest_nocache( array( 'coins' => 0 ), 400 );
					}
					$c = usg_award( get_current_user_id(), 'slot_demo', 's' . $slot, sprintf( __( 'Played the %s demo', 'usasmartgamers-core' ), get_the_title( $slot ) ) );
					return usg_rest_nocache( array( 'coins' => $c, 'balance' => usg_coin_balance( get_current_user_id() ) ) );
				},
			)
		);

		register_rest_route(
			'usg/v1',
			'/me/seen',
			array(
				'methods'             => 'POST',
				'permission_callback' => $member,
				'callback'            => function () {
					update_user_meta( get_current_user_id(), '_usg_seen', current_time( 'mysql', true ) );
					return usg_rest_nocache( array( 'ok' => true ) );
				},
			)
		);
	}
);
