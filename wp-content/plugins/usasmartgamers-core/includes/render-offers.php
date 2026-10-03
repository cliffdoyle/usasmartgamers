<?php
/**
 * Render callbacks: toplists, offer cards, review summary, comparison, pros/cons, claim buttons, sticky CTA.
 */

defined( 'ABSPATH' ) || exit;

function usg_claim_link( array $v, string $tag, string $class = 'usg-btn usg-btn--cta', string $label = '' ): string {
	$label = $label ?: $v['cta'];
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( usg_claim_url( $v['operator'], $tag, $v['offer'] ) ) . '" rel="nofollow sponsored noopener" target="_blank">' . esc_html( $label ) . ' ' . usg_icon( 'external' ) . '</a>';
}

function usg_code_box( string $code ): string {
	if ( '' === $code ) {
		return '<div class="usg-code usg-code--none"><span class="usg-code__label">' . esc_html__( 'No code needed', 'usasmartgamers-core' ) . '</span></div>';
	}
	return '<div class="usg-code"><span class="usg-code__label">' . esc_html__( 'Promo code', 'usasmartgamers-core' ) . '</span><button type="button" class="usg-code__btn" data-usg-copy="' . esc_attr( $code ) . '" aria-label="' . esc_attr__( 'Copy promo code', 'usasmartgamers-core' ) . '"><strong>' . esc_html( $code ) . '</strong>' . usg_icon( 'copy' ) . '<span class="usg-code__done">' . esc_html__( 'Copied!', 'usasmartgamers-core' ) . '</span></button></div>';
}

function usg_terms_html( string $terms ): string {
	$rg = (string) usg_option( 'offer_rg' );
	if ( '' === trim( $terms ) ) {
		return $rg ? '<p class="usg-terms">' . esc_html( $rg ) . '</p>' : '';
	}
	return '<details class="usg-terms"><summary>' . esc_html( $rg ?: __( 'Terms apply', 'usasmartgamers-core' ) ) . ' <span>' . esc_html__( 'Full T&Cs', 'usasmartgamers-core' ) . '</span></summary><p>' . nl2br( esc_html( $terms ) ) . '</p></details>';
}

function usg_offer_card( array $v, int $rank, string $tag, string $skin ): string {
	$review = $v['review'] ? '<a class="usg-review-link" href="' . esc_url( $v['review'] ) . '">' . esc_html( sprintf( __( '%s review', 'usasmartgamers-core' ), $v['name'] ) ) . '</a>' : '<span class="usg-review-link">' . esc_html( $v['name'] ) . '</span>';
	$pill   = $v['pill'] ? '<span class="usg-pill">' . esc_html( $v['pill'] ) . '</span>' : '';
	$excl   = $v['exclusive'] ? '<span class="usg-pill usg-pill--gold">' . esc_html__( 'Exclusive', 'usasmartgamers-core' ) . '</span>' : '';
	$bul    = '';
	if ( $v['bullets'] ) {
		$bul = '<ul class="usg-bullets">';
		foreach ( $v['bullets'] as $b ) {
			$bul .= '<li>' . usg_icon( 'check' ) . esc_html( $b ) . '</li>';
		}
		$bul .= '</ul>';
	}
	$logo = $v['review'] ? '<a href="' . esc_url( $v['review'] ) . '" class="usg-logo-link" tabindex="-1">' . usg_operator_logo( $v['operator'] ) . '</a>' : usg_operator_logo( $v['operator'] );

	if ( 'cards' === $skin ) {
		return '<article class="usg-offer usg-offer--card"><header class="usg-offer__head">' . $logo . '<h3 class="usg-offer__headline">' . esc_html( $v['headline'] ) . '</h3></header><div class="usg-offer__body"><div class="usg-offer__meta">' . $review . '<span class="usg-offer__rating">' . usg_stars( $v['rating'] ) . '</span></div>' . $pill . $excl . $bul . usg_terms_html( $v['terms'] ) . '<div class="usg-offer__actions">' . usg_code_box( $v['code'] ) . usg_claim_link( $v, $tag . '-' . $rank ) . '</div></div></article>';
	}
	// rows (default)
	return '<article class="usg-offer usg-offer--row"><div class="usg-offer__rank">' . (int) $rank . '</div>' . $logo . '<div class="usg-offer__main"><div class="usg-offer__meta">' . $review . $pill . $excl . '</div><h3 class="usg-offer__headline">' . esc_html( $v['headline'] ) . '</h3>' . $bul . '<span class="usg-offer__rating">' . usg_stars( $v['rating'] ) . '</span></div><div class="usg-offer__side">' . usg_claim_link( $v, $tag . '-' . $rank ) . ( $v['code'] ? usg_code_box( $v['code'] ) : '' ) . '</div><div class="usg-offer__foot">' . usg_terms_html( $v['terms'] ) . '</div></article>';
}

