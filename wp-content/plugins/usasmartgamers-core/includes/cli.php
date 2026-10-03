<?php
/**
 * WP-CLI: `wp usg seed [--update]` — builds the site structure and clearly-fictional SAMPLE content.
 * Idempotent: existing items are matched by slug/path and only rewritten with --update.
 */

defined( 'ABSPATH' ) || exit;

class USG_Seeder {

	private bool $update;
	private array $ops     = array();
	private array $offers  = array();
	private array $lists   = array();
	private array $authors = array();
	private array $pages   = array();
	private array $slots   = array();
	private array $cats    = array();

	public function __construct( bool $update ) {
		$this->update = $update;
	}

	/* ---------- helpers ---------- */

	private function log( string $m ): void {
		WP_CLI::log( $m );
	}

	private static function b( string $name, array $attrs = array() ): string {
		return '<!-- wp:usg/' . $name . ( $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '' ) . ' /-->' . "\n\n";
	}

	private static function p( string $html ): string {
		return "<!-- wp:paragraph -->\n<p>$html</p>\n<!-- /wp:paragraph -->\n\n";
	}

	private static function h( string $text, int $level = 2 ): string {
		$attr = 2 === $level ? '' : ' {"level":' . $level . '}';
		return "<!-- wp:heading$attr -->\n<h$level class=\"wp-block-heading\">$text</h$level>\n<!-- /wp:heading -->\n\n";
	}

	private static function ul( array $items ): string {
		$li = '';
		foreach ( $items as $i ) {
			$li .= "<!-- wp:list-item -->\n<li>$i</li>\n<!-- /wp:list-item -->\n";
		}
		return "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n$li</ul>\n<!-- /wp:list -->\n\n";
	}

