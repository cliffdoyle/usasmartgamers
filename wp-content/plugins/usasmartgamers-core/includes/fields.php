<?php
/**
 * Lightweight custom-field framework: definitions + rendering + saving for posts, terms and users.
 */

defined( 'ABSPATH' ) || exit;

function usg_post_field_groups(): array {
	static $groups = null;
	if ( null !== $groups ) {
		return $groups;
	}
	$avail = array(
		'listed'        => __( 'Only in the states ticked below', 'usasmartgamers-core' ),
		'all_except'    => __( 'All US states EXCEPT those ticked below', 'usasmartgamers-core' ),
		'international' => __( 'Outside the US only', 'usasmartgamers-core' ),
	);
	$article = array(
		'id'      => 'usg_article',
		'title'   => __( 'Article settings', 'usasmartgamers-core' ),
		'context' => 'side',
		'fields'  => array(
			array( 'key' => 'fact_checker', 'label' => __( 'Fact-checked by', 'usasmartgamers-core' ), 'type' => 'user' ),
			array( 'key' => 'sticky_operator', 'label' => __( 'Sticky footer offer (operator)', 'usasmartgamers-core' ), 'type' => 'post', 'post_type' => 'usg_operator' ),
			array( 'key' => 'hide_byline', 'label' => __( 'Hide byline', 'usasmartgamers-core' ), 'type' => 'checkbox' ),
			array( 'key' => 'hide_author_box', 'label' => __( 'Hide author box', 'usasmartgamers-core' ), 'type' => 'checkbox' ),
			array( 'key' => 'hide_toc', 'label' => __( 'Hide sticky section menu', 'usasmartgamers-core' ), 'type' => 'checkbox' ),
		),
	);

	$groups = array(
		'usg_operator' => array(
			array(
				'id'     => 'usg_operator_main',
				'title'  => __( 'Operator details', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'vertical', 'label' => __( 'Vertical', 'usasmartgamers-core' ), 'type' => 'select', 'options' => usg_verticals() ),
					array( 'key' => 'logo', 'label' => __( 'Logo', 'usasmartgamers-core' ), 'type' => 'image' ),
					array( 'key' => 'brand_color', 'label' => __( 'Logo background colour', 'usasmartgamers-core' ), 'type' => 'color' ),
					array( 'key' => 'rating', 'label' => __( 'Overall editorial score (0–5)', 'usasmartgamers-core' ), 'type' => 'number', 'step' => '0.1' ),
					array( 'key' => 'review_page', 'label' => __( 'Review page', 'usasmartgamers-core' ), 'type' => 'post', 'post_type' => 'page' ),
					array( 'key' => 'affiliate_url', 'label' => __( 'Default affiliate URL', 'usasmartgamers-core' ), 'type' => 'url', 'help' => __( 'Use {tag} to pass the placement sub-ID. Offers can override this.', 'usasmartgamers-core' ) ),
					array( 'key' => 'tagline', 'label' => __( 'One-line summary', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'pill', 'label' => __( 'Highlight pill (e.g. "Fast payouts")', 'usasmartgamers-core' ), 'type' => 'text' ),
				),
			),
			array(
				'id'     => 'usg_operator_avail',
				'title'  => __( 'Availability (drives geo-targeting)', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'availability', 'label' => __( 'Where is it available?', 'usasmartgamers-core' ), 'type' => 'select', 'options' => $avail ),
					array( 'key' => 'states', 'label' => __( 'States', 'usasmartgamers-core' ), 'type' => 'states' ),
				),
			),
			array(
				'id'     => 'usg_operator_facts',
				'title'  => __( 'Key facts', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'payout_speed', 'label' => __( 'Payout speed', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'min_deposit', 'label' => __( 'Minimum deposit / purchase', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'win_rate', 'label' => __( 'Average RTP / win rate', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'games_count', 'label' => __( 'Number of games', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'license', 'label' => __( 'Licences / regulators', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'launched', 'label' => __( 'Launched', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'support', 'label' => __( 'Customer support', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'app_ios', 'label' => __( 'App Store rating', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'app_android', 'label' => __( 'Google Play rating', 'usasmartgamers-core' ), 'type' => 'text' ),
				),
			),
			array(
				'id'     => 'usg_operator_scores',
				'title'  => __( 'Review grades', 'usasmartgamers-core' ),
				'fields' => array(
					array(
						'key'    => 'scores',
						'label'  => __( 'Sub-scores', 'usasmartgamers-core' ),
						'type'   => 'repeater',
						'fields' => array(
							array( 'key' => 'label', 'label' => __( 'Criterion', 'usasmartgamers-core' ), 'type' => 'text' ),
							array( 'key' => 'score', 'label' => __( 'Score /5', 'usasmartgamers-core' ), 'type' => 'number', 'step' => '0.1' ),
							array( 'key' => 'note', 'label' => __( 'One-line justification', 'usasmartgamers-core' ), 'type' => 'text' ),
						),
					),
				),
			),
			array(
				'id'     => 'usg_operator_proscons',
				'title'  => __( 'Pros, cons & audience', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'pros', 'label' => __( 'Pros (one per line)', 'usasmartgamers-core' ), 'type' => 'lines' ),
					array( 'key' => 'cons', 'label' => __( 'Cons (one per line)', 'usasmartgamers-core' ), 'type' => 'lines' ),
					array( 'key' => 'suits', 'label' => __( '"Will suit players who…" (one per line)', 'usasmartgamers-core' ), 'type' => 'lines' ),
					array( 'key' => 'terms', 'label' => __( 'Default T&Cs / responsible-gambling text', 'usasmartgamers-core' ), 'type' => 'textarea' ),
				),
			),
		),
		'usg_offer'    => array(
			array(
				'id'     => 'usg_offer_main',
				'title'  => __( 'Offer', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'operator', 'label' => __( 'Operator', 'usasmartgamers-core' ), 'type' => 'post', 'post_type' => 'usg_operator' ),
					array( 'key' => 'headline', 'label' => __( 'Headline (e.g. "Up to 1,000 Bonus Spins")', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'bullets', 'label' => __( 'Bullet points (one per line)', 'usasmartgamers-core' ), 'type' => 'lines' ),
					array( 'key' => 'cta_label', 'label' => __( 'Button label', 'usasmartgamers-core' ), 'type' => 'text', 'placeholder' => 'Claim offer' ),
					array( 'key' => 'promo_code', 'label' => __( 'Promo code (blank = no code needed)', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'offer_type', 'label' => __( 'Offer type', 'usasmartgamers-core' ), 'type' => 'select', 'options' => array( 'welcome' => 'Welcome bonus', 'no_deposit' => 'No-deposit bonus', 'free_spins' => 'Free spins', 'deposit_match' => 'Deposit match', 'lossback' => 'Lossback', 'sweeps' => 'Sweepstakes coins', 'bet_and_get' => 'Bet & get', 'reload' => 'Reload / existing users' ) ),
					array( 'key' => 'exclusive', 'label' => __( 'Exclusive to us', 'usasmartgamers-core' ), 'type' => 'checkbox' ),
					array( 'key' => 'affiliate_url', 'label' => __( 'Affiliate URL (overrides operator)', 'usasmartgamers-core' ), 'type' => 'url' ),
					array( 'key' => 'terms', 'label' => __( 'Full T&Cs (shown under the offer)', 'usasmartgamers-core' ), 'type' => 'textarea' ),
					array( 'key' => 'start', 'label' => __( 'Start date', 'usasmartgamers-core' ), 'type' => 'date' ),
					array( 'key' => 'end', 'label' => __( 'End date (offer auto-hides after)', 'usasmartgamers-core' ), 'type' => 'date' ),
				),
			),
			array(
				'id'     => 'usg_offer_avail',
				'title'  => __( 'Where this offer is valid', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'availability', 'label' => __( 'Availability', 'usasmartgamers-core' ), 'type' => 'select', 'options' => array( 'inherit' => __( 'Same as operator', 'usasmartgamers-core' ) ) + array_slice( $avail, 0, 2, true ) ),
					array( 'key' => 'states', 'label' => __( 'States', 'usasmartgamers-core' ), 'type' => 'states' ),
				),
			),
		),
		'usg_toplist'  => array(
			array(
				'id'     => 'usg_toplist_items',
				'title'  => __( 'Ranked operators', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'geo', 'label' => __( 'Geo-filter: hide operators that are not available in the visitor’s state', 'usasmartgamers-core' ), 'type' => 'checkbox', 'default' => '1' ),
					array(
						'key'    => 'items',
						'label'  => __( 'Operators (top = #1)', 'usasmartgamers-core' ),
						'type'   => 'repeater',
						'fields' => array(
							array( 'key' => 'operator', 'label' => __( 'Operator', 'usasmartgamers-core' ), 'type' => 'post', 'post_type' => 'usg_operator' ),
							array( 'key' => 'offer', 'label' => __( 'Offer (blank = best valid offer)', 'usasmartgamers-core' ), 'type' => 'post', 'post_type' => 'usg_offer' ),
							array( 'key' => 'pill', 'label' => __( 'Pill text', 'usasmartgamers-core' ), 'type' => 'text' ),
						),
					),
				),
			),
		),
		'usg_slot'     => array(
			array(
				'id'     => 'usg_slot_specs',
				'title'  => __( 'Slot specs', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'rtp', 'label' => __( 'RTP %', 'usasmartgamers-core' ), 'type' => 'number', 'step' => '0.01' ),
					array( 'key' => 'volatility', 'label' => __( 'Volatility', 'usasmartgamers-core' ), 'type' => 'select', 'options' => array( '' => '—', 'Low' => 'Low', 'Medium' => 'Medium', 'Medium-high' => 'Medium-high', 'High' => 'High' ) ),
					array( 'key' => 'max_win', 'label' => __( 'Max win (e.g. 5,000x)', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'bet_range', 'label' => __( 'Bet range', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'paylines', 'label' => __( 'Paylines', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'reels', 'label' => __( 'Reels (e.g. 5×3)', 'usasmartgamers-core' ), 'type' => 'text' ),
					array( 'key' => 'release', 'label' => __( 'Release date', 'usasmartgamers-core' ), 'type' => 'date' ),
					array( 'key' => 'rating', 'label' => __( 'Editorial rating (0–5)', 'usasmartgamers-core' ), 'type' => 'number', 'step' => '0.1' ),
					array( 'key' => 'features', 'label' => __( 'Feature chips (one per line)', 'usasmartgamers-core' ), 'type' => 'lines' ),
					array( 'key' => 'demo_url', 'label' => __( 'Demo iframe URL', 'usasmartgamers-core' ), 'type' => 'url' ),
					array( 'key' => 'operators', 'label' => __( 'Where to play (operators)', 'usasmartgamers-core' ), 'type' => 'posts', 'post_type' => 'usg_operator' ),
				),
			),
		),
		'usg_reward'   => array(
			array(
				'id'     => 'usg_reward_main',
				'title'  => __( 'Reward', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'cost', 'label' => __( 'Cost (coins)', 'usasmartgamers-core' ), 'type' => 'number' ),
					array( 'key' => 'reward_type', 'label' => __( 'Type', 'usasmartgamers-core' ), 'type' => 'select', 'options' => array( 'gift_card' => 'Gift card', 'offer' => 'Exclusive offer', 'merch' => 'Merch', 'entry' => 'Prize draw entry' ) ),
					array( 'key' => 'stock', 'label' => __( 'Stock (blank = unlimited)', 'usasmartgamers-core' ), 'type' => 'number' ),
					array( 'key' => 'delivery', 'label' => __( 'Delivery note', 'usasmartgamers-core' ), 'type' => 'text', 'placeholder' => 'Delivered by email within 3 business days' ),
				),
			),
		),
		'usg_redemption' => array(
			array(
				'id'     => 'usg_redemption_main',
				'title'  => __( 'Redemption', 'usasmartgamers-core' ),
				'fields' => array(
					array( 'key' => 'status', 'label' => __( 'Status', 'usasmartgamers-core' ), 'type' => 'select', 'options' => array( 'pending' => 'Pending', 'fulfilled' => 'Fulfilled', 'rejected' => 'Rejected (coins refunded)' ) ),
					array( 'key' => 'admin_note', 'label' => __( 'Note (e.g. gift card sent)', 'usasmartgamers-core' ), 'type' => 'textarea' ),
				),
			),
		),
	);
	foreach ( array( 'page', 'post', 'usg_blog', 'usg_slot' ) as $pt ) {
		$groups[ $pt ][] = $article;
	}
	return $groups;
}

function usg_term_field_groups(): array {
	$st = usg_state_statuses();
	return array(
		'usg_state'    => array(
			array( 'key' => 'casino_status', 'label' => __( 'Online casino status', 'usasmartgamers-core' ), 'type' => 'select', 'options' => $st['casino'] ),
			array( 'key' => 'sports_status', 'label' => __( 'Sports betting status', 'usasmartgamers-core' ), 'type' => 'select', 'options' => $st['sports'] ),
			array( 'key' => 'sweeps_status', 'label' => __( 'Sweepstakes status', 'usasmartgamers-core' ), 'type' => 'select', 'options' => $st['sweeps'] ),
			array( 'key' => 'regulator', 'label' => __( 'Regulator', 'usasmartgamers-core' ), 'type' => 'text' ),
			array( 'key' => 'legal_age', 'label' => __( 'Legal age (online)', 'usasmartgamers-core' ), 'type' => 'text' ),
			array( 'key' => 'helpline', 'label' => __( 'Problem gambling helpline', 'usasmartgamers-core' ), 'type' => 'text' ),
			array( 'key' => 'hub_page', 'label' => __( 'State hub page', 'usasmartgamers-core' ), 'type' => 'post', 'post_type' => 'page' ),
			array( 'key' => 'bill', 'label' => __( 'Bill(s) being tracked', 'usasmartgamers-core' ), 'type' => 'text' ),
			array( 'key' => 'bill_notes', 'label' => __( 'Bill tracker notes', 'usasmartgamers-core' ), 'type' => 'textarea' ),
			array( 'key' => 'last_action', 'label' => __( 'Last legislative action', 'usasmartgamers-core' ), 'type' => 'text' ),
		),
		'usg_provider' => array(
			array( 'key' => 'logo', 'label' => __( 'Logo', 'usasmartgamers-core' ), 'type' => 'image' ),
			array( 'key' => 'founded', 'label' => __( 'Founded', 'usasmartgamers-core' ), 'type' => 'text' ),
			array( 'key' => 'hq', 'label' => __( 'Headquarters', 'usasmartgamers-core' ), 'type' => 'text' ),
			array( 'key' => 'website', 'label' => __( 'Website', 'usasmartgamers-core' ), 'type' => 'url' ),
		),
		'usg_payment'  => array(
			array( 'key' => 'logo', 'label' => __( 'Logo', 'usasmartgamers-core' ), 'type' => 'image' ),
			array( 'key' => 'speed', 'label' => __( 'Typical withdrawal speed', 'usasmartgamers-core' ), 'type' => 'text' ),
			array( 'key' => 'guide_page', 'label' => __( 'Guide page', 'usasmartgamers-core' ), 'type' => 'post', 'post_type' => 'page' ),
		),
	);
}

function usg_user_fields(): array {
	return array(
		array( 'key' => 'job_title', 'label' => __( 'Job title', 'usasmartgamers-core' ), 'type' => 'text' ),
		array( 'key' => 'credentials', 'label' => __( 'Credentials / experience', 'usasmartgamers-core' ), 'type' => 'text' ),
		array( 'key' => 'expertise', 'label' => __( 'Areas of expertise (comma separated)', 'usasmartgamers-core' ), 'type' => 'text' ),
		array( 'key' => 'avatar', 'label' => __( 'Profile photo', 'usasmartgamers-core' ), 'type' => 'image' ),
		array( 'key' => 'social_x', 'label' => 'X / Twitter URL', 'type' => 'url' ),
		array( 'key' => 'social_linkedin', 'label' => 'LinkedIn URL', 'type' => 'url' ),
		array( 'key' => 'social_facebook', 'label' => 'Facebook URL', 'type' => 'url' ),
		array( 'key' => 'social_instagram', 'label' => 'Instagram URL', 'type' => 'url' ),
		array( 'key' => 'fun_facts', 'label' => __( 'Fun facts (one per line)', 'usasmartgamers-core' ), 'type' => 'lines' ),
		array( 'key' => 'qa', 'label' => __( 'Q&A (one per line: Question | Answer)', 'usasmartgamers-core' ), 'type' => 'lines' ),
		array( 'key' => 'show_in_team', 'label' => __( 'Show on team pages', 'usasmartgamers-core' ), 'type' => 'checkbox' ),
		array( 'key' => 'team_order', 'label' => __( 'Team order', 'usasmartgamers-core' ), 'type' => 'number' ),
	);
}

/* ---------- Rendering ---------- */

function usg_field_input( array $f, $value, string $name ): string {
	$type = $f['type'] ?? 'text';
	$ph   = isset( $f['placeholder'] ) ? ' placeholder="' . esc_attr( $f['placeholder'] ) . '"' : '';
	switch ( $type ) {
		case 'textarea':
		case 'lines':
			return '<textarea class="widefat" rows="' . (int) ( $f['rows'] ?? 4 ) . '" name="' . esc_attr( $name ) . '"' . $ph . '>' . esc_textarea( (string) $value ) . '</textarea>';
		case 'number':
			return '<input type="number" step="' . esc_attr( $f['step'] ?? 'any' ) . '" class="small-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
		case 'url':
			return '<input type="url" class="widefat" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"' . $ph . '>';
		case 'date':
			return '<input type="date" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
		case 'color':
			return '<input type="color" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ? (string) $value : '#13294b' ) . '">';
		case 'checkbox':
			return '<input type="hidden" name="' . esc_attr( $name ) . '" value="0"><label><input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( '1', (string) $value, false ) . '> ' . esc_html__( 'Yes', 'usasmartgamers-core' ) . '</label>';
		case 'select':
		case 'post':
		case 'user':
			if ( 'post' === $type ) {
				$opts = usg_post_options( $f['post_type'] );
			} elseif ( 'user' === $type ) {
				$opts = usg_author_options();
			} else {
				$opts = $f['options'];
			}
			$h = '<select name="' . esc_attr( $name ) . '" class="usg-select">';
			foreach ( $opts as $k => $label ) {
				$h .= '<option value="' . esc_attr( (string) $k ) . '"' . selected( (string) $k, (string) $value, false ) . '>' . esc_html( $label ) . '</option>';
			}
			return $h . '</select>';
		case 'image':
			$id  = (int) $value;
			$img = $id ? wp_get_attachment_image( $id, 'thumbnail' ) : '';
			return '<div class="usg-image-field"><input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $id ?: '' ) . '"><div class="usg-image-preview">' . $img . '</div><button type="button" class="button usg-image-select">' . esc_html__( 'Select image', 'usasmartgamers-core' ) . '</button> <button type="button" class="button-link usg-image-remove">' . esc_html__( 'Remove', 'usasmartgamers-core' ) . '</button></div>';
		case 'states':
		case 'posts':
			$opts = 'states' === $type ? usg_states() : usg_post_options( $f['post_type'], false );
			$vals = array_map( 'strval', (array) $value );
			$h    = '<input type="hidden" name="' . esc_attr( $name ) . '[]" value=""><div class="usg-checkgrid' . ( 'states' === $type ? ' usg-checkgrid--states' : '' ) . '">';
			foreach ( $opts as $k => $label ) {
				$h .= '<label title="' . esc_attr( $label ) . '"><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( (string) $k ) . '"' . checked( in_array( (string) $k, $vals, true ), true, false ) . '> ' . esc_html( 'states' === $type ? $k : $label ) . '</label>';
			}
			$h .= '</div>';
			if ( 'states' === $type ) {
				$h .= '<p><button type="button" class="button-link usg-check-all">' . esc_html__( 'Tick all', 'usasmartgamers-core' ) . '</button> · <button type="button" class="button-link usg-check-none">' . esc_html__( 'Untick all', 'usasmartgamers-core' ) . '</button></p>';
			}
			return $h;
		case 'repeater':
			$rows = is_array( $value ) ? array_values( $value ) : array();
			$h    = '<div class="usg-repeater"><table class="widefat striped"><thead><tr><th class="usg-rep-handle"></th>';
			foreach ( $f['fields'] as $sf ) {
				$h .= '<th>' . esc_html( $sf['label'] ) . '</th>';
			}
			$h .= '<th></th></tr></thead><tbody>';
			foreach ( $rows as $i => $row ) {
				$h .= usg_repeater_row( $f, $name, (string) $i, (array) $row );
			}
			$h .= '</tbody></table><template class="usg-rep-template">' . usg_repeater_row( $f, $name, '__i__', array() ) . '</template><p><button type="button" class="button usg-rep-add">' . esc_html__( '+ Add row', 'usasmartgamers-core' ) . '</button></p></div>';
			return $h;
		default:
			return '<input type="text" class="widefat" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"' . $ph . '>';
	}
}

function usg_repeater_row( array $f, string $name, string $i, array $row ): string {
	$h = '<tr><td class="usg-rep-handle"><button type="button" class="button-link usg-rep-up" aria-label="Move up">▲</button><button type="button" class="button-link usg-rep-down" aria-label="Move down">▼</button></td>';
	foreach ( $f['fields'] as $sf ) {
		$h .= '<td>' . usg_field_input( $sf, $row[ $sf['key'] ] ?? '', $name . '[' . $i . '][' . $sf['key'] . ']' ) . '</td>';
	}
	return $h . '<td><button type="button" class="button-link-delete usg-rep-remove">' . esc_html__( 'Remove', 'usasmartgamers-core' ) . '</button></td></tr>';
}

function usg_field_row( array $f, $value, string $name ): string {
	$help = isset( $f['help'] ) ? '<p class="description">' . esc_html( $f['help'] ) . '</p>' : '';
	return '<div class="usg-field usg-field--' . esc_attr( $f['type'] ?? 'text' ) . '"><label class="usg-field__label">' . esc_html( $f['label'] ) . '</label>' . usg_field_input( $f, $value, $name ) . $help . '</div>';
}

/* ---------- Sanitising ---------- */

function usg_field_sanitize( array $f, $raw ) {
	switch ( $f['type'] ?? 'text' ) {
		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( (string) $raw );
		case 'number':
			return is_numeric( $raw ) ? (string) ( 0 + $raw ) : '';
		case 'url':
			return esc_url_raw( (string) $raw, array( 'http', 'https' ) );
		case 'date':
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $raw ) ? (string) $raw : '';
		case 'color':
			return (string) sanitize_hex_color( (string) $raw );
		case 'checkbox':
			return '1' === (string) $raw ? '1' : '';
		case 'select':
			return array_key_exists( (string) $raw, $f['options'] ) ? (string) $raw : '';
		case 'post':
		case 'user':
		case 'image':
			return absint( $raw ) ?: '';
		case 'states':
			return array_values( array_intersect( array_map( 'strval', (array) $raw ), array_keys( usg_states() ) ) );
		case 'posts':
			return array_values( array_filter( array_map( 'absint', (array) $raw ) ) );
		case 'repeater':
			$out = array();
			foreach ( (array) $raw as $key => $row ) {
				if ( '__i__' === (string) $key || ! is_array( $row ) ) {
					continue;
				}
				$clean = array();
				foreach ( $f['fields'] as $sf ) {
					$clean[ $sf['key'] ] = usg_field_sanitize( $sf, $row[ $sf['key'] ] ?? '' );
				}
				if ( implode( '', array_map( fn( $v ) => is_array( $v ) ? implode( '', $v ) : (string) $v, $clean ) ) !== '' ) {
					$out[] = $clean;
				}
			}
			return $out;
		default:
			return sanitize_text_field( (string) $raw );
	}
}

/* ---------- Post meta boxes ---------- */

add_action(
	'add_meta_boxes',
	function ( $post_type ) {
		foreach ( usg_post_field_groups()[ $post_type ] ?? array() as $group ) {
			add_meta_box(
				$group['id'],
				$group['title'],
				function ( $post ) use ( $group ) {
					wp_nonce_field( 'usg_meta_save', 'usg_meta_nonce' );
					echo '<div class="usg-fields">';
					foreach ( $group['fields'] as $f ) {
						$v = get_post_meta( $post->ID, '_usg_' . $f['key'], true );
						if ( '' === $v && isset( $f['default'] ) && 'auto-draft' === $post->post_status ) {
							$v = $f['default'];
						}
						echo usg_field_row( $f, $v, 'usg_fields[' . $f['key'] . ']' ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					echo '</div>';
				},
				$post_type,
				$group['context'] ?? 'normal',
				'side' === ( $group['context'] ?? '' ) ? 'default' : 'high'
			);
		}
	}
);

add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( ! isset( $_POST['usg_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['usg_meta_nonce'] ), 'usg_meta_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$input = isset( $_POST['usg_fields'] ) ? wp_unslash( (array) $_POST['usg_fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( usg_post_field_groups()[ $post->post_type ] ?? array() as $group ) {
			foreach ( $group['fields'] as $f ) {
				if ( ! array_key_exists( $f['key'], $input ) && 'repeater' !== $f['type'] ) {
					continue;
				}
				$val = usg_field_sanitize( $f, $input[ $f['key'] ] ?? array() );
				if ( '' === $val || array() === $val ) {
					delete_post_meta( $post_id, '_usg_' . $f['key'] );
				} else {
					update_post_meta( $post_id, '_usg_' . $f['key'], $val );
				}
			}
		}
		do_action( 'usg_meta_saved', $post_id, $post );
	},
	10,
	2
);

/* ---------- Term meta ---------- */

add_action(
	'init',
	function () {
		foreach ( array_keys( usg_term_field_groups() ) as $tax ) {
			add_action(
				"{$tax}_edit_form_fields",
				function ( $term ) use ( $tax ) {
					wp_nonce_field( 'usg_term_save', 'usg_term_nonce' );
					foreach ( usg_term_field_groups()[ $tax ] as $f ) {
						echo '<tr class="form-field"><th scope="row">' . esc_html( $f['label'] ) . '</th><td>' . usg_field_input( $f, get_term_meta( $term->term_id, '_usg_' . $f['key'], true ), 'usg_fields[' . $f['key'] . ']' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
				}
			);
			add_action(
				"{$tax}_add_form_fields",
				function () use ( $tax ) {
					wp_nonce_field( 'usg_term_save', 'usg_term_nonce' );
					foreach ( usg_term_field_groups()[ $tax ] as $f ) {
						echo '<div class="form-field">' . usg_field_row( $f, '', 'usg_fields[' . $f['key'] . ']' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
				}
			);
			$save = function ( $term_id ) use ( $tax ) {
				if ( ! isset( $_POST['usg_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['usg_term_nonce'] ), 'usg_term_save' ) || ! current_user_can( 'manage_categories' ) ) {
					return;
				}
				$input = isset( $_POST['usg_fields'] ) ? wp_unslash( (array) $_POST['usg_fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				foreach ( usg_term_field_groups()[ $tax ] as $f ) {
					if ( array_key_exists( $f['key'], $input ) ) {
						update_term_meta( $term_id, '_usg_' . $f['key'], usg_field_sanitize( $f, $input[ $f['key'] ] ) );
					}
				}
			};
			add_action( "created_{$tax}", $save );
			add_action( "edited_{$tax}", $save );
		}
	},
	20
);

/* ---------- User meta ---------- */

$usg_user_form = function ( $user ) {
	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}
	wp_nonce_field( 'usg_user_save', 'usg_user_nonce' );
	echo '<h2>' . esc_html__( 'Author profile (USA Smart Gamers)', 'usasmartgamers-core' ) . '</h2><table class="form-table">';
	foreach ( usg_user_fields() as $f ) {
		echo '<tr><th>' . esc_html( $f['label'] ) . '</th><td>' . usg_field_input( $f, get_user_meta( $user->ID, '_usg_' . $f['key'], true ), 'usg_fields[' . $f['key'] . ']' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</table>';
};
add_action( 'show_user_profile', $usg_user_form );
add_action( 'edit_user_profile', $usg_user_form );

$usg_user_save = function ( $user_id ) {
	if ( ! isset( $_POST['usg_user_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['usg_user_nonce'] ), 'usg_user_save' ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	$input = isset( $_POST['usg_fields'] ) ? wp_unslash( (array) $_POST['usg_fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	foreach ( usg_user_fields() as $f ) {
		if ( array_key_exists( $f['key'], $input ) ) {
			update_user_meta( $user_id, '_usg_' . $f['key'], usg_field_sanitize( $f, $input[ $f['key'] ] ) );
		}
	}
};
add_action( 'personal_options_update', $usg_user_save );
add_action( 'edit_user_profile_update', $usg_user_save );

/* ---------- Admin assets ---------- */

add_action(
	'admin_enqueue_scripts',
	function () {
		wp_enqueue_media();
		wp_enqueue_style( 'usg-admin', USG_CORE_URL . 'assets/admin.css', array(), (string) filemtime( USG_CORE_DIR . 'assets/admin.css' ) );
		wp_enqueue_script( 'usg-admin', USG_CORE_URL . 'assets/admin.js', array( 'jquery' ), (string) filemtime( USG_CORE_DIR . 'assets/admin.js' ), true );
	}
);