function usg_offer_table( array $items, string $tag ): string {
	$h = '<div class="usg-table-wrap"><table class="usg-table"><thead><tr><th>#</th><th>' . esc_html__( 'Operator', 'usasmartgamers-core' ) . '</th><th>' . esc_html__( 'Offer', 'usasmartgamers-core' ) . '</th><th>' . esc_html__( 'Rating', 'usasmartgamers-core' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $items as $i => $v ) {
		$name = $v['review'] ? '<a href="' . esc_url( $v['review'] ) . '">' . esc_html( $v['name'] ) . '</a>' : esc_html( $v['name'] );
		$h   .= '<tr><td>' . ( $i + 1 ) . '</td><td class="usg-table__op">' . usg_operator_logo( $v['operator'], 'usg-logo--sm' ) . $name . '</td><td>' . esc_html( $v['headline'] ) . ( $v['code'] ? '<br><small>' . esc_html__( 'Code:', 'usasmartgamers-core' ) . ' <strong>' . esc_html( $v['code'] ) . '</strong></small>' : '' ) . '</td><td>' . usg_stars( $v['rating'] ) . '</td><td>' . usg_claim_link( $v, $tag . '-' . ( $i + 1 ), 'usg-btn usg-btn--cta usg-btn--sm' ) . '</td></tr>';
	}
	return $h . '</tbody></table></div>';
}

function usg_render_toplist_inner( int $toplist_id, ?string $state, string $skin, int $limit, string $tag ): string {
	$items = usg_toplist_items( $toplist_id, $state, $limit );
	if ( ! $items ) {
		$msg = 'INTL' === $state ? usg_option( 'intl_message' ) : __( 'No operators are currently available in this location. Check back soon — new states are launching all the time.', 'usasmartgamers-core' );
		return '<div class="usg-notice">' . esc_html( $msg ) . '</div>';
	}
	if ( 'table' === $skin ) {
		return usg_offer_table( $items, $tag );
	}
	$h = '<div class="usg-offers usg-offers--' . esc_attr( $skin ) . '">';
	foreach ( $items as $i => $v ) {
		$h .= usg_offer_card( $v, $i + 1, $tag, $skin );
	}
	return $h . '</div>';
}

function usg_geo_label( ?string $state, bool $locked ): string {
	if ( ! $state ) {
		return '<p class="usg-geo__label">' . usg_icon( 'pin' ) . esc_html__( 'Showing top picks nationwide.', 'usasmartgamers-core' ) . ' <button type="button" class="usg-linkbtn" data-usg-open-picker>' . esc_html__( 'Choose your state', 'usasmartgamers-core' ) . '</button></p>';
	}
	if ( $locked ) {
		return '';
	}
	if ( 'INTL' === $state ) {
		return '<p class="usg-geo__label">' . usg_icon( 'pin' ) . esc_html__( 'You appear to be outside the US, so we’re showing offers for international players.', 'usasmartgamers-core' ) . ' <button type="button" class="usg-linkbtn" data-usg-open-picker>' . esc_html__( 'Choose a US state', 'usasmartgamers-core' ) . '</button></p>';
	}
	/* translators: %s: state name */
	return '<p class="usg-geo__label">' . usg_icon( 'pin' ) . esc_html( sprintf( __( 'Showing offers available in %s.', 'usasmartgamers-core' ), usg_state_name( $state ) ) ) . ' <button type="button" class="usg-linkbtn" data-usg-open-picker>' . esc_html__( 'Change', 'usasmartgamers-core' ) . '</button></p>';
}

