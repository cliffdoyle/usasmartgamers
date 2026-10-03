<?php
/**
 * WP-CLI: `wp usg seed [--update]` — builds the site structure and clearly-fictional SAMPLE content.
 * Idempotent: existing items are matched by slug/path and only rewritten with --update.
 */

defined( 'ABSPATH' ) || exit;

class USG_Seeder {

	private bool $update;
	private array $lists   = array();
	private array $authors = array();
	private array $pages   = array();
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
		$this->toplists();
		$this->pages();
		$this->short_titles();
		$this->assign_team();
		$this->menus();
		$this->settings();
		// Rules are rebuilt on the next request, when taxonomies register with the new category base.
		delete_option( 'rewrite_rules' );
		WP_CLI::success( 'Seed complete. Add operators, offers, slots and news in wp-admin — empty sections show "Coming soon" until then.' );
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
					'last_action'   => '',
				),
				''
			);
		}
		$this->log( 'States seeded.' );
	}

	private function authors(): void {
		// Real team: Vanessa writes casino/slot/editorial content, George writes sports/sweepstakes/news; each fact-checks the other.
		$team = array(
			'vanessa' => array( 'philimorevanessa', 'Vanessa', 'Phillimore', 'info@ontariogamers.ca', 'Senior Casino & Slots Writer', 'Online casinos, Slot reviews, Responsible gambling', 'https://www.linkedin.com/in/vanessa-phillimore-53308a352/', 1 ),
			'george'  => array( 'georgeowens', 'George', 'Owens', 'george.owens@ontariogamers.ca', 'Sports Betting & News Writer', 'Sports betting, Sweepstakes casinos, Industry news', 'https://www.linkedin.com/in/george-owens-b051aa328', 2 ),
		);
		foreach ( $team as $key => $p ) {
			$user = get_user_by( 'login', $p[0] );
			if ( ! $user ) {
				// Fresh/local environments only: create the profile as an author with an unusable random password.
				$id = wp_insert_user( array( 'user_login' => $p[0], 'user_email' => $p[3], 'user_pass' => wp_generate_password( 32 ), 'first_name' => $p[1], 'last_name' => $p[2], 'display_name' => $p[1] . ' ' . $p[2], 'role' => 'author' ) );
				foreach ( array( 'job_title' => $p[4], 'expertise' => $p[5], 'social_linkedin' => $p[6], 'show_in_team' => '1', 'team_order' => $p[7] ) as $k => $v ) {
					update_user_meta( (int) $id, '_usg_' . $k, $v );
				}
				$user = get_user_by( 'id', (int) $id );
			}
			$this->authors[ $key ] = (int) $user->ID;
		}
		// Internal aliases used throughout the content builders.
		$this->authors['jordan'] = $this->authors['vanessa'];
		$this->authors['maya']   = $this->authors['george'];
		$this->authors['chris']  = $this->authors['george'];
		$this->log( 'Team authors ready.' );
	}

	/**
	 * Final pass: assign each seeded item to Vanessa or George and make the other one the fact-checker.
	 */
	private function assign_team(): void {
		$v      = $this->authors['vanessa'];
		$g      = $this->authors['george'];
		$george = array( 'sweepstakes-casinos', 'sports-betting', 'slots', 'news', 'casino-bill-tracker', 'tools/odds-converter', 'tools/implied-probability', 'tools/hedge-calculator', 'tools/martingale' );
		foreach ( get_posts( array( 'post_type' => array( 'page', 'post', 'usg_blog', 'usg_slot' ), 'post_status' => 'any', 'posts_per_page' => -1 ) ) as $post ) {
			if ( 'page' === $post->post_type ) {
				$author = in_array( get_page_uri( $post ), $george, true ) ? $g : $v;
			} else {
				$author = 'usg_slot' === $post->post_type ? $v : $g;
			}
			if ( (int) $post->post_author !== $author ) {
				wp_update_post( array( 'ID' => $post->ID, 'post_author' => $author ) );
			}
			if ( get_post_meta( $post->ID, '_usg_fact_checker', true ) ) {
				update_post_meta( $post->ID, '_usg_fact_checker', $author === $v ? $g : $v );
			}
		}
		$this->log( 'Content assigned to Vanessa & George.' );
	}

	private function taxonomies(): void {
		foreach ( array( 'PayPal' => '1–2 days', 'Visa' => '3–5 days', 'Mastercard' => '3–5 days', 'Venmo' => 'Same day', 'Apple Pay' => '1–2 days', 'Play+ prepaid' => 'Same day', 'Online banking (ACH)' => '1–3 days', 'PayNearMe (cash)' => 'N/A', 'Skrill' => '1–2 days', 'Cash at casino cage' => 'Instant' ) as $name => $speed ) {
			$this->term( 'usg_payment', $name, sanitize_title( $name ), array( 'speed' => $speed ) );
		}
		foreach ( array( 'legislation' => 'Legislation', 'industry' => 'Industry', 'promotions' => 'Promotions', 'guides' => 'Guides', 'new-jersey' => 'New Jersey', 'pennsylvania' => 'Pennsylvania', 'michigan' => 'Michigan' ) as $slug => $name ) {
			$this->cats[ $slug ] = $this->term( 'category', $name, $slug );
		}
		$this->log( 'Payment methods & news categories ready.' );
	}


	private function toplists(): void {
		// Empty containers: editors add operators in wp-admin (Operators & Offers → Toplists); pages pick them up automatically.
		foreach ( array( 'top-casinos' => 'Top online casinos', 'top-sweeps' => 'Top sweepstakes casinos', 'top-sportsbooks' => 'Top sportsbooks', 'home-best' => 'Homepage — best overall' ) as $slug => $title ) {
			$this->lists[ $slug ] = $this->upsert( 'usg_toplist', $slug, array( 'post_title' => $title ), array( 'geo' => '1', 'items' => array() ) );
		}
		$this->log( 'Toplists ready (empty).' );
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
			. self::h( 'Understanding bonus terms and wagering requirements' )
			. self::p( 'A wagering requirement tells you how many times you must play through a bonus before you can withdraw winnings. Use our <a href="/tools/casino-bonus-calculator/">bonus calculator</a> to see what an offer is really worth.' )
			. self::b( 'faq', array( 'title' => 'Online casino FAQ', 'items' => "Q: Can I play at an online casino if I live in a non-legal state?\nA: You must be physically located inside a legal state when you play. Sweepstakes casinos are an alternative in most other states.\n\nQ: How do I know a casino is licensed?\nA: Licensed casinos display their state regulator’s seal and licence number, usually in the website footer.\n\nQ: What is the fastest payout method?\nA: E-wallets like PayPal and Venmo, and prepaid Play+ cards, are usually the fastest." ) ),
			array( 'meta' => $fc, 'excerpt' => 'Our ranked list of the best legal real-money online casinos in the US.' )
		);
		$states = array( 'new-jersey' => array( 'NJ', 'New Jersey', 'NJ Division of Gaming Enforcement', '2013' ), 'pennsylvania' => array( 'PA', 'Pennsylvania', 'Pennsylvania Gaming Control Board', '2019' ), 'michigan' => array( 'MI', 'Michigan', 'Michigan Gaming Control Board', '2021' ) );
		foreach ( $states as $slug => $s ) {
			$this->page(
				'online-casinos/' . $slug,
				'Best ' . $s[1] . ' Online Casinos ([month] [year]): Legal ' . $s[0] . ' Casino Apps',
				self::p( 'Online casinos have been legal in ' . $s[1] . ' since ' . $s[3] . '. Every site below is licensed by the ' . $s[2] . '.' )
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
			array( 'author' => $C, 'meta' => $fc )
		);

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
			array( 'meta' => $fc )
		);

		// Slots.
		$this->page(
			'slots',
			'Real Money Slots: Free Demos & Reviews ([month] [year])',
			self::p( 'Try free demos of popular slots, compare RTP and volatility, and find out where to play for real money.' )
			. self::b( 'slot-grid', array( 'title' => 'Top-rated slots', 'count' => 8, 'orderby' => 'rating' ) )
			. self::h( 'Best casinos for slots' )
			. self::b( 'toplist', array( 'toplist' => $L['top-casinos'], 'skin' => 'table', 'limit' => 5, 'tag' => 'slots-hub' ) )
			. self::h( 'RTP and volatility explained' )
			. self::p( '<strong>RTP</strong> (return to player) is the share of all wagers a slot pays back over millions of spins. <strong>Volatility</strong> describes how often and how big the wins are: low volatility pays small amounts often; high volatility pays rarely but bigger.' ),
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
			self::b( 'link-grid', array( 'title' => 'Start here', 'columns' => '3', 'links' => "Online casinos|/online-casinos/|How legal real-money casinos work\nSweepstakes casinos|/sweepstakes-casinos/|Free-to-play casinos explained\nSlots|/slots/|RTP, volatility and free demos\nBill tracker|/casino-bill-tracker/|Where online gambling is heading\nResponsible gambling|/responsible-gambling/|Tools to stay in control\nGambling tools|/tools/|Calculators for bonuses and bets" ) )
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
		foreach ( array(
			'casino-bonus-calculator' => array( 'bonus', 'Casino Bonus & Wagering Calculator', 'Enter your deposit, the match percentage, the maximum bonus and the wagering requirement. The calculator shows how much you must bet before you can withdraw, the expected loss while clearing the bonus at your chosen RTP, and whether the bonus is worth more than it costs.' ),
			'odds-converter'          => array( 'odds', 'Betting Odds Converter', 'Choose the format you have (American, decimal or fractional), enter the odds and your stake. You will see the same price in every format, the implied probability and your potential payout.' ),
			'implied-probability'     => array( 'implied', 'Implied Probability & Vig Calculator', 'Enter the American odds for both sides of a market. The calculator converts each price to a probability, shows the bookmaker margin (vig) and the fair, no-vig probability of each outcome.' ),
			'hedge-calculator'        => array( 'hedge', 'Hedge Bet Calculator', 'Enter your original stake and odds, then the odds available on the opposite outcome. The calculator tells you how much to bet to lock in the same profit whatever happens.' ),
			'martingale'              => array( 'martingale', 'Martingale Strategy Calculator', 'Enter your base bet, multiplier and the losing streak you want to survive. The table shows how quickly the required stakes grow — a clear illustration of why doubling up is risky.' ),
		) as $slug => $t ) {
			$this->page( 'tools/' . $slug, $t[1], self::p( 'Enter your numbers below — results update instantly.' ) . self::b( 'calculator', array( 'type' => $t[0] ) ) . self::h( 'How to use this calculator' ) . self::p( $t[2] ) . self::b( 'faq', array( 'items' => "Q: Is this calculator free?\nA: Yes, all our tools are free to use.\n\nQ: Can a calculator help me beat the house?\nA: No. Calculators help you understand value and risk, but the house edge always applies." ) ), array( 'meta' => $fc ) );
		}
		$this->page( 'casino-finder', 'Casino Finder: Search Every Operator', self::b( 'casino-finder', array() ), array( 'template' => $landing, 'excerpt' => 'Filter every casino, sweepstakes site and sportsbook by state, type and payment method.' ) );
		$this->page( 'casino-bill-tracker', 'US Online Casino Bill Tracker [year]', self::p( 'Track online casino legislation in every state.' ) . self::b( 'bill-tracker', array() ), array( 'author' => $M ) );
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
		$this->page( 'our-team', 'Meet the USA Smart Gamers Team', self::p( 'Our writers test every operator hands-on and every money page is fact-checked by a second editor.' ) . self::b( 'team-grid', array( 'title' => '', 'count' => 24 ) ), array( 'template' => $landing ) );

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

	private function short_titles(): void {
		$short = array(
			'online-casinos' => 'Online Casinos', 'online-casinos/new-jersey' => 'New Jersey', 'online-casinos/pennsylvania' => 'Pennsylvania', 'online-casinos/michigan' => 'Michigan', 'online-casinos/bonus' => 'Casino Bonuses',
			'sweepstakes-casinos' => 'Sweepstakes Casinos', 'sports-betting' => 'Sports Betting', 'slots' => 'Slots', 'news' => 'News', 'learn' => 'Learn', 'tools' => 'Tools',
			'casino-bill-tracker' => 'Bill Tracker', 'casino-finder' => 'Casino Finder', 'payments' => 'Payments', 'rewards' => 'Smart Rewards', 'our-team' => 'Our Team', 'about' => 'About',
		);
		foreach ( $short as $path => $label ) {
			if ( isset( $this->pages[ $path ] ) ) {
				update_post_meta( $this->pages[ $path ], '_usg_short_title', $label );
			}
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
					array( 'Online casinos', '/online-casinos/', array( array( 'Best online casinos', '/online-casinos/' ), array( 'Casino bonuses', '/online-casinos/bonus/' ), array( 'Casino finder', '/casino-finder/' ), array( 'Payment methods', '/payments/' ) ) ),
					array( 'By state', '/casino-bill-tracker/', array( array( 'New Jersey', '/online-casinos/new-jersey/' ), array( 'Pennsylvania', '/online-casinos/pennsylvania/' ), array( 'Michigan', '/online-casinos/michigan/' ), array( 'Bill tracker', '/casino-bill-tracker/', null, 'badge-new' ) ) ),
				) ),
				array( 'Sweepstakes', '/sweepstakes-casinos/' ),
				array( 'Slots', '/slots/' ),
				array( 'Sports Betting', '/sports-betting/', array(
					array( 'Sportsbooks', '/sports-betting/', array( array( 'Best sportsbooks', '/sports-betting/' ) ) ),
					array( 'Betting tools', '/tools/', array( array( 'Odds converter', '/tools/odds-converter/' ), array( 'Implied probability', '/tools/implied-probability/' ), array( 'Hedge calculator', '/tools/hedge-calculator/' ) ) ),
				) ),
				array( 'News & Guides', '/news/', array(
					array( 'News', '/news/', array( array( 'Latest news', '/news/' ), array( 'Legislation', '/news/topic/legislation/' ), array( 'Industry', '/news/topic/industry/' ) ) ),
					array( 'Learn', '/learn/', array( array( 'Learn hub', '/learn/' ), array( 'Insights blog', '/insights/' ), array( 'Responsible gambling', '/responsible-gambling/' ) ) ),
					array( 'Tools', '/tools/', array( array( 'All tools', '/tools/' ), array( 'Bonus calculator', '/tools/casino-bonus-calculator/' ), array( 'Martingale calculator', '/tools/martingale/' ) ) ),
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
		update_option( 'category_base', 'news/topic' );
		update_option( 'default_comment_status', 'closed' );
		update_option( 'default_ping_status', 'closed' );
		update_option( 'users_can_register', 0 );
		update_option( 'comment_moderation', 1 );
		$s                            = get_option( 'usg_settings', array() );
		$s['default_sticky_operator'] = '';
		update_option( 'usg_settings', $s );
		$this->log( 'Settings applied.' );
	}
}

WP_CLI::add_command(
	'usg seed',
	function ( $args, $assoc ) {
		( new USG_Seeder( ! empty( $assoc['update'] ) ) )->run();
	},
	array( 'shortdesc' => 'Seed USA Smart Gamers site structure (pages, menus, states, toplists). Use --update to overwrite existing seeded items.' )
);