	private function meta( int $id, array $meta ): void {
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, '_usg_' . $k, $v );
		}
	}

	private function upsert( string $type, string $slug, array $args, array $meta = array(), int $parent = 0 ): int {
		$existing = get_posts( array( 'post_type' => $type, 'name' => $slug, 'post_parent' => $parent, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
		$id       = $existing ? (int) $existing[0] : 0;
		if ( $id && ! $this->update ) {
			return $id;
		}
		$data = array_merge( array( 'post_type' => $type, 'post_name' => $slug, 'post_status' => 'publish', 'post_parent' => $parent ), $args );
		if ( $id ) {
			$data['ID'] = $id;
			wp_update_post( wp_slash( $data ) );
		} else {
			$id = (int) wp_insert_post( wp_slash( $data ) );
		}
		$this->meta( $id, $meta );
		return $id;
	}

	private function page( string $path, string $title, string $content, array $opts = array() ): int {
		$parts  = explode( '/', $path );
		$slug   = array_pop( $parts );
		$parent = $parts ? ( $this->pages[ implode( '/', $parts ) ] ?? 0 ) : 0;
		$args   = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_author'  => $opts['author'] ?? $this->authors['jordan'],
			'post_excerpt' => $opts['excerpt'] ?? '',
			'menu_order'   => $opts['order'] ?? 0,
		);
		$id = $this->upsert( 'page', $slug, $args, $opts['meta'] ?? array(), $parent );
		update_post_meta( $id, '_wp_page_template', $opts['template'] ?? 'default' );
		$this->pages[ $path ] = $id;
		return $id;
	}

	private function term( string $tax, string $name, string $slug, array $meta = array(), string $desc = '' ): int {
		$t = get_term_by( 'slug', $slug, $tax );
		if ( ! $t ) {
			$r  = wp_insert_term( $name, $tax, array( 'slug' => $slug, 'description' => $desc ) );
			$id = is_wp_error( $r ) ? 0 : (int) $r['term_id'];
		} else {
			$id = (int) $t->term_id;
			if ( $this->update && $desc ) {
				wp_update_term( $id, $tax, array( 'description' => $desc ) );
			}
		}
		if ( $id && ( ! $t || $this->update ) ) {
			foreach ( $meta as $k => $v ) {
				update_term_meta( $id, '_usg_' . $k, $v );
			}
		}
		return $id;
	}

	/* ---------- run ---------- */

	public function run(): void {
		wp_set_current_user( 1 );
		$this->states();
		$this->authors();
		$this->taxonomies();
		$this->operators();
		$this->toplists();
		$this->slots();
		$this->pages();
		$this->links();
		$this->posts();
		$this->rewards();
		$this->menus();
		$this->settings();
		flush_rewrite_rules( false );
		WP_CLI::success( 'Seed complete. Remember: operators, offers, slots, authors and news are SAMPLE content — replace before launch.' );
	}

	private function states(): void {
		$casino_legal   = array( 'CT', 'DE', 'MI', 'NJ', 'PA', 'RI', 'WV' );
		$casino_pending = array( 'IL', 'MA', 'NY', 'MD', 'OH', 'IN', 'VA', 'HI', 'ME' );
		$sports_online  = array( 'AZ', 'AR', 'CO', 'CT', 'DC', 'DE', 'FL', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MO', 'NV', 'NH', 'NJ', 'NY', 'NC', 'OH', 'OR', 'PA', 'RI', 'TN', 'VT', 'VA', 'WV', 'WY' );
		$sports_retail  = array( 'MS', 'MT', 'NE', 'NM', 'ND', 'SD', 'WA', 'WI' );
		$sports_pending = array( 'CA', 'TX', 'GA', 'MN', 'OK', 'SC' );
		$sweeps_restr   = array( 'WA', 'ID', 'NV', 'MI', 'MT', 'CT', 'NJ', 'NY', 'CA', 'LA', 'DE' );
		$regulators     = array( 'NJ' => 'NJ Division of Gaming Enforcement', 'PA' => 'Pennsylvania Gaming Control Board', 'MI' => 'Michigan Gaming Control Board', 'WV' => 'West Virginia Lottery Commission', 'DE' => 'Delaware Lottery', 'CT' => 'CT Dept. of Consumer Protection', 'RI' => 'Rhode Island Lottery' );
		$bills          = array(
			'IL' => array( 'SB/HB iGaming proposals', 'Lawmakers have repeatedly filed companion bills to authorize online casinos, often tied to budget discussions.' ),
			'MA' => array( 'Companion iGaming bills', 'Bills would let existing casino licensees offer online games; committee review ongoing.' ),
			'NY' => array( 'Senate iGaming bill', 'Proposal to authorize online casinos through existing licensees; supporters cite tax revenue.' ),
			'MD' => array( 'Constitutional amendment proposal', 'Would send online casino legalization to voters via referendum.' ),
			'OH' => array( 'iGaming study / bill', 'Legislators have discussed online casino authorization alongside sports betting tax changes.' ),
			'IN' => array( 'iGaming bill', 'Previous bills stalled; renewed interest expected in future sessions.' ),
			'VA' => array( 'iGaming bill', 'Proposal would allow online casinos under the state lottery and casino licensees.' ),
			'HI' => array( 'Gaming study bill', 'Hawaii has no legal gambling; any bill faces strong opposition.' ),
			'ME' => array( 'Tribal iGaming bill', 'Would allow Maine’s tribes to offer online casino games.' ),
		);
		foreach ( usg_states() as $code => $name ) {
			$this->term(
				'usg_state',
				$name,
				strtolower( $code ),
				array(
					'casino_status' => in_array( $code, $casino_legal, true ) ? 'legal' : ( in_array( $code, $casino_pending, true ) ? 'pending' : 'unlikely' ),
					'sports_status' => in_array( $code, $sports_online, true ) ? 'legal' : ( in_array( $code, $sports_retail, true ) ? 'retail' : ( in_array( $code, $sports_pending, true ) ? 'pending' : 'no' ) ),
					'sweeps_status' => in_array( $code, $sweeps_restr, true ) ? 'restricted' : 'allowed',
					'regulator'     => $regulators[ $code ] ?? '',
					'legal_age'     => '21+',
					'helpline'      => '1-800-GAMBLER',
					'bill'          => $bills[ $code ][0] ?? '',
					'bill_notes'    => $bills[ $code ][1] ?? '',
					'last_action'   => isset( $bills[ $code ] ) ? 'Sample status — verify before publishing' : '',
				),
				'Sample legal-status data — verify every state before launch.'
			);
		}
		$this->log( 'States seeded.' );
	}

	private function authors(): void {
		$people = array(
			'jordan' => array( 'Jordan Reyes', 'Senior Casino Analyst', 'Online casinos, Bonuses, State regulation', 1 ),
			'maya'   => array( 'Maya Thompson', 'Managing Editor & Fact-checker', 'Editorial standards, Responsible gambling, Legislation', 2 ),
			'chris'  => array( 'Chris Delgado', 'Slots & Sweepstakes Writer', 'Slots, Sweepstakes casinos, Game math', 3 ),
		);
		foreach ( $people as $key => $p ) {
			$login = 'sample_' . $key;
			$user  = get_user_by( 'login', $login );
			$id    = $user ? $user->ID : wp_insert_user(
				array(
					'user_login'   => $login,
					'user_email'   => $key . '@usasmartgamers.invalid',
					'user_pass'    => wp_generate_password( 32 ),
					'display_name' => $p[0],
					'first_name'   => explode( ' ', $p[0] )[0],
					'last_name'    => explode( ' ', $p[0] )[1],
					'role'         => 'author',
					'description'  => '[Placeholder profile — replace with a real team member before launch.] ' . $p[0] . ' covers ' . strtolower( $p[2] ) . ' for USA Smart Gamers, testing operators hands-on and translating the fine print into plain English.',
				)
			);
			$this->authors[ $key ] = (int) $id;
			foreach ( array( 'job_title' => $p[1], 'expertise' => $p[2], 'show_in_team' => '1', 'team_order' => $p[3], 'credentials' => 'Sample credentials', 'fun_facts' => "Favourite table game: blackjack\nHas visited 30+ casinos across the US", 'qa' => "What do you look for first in a casino? | How fast and how reliably it pays out.\nBest bankroll tip? | Set a budget before you play and never chase losses." ) as $k => $v ) {
				update_user_meta( (int) $id, '_usg_' . $k, $v );
			}
		}
		$this->log( 'Sample authors ready.' );
	}

	private function taxonomies(): void {
		foreach ( array( 'PayPal' => '1–2 days', 'Visa' => '3–5 days', 'Mastercard' => '3–5 days', 'Venmo' => 'Same day', 'Apple Pay' => '1–2 days', 'Play+ prepaid' => 'Same day', 'Online banking (ACH)' => '1–3 days', 'PayNearMe (cash)' => 'N/A', 'Skrill' => '1–2 days', 'Cash at casino cage' => 'Instant' ) as $name => $speed ) {
			$this->term( 'usg_payment', $name, sanitize_title( $name ), array( 'speed' => $speed ) );
		}
		$provs = array(
			'frontier-gaming' => array( 'Frontier Gaming (sample)', '2009', 'Las Vegas, NV' ),
			'neon-reel'       => array( 'Neon Reel Studios (sample)', '2015', 'Austin, TX' ),
			'atlas-play'      => array( 'Atlas Play (sample)', '2012', 'Newark, NJ' ),
		);
		foreach ( $provs as $slug => $p ) {
			$this->term( 'usg_provider', $p[0], $slug, array( 'founded' => $p[1], 'hq' => $p[2] ), 'Fictional sample studio used to demonstrate provider pages. Replace with real providers.' );
		}
		foreach ( array( 'legislation' => 'Legislation', 'industry' => 'Industry', 'promotions' => 'Promotions', 'guides' => 'Guides', 'new-jersey' => 'New Jersey', 'pennsylvania' => 'Pennsylvania', 'michigan' => 'Michigan' ) as $slug => $name ) {
			$this->cats[ $slug ] = $this->term( 'category', $name, $slug );
		}
		$this->log( 'Payments, providers, news categories ready.' );
	}

	private function operators(): void {
		$casino_states = array( 'NJ', 'PA', 'MI', 'WV' );
		$sweeps_excl   = array( 'WA', 'ID', 'NV', 'MI', 'MT', 'CT', 'NJ', 'NY', 'CA', 'LA', 'DE' );
		$sports_states = array( 'AZ', 'CO', 'IL', 'IN', 'IA', 'KS', 'MD', 'MA', 'MI', 'NJ', 'NY', 'NC', 'OH', 'PA', 'TN', 'VA' );
		$scores_casino = array( 'Welcome bonus', 'Game variety', 'Payout speed', 'App & UX', 'Customer support', 'Existing-player perks' );
		$scores_sweeps = array( 'Free coins & bonus', 'Prize redemption', 'Game quality', 'Website & app', 'Customer support', 'Ongoing promotions' );
		$scores_sports = array( 'Welcome offer', 'Odds & markets', 'Live betting', 'App & UX', 'Payouts', 'Existing-user promos' );
		$terms_casino  = '21+ and physically present in an eligible state. New customers only. Minimum deposit required. Bonus funds subject to wagering requirements and expire after 14 days. Game restrictions apply. Gambling problem? Call 1-800-GAMBLER. SAMPLE TERMS — replace with the operator’s official terms.';
		$terms_sweeps  = 'No purchase necessary. Void where prohibited. 18+ (21+ in some states). Sweeps Coins have no cash value until redeemed per the official rules. SAMPLE TERMS — replace with the operator’s official rules.';
		$terms_sports  = '21+. New customers in eligible states only. Bonus bets expire after 7 days and stake is not returned. Gambling problem? Call 1-800-GAMBLER. SAMPLE TERMS — replace with the operator’s official terms.';

		$list = array(
			'liberty-spins'     => array( 'Liberty Spins Casino', 'casino', '#1e3a8a', 4.7, 'listed', $casino_states, 'Fast payouts', 'Up to $1,000 deposit match + 200 bonus spins', 'SMART1000', array( 'PayPal', 'Visa', 'Venmo', 'Play+ prepaid', 'Online banking (ACH)' ), '24 hours', '$10', '96.4%', '2,500+', array( 4.8, 4.9, 4.8, 4.6, 4.5, 4.4 ) ),
			'stars-and-stripes' => array( 'Stars & Stripes Casino', 'casino', '#b91c1c', 4.6, 'listed', array_merge( $casino_states, array( 'CT' ) ), 'Huge game library', '100% match up to $500 + $25 on the house', 'USGSTARS', array( 'PayPal', 'Visa', 'Mastercard', 'Apple Pay' ), '1–2 days', '$10', '96.1%', '3,000+', array( 4.6, 5.0, 4.4, 4.7, 4.3, 4.5 ) ),
			'golden-eagle'      => array( 'Golden Eagle Casino', 'casino', '#a16207', 4.5, 'listed', array( 'NJ', 'PA', 'MI' ), 'Best loyalty perks', '500 bonus spins on a $10 deposit', '', array( 'Visa', 'Mastercard', 'PayNearMe (cash)', 'Cash at casino cage' ), '1–3 days', '$10', '96.0%', '1,800+', array( 4.5, 4.4, 4.3, 4.5, 4.6, 4.8 ) ),
			'empire-reels'      => array( 'Empire Reels Casino', 'casino', '#4c1d95', 4.4, 'listed', array( 'NJ', 'PA', 'WV', 'DE' ), 'Low wagering', '$25 no-deposit bonus + 100% match up to $1,000', 'EMPIRE25', array( 'PayPal', 'Skrill', 'Visa' ), '1–2 days', '$20', '95.8%', '1,500+', array( 4.6, 4.2, 4.4, 4.3, 4.2, 4.3 ) ),
			'coastline'         => array( 'Coastline Casino', 'casino', '#0e7490', 4.3, 'listed', array( 'NJ', 'MI', 'RI' ), 'Great live dealer', 'Up to $500 back in casino bonus on first-day losses', '', array( 'PayPal', 'Visa', 'Online banking (ACH)' ), '2–3 days', '$10', '95.9%', '1,200+', array( 4.3, 4.3, 4.1, 4.4, 4.3, 4.2 ) ),
			'patriot-play'      => array( 'Patriot Play Casino', 'casino', '#14532d', 4.2, 'listed', array( 'PA', 'MI', 'WV' ), 'Daily jackpots', '100 bonus spins + 100% match up to $250', 'PATRIOT', array( 'Visa', 'Venmo', 'Play+ prepaid' ), '1–2 days', '$5', '95.6%', '900+', array( 4.2, 4.0, 4.3, 4.2, 4.1, 4.4 ) ),
			'sweepstars'        => array( 'SweepStars Social Casino', 'sweepstakes', '#7c3aed', 4.6, 'all_except', $sweeps_excl, 'Fast redemptions', '100,000 Gold Coins + 5 free Sweeps Coins on sign-up', '', array( 'Visa', 'Mastercard', 'Online banking (ACH)', 'Skrill' ), '1–3 days', 'Free to play', '96.0%', '800+', array( 4.6, 4.7, 4.5, 4.6, 4.3, 4.5 ) ),
			'lucky-frontier'    => array( 'Lucky Frontier', 'sweepstakes', '#c2410c', 4.5, 'all_except', $sweeps_excl, 'Daily login bonus', '50,000 GC + 3 SC free, plus 200% first purchase bonus', 'FRONTIER', array( 'Visa', 'Apple Pay', 'Skrill' ), '2–4 days', 'Free to play', '95.5%', '600+', array( 4.6, 4.4, 4.4, 4.5, 4.2, 4.6 ) ),
			'coin-canyon'       => array( 'Coin Canyon', 'sweepstakes', '#b45309', 4.4, 'all_except', $sweeps_excl, 'Exclusive slots', '25,000 GC + 2.5 SC free on sign-up', '', array( 'Visa', 'Mastercard', 'Online banking (ACH)' ), '3–5 days', 'Free to play', '95.7%', '500+', array( 4.3, 4.4, 4.5, 4.3, 4.2, 4.4 ) ),
			'big-sky-sweeps'    => array( 'Big Sky Sweeps', 'sweepstakes', '#0369a1', 4.2, 'all_except', $sweeps_excl, 'Low redemption minimum', '10 free SC after email verification', '', array( 'Visa', 'Skrill' ), '2–5 days', 'Free to play', '95.2%', '400+', array( 4.1, 4.4, 4.2, 4.1, 4.0, 4.2 ) ),
			'gridiron-bet'      => array( 'Gridiron Bet', 'sportsbook', '#166534', 4.6, 'listed', $sports_states, 'Best odds boosts', 'Bet $5, get $200 in bonus bets', '', array( 'PayPal', 'Visa', 'Venmo', 'Online banking (ACH)' ), '24 hours', '$5', 'N/A', '30+ sports', array( 4.7, 4.6, 4.5, 4.7, 4.6, 4.5 ) ),
			'fastbreak'         => array( 'Fastbreak Sportsbook', 'sportsbook', '#9a3412', 4.4, 'listed', $sports_states, 'Same-game parlays', 'First bet safety net up to $1,000', 'FASTUSG', array( 'PayPal', 'Visa', 'Apple Pay' ), '1–2 days', '$10', 'N/A', '25+ sports', array( 4.4, 4.5, 4.4, 4.3, 4.3, 4.4 ) ),
			'global-spins'      => array( 'Global Spins International', 'casino', '#334155', 4.0, 'international', array(), 'International players', 'Welcome package for non-US players', '', array( 'Visa', 'Skrill' ), '2–3 days', '$20', '96.0%', '2,000+', array( 4.0, 4.1, 4.0, 4.0, 3.9, 4.0 ) ),
		);
		$i = 0;
		foreach ( $list as $slug => $o ) {
			$labels = 'sweepstakes' === $o[1] ? $scores_sweeps : ( 'sportsbook' === $o[1] ? $scores_sports : $scores_casino );
			$scores = array();
			foreach ( $labels as $k => $label ) {
				$scores[] = array( 'label' => $label, 'score' => (string) $o[14][ $k ], 'note' => 'Sample justification — describe what the tester found for ' . strtolower( $label ) . '.' );
			}
			$id = $this->upsert(
				'usg_operator',
				$slug,
				array( 'post_title' => $o[0], 'post_excerpt' => 'SAMPLE operator for demonstration.' ),
				array(
					'vertical'      => $o[1],
					'brand_color'   => $o[2],
					'rating'        => (string) $o[3],
					'availability'  => $o[4],
					'states'        => $o[5],
					'pill'          => $o[6],
					'tagline'       => $o[7],
					'affiliate_url' => 'https://example.com/go/' . $slug . '?subid={tag}',
					'payout_speed'  => $o[10],
					'min_deposit'   => $o[11],
					'win_rate'      => $o[12],
					'games_count'   => $o[13],
					'license'       => 'casino' === $o[1] && 'international' !== $o[4] ? 'State gaming regulators (see availability)' : ( 'sweepstakes' === $o[1] ? 'Operates under sweepstakes laws' : 'State regulators' ),
					'launched'      => (string) ( 2014 + $i % 8 ),
					'support'       => 'Live chat, email',
					'app_ios'       => number_format( 4.3 + ( $i % 6 ) / 10, 1 ) . '/5',
					'app_android'   => number_format( 4.1 + ( $i % 7 ) / 10, 1 ) . '/5',
					'scores'        => $scores,
					'pros'          => "Strong welcome offer with clear terms\nQuick, reliable withdrawals\nPolished mobile app",
					'cons'          => "Limited availability by state\nLoyalty program could be clearer",
					'suits'         => "Want a big welcome bonus with fair wagering\nPrefer fast cash-outs to e-wallets\nPlay mostly on mobile",
					'terms'         => 'sweepstakes' === $o[1] ? $terms_sweeps : ( 'sportsbook' === $o[1] ? $terms_sports : $terms_casino ),
				)
			);
			wp_set_object_terms( $id, array_map( 'sanitize_title', $o[9] ), 'usg_payment' );
			$this->ops[ $slug ] = $id;

			$this->offers[ $slug ] = $this->upsert(
				'usg_offer',
				$slug . '-welcome',
				array( 'post_title' => $o[0] . ' — welcome offer', 'menu_order' => 10 ),
				array(
					'operator'     => $id,
					'headline'     => $o[7],
					'bullets'      => "Sample offer — replace with the live promotion\nNew players only\n" . ( $o[8] ? 'Use code ' . $o[8] . ' at sign-up' : 'No promo code needed' ),
					'cta_label'    => 'sweepstakes' === $o[1] ? 'Play for free' : 'Claim offer',
					'promo_code'   => $o[8],
					'offer_type'   => 'sweepstakes' === $o[1] ? 'sweeps' : 'welcome',
					'availability' => 'inherit',
				)
			);
			$i++;
		}
		// Geo demo: a New Jersey-only offer that outranks the national one for NJ visitors.
		$this->offers['liberty-nj'] = $this->upsert(
			'usg_offer',
			'liberty-spins-nj-exclusive',
			array( 'post_title' => 'Liberty Spins — NJ exclusive', 'menu_order' => 1 ),
			array( 'operator' => $this->ops['liberty-spins'], 'headline' => 'NJ exclusive: $50 no-deposit bonus + up to $1,000 match', 'bullets' => "Sample New Jersey-only offer (demonstrates geo-targeting)\n$50 credited after verification", 'cta_label' => 'Claim NJ offer', 'promo_code' => 'SMARTNJ', 'offer_type' => 'no_deposit', 'exclusive' => '1', 'availability' => 'listed', 'states' => array( 'NJ' ) )
		);
		$this->log( 'Sample operators & offers ready.' );
	}

	private function toplists(): void {
		$mk = function ( string $slug, string $title, array $ops ) {
			$items = array();
			foreach ( $ops as $o ) {
				$items[] = array( 'operator' => $this->ops[ $o ], 'offer' => '', 'pill' => '' );
			}
			$this->lists[ $slug ] = $this->upsert( 'usg_toplist', $slug, array( 'post_title' => $title ), array( 'geo' => '1', 'items' => $items ) );
		};
		$mk( 'top-casinos', 'Top online casinos', array( 'liberty-spins', 'stars-and-stripes', 'golden-eagle', 'empire-reels', 'coastline', 'patriot-play', 'global-spins' ) );
		$mk( 'top-sweeps', 'Top sweepstakes casinos', array( 'sweepstars', 'lucky-frontier', 'coin-canyon', 'big-sky-sweeps', 'global-spins' ) );
		$mk( 'top-sportsbooks', 'Top sportsbooks', array( 'gridiron-bet', 'fastbreak', 'global-spins' ) );
		$mk( 'home-best', 'Homepage — best overall', array( 'liberty-spins', 'sweepstars', 'stars-and-stripes', 'gridiron-bet', 'lucky-frontier', 'golden-eagle', 'global-spins' ) );
		$this->log( 'Toplists ready.' );
	}

	private function slots(): void {
		$list = array(
			'liberty-bell-riches'    => array( 'Liberty Bell Riches', 'frontier-gaming', 96.2, 'Medium', '5,000x', 4.6, "Free spins\nWild multipliers\nHold & spin" ),
			'gold-rush-canyon'       => array( 'Gold Rush Canyon', 'frontier-gaming', 96.5, 'High', '10,000x', 4.5, "Cascading reels\nFree spins\nBuy feature" ),
			'buffalo-thunder-plains' => array( 'Buffalo Thunder Plains', 'frontier-gaming', 95.9, 'Medium-high', '7,500x', 4.4, "Xtra ways\nFree spins\nScatters" ),
			'neon-nights-megaways'   => array( 'Neon Nights Megaways', 'neon-reel', 96.4, 'High', '20,000x', 4.7, "Megaways\nUnlimited multipliers\nFree spins" ),
			'lucky-lobster-wharf'    => array( 'Lucky Lobster Wharf', 'neon-reel', 96.0, 'Medium', '3,000x', 4.2, "Pick bonus\nWilds\nScatters" ),
			'pharaohs-vault'         => array( 'Pharaoh’s Vault', 'atlas-play', 96.1, 'High', '12,500x', 4.3, "Expanding symbols\nFree spins\nGamble feature" ),
			'stars-and-bars-deluxe'  => array( 'Stars & Bars Deluxe', 'atlas-play', 95.5, 'Low', '1,000x', 4.0, "Classic 3-reel\nRespins\nWild multipliers" ),
			'dragon-pearl-fortune'   => array( 'Dragon Pearl Fortune', 'atlas-play', 96.3, 'Medium', '8,888x', 4.5, "Hold & spin\nFour jackpots\nFree spins" ),
		);
		foreach ( $list as $slug => $s ) {
			$content = self::p( '<strong>Sample slot review.</strong> ' . $s[0] . ' is a fictional game used to demonstrate the slot template. Replace it with real slot reviews — each one should cover gameplay, features, RTP, volatility and where to play.' )
				. self::h( 'How to play ' . $s[0] )
				. self::p( 'Choose your stake, spin the reels and land matching symbols from left to right. Scatters trigger the bonus round, where multipliers and extra spins drive the biggest wins.' )
				. self::h( $s[0] . ' bonus features' )
				. self::ul( usg_lines( $s[6] ) )
				. self::h( 'Final thoughts' )
				. self::p( 'With an RTP of ' . $s[2] . '% and ' . strtolower( $s[3] ) . ' volatility, this game suits players who enjoy a balance of frequent hits and big-win potential. Always try the free demo first and set a budget before playing for real money.' );
			$id = $this->upsert(
				'usg_slot',
				$slug,
				array( 'post_title' => $s[0], 'post_content' => $content, 'post_excerpt' => 'Sample slot review: ' . $s[0] . '.', 'post_author' => $this->authors['chris'] ),
				array( 'rtp' => (string) $s[2], 'volatility' => $s[3], 'max_win' => $s[4], 'rating' => (string) $s[5], 'features' => $s[6], 'bet_range' => '$0.20 – $100', 'paylines' => '25', 'reels' => '5×3', 'release' => '2025-0' . ( 1 + strlen( $slug ) % 9 ) . '-15', 'operators' => array( $this->ops['liberty-spins'], $this->ops['stars-and-stripes'], $this->ops['sweepstars'] ), 'fact_checker' => $this->authors['maya'] )
			);
			wp_set_object_terms( $id, $s[1], 'usg_provider' );
			$this->slots[ $slug ] = $id;
		}
		$this->log( 'Sample slots ready.' );
	}

	private function review_content( string $op, string $vertical_label ): string {
		$id   = $this->ops[ $op ];
		$name = get_the_title( $id );
		$c    = self::b( 'review-summary', array( 'operator' => $id ) )
			. self::b( 'key-takeaways', array( 'items' => "Sample review — replace with hands-on testing notes\nWelcome offer: " . usg_meta( $id, 'tagline' ) . "\nPayouts in " . usg_meta( $id, 'payout_speed' ) . ' on average' ) )
			. self::h( 'My verdict on ' . $name )
			. self::p( '<strong>This is sample content.</strong> Write your honest, first-hand verdict here: who the ' . strtolower( $vertical_label ) . ' is best for, what stood out during testing, and any drawbacks players should know about before signing up.' )
			. self::b( 'user-reviews', array( 'operator' => $id, 'title' => 'Real player reviews of ' . $name ) )
			. self::h( $name . ' bonus & promo code | ' . number_format( (float) usg_meta( $id, 'scores' )[0]['score'], 1 ) . ' / 5' )
			. self::b( 'claim-button', array( 'operator' => $id, 'style' => 'green', 'tag' => 'review-bonus' ) )
			. self::p( 'Explain exactly how to claim the offer step by step, the wagering requirement, eligible games, expiry and any state restrictions.' )
			. self::b( 'expert-insight', array( 'author' => $this->authors['maya'], 'quote' => 'Always check which games count 100% toward wagering. A generous match bonus is only valuable if you can realistically clear it.' ) )
			. self::h( 'Games & software | ' . number_format( (float) usg_meta( $id, 'scores' )[1]['score'], 1 ) . ' / 5' )
			. self::p( 'Describe the game library: number of slots, table games, live dealer, exclusives and the studios powering them.' )
			. self::h( 'Deposits & withdrawals | ' . number_format( (float) usg_meta( $id, 'scores' )[2]['score'], 1 ) . ' / 5' )
			. self::p( 'Record the payment methods you tested, deposit limits and exactly how long each withdrawal took.' )
			. self::h( 'Pros & cons of ' . $name )
			. self::b( 'pros-cons', array( 'operator' => $id ) )
			. self::h( 'Is ' . $name . ' legit and safe?' )
			. self::p( 'Cover licensing, responsible-gambling tools (deposit limits, time-outs, self-exclusion) and account security.' )
			. self::b( 'faq', array( 'title' => $name . ' FAQ', 'items' => "Q: Is $name legal?\nA: Sample answer — list the states where it is licensed and the regulator for each.\n\nQ: How fast does $name pay out?\nA: In our sample testing, withdrawals arrived in " . usg_meta( $id, 'payout_speed' ) . ".\n\nQ: Does $name have a promo code?\nA: " . ( usg_meta( $this->offers[ $op ], 'promo_code' ) ? 'Yes — use ' . usg_meta( $this->offers[ $op ], 'promo_code' ) . ' when you sign up.' : 'No code is needed; the offer applies automatically.' ) ) );
		return $c;
	}

	private function pages(): void {
		$J  = $this->authors['jordan'];
		$M  = $this->authors['maya'];
		$C  = $this->authors['chris'];
		$fc = array( 'fact_checker' => $M );
		$L  = $this->lists;
		$landing = 'page-templates/landing.php';

		// Home.
		$home = self::b( 'hero', array( 'title' => 'Play smarter. Win the right way.', 'text' => 'Honest, hands-on reviews of legal US online casinos, sweepstakes casinos and sportsbooks — plus the best bonuses, free slot demos and the tools to make every bet a smarter one.', 'buttons' => "Online Casinos|/online-casinos/\nSweepstakes Casinos|/sweepstakes-casinos/\nSlots|/slots/\nSports Betting|/sports-betting/\nBonus Calculator|/tools/casino-bonus-calculator/" ) )
			. self::b( 'toplist-tabs', array( 'title' => 'Top-rated gambling sites for [month] [year]', 'tab1_label' => 'Best overall', 'tab1_toplist' => $L['home-best'], 'tab2_label' => 'Online casinos', 'tab2_toplist' => $L['top-casinos'], 'tab3_label' => 'Sweepstakes', 'tab3_toplist' => $L['top-sweeps'], 'limit' => 5, 'skin' => 'rows' ) )
			. self::b( 'cta-banner', array( 'title' => 'Smart Rewards: earn coins for reading, rating and playing demos', 'text' => 'Join free, collect coins every day and swap them for gift cards and exclusive offers.', 'button_label' => 'Join & get 500 coins', 'button_url' => '/rewards/', 'style' => 'navy' ) )
			. self::b( 'how-we-test', array() )
			. self::b( 'link-grid', array( 'title' => 'Everything you need, in one place', 'columns' => '3', 'links' => "Real-money casinos|/online-casinos/|Legal, regulated sites ranked by our experts\nSweepstakes casinos|/sweepstakes-casinos/|Play for free in most US states\nCasino bonuses|/online-casinos/bonus/|The best welcome offers and promo codes\nSlot reviews & demos|/slots/|Try before you play for real\nCasino finder|/casino-finder/|Filter every operator by state and payment method\nBill tracker|/casino-bill-tracker/|Where online gambling is legal — and where it’s next" ) )
			. self::b( 'slot-grid', array( 'title' => 'Most popular slot demos', 'count' => 4, 'orderby' => 'rating' ) )
			. self::b( 'state-map', array( 'title' => 'Where are online casinos legal?', 'vertical' => 'casino' ) )
			. self::b( 'news-feed', array( 'title' => 'Latest news & guides', 'count' => 4, 'layout' => 'grid' ) )
			. self::b( 'team-grid', array( 'title' => 'Meet our experts' ) )
			. self::b( 'faq', array( 'items' => "Q: Is online gambling legal in the US?\nA: It depends on your state and the type of game. Real-money online casinos are legal in a handful of states, sports betting in most, and sweepstakes casinos are available in the majority of states.\n\nQ: How does USA Smart Gamers make money?\nA: We may earn a commission when you sign up through our links. This never changes our scores or rankings.\n\nQ: Are the sites you list safe?\nA: We only recommend operators that are licensed or legally operating where they accept players, and we test each one hands-on." ) );
		$this->page( 'home', 'USA Smart Gamers — Legal US online gambling guides', $home, array( 'template' => 'default', 'meta' => array( 'hide_byline' => '1', 'hide_author_box' => '1' ) ) );

		// Online casinos hub.
		$this->page(
			'online-casinos',
			'Best US Online Casinos for [month] [year]: Real Money Sites Ranked',
			self::p( 'We have tested every legal real-money online casino in the US. Below are our top picks for [month] [year], tailored to the state you are playing from.' )
			. self::b( 'toplist', array( 'toplist' => $L['top-casinos'], 'title' => 'Top online casinos this month', 'skin' => 'rows', 'tag' => 'casinos-hub' ) )
			. self::b( 'how-we-test', array( 'title' => 'How we rank the best online casinos' ) )
			. self::h( 'Where are real-money online casinos legal?' )
			. self::p( 'Online casinos are regulated state by state. Use the map below to see where they are live, where bills are being considered, and where they are unlikely to arrive soon.' )
			. self::b( 'state-map', array( 'vertical' => 'casino' ) )
			. self::b( 'state-table', array( 'title' => 'Legal online casino states', 'vertical' => 'casino', 'filter' => 'legal' ) )
			. self::h( 'Compare the top casinos side by side' )
			. self::b( 'comparison', array( 'operators' => array( $this->ops['liberty-spins'], $this->ops['stars-and-stripes'], $this->ops['golden-eagle'], $this->ops['empire-reels'] ) ) )
			. self::h( 'Understanding bonus terms and wagering requirements' )
			. self::p( 'A wagering requirement tells you how many times you must play through a bonus before you can withdraw winnings. Use our <a href="/tools/casino-bonus-calculator/">bonus calculator</a> to see what an offer is really worth.' )
			. self::b( 'faq', array( 'title' => 'Online casino FAQ', 'items' => "Q: Can I play at an online casino if I live in a non-legal state?\nA: You must be physically located inside a legal state when you play. Sweepstakes casinos are an alternative in most other states.\n\nQ: How do I know a casino is licensed?\nA: Licensed casinos display their state regulator’s seal and licence number, usually in the website footer.\n\nQ: What is the fastest payout method?\nA: E-wallets like PayPal and Venmo, and prepaid Play+ cards, are usually the fastest." ) ),
			array( 'meta' => $fc + array( 'sticky_operator' => $this->ops['liberty-spins'] ), 'excerpt' => 'Our ranked list of the best legal real-money online casinos in the US.' )
		);
		foreach ( array( 'liberty-spins', 'stars-and-stripes', 'golden-eagle' ) as $op ) {
			$this->page( 'online-casinos/' . $op, 'My honest ' . get_the_title( $this->ops[ $op ] ) . ' review & bonus code ([month] [year])', $this->review_content( $op, 'Casino' ), array( 'meta' => $fc + array( 'sticky_operator' => $this->ops[ $op ] ) ) );
		}
		$states = array( 'new-jersey' => array( 'NJ', 'New Jersey', 'NJ Division of Gaming Enforcement', '2013' ), 'pennsylvania' => array( 'PA', 'Pennsylvania', 'Pennsylvania Gaming Control Board', '2019' ), 'michigan' => array( 'MI', 'Michigan', 'Michigan Gaming Control Board', '2021' ) );
		foreach ( $states as $slug => $s ) {
			$this->page(
				'online-casinos/' . $slug,
				'Best ' . $s[1] . ' Online Casinos ([month] [year]): Legal ' . $s[0] . ' Casino Apps',
				self::p( 'Online casinos have been legal in ' . $s[1] . ' since ' . $s[3] . ' (sample data — verify). Every site below is licensed by the ' . $s[2] . '.' )
				. self::b( 'toplist', array( 'toplist' => $L['top-casinos'], 'title' => 'Top ' . $s[0] . ' online casinos for [month] [year]', 'skin' => 'cards', 'state' => $s[0], 'tag' => 'state-' . strtolower( $s[0] ) ) )
				. self::h( 'Is online gambling legal in ' . $s[1] . '?' )
				. self::p( 'Yes. Players must be 21+ and physically located in ' . $s[1] . '. Operators use geolocation to confirm your location before you can play for real money.' )
				. self::h( 'How to sign up at a ' . $s[1] . ' online casino' )
				. self::ul( array( 'Pick a casino from our list and tap Claim Offer.', 'Enter your details and verify your identity (last 4 of SSN).', 'Allow location access so the casino can confirm you are in ' . $s[1] . '.', 'Make your first deposit and claim the welcome bonus.' ) )
				. self::h( 'Responsible gambling in ' . $s[1] )
				. self::p( 'All licensed casinos offer deposit limits, time-outs and self-exclusion. If gambling stops being fun, call <strong>1-800-GAMBLER</strong> for free, confidential help.' )
				. self::b( 'faq', array( 'items' => "Q: What is the legal gambling age in {$s[1]}?\nA: 21 for online casinos.\n\nQ: Who regulates online casinos in {$s[1]}?\nA: The {$s[2]}." ) ),
				array( 'meta' => $fc )
			);
			$term = usg_state_term( $s[0] );
			if ( $term ) {
				update_term_meta( $term->term_id, '_usg_hub_page', $this->pages[ 'online-casinos/' . $slug ] );
			}
		}
		$this->page(
			'online-casinos/bonus',
			'Best Online Casino Bonuses & Promo Codes ([month] [year])',
			self::p( 'The best casino welcome offers available in your state right now, with the codes you need and the terms that matter.' )
			. self::b( 'toplist', array( 'toplist' => $L['top-casinos'], 'skin' => 'cards', 'limit' => 6, 'tag' => 'bonus-page' ) )
			. self::h( 'Calculate what a bonus is really worth' )
			. self::b( 'calculator', array( 'type' => 'bonus' ) )
			. self::h( 'Types of casino bonuses' )
			. self::ul( array( '<strong>Deposit match</strong> — the casino matches a percentage of your first deposit.', '<strong>No-deposit bonus</strong> — free credit just for signing up.', '<strong>Bonus spins</strong> — free spins on selected slots.', '<strong>Lossback</strong> — a refund of net losses as bonus credit.' ) ),
			array( 'meta' => $fc )
		);

		// Sweepstakes.
		$this->page(
			'sweepstakes-casinos',
			'Best Sweepstakes Casinos ([month] [year]): Full List of US Sweeps Sites',
			self::p( 'Sweepstakes casinos let you play casino-style games for free with Gold Coins and Sweeps Coins that can be redeemed for prizes. They are available in most US states.' )
			. self::b( 'toplist', array( 'toplist' => $L['top-sweeps'], 'title' => 'Top sweepstakes casinos for [month] [year]', 'skin' => 'rows', 'tag' => 'sweeps-hub' ) )
			. self::h( 'Where are sweepstakes casinos available?' )
			. self::b( 'state-map', array( 'vertical' => 'sweeps' ) )
			. self::h( 'How sweepstakes casinos work' )
			. self::p( 'You receive free Gold Coins (for fun) and Sweeps Coins (redeemable) when you sign up and log in daily. No purchase is ever necessary to play.' )
			. self::b( 'faq', array( 'items' => "Q: Are sweepstakes casinos legal?\nA: They operate under US sweepstakes laws and are available in most states; a growing number of states restrict them.\n\nQ: Can I win real money?\nA: Sweeps Coins won through play can be redeemed for cash prizes once you meet the minimum and verify your identity." ) ),
			array( 'author' => $C, 'meta' => $fc + array( 'sticky_operator' => $this->ops['sweepstars'] ) )
		);
		foreach ( array( 'sweepstars', 'lucky-frontier' ) as $op ) {
			$this->page( 'sweepstakes-casinos/' . $op, get_the_title( $this->ops[ $op ] ) . ' review ([month] [year]): Free coins & promo code', $this->review_content( $op, 'Sweepstakes casino' ), array( 'author' => $C, 'meta' => $fc + array( 'sticky_operator' => $this->ops[ $op ] ) ) );
		}

		// Sports.
		$this->page(
			'sports-betting',
			'Best Sports Betting Sites & Apps ([month] [year])',
			self::p( 'Compare the best legal online sportsbooks in your state, with current promos and expert tips.' )
			. self::b( 'toplist', array( 'toplist' => $L['top-sportsbooks'], 'title' => 'Top sportsbooks this month', 'skin' => 'rows', 'tag' => 'sports-hub' ) )
			. self::h( 'Where is sports betting legal?' )
			. self::b( 'state-map', array( 'vertical' => 'sports' ) )
			. self::h( 'Betting tools' )
			. self::b( 'link-grid', array( 'columns' => '3', 'links' => "Odds converter|/tools/odds-converter/|American, decimal and fractional\nImplied probability|/tools/implied-probability/|Find the vig on any market\nHedge calculator|/tools/hedge-calculator/|Lock in profit on a futures bet" ) ),
			array( 'meta' => $fc + array( 'sticky_operator' => $this->ops['gridiron-bet'] ) )
		);
		$this->page( 'sports-betting/gridiron-bet', 'Gridiron Bet review ([month] [year]): Promo code & app', $this->review_content( 'gridiron-bet', 'Sportsbook' ), array( 'meta' => $fc + array( 'sticky_operator' => $this->ops['gridiron-bet'] ) ) );

		// Slots.
		$this->page(
			'slots',
			'Real Money Slots: Free Demos & Reviews ([month] [year])',
			self::p( 'Try free demos of popular slots, compare RTP and volatility, and find out where to play for real money.' )
			. self::b( 'slot-grid', array( 'title' => 'Top-rated slots', 'count' => 8, 'orderby' => 'rating' ) )
			. self::h( 'Best casinos for slots' )
			. self::b( 'toplist', array( 'toplist' => $L['top-casinos'], 'skin' => 'table', 'limit' => 5, 'tag' => 'slots-hub' ) )
			. self::h( 'RTP and volatility explained' )
			. self::p( '<strong>RTP</strong> (return to player) is the share of all wagers a slot pays back over millions of spins. <strong>Volatility</strong> describes how often and how big the wins are: low volatility pays small amounts often; high volatility pays rarely but bigger.' )
			. self::b( 'link-grid', array( 'title' => 'Browse by studio', 'columns' => '3', 'links' => "Frontier Gaming|/slots/frontier-gaming/|Sample provider\nNeon Reel Studios|/slots/neon-reel/|Sample provider\nAtlas Play|/slots/atlas-play/|Sample provider" ) ),
			array( 'author' => $C, 'meta' => $fc )
		);

		// News & content hubs.
		$news = self::p( 'The latest on US gambling legislation, the industry, and the promotions worth your attention.' );
		foreach ( array( 'legislation' => 'Legislation & regulation', 'industry' => 'Industry', 'guides' => 'Guides' ) as $slug => $label ) {
			$news .= self::b( 'news-feed', array( 'title' => $label, 'category' => $this->cats[ $slug ], 'count' => 3, 'layout' => 'grid' ) );
		}
		$this->page( 'news', 'Casino & Gambling News', $news, array( 'template' => $landing, 'author' => $M, 'excerpt' => 'Breaking news and analysis on US online gambling.' ) );
		$this->page(
			'learn',
			'Learn Hub: Casino Games & Betting Strategy Guides',
			self::b( 'link-grid', array( 'title' => 'Start here', 'columns' => '3', 'links' => "How wagering requirements work|/news/how-wagering-requirements-work/|Bonus maths in plain English\nRTP & volatility|/news/understanding-rtp-and-volatility/|Pick the right slots for your style\nSpotting a legit sweepstakes casino|/news/signs-a-sweepstakes-casino-is-legit/|Five checks before you sign up\nWhat legalization changes|/news/what-happens-when-a-state-legalizes-online-casinos/|From bill to launch\nResponsible gambling|/responsible-gambling/|Tools to stay in control\nGambling tools|/tools/|Calculators for bonuses and bets" ) )
			. self::b( 'news-feed', array( 'title' => 'From our blog', 'post_type' => 'usg_blog', 'count' => 3 ) ),
			array( 'template' => $landing, 'author' => $M, 'excerpt' => 'Guides to casino games, bonuses and responsible play.' )
		);

		// Tools.
		$this->page(
			'tools',
			'Free Gambling Calculators & Tools',
			self::b( 'link-grid', array( 'columns' => '3', 'links' => "Casino bonus calculator|/tools/casino-bonus-calculator/|See the real value of any bonus\nOdds converter|/tools/odds-converter/|American, decimal, fractional\nImplied probability|/tools/implied-probability/|Find the bookmaker margin\nHedge calculator|/tools/hedge-calculator/|Guarantee a profit\nMartingale calculator|/tools/martingale/|See the true risk of doubling up\nCasino finder|/casino-finder/|Filter every operator" ) )
			. self::p( 'These tools are for education. No calculator can overcome the house edge — always gamble within your means.' ),
			array( 'template' => $landing, 'excerpt' => 'Calculators to understand bonuses, odds and betting systems.' )
		);
		foreach ( array( 'casino-bonus-calculator' => array( 'bonus', 'Casino Bonus & Wagering Calculator' ), 'odds-converter' => array( 'odds', 'Betting Odds Converter' ), 'implied-probability' => array( 'implied', 'Implied Probability & Vig Calculator' ), 'hedge-calculator' => array( 'hedge', 'Hedge Bet Calculator' ), 'martingale' => array( 'martingale', 'Martingale Strategy Calculator' ) ) as $slug => $t ) {
			$this->page( 'tools/' . $slug, $t[1], self::p( 'Enter your numbers below — results update instantly.' ) . self::b( 'calculator', array( 'type' => $t[0] ) ) . self::h( 'How to use this calculator' ) . self::p( 'Sample explainer — describe each input, show a worked example and explain how to interpret the result.' ) . self::b( 'faq', array( 'items' => "Q: Is this calculator free?\nA: Yes, all our tools are free to use.\n\nQ: Can a calculator help me beat the house?\nA: No. Calculators help you understand value and risk, but the house edge always applies." ) ), array( 'meta' => $fc ) );
		}
		$this->page( 'casino-finder', 'Casino Finder: Search Every Operator', self::b( 'casino-finder', array() ), array( 'template' => $landing, 'excerpt' => 'Filter every casino, sweepstakes site and sportsbook by state, type and payment method.' ) );
		$this->page( 'casino-bill-tracker', 'US Online Casino Bill Tracker [year]', self::p( 'Track online casino legislation in every state. <em>Sample data — verify each state before publishing.</em>' ) . self::b( 'bill-tracker', array() ), array( 'author' => $M ) );
		$this->page( 'payments', 'Casino Payment Methods: Fastest Deposits & Withdrawals', self::p( 'How to fund your account and get paid fast at US online casinos.' ) . self::b( 'casino-finder', array() ), array( 'meta' => $fc ) );

		// Community.
		$this->page(
			'rewards',
			'Smart Rewards: Earn Coins, Redeem Prizes',
			self::b( 'hero', array( 'title' => 'Get rewarded for what you already do', 'text' => 'Earn coins for logging in, rating casinos, writing reviews and playing slot demos — then swap them for gift cards and exclusive offers. 100% free.', 'buttons' => "Join free & get 500 coins|/account/\nHow it works|#ways-to-earn-coins" ) )
			. self::b( 'rewards', array( 'section' => 'both' ) )
			. self::b( 'faq', array( 'items' => "Q: Does it cost anything?\nA: No. Smart Rewards is free and never requires a deposit.\n\nQ: Do coins have cash value?\nA: No. Coins are promotional points that can only be redeemed for rewards in our catalogue.\n\nQ: Who can join?\nA: US residents aged 21 or older." ) ),
			array( 'template' => $landing )
		);
		$this->page( 'account', 'My Account', self::b( 'account', array() ), array( 'template' => $landing, 'meta' => array( 'hide_toc' => '1' ) ) );
		$this->page( 'our-team', 'Meet the USA Smart Gamers Team', self::p( 'Our reviewers and editors test every operator hands-on. <em>Sample profiles — replace with your real team.</em>' ) . self::b( 'team-grid', array( 'title' => '', 'count' => 24 ) ), array( 'template' => $landing ) );

		// About & legal.
		$this->page( 'about', 'About USA Smart Gamers', self::p( 'USA Smart Gamers is an independent guide to legal online gambling in the United States. Our mission is simple: help adults make smarter, safer choices about where and how they play.' ) . self::h( 'What we do' ) . self::ul( array( 'Hands-on reviews of licensed casinos, sweepstakes sites and sportsbooks', 'Verified bonuses and promo codes', 'Free tools, slot demos and guides', 'News and legislation tracking for every state' ) ) . self::h( 'How we make money' ) . self::p( 'We may earn a commission when readers sign up through our links. Commercial relationships never influence our scores. <a href="/disclaimer/">Read our advertising disclosure</a>.' ) . self::b( 'team-grid', array( 'title' => 'Our team', 'count' => 8 ) ), array( 'author' => $M ) );
		$this->page( 'about/how-we-rate', 'How We Rate Online Casinos', self::b( 'how-we-test', array( 'title' => 'Our rating methodology' ) ) . self::p( 'Each operator receives sub-scores for bonus value, game variety, payout speed, app experience, customer support and existing-player perks. The overall editorial score is a weighted average set by our editors.' ), array( 'author' => $M ) );
		$this->page( 'about/editorial-guidelines', 'Editorial Guidelines', self::p( 'Accuracy, independence and transparency guide everything we publish.' ) . self::ul( array( 'Every review is based on first-hand testing.', 'Every money page is fact-checked by a second editor.', 'We update reviews whenever offers or terms change, and show the last-updated date.', 'Corrections are made promptly — contact us if you spot an error.' ) ), array( 'author' => $M ) );
		$this->page( 'responsible-gambling', 'Responsible Gambling', self::p( 'Gambling should always be entertainment, never a way to make money. If it stops being fun, help is available 24/7.' ) . self::h( 'Warning signs' ) . self::ul( array( 'Spending more than you can afford to lose', 'Chasing losses', 'Gambling to escape problems', 'Hiding gambling from friends or family' ) ) . self::h( 'Tools that help' ) . self::p( 'Licensed operators offer deposit limits, session reminders, time-outs and self-exclusion. Many states also run statewide self-exclusion programs.' ) . self::h( 'Get help' ) . self::p( 'Call or text <strong>1-800-GAMBLER</strong> or visit the <a href="https://www.ncpgambling.org/" rel="noopener">National Council on Problem Gambling</a>.' ), array( 'author' => $M, 'meta' => array( 'hide_toc' => '1' ) ) );
		$this->page( 'disclaimer', 'Advertising Disclosure', self::p( (string) usg_option( 'disclosure' ) ) . self::p( 'Offers are subject to change and to each operator’s terms. Always read the full terms before signing up. All content is intended for audiences aged 21 and over.' ), array( 'author' => $M, 'meta' => array( 'hide_byline' => '1', 'hide_author_box' => '1' ) ) );
		$this->page( 'privacy-policy', 'Privacy Policy', self::p( '<strong>Template — have this reviewed by a lawyer before launch.</strong>' ) . self::h( 'What we collect' ) . self::p( 'Account details you provide (username, email, state), reviews you submit, coin activity, and — only with your consent — analytics cookies. Affiliate clicks are logged without IP addresses.' ) . self::h( 'How we use it' ) . self::p( 'To run your account and rewards, moderate reviews, improve the site and comply with the law. We never sell your personal data.' ) . self::h( 'Your rights' ) . self::p( 'Contact us to access, correct or delete your data.' ), array( 'author' => $M, 'meta' => array( 'hide_byline' => '1', 'hide_author_box' => '1', 'hide_toc' => '1' ) ) );
		$this->page( 'terms', 'Terms of Use', self::p( '<strong>Template — have this reviewed by a lawyer before launch.</strong> By using this site you confirm you are 21 or older. Content is for information only and is not legal or financial advice. Smart Rewards coins have no cash value and may be changed or withdrawn at any time.' ), array( 'author' => $M, 'meta' => array( 'hide_byline' => '1', 'hide_author_box' => '1', 'hide_toc' => '1' ) ) );
		$this->page( 'contact-us', 'Contact Us', self::p( 'Questions, corrections or partnership enquiries — we read every message.' ) . self::b( 'contact-form', array() ), array( 'template' => $landing, 'meta' => array( 'hide_toc' => '1' ) ) );
		$this->log( 'Pages ready (' . count( $this->pages ) . ').' );
	}

	private function links(): void {
		$map = array( 'liberty-spins' => 'online-casinos/liberty-spins', 'stars-and-stripes' => 'online-casinos/stars-and-stripes', 'golden-eagle' => 'online-casinos/golden-eagle', 'sweepstars' => 'sweepstakes-casinos/sweepstars', 'lucky-frontier' => 'sweepstakes-casinos/lucky-frontier', 'gridiron-bet' => 'sports-betting/gridiron-bet' );
		foreach ( $map as $op => $path ) {
			update_post_meta( $this->ops[ $op ], '_usg_review_page', $this->pages[ $path ] );
		}
	}

	private function posts(): void {
		$posts = array(
			'how-wagering-requirements-work'                     => array( 'How wagering requirements really work', 'guides', 'A plain-English guide to playthrough, game weighting and bonus value.' ),
			'understanding-rtp-and-volatility'                   => array( 'Understanding RTP and volatility before you spin', 'guides', 'Why two slots with the same RTP can feel completely different.' ),
			'signs-a-sweepstakes-casino-is-legit'                => array( 'Five signs a sweepstakes casino is legit', 'industry', 'What to check before you sign up for any sweeps site.' ),
			'what-happens-when-a-state-legalizes-online-casinos' => array( 'What happens after a state legalizes online casinos?', 'legislation', 'From signed bill to first bet: the typical timeline.' ),
		);
		foreach ( $posts as $slug => $p ) {
			$id = $this->upsert(
				'post',
				$slug,
				array(
					'post_title'    => $p[0],
					'post_excerpt'  => $p[2],
					'post_author'   => $this->authors['jordan'],
					'post_category' => array( $this->cats[ $p[1] ] ),
					'post_content'  => self::p( '<strong>Sample article.</strong> ' . $p[2] . ' Replace this evergreen sample with your own reporting.' ) . self::h( 'The short version' ) . self::p( 'Summarise the key point in two or three sentences for readers in a hurry.' ) . self::h( 'The details' ) . self::p( 'Expand with examples, numbers and sources. Link to relevant reviews and tools — for example our <a href="/tools/casino-bonus-calculator/">bonus calculator</a>.' ) . self::h( 'What it means for players' ) . self::p( 'Close with practical takeaways, and remind readers to gamble responsibly.' ),
				),
				array( 'fact_checker' => $this->authors['maya'] )
			);
		}
		foreach ( array( 'vegas-vs-atlantic-city' => 'Las Vegas vs. Atlantic City: which casino trip suits you?', 'psychology-of-near-misses' => 'The psychology of near-misses in slot games' ) as $slug => $title ) {
			$this->upsert( 'usg_blog', $slug, array( 'post_title' => $title, 'post_author' => $this->authors['chris'], 'post_excerpt' => 'Sample Insights article.', 'post_content' => self::p( '<strong>Sample blog post.</strong> Lifestyle and opinion pieces live in the Insights section, separate from news.' ) . self::h( 'Section heading' ) . self::p( 'Your content here.' ) ) );
		}
		$this->log( 'Sample news & insights ready.' );
	}

	private function rewards(): void {
		foreach ( array( 'prize-draw-entry' => array( 'Monthly prize draw entry', 1000, 'entry', 'One entry into our monthly prize draw.' ), 'gift-card-10' => array( '$10 digital gift card', 5000, 'gift_card', 'A $10 e-gift card delivered by email.' ), 'usg-cap' => array( 'USA Smart Gamers cap', 8000, 'merch', 'Embroidered cap shipped to US addresses.' ), 'gift-card-25' => array( '$25 digital gift card', 12000, 'gift_card', 'A $25 e-gift card delivered by email.' ) ) as $slug => $r ) {
			$this->upsert( 'usg_reward', $slug, array( 'post_title' => $r[0], 'post_excerpt' => $r[3] ), array( 'cost' => (string) $r[1], 'reward_type' => $r[2], 'stock' => '', 'delivery' => 'Delivered within 5 business days' ) );
		}
	}

	private function menu( string $name, string $location, array $tree ): void {
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu && $this->update ) {
			wp_delete_nav_menu( $menu->term_id );
			$menu = false;
		}
		if ( $menu ) {
			$menu_id = $menu->term_id;
		} else {
			$menu_id = wp_create_nav_menu( $name );
			$add     = function ( array $items, int $parent ) use ( &$add, $menu_id ) {
				foreach ( $items as $it ) {
					$id = wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-title'     => $it[0],
							'menu-item-url'       => home_url( $it[1] ),
							'menu-item-status'    => 'publish',
							'menu-item-parent-id' => $parent,
							'menu-item-classes'   => $it[3] ?? '',
						)
					);
					if ( ! empty( $it[2] ) ) {
						$add( $it[2], (int) $id );
					}
				}
			};
			$add( $tree, 0 );
		}
		$locs              = get_theme_mod( 'nav_menu_locations', array() );
		$locs[ $location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locs );
	}

	private function menus(): void {
		$this->menu(
			'Primary',
			'primary',
			array(
				array( 'Online Casinos', '/online-casinos/', array(
					array( 'Top casinos', '/online-casinos/', array( array( 'Liberty Spins Casino', '/online-casinos/liberty-spins/', null, 'badge-trending' ), array( 'Stars & Stripes Casino', '/online-casinos/stars-and-stripes/' ), array( 'Golden Eagle Casino', '/online-casinos/golden-eagle/' ), array( 'All online casinos', '/online-casinos/' ) ) ),
					array( 'By state', '/casino-bill-tracker/', array( array( 'New Jersey', '/online-casinos/new-jersey/' ), array( 'Pennsylvania', '/online-casinos/pennsylvania/' ), array( 'Michigan', '/online-casinos/michigan/' ), array( 'Bill tracker', '/casino-bill-tracker/', null, 'badge-new' ) ) ),
					array( 'Bonuses & banking', '/online-casinos/bonus/', array( array( 'Casino bonuses', '/online-casinos/bonus/' ), array( 'Bonus calculator', '/tools/casino-bonus-calculator/' ), array( 'Payment methods', '/payments/' ), array( 'Casino finder', '/casino-finder/' ) ) ),
				) ),
				array( 'Sweepstakes', '/sweepstakes-casinos/', array(
					array( 'Top sweepstakes casinos', '/sweepstakes-casinos/', array( array( 'SweepStars', '/sweepstakes-casinos/sweepstars/', null, 'badge-hot' ), array( 'Lucky Frontier', '/sweepstakes-casinos/lucky-frontier/' ), array( 'All sweepstakes casinos', '/sweepstakes-casinos/' ) ) ),
				) ),
				array( 'Slots', '/slots/', array(
					array( 'Slots', '/slots/', array( array( 'Real money slots', '/slots/' ), array( 'Frontier Gaming', '/slots/frontier-gaming/' ), array( 'Neon Reel Studios', '/slots/neon-reel/' ), array( 'Atlas Play', '/slots/atlas-play/' ) ) ),
					array( 'Top slot demos', '/slots/', array( array( 'Neon Nights Megaways', '/slots/neon-reel/neon-nights-megaways/', null, 'badge-hot' ), array( 'Liberty Bell Riches', '/slots/frontier-gaming/liberty-bell-riches/' ), array( 'Dragon Pearl Fortune', '/slots/atlas-play/dragon-pearl-fortune/' ) ) ),
				) ),
				array( 'Sports Betting', '/sports-betting/', array(
					array( 'Sportsbooks', '/sports-betting/', array( array( 'Best sportsbooks', '/sports-betting/' ), array( 'Gridiron Bet review', '/sports-betting/gridiron-bet/' ) ) ),
					array( 'Betting tools', '/tools/', array( array( 'Odds converter', '/tools/odds-converter/' ), array( 'Implied probability', '/tools/implied-probability/' ), array( 'Hedge calculator', '/tools/hedge-calculator/' ) ) ),
				) ),
				array( 'News & Guides', '/news/', array(
					array( 'News', '/news/', array( array( 'Latest news', '/news/' ), array( 'Legislation', '/news/topic/legislation/' ), array( 'Industry', '/news/topic/industry/' ) ) ),
					array( 'Learn', '/learn/', array( array( 'Learn hub', '/learn/' ), array( 'Insights blog', '/insights/' ), array( 'Responsible gambling', '/responsible-gambling/' ) ) ),
					array( 'Tools', '/tools/', array( array( 'All tools', '/tools/' ), array( 'Martingale calculator', '/tools/martingale/' ) ) ),
				) ),
				array( 'Rewards', '/rewards/', null, 'badge-new' ),
			)
		);
		$this->menu( 'State guides', 'footer_guides', array( array( 'New Jersey casinos', '/online-casinos/new-jersey/' ), array( 'Pennsylvania casinos', '/online-casinos/pennsylvania/' ), array( 'Michigan casinos', '/online-casinos/michigan/' ), array( 'Online casino bill tracker', '/casino-bill-tracker/' ), array( 'Responsible gambling', '/responsible-gambling/' ) ) );
		$this->menu( 'Popular pages', 'footer_popular', array( array( 'Online casinos', '/online-casinos/' ), array( 'Sweepstakes casinos', '/sweepstakes-casinos/' ), array( 'Slots', '/slots/' ), array( 'Sports betting', '/sports-betting/' ), array( 'Casino finder', '/casino-finder/' ), array( 'Gambling tools', '/tools/' ) ) );
		$this->menu( 'About us', 'footer_about', array( array( 'About us', '/about/' ), array( 'How we rate', '/about/how-we-rate/' ), array( 'Editorial guidelines', '/about/editorial-guidelines/' ), array( 'Our team', '/our-team/' ), array( 'Advertising disclosure', '/disclaimer/' ), array( 'Privacy policy', '/privacy-policy/' ), array( 'Terms of use', '/terms/' ), array( 'Contact us', '/contact-us/' ) ) );
		$this->log( 'Menus ready.' );
	}

	private function settings(): void {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $this->pages['home'] );
		update_option( 'wp_page_for_privacy_policy', $this->pages['privacy-policy'] );
		update_option( 'blogname', 'USA Smart Gamers' );
		update_option( 'blogdescription', 'Honest reviews, legal bonuses and expert guides for US players' );
		update_option( 'permalink_structure', '/news/%postname%/' );
		update_option( 'category_base', 'topic' );
		update_option( 'default_comment_status', 'closed' );
		update_option( 'default_ping_status', 'closed' );
		update_option( 'users_can_register', 0 );
		update_option( 'comment_moderation', 1 );
		$s                            = get_option( 'usg_settings', array() );
		$s['default_sticky_operator'] = $this->ops['liberty-spins'];
		update_option( 'usg_settings', $s );
		$this->log( 'Settings applied.' );
	}
}

WP_CLI::add_command(
	'usg seed',
	function ( $args, $assoc ) {
		( new USG_Seeder( ! empty( $assoc['update'] ) ) )->run();
	},
	array( 'shortdesc' => 'Seed USA Smart Gamers structure + sample content. Use --update to overwrite existing seeded items.' )
);