function usg_block_toplist( array $a ): string {
	$id = (int) ( $a['toplist'] ?? 0 );
	if ( ! $id ) {
		return '';
	}
	$locked = ! empty( $a['state'] ) && isset( usg_states()[ $a['state'] ] );
	$geo    = array_key_exists( '__state', $a );
	$state  = $locked ? $a['state'] : ( $geo ? $a['__state'] : null );
	$skin   = in_array( $a['skin'] ?? '', array( 'rows', 'cards', 'table' ), true ) ? $a['skin'] : 'rows';
	$tag    = sanitize_key( $a['tag'] ?? 'toplist' ) ?: 'toplist';
	$inner  = usg_geo_label( $state, $locked ) . usg_render_toplist_inner( $id, $state, $skin, (int) ( $a['limit'] ?? 10 ), $tag );
	if ( $geo ) {
		return $inner;
	}
	$title = ! empty( $a['title'] ) ? '<h2 class="usg-block-title">' . esc_html( usg_tokens( $a['title'] ) ) . '</h2>' : '';
	$data  = $locked ? '' : ' data-usg-geo="' . esc_attr( wp_json_encode( array( 'block' => 'toplist', 'attrs' => usg_block_clean_attrs( 'toplist', $a ) ) ) ) . '"';
	return '<section class="usg-toplist">' . $title . '<div class="usg-geo"' . $data . '>' . $inner . '</div></section>';
}

function usg_block_toplist_tabs( array $a ): string {
	$tabs = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		if ( ! empty( $a[ "tab{$i}_toplist" ] ) ) {
			$tabs[] = array( $a[ "tab{$i}_label" ] ?: get_the_title( (int) $a[ "tab{$i}_toplist" ] ), (int) $a[ "tab{$i}_toplist" ] );
		}
	}
	if ( ! $tabs ) {
		return '';
	}
	$uid   = 'usg-tabs-' . wp_unique_id();
	$title = ! empty( $a['title'] ) ? '<h2 class="usg-block-title">' . esc_html( usg_tokens( $a['title'] ) ) . '</h2>' : '';
	$nav   = '<div class="usg-tabs__nav" role="tablist">';
	$pan   = '';
	foreach ( $tabs as $i => $t ) {
		$nav .= '<button type="button" role="tab" class="usg-tabs__tab" id="' . esc_attr( "$uid-t$i" ) . '" aria-controls="' . esc_attr( "$uid-p$i" ) . '" aria-selected="' . ( 0 === $i ? 'true' : 'false' ) . '">' . esc_html( $t[0] ) . '</button>';
		$pan .= '<div class="usg-tabs__panel" role="tabpanel" id="' . esc_attr( "$uid-p$i" ) . '" aria-labelledby="' . esc_attr( "$uid-t$i" ) . '"' . ( 0 === $i ? '' : ' hidden' ) . '>' . usg_block_toplist( array( 'toplist' => $t[1], 'skin' => $a['skin'] ?? 'rows', 'limit' => (int) ( $a['limit'] ?? 5 ), 'tag' => 'tabs-' . sanitize_key( $t[0] ) ) ) . '</div>';
	}
	return '<section class="usg-tabs">' . $title . $nav . '</div>' . $pan . '</section>';
}

