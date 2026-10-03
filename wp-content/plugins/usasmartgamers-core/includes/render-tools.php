<?php
/**
 * Render callbacks: calculators, casino finder, bill tracker. Interactivity lives in assets/front.js.
 */

defined( 'ABSPATH' ) || exit;

function usg_calc_field( string $name, string $label, string $value, string $extra = '' ): string {
	return '<label class="usg-calc__field"><span>' . esc_html( $label ) . '</span><input type="number" step="any" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" ' . $extra . '></label>';
}

function usg_block_calculator( array $a ): string {
	$type = $a['type'] ?? 'bonus';
	switch ( $type ) {
		case 'odds':
			$title  = __( 'Odds converter', 'usasmartgamers-core' );
			$fields = '<label class="usg-calc__field"><span>' . esc_html__( 'Odds format', 'usasmartgamers-core' ) . '</span><select name="format"><option value="american">American (+150 / -110)</option><option value="decimal">Decimal (2.50)</option><option value="fractional">Fractional (3/2)</option></select></label><label class="usg-calc__field"><span>' . esc_html__( 'Odds', 'usasmartgamers-core' ) . '</span><input type="text" name="odds" value="+150"></label>' . usg_calc_field( 'stake', __( 'Stake ($)', 'usasmartgamers-core' ), '10' );
			$out    = array( 'american' => 'American', 'decimal' => 'Decimal', 'fractional' => 'Fractional', 'implied' => 'Implied probability', 'payout' => 'Total payout', 'profit' => 'Profit' );
			break;
		case 'implied':
			$title  = __( 'Implied probability & vig calculator', 'usasmartgamers-core' );
			$fields = '<label class="usg-calc__field"><span>' . esc_html__( 'Side A odds (American)', 'usasmartgamers-core' ) . '</span><input type="text" name="a" value="-110"></label><label class="usg-calc__field"><span>' . esc_html__( 'Side B odds (American)', 'usasmartgamers-core' ) . '</span><input type="text" name="b" value="-110"></label>';
			$out    = array( 'pa' => 'Side A implied', 'pb' => 'Side B implied', 'vig' => 'Bookmaker margin (vig)', 'fa' => 'Side A no-vig', 'fb' => 'Side B no-vig' );
			break;
		case 'hedge':
			$title  = __( 'Hedge bet calculator', 'usasmartgamers-core' );
			$fields = usg_calc_field( 'stake', __( 'Original stake ($)', 'usasmartgamers-core' ), '100' ) . '<label class="usg-calc__field"><span>' . esc_html__( 'Original odds (American)', 'usasmartgamers-core' ) . '</span><input type="text" name="odds1" value="+300"></label><label class="usg-calc__field"><span>' . esc_html__( 'Hedge odds (American)', 'usasmartgamers-core' ) . '</span><input type="text" name="odds2" value="-150"></label>';
			$out    = array( 'hedge' => 'Hedge stake for equal profit', 'profit' => 'Guaranteed profit', 'ifwin1' => 'Profit if original wins (no hedge)' );
			break;
		case 'martingale':
			$title  = __( 'Martingale calculator', 'usasmartgamers-core' );
			$fields = usg_calc_field( 'base', __( 'Base bet ($)', 'usasmartgamers-core' ), '5' ) . usg_calc_field( 'mult', __( 'Multiplier after a loss', 'usasmartgamers-core' ), '2' ) . usg_calc_field( 'losses', __( 'Losing streak to survive', 'usasmartgamers-core' ), '7', 'min="1" max="20"' ) . usg_calc_field( 'bankroll', __( 'Your bankroll ($)', 'usasmartgamers-core' ), '500' );
			$out    = array( 'needed' => 'Bankroll needed', 'lastbet' => 'Size of final bet', 'survive' => 'Losses your bankroll survives' );
			break;
		default:
			$type   = 'bonus';
			$title  = __( 'Casino bonus & wagering calculator', 'usasmartgamers-core' );
			$fields = usg_calc_field( 'deposit', __( 'Deposit ($)', 'usasmartgamers-core' ), '100' ) . usg_calc_field( 'match', __( 'Match (%)', 'usasmartgamers-core' ), '100' ) . usg_calc_field( 'max', __( 'Max bonus ($)', 'usasmartgamers-core' ), '500' ) . usg_calc_field( 'wager', __( 'Wagering requirement (x)', 'usasmartgamers-core' ), '15' ) . '<label class="usg-calc__field"><span>' . esc_html__( 'Wagering applies to', 'usasmartgamers-core' ) . '</span><select name="applies"><option value="bonus">Bonus only</option><option value="both">Deposit + bonus</option></select></label>' . usg_calc_field( 'contrib', __( 'Game contribution (%)', 'usasmartgamers-core' ), '100' ) . usg_calc_field( 'rtp', __( 'Game RTP (%)', 'usasmartgamers-core' ), '96' );
			$out    = array( 'bonus' => 'Bonus amount', 'playthrough' => 'Total wagering needed', 'loss' => 'Expected loss while wagering', 'ev' => 'Expected value of bonus' );
	}
	$res = '';
	foreach ( $out as $k => $l ) {
		$res .= '<div class="usg-calc__out"><dt>' . esc_html( $l ) . '</dt><dd data-out="' . esc_attr( $k ) . '">—</dd></div>';
	}
	return '<section class="usg-calc" data-usg-calc="' . esc_attr( $type ) . '"><h2 class="usg-calc__title">' . esc_html( $title ) . '</h2><form class="usg-calc__form" onsubmit="return false">' . $fields . '</form><dl class="usg-calc__results">' . $res . '</dl><p class="usg-calc__note">' . esc_html__( 'For illustration only. Results are mathematical estimates, not guarantees. Gamble responsibly — 21+.', 'usasmartgamers-core' ) . '</p>' . ( 'martingale' === $type ? '<div class="usg-table-wrap"><table class="usg-table" data-out="table"></table></div>' : '' ) . '</section>';
}

