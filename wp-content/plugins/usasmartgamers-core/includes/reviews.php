<?php
/**
 * Player reviews: stored as comments (type usg_review) on operators and slots, moderated in Comments.
 */

defined( 'ABSPATH' ) || exit;

function usg_review_subratings( int $post_id ): array {
	return 'usg_slot' === get_post_type( $post_id )
		? array( 'features' => __( 'Bonus features', 'usasmartgamers-core' ), 'payout' => __( 'Payout potential', 'usasmartgamers-core' ), 'graphics' => __( 'Graphics & sound', 'usasmartgamers-core' ), 'fun' => __( 'Fun factor', 'usasmartgamers-core' ) )
		: array( 'bonus' => __( 'Bonuses', 'usasmartgamers-core' ), 'games' => __( 'Games', 'usasmartgamers-core' ), 'payouts' => __( 'Payouts', 'usasmartgamers-core' ), 'app' => __( 'App & UX', 'usasmartgamers-core' ), 'support' => __( 'Support', 'usasmartgamers-core' ) );
}

function usg_review_stats( int $post_id ): array {
	$comments = get_comments( array( 'post_id' => $post_id, 'type' => 'usg_review', 'status' => 'approve', 'number' => 500 ) );
	$sum      = 0;
	$subs     = array();
	foreach ( $comments as $c ) {
		$sum += (int) get_comment_meta( $c->comment_ID, 'usg_rating', true );
		foreach ( (array) get_comment_meta( $c->comment_ID, 'usg_sub', true ) as $k => $v ) {
			$subs[ $k ][] = (int) $v;
		}
	}
	$n = count( $comments );
	return array(
		'count'    => $n,
		'avg'      => $n ? round( $sum / $n, 1 ) : 0,
		'subs'     => array_map( fn( $a ) => round( array_sum( $a ) / max( 1, count( $a ) ), 1 ), $subs ),
		'comments' => $comments,
	);
}

function usg_star_input( string $name, string $label, bool $required = false ): string {
	$h = '<fieldset class="usg-starinput"><legend>' . esc_html( $label ) . '</legend><div class="usg-starinput__stars">';
	for ( $i = 5; $i >= 1; $i-- ) {
		$id = 'usg-' . sanitize_html_class( $name ) . '-' . $i . '-' . wp_unique_id();
		$h .= '<input type="radio" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . $i . '"' . ( $required && 5 === $i ? ' required' : '' ) . '><label for="' . esc_attr( $id ) . '" title="' . $i . '">★</label>';
	}
	return $h . '</div></fieldset>';
}

function usg_render_user_reviews( int $post_id, string $title = '' ): string {
	if ( ! $post_id || ! in_array( get_post_type( $post_id ), array( 'usg_operator', 'usg_slot' ), true ) ) {
		return '';
	}
	$stats = usg_review_stats( $post_id );
	$labels = usg_review_subratings( $post_id );
	$h      = '<div class="usg-ureviews__summary"><div class="usg-ureviews__avg"><span class="usg-score">' . esc_html( $stats['count'] ? number_format_i18n( $stats['avg'], 1 ) : '–' ) . '</span>' . usg_stars( $stats['avg'], false ) . '<span>' . esc_html( sprintf( _n( '%s player review', '%s player reviews', $stats['count'], 'usasmartgamers-core' ), number_format_i18n( $stats['count'] ) ) ) . '</span></div><ul class="usg-grades usg-grades--compact">';
	foreach ( $labels as $k => $l ) {
		$v  = $stats['subs'][ $k ] ?? 0;
		$h .= '<li class="usg-grade"><div class="usg-grade__top"><span>' . esc_html( $l ) . '</span><strong>' . esc_html( $v ? number_format_i18n( $v, 1 ) : '–' ) . '</strong></div><span class="usg-bar"><span style="width:' . esc_attr( $v / 5 * 100 ) . '%"></span></span></li>';
	}
	$h .= '</ul></div>';

	if ( $stats['comments'] ) {
		$h .= '<ol class="usg-ureviews__list" data-usg-showmore="5">';
		foreach ( $stats['comments'] as $c ) {
			$h .= '<li class="usg-ureview">' . get_avatar( $c, 40 ) . '<div><p class="usg-ureview__head"><strong>' . esc_html( $c->comment_author ) . '</strong> ' . usg_stars( (int) get_comment_meta( $c->comment_ID, 'usg_rating', true ), false ) . '</p>' . ( get_comment_meta( $c->comment_ID, 'usg_title', true ) ? '<p class="usg-ureview__title">' . esc_html( get_comment_meta( $c->comment_ID, 'usg_title', true ) ) . '</p>' : '' ) . '<p>' . esc_html( $c->comment_content ) . '</p><time>' . esc_html( sprintf( __( 'Posted %s ago', 'usasmartgamers-core' ), human_time_diff( strtotime( $c->comment_date_gmt . ' UTC' ) ) ) ) . '</time></div></li>';
		}
		$h .= '</ol>';
	} else {
		$h .= '<p class="usg-notice">' . esc_html__( 'No player reviews yet — be the first.', 'usasmartgamers-core' ) . '</p>';
	}

	if ( is_user_logged_in() ) {
		$subs = '';
		foreach ( $labels as $k => $l ) {
			$subs .= usg_star_input( 'sub[' . $k . ']', $l );
		}
		$h .= '<details class="usg-ureviews__formwrap"><summary class="usg-btn usg-btn--navy">' . esc_html__( 'Write a review', 'usasmartgamers-core' ) . ( usg_rewards_enabled() ? ' · ' . esc_html__( 'earn coins', 'usasmartgamers-core' ) : '' ) . '</summary><form class="usg-ureviews__form" data-usg-review="' . (int) $post_id . '">' . usg_star_input( 'rating', __( 'Overall rating', 'usasmartgamers-core' ), true ) . '<div class="usg-ureviews__subs">' . $subs . '</div><label>' . esc_html__( 'Title (optional)', 'usasmartgamers-core' ) . '<input type="text" name="title" maxlength="80"></label><label>' . esc_html__( 'Your review (optional — 80+ characters earns the review bonus)', 'usasmartgamers-core' ) . '<textarea name="content" rows="4" maxlength="2000"></textarea></label><button type="submit" class="usg-btn usg-btn--green">' . esc_html__( 'Submit review', 'usasmartgamers-core' ) . '</button><p class="usg-form-msg" aria-live="polite"></p></form></details>';
	} else {
		$h .= '<a class="usg-btn usg-btn--navy usg-btn--block" href="' . esc_url( usg_page_url_by_path( 'account', '/account/' ) ) . '">' . esc_html__( 'Join or log in to write a review', 'usasmartgamers-core' ) . ( usg_rewards_enabled() ? ' — ' . esc_html__( 'earn free coins', 'usasmartgamers-core' ) : '' ) . usg_icon( 'arrow' ) . '</a>';
	}
	return '<section class="usg-ureviews" id="player-reviews">' . ( $title ? '<h2 class="usg-block-title">' . esc_html( $title ) . '</h2>' : '' ) . $h . '</section>';
}