function usg_resolve_offer( array $a ): array {
	$op = (int) ( $a['operator'] ?? 0 );
	if ( ! $op || 'publish' !== get_post_status( $op ) ) {
		return array();
	}
	$offer = (int) ( $a['offer'] ?? 0 );
	if ( ! $offer || (int) usg_meta( $offer, 'operator' ) !== $op || ! usg_offer_valid( $offer, null ) ) {
		$offer = usg_best_offer( $op, null );
	}
	return usg_offer_view( $op, $offer );
}

function usg_block_cta_card( array $a ): string {
	$v = usg_resolve_offer( $a );
	return $v ? '<div class="usg-offers usg-offers--cards usg-offers--single">' . usg_offer_card( $v, 1, sanitize_key( $a['tag'] ?? 'offer-card' ) ?: 'offer-card', 'cards' ) . '</div>' : '';
}

function usg_block_bonus_card( array $a ): string {
	$v = usg_resolve_offer( $a );
	if ( ! $v ) {
		return '';
	}
	$eyebrow = ! empty( $a['title'] ) ? '<span class="usg-eyebrow">' . esc_html( $a['title'] ) . '</span>' : '';
	$bul     = '';
	foreach ( $v['bullets'] as $b ) {
		$bul .= '<li>' . usg_icon( 'check' ) . esc_html( $b ) . '</li>';
	}
	return '<aside class="usg-bonus">' . usg_operator_logo( $v['operator'], 'usg-logo--lg' ) . '<div class="usg-bonus__body">' . $eyebrow . '<p class="usg-bonus__name">' . esc_html( $v['name'] ) . ' ' . usg_stars( $v['rating'] ) . '</p><h3 class="usg-bonus__headline">' . esc_html( $v['headline'] ) . '</h3>' . ( $bul ? '<ul class="usg-bullets">' . $bul . '</ul>' : '' ) . '<div class="usg-offer__actions">' . usg_code_box( $v['code'] ) . usg_claim_link( $v, 'bonus-card' ) . '</div>' . usg_terms_html( $v['terms'] ) . '</div></aside>';
}

function usg_block_claim_button( array $a ): string {
	$v = usg_resolve_offer( $a );
	if ( ! $v ) {
		return '';
	}
	$style = in_array( $a['style'] ?? '', array( 'green', 'red', 'navy' ), true ) ? $a['style'] : 'green';
	return '<div class="usg-claim">' . usg_claim_link( $v, sanitize_key( $a['tag'] ?? 'inline-button' ) ?: 'inline-button', 'usg-btn usg-btn--' . $style . ' usg-btn--block', $a['label'] ?? '' ) . '<p class="usg-claim__rg">' . esc_html( (string) usg_option( 'offer_rg' ) ) . '</p></div>';
}

function usg_availability_text( int $op ): string {
	$mode   = usg_meta( $op, 'availability', 'listed' );
	$states = (array) usg_meta( $op, 'states', array() );
	$names  = array_map( 'usg_state_name', $states );
	if ( 'international' === $mode ) {
		return __( 'Available outside the US only.', 'usasmartgamers-core' );
	}
	if ( 'all_except' === $mode ) {
		/* translators: %s: list of states */
		return $names ? sprintf( __( 'Available in most US states except %s.', 'usasmartgamers-core' ), implode( ', ', $names ) ) : __( 'Available nationwide.', 'usasmartgamers-core' );
	}
	/* translators: %s: list of states */
	return $names ? sprintf( __( 'Legal and available in %s.', 'usasmartgamers-core' ), implode( ', ', $names ) ) : '';
}

