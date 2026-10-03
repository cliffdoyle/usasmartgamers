<?php
/**
 * GET /wp-json/usg/v1/health — used by the deploy pipeline smoke test.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'usg/v1',
			'/health',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					$response = new WP_REST_Response(
						array(
							'status' => 'ok',
							'core'   => USG_CORE_VERSION,
							'build'  => function_exists( 'usg_build_id' ) ? usg_build_id() : 'unknown',
						)
					);
					$response->header( 'Cache-Control', 'no-store' );
					return $response;
				},
			)
		);
	}
);
