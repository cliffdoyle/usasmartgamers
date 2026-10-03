<?php
/**
 * Affiliate redirects: /claim/{operator}/?tag={placement}&o={offer}
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		add_rewrite_rule( '^claim/([^/]+)/?$', 'index.php?usg_claim=$matches[1]', 'top' );
	}
);

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'usg_claim';
		return $vars;
	}
);

add_action(
	'template_redirect',
	function () {
		$slug = get_query_var( 'usg_claim' );
		if ( ! $slug ) {
			return;
		}
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );

		$op = get_page_by_path( sanitize_title( $slug ), OBJECT, 'usg_operator' );
		if ( ! $op || 'publish' !== $op->post_status ) {
			wp_safe_redirect( home_url( '/' ), 302 );
			exit;
		}
		$state = usg_detect_state();
		$offer = isset( $_GET['o'] ) ? absint( $_GET['o'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $offer && ( (int) usg_meta( $offer, 'operator' ) !== $op->ID || ! usg_offer_valid( $offer, $state ) ) ) {
			$offer = 0;
		}
		if ( ! $offer ) {
			$offer = usg_best_offer( $op->ID, $state );
		}
		$tag = isset( $_GET['tag'] ) ? substr( sanitize_key( wp_unslash( $_GET['tag'] ) ), 0, 64 ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$url = $offer ? (string) usg_meta( $offer, 'affiliate_url' ) : '';
		if ( ! $url ) {
			$url = (string) usg_meta( $op->ID, 'affiliate_url' );
		}
		$url = esc_url_raw( str_replace( '{tag}', rawurlencode( $tag ?: 'site' ), $url ), array( 'http', 'https' ) );
		if ( ! $url ) {
			$review = (int) usg_meta( $op->ID, 'review_page' );
			wp_safe_redirect( $review ? get_permalink( $review ) : home_url( '/' ), 302 );
			exit;
		}

		global $wpdb;
		$ref  = wp_get_referer();
		$page = $ref ? (string) wp_parse_url( $ref, PHP_URL_PATH ) : '';
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'usg_clicks',
			array(
				'created'     => current_time( 'mysql', true ),
				'operator_id' => $op->ID,
				'offer_id'    => $offer,
				'tag'         => $tag,
				'page'        => substr( $page, 0, 255 ),
				'state'       => (string) $state,
				'user_id'     => get_current_user_id(),
			)
		);
		wp_redirect( $url, 302, 'USA Smart Gamers' ); // phpcs:ignore WordPress.Security.SafeRedirect -- external affiliate destinations are admin-controlled.
		exit;
	},
	1
);