function usg_block_review_summary( array $a ): string {
	$op = (int) ( $a['operator'] ?? 0 );
	if ( ! $op ) {
		return '';
	}
	$v      = usg_offer_view( $op, usg_best_offer( $op, null ) );
	$facts  = array(
		'payout_speed' => __( 'Payout speed', 'usasmartgamers-core' ),
		'min_deposit'  => __( 'Min. deposit', 'usasmartgamers-core' ),
		'win_rate'     => __( 'Avg. RTP', 'usasmartgamers-core' ),
		'games_count'  => __( 'Games', 'usasmartgamers-core' ),
		'license'      => __( 'Licensed by', 'usasmartgamers-core' ),
		'support'      => __( 'Support', 'usasmartgamers-core' ),
	);
	$fact_h = '';
	foreach ( $facts as $k => $label ) {
		$val = usg_meta( $op, $k );
		if ( $val ) {
			$fact_h .= '<div class="usg-fact"><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $val ) . '</dd></div>';
		}
	}
	$payments = get_the_terms( $op, 'usg_payment' );
	if ( $payments && ! is_wp_error( $payments ) ) {
		$fact_h .= '<div class="usg-fact usg-fact--wide"><dt>' . esc_html__( 'Banking', 'usasmartgamers-core' ) . '</dt><dd>' . esc_html( implode( ', ', wp_list_pluck( $payments, 'name' ) ) ) . '</dd></div>';
	}
	$scores = '';
	foreach ( (array) usg_meta( $op, 'scores', array() ) as $s ) {
		$sc      = max( 0, min( 5, (float) ( $s['score'] ?? 0 ) ) );
		$scores .= '<li class="usg-grade"><div class="usg-grade__top"><span>' . esc_html( $s['label'] ?? '' ) . '</span><strong>' . esc_html( number_format_i18n( $sc, 1 ) ) . '/5</strong></div><span class="usg-bar"><span style="width:' . esc_attr( $sc / 5 * 100 ) . '%"></span></span>' . ( ! empty( $s['note'] ) ? '<p>' . esc_html( $s['note'] ) . '</p>' : '' ) . '</li>';
	}
	$suits = '';
	foreach ( usg_lines( usg_meta( $op, 'suits' ) ) as $l ) {
		$suits .= '<li>' . usg_icon( 'check' ) . esc_html( $l ) . '</li>';
	}
	$avail = usg_availability_text( $op );

	usg_schema_add(
		array(
			'@type'        => 'Review',
			'name'         => get_the_title( $op ) . ' review',
			'itemReviewed' => array( '@type' => 'Organization', 'name' => get_the_title( $op ) ),
			'reviewRating' => array( '@type' => 'Rating', 'ratingValue' => $v['rating'], 'bestRating' => 5, 'worstRating' => 0 ),
			'author'       => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', get_the_ID() ) ) ),
		)
	);

	return '<section class="usg-summary"><div class="usg-summary__top">' . usg_operator_logo( $op, 'usg-logo--lg' ) . '<div class="usg-summary__score"><span class="usg-score">' . esc_html( number_format_i18n( $v['rating'], 1 ) ) . '</span><span class="usg-score__label">' . esc_html__( 'Overall editorial score', 'usasmartgamers-core' ) . '</span>' . usg_stars( $v['rating'], false ) . '</div><div class="usg-summary__offer"><p class="usg-summary__headline">' . esc_html( $v['headline'] ) . '</p>' . usg_claim_link( $v, 'review-summary', 'usg-btn usg-btn--green usg-btn--block' ) . usg_code_box( $v['code'] ) . '</div></div>'
		. ( $fact_h ? '<dl class="usg-facts">' . $fact_h . '</dl>' : '' )
		. ( $scores ? '<ul class="usg-grades">' . $scores . '</ul>' : '' )
		. ( $avail ? '<p class="usg-summary__avail">' . usg_icon( 'shield' ) . esc_html( $avail ) . '</p>' : '' )
		. ( $suits ? '<div class="usg-summary__suits"><p><strong>' . esc_html( sprintf( __( '%s will suit players who…', 'usasmartgamers-core' ), get_the_title( $op ) ) ) . '</strong></p><ul class="usg-bullets">' . $suits . '</ul></div>' : '' )
		. usg_terms_html( $v['terms'] ) . '</section>';
}