function usg_block_casino_finder( array $a ): string {
	$verts = '<option value="">' . esc_html__( 'All types', 'usasmartgamers-core' ) . '</option>';
	foreach ( usg_verticals() as $k => $l ) {
		$verts .= '<option value="' . esc_attr( $k ) . '"' . selected( $k, $a['vertical'] ?? '', false ) . '>' . esc_html( $l ) . '</option>';
	}
	$states = '<option value="">' . esc_html__( 'Any state', 'usasmartgamers-core' ) . '</option>';
	foreach ( usg_states() as $k => $l ) {
		$states .= '<option value="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</option>';
	}
	$pays = '<option value="">' . esc_html__( 'Any payment method', 'usasmartgamers-core' ) . '</option>';
	foreach ( get_terms( array( 'taxonomy' => 'usg_payment', 'hide_empty' => false ) ) as $t ) {
		$pays .= '<option value="' . esc_attr( $t->slug ) . '">' . esc_html( $t->name ) . '</option>';
	}
	return '<section class="usg-finder" data-usg-finder><form class="usg-finder__filters" onsubmit="return false"><label class="usg-finder__search">' . usg_icon( 'search' ) . '<input type="search" name="q" placeholder="' . esc_attr__( 'Search by name…', 'usasmartgamers-core' ) . '"></label><select name="vertical">' . $verts . '</select><select name="state">' . $states . '</select><select name="payment">' . $pays . '</select><select name="sort"><option value="rating">' . esc_html__( 'Top rated', 'usasmartgamers-core' ) . '</option><option value="name">A–Z</option></select></form><p class="usg-finder__count" aria-live="polite"></p><div class="usg-finder__results"><p class="usg-notice">' . esc_html__( 'Loading operators…', 'usasmartgamers-core' ) . '</p></div></section>';
}

function usg_block_bill_tracker( array $a ): string {
	$groups = array(
		'pending'  => __( 'States with active proposals', 'usasmartgamers-core' ),
		'legal'    => __( 'Legal online casino states', 'usasmartgamers-core' ),
		'unlikely' => __( 'Not legal / unlikely to legalize soon', 'usasmartgamers-core' ),
	);
	$by = array( 'pending' => array(), 'legal' => array(), 'unlikely' => array() );
	foreach ( usg_state_status_map( 'casino' ) as $code => $d ) {
		$by[ $d['status'] ?: 'unlikely' ][] = $d['term'];
	}
	$h = '<h2 class="usg-block-title">' . esc_html( $a['title'] ?: __( 'Online casino legislation by state', 'usasmartgamers-core' ) ) . '</h2>';
	$h .= usg_block_state_map( array( 'vertical' => 'casino' ) );
	foreach ( $groups as $k => $label ) {
		if ( empty( $by[ $k ] ) ) {
			continue;
		}
		$h .= '<h3>' . esc_html( $label ) . ' <span class="usg-count">' . count( $by[ $k ] ) . '</span></h3><div class="usg-bills">';
		foreach ( $by[ $k ] as $t ) {
			$bill  = usg_term_meta( $t->term_id, 'bill' );
			$notes = usg_term_meta( $t->term_id, 'bill_notes' );
			$last  = usg_term_meta( $t->term_id, 'last_action' );
			$hub   = (int) usg_term_meta( $t->term_id, 'hub_page' );
			$name  = $hub && 'publish' === get_post_status( $hub ) ? '<a href="' . esc_url( get_permalink( $hub ) ) . '">' . esc_html( $t->name ) . '</a>' : esc_html( $t->name );
			if ( 'unlikely' === $k && ! $bill && ! $notes ) {
				$h .= '<span class="usg-bill usg-bill--mini">' . $name . '</span>';
				continue;
			}
			$h .= '<article class="usg-bill"><h4>' . $name . ( $bill ? ' <small>' . esc_html( $bill ) . '</small>' : '' ) . '</h4>' . ( $notes ? '<p>' . esc_html( $notes ) . '</p>' : '' ) . ( $last ? '<p class="usg-bill__last">' . usg_icon( 'clock' ) . esc_html( $last ) . '</p>' : '' ) . '</article>';
		}
		$h .= '</div>';
	}
	return '<section class="usg-tracker">' . $h . '</section>';
}