function usg_block_user_reviews( array $a ): string {
	return usg_render_user_reviews( (int) ( $a['operator'] ?? 0 ), (string) ( $a['title'] ?? '' ) );
}

function usg_submit_review( int $user_id, int $post_id, array $in ) {
	if ( ! in_array( get_post_type( $post_id ), array( 'usg_operator', 'usg_slot' ), true ) || 'publish' !== get_post_status( $post_id ) ) {
		return new WP_Error( 'usg_review', __( 'You cannot review this item.', 'usasmartgamers-core' ) );
	}
	$rating = (int) ( $in['rating'] ?? 0 );
	if ( $rating < 1 || $rating > 5 ) {
		return new WP_Error( 'usg_review', __( 'Please choose an overall star rating.', 'usasmartgamers-core' ) );
	}
	if ( get_comments( array( 'post_id' => $post_id, 'user_id' => $user_id, 'type' => 'usg_review', 'count' => true, 'status' => 'all' ) ) ) {
		return new WP_Error( 'usg_review', __( 'You have already reviewed this one — thanks!', 'usasmartgamers-core' ) );
	}
	global $wpdb;
	$today = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->comments WHERE user_id=%d AND comment_type='usg_review' AND comment_date_gmt >= %s", $user_id, gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ) ) ); // phpcs:ignore
	if ( $today >= 5 ) {
		return new WP_Error( 'usg_review', __( 'You have reached today’s review limit. Please come back tomorrow.', 'usasmartgamers-core' ) );
	}
	$user  = get_userdata( $user_id );
	$valid = array_keys( usg_review_subratings( $post_id ) );
	$subs  = array();
	foreach ( (array) ( $in['sub'] ?? array() ) as $k => $v ) {
		if ( in_array( $k, $valid, true ) && (int) $v >= 1 && (int) $v <= 5 ) {
			$subs[ $k ] = (int) $v;
		}
	}
	$cid = wp_insert_comment(
		array(
			'comment_post_ID'  => $post_id,
			'comment_author'   => $user->display_name,
			'comment_author_email' => $user->user_email,
			'comment_content'  => sanitize_textarea_field( mb_substr( (string) ( $in['content'] ?? '' ), 0, 2000 ) ),
			'comment_type'     => 'usg_review',
			'comment_approved' => 0,
			'user_id'          => $user_id,
		)
	);
	if ( ! $cid ) {
		return new WP_Error( 'usg_review', __( 'Could not save your review.', 'usasmartgamers-core' ) );
	}
	add_comment_meta( $cid, 'usg_rating', $rating );
	add_comment_meta( $cid, 'usg_sub', $subs );
	add_comment_meta( $cid, 'usg_title', sanitize_text_field( mb_substr( (string) ( $in['title'] ?? '' ), 0, 80 ) ) );
	return $cid;
}

/* Award coins when a review is approved (once). */
add_action(
	'transition_comment_status',
	function ( $new, $old, $comment ) {
		if ( 'approved' !== $new || 'usg_review' !== $comment->comment_type || get_comment_meta( $comment->comment_ID, 'usg_awarded', true ) ) {
			return;
		}
		$action = mb_strlen( trim( $comment->comment_content ) ) >= 80 ? 'review' : 'rating';
		usg_award( (int) $comment->user_id, $action, 'c' . $comment->comment_ID, sprintf( __( 'Review approved: %s', 'usasmartgamers-core' ), get_the_title( $comment->comment_post_ID ) ) );
		add_comment_meta( $comment->comment_ID, 'usg_awarded', 1 );
	},
	10,
	3
);

/* Make reviews filterable in the Comments screen. */
add_filter(
	'admin_comment_types_dropdown',
	function ( $types ) {
		$types['usg_review'] = __( 'Player reviews', 'usasmartgamers-core' );
		return $types;
	}
);

add_filter(
	'comment_text',
	function ( $text, $comment = null ) {
		if ( is_admin() && $comment && 'usg_review' === $comment->comment_type ) {
			$r    = (int) get_comment_meta( $comment->comment_ID, 'usg_rating', true );
			$text = '<p><strong>' . str_repeat( '★', $r ) . str_repeat( '☆', 5 - $r ) . '</strong> ' . esc_html( (string) get_comment_meta( $comment->comment_ID, 'usg_title', true ) ) . '</p>' . $text;
		}
		return $text;
	},
	10,
	2
);