function usg_block_comparison( array $a ): string {
	$ids = array_filter( array_map( 'absint', (array) ( $a['operators'] ?? array() ) ) );
	if ( ! $ids ) {
		return '';
	}
	$h = ( ! empty( $a['title'] ) ? '<h2 class="usg-block-title">' . esc_html( usg_tokens( $a['title'] ) ) . '</h2>' : '' ) . '<div class="usg-table-wrap"><table class="usg-table usg-table--compare"><thead><tr><th>' . esc_html__( 'Operator', 'usasmartgamers-core' ) . '</th><th>' . esc_html__( 'Rating', 'usasmartgamers-core' ) . '</th><th>' . esc_html__( 'Welcome offer', 'usasmartgamers-core' ) . '</th><th>' . esc_html__( 'Payout speed', 'usasmartgamers-core' ) . '</th><th>' . esc_html__( 'Min. deposit', 'usasmartgamers-core' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $ids as $op ) {
		if ( 'publish' !== get_post_status( $op ) ) {
			continue;
		}
		$v  = usg_offer_view( $op, usg_best_offer( $op, null ) );
		$h .= '<tr><td class="usg-table__op">' . usg_operator_logo( $op, 'usg-logo--sm' ) . ( $v['review'] ? '<a href="' . esc_url( $v['review'] ) . '">' . esc_html( $v['name'] ) . '</a>' : esc_html( $v['name'] ) ) . '</td><td>' . usg_stars( $v['rating'] ) . '</td><td>' . esc_html( $v['headline'] ) . '</td><td>' . esc_html( usg_meta( $op, 'payout_speed', '—' ) ) . '</td><td>' . esc_html( usg_meta( $op, 'min_deposit', '—' ) ) . '</td><td>' . usg_claim_link( $v, 'comparison', 'usg-btn usg-btn--cta usg-btn--sm' ) . '</td></tr>';
	}
	return '<section class="usg-compare">' . $h . '</tbody></table></div></section>';
}

function usg_block_pros_cons( array $a ): string {
	$op   = (int) ( $a['operator'] ?? 0 );
	$pros = usg_lines( $a['pros'] ?? '' ) ?: ( $op ? usg_lines( usg_meta( $op, 'pros' ) ) : array() );
	$cons = usg_lines( $a['cons'] ?? '' ) ?: ( $op ? usg_lines( usg_meta( $op, 'cons' ) ) : array() );
	if ( ! $pros && ! $cons ) {
		return '';
	}
	$li = function ( array $items, string $icon ): string {
		$h = '';
		foreach ( $items as $i ) {
			$h .= '<li>' . usg_icon( $icon ) . esc_html( $i ) . '</li>';
		}
		return $h;
	};
	return '<div class="usg-proscons"><div class="usg-proscons__col usg-proscons__col--pros"><h3>' . esc_html__( 'Pros', 'usasmartgamers-core' ) . '</h3><ul>' . $li( $pros, 'check' ) . '</ul></div><div class="usg-proscons__col usg-proscons__col--cons"><h3>' . esc_html__( 'Cons', 'usasmartgamers-core' ) . '</h3><ul>' . $li( $cons, 'x' ) . '</ul></div></div>';
}

/**
 * Sticky footer CTA (theme calls this).
 */
function usg_sticky_cta( int $op ): string {
	if ( ! $op || 'publish' !== get_post_status( $op ) ) {
		return '';
	}
	$v = usg_offer_view( $op, usg_best_offer( $op, null ) );
	return '<div class="usg-sticky-cta" data-usg-sticky hidden><div class="usg-sticky-cta__inner">' . usg_operator_logo( $op, 'usg-logo--sm' ) . '<p class="usg-sticky-cta__text"><strong>' . esc_html( $v['name'] ) . '</strong> ' . esc_html( $v['headline'] ) . '</p>' . usg_claim_link( $v, 'sticky-footer', 'usg-btn usg-btn--green' ) . '<button type="button" class="usg-sticky-cta__close" aria-label="' . esc_attr__( 'Close', 'usasmartgamers-core' ) . '">' . usg_icon( 'x' ) . '</button></div></div>';
}
