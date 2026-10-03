<?php
/**
 * Block registry. Every block is a dynamic (server-rendered) block; the editor UI is generated from this registry
 * by assets/editor.js, so adding a block = one entry here + one usg_block_{key}() render function.
 */

defined( 'ABSPATH' ) || exit;

function usg_block_defs(): array {
	$skins = array( 'rows' => 'Ranked rows', 'cards' => 'Offer cards', 'table' => 'Compact table' );
	$vert  = array( 'casino' => 'Online casino', 'sports' => 'Sports betting', 'sweeps' => 'Sweepstakes' );
	return array(
		'hero'           => array( 'Hero', 'cover-image', array(
			'title'   => array( 'type' => 'text', 'label' => 'Heading (H1)' ),
			'text'    => array( 'type' => 'textarea', 'label' => 'Intro text' ),
			'buttons' => array( 'type' => 'textarea', 'label' => 'Pill links (one per line: Label|URL)' ),
			'style'   => array( 'type' => 'select', 'label' => 'Style', 'options' => array( 'gradient' => 'Brand gradient', 'light' => 'Light' ), 'default' => 'gradient' ),
		) ),
		'toplist'        => array( 'Toplist', 'list-view', array(
			'toplist' => array( 'type' => 'post', 'source' => 'toplists', 'label' => 'Toplist' ),
			'title'   => array( 'type' => 'text', 'label' => 'Heading (optional)' ),
			'skin'    => array( 'type' => 'select', 'label' => 'Design', 'options' => $skins, 'default' => 'rows' ),
			'limit'   => array( 'type' => 'number', 'label' => 'Max operators', 'default' => 10 ),
			'state'   => array( 'type' => 'select', 'source' => 'states', 'label' => 'Lock to a state (state pages). Blank = auto-detect visitor.' ),
			'tag'     => array( 'type' => 'text', 'label' => 'Tracking tag', 'default' => 'toplist' ),
		) ),
		'toplist_tabs'   => array( 'Toplist tabs', 'index-card', array(
			'title'         => array( 'type' => 'text', 'label' => 'Heading (optional)' ),
			'tab1_label'    => array( 'type' => 'text', 'label' => 'Tab 1 label' ),
			'tab1_toplist'  => array( 'type' => 'post', 'source' => 'toplists', 'label' => 'Tab 1 toplist' ),
			'tab2_label'    => array( 'type' => 'text', 'label' => 'Tab 2 label' ),
			'tab2_toplist'  => array( 'type' => 'post', 'source' => 'toplists', 'label' => 'Tab 2 toplist' ),
			'tab3_label'    => array( 'type' => 'text', 'label' => 'Tab 3 label' ),
			'tab3_toplist'  => array( 'type' => 'post', 'source' => 'toplists', 'label' => 'Tab 3 toplist' ),
			'skin'          => array( 'type' => 'select', 'label' => 'Design', 'options' => $skins, 'default' => 'rows' ),
			'limit'         => array( 'type' => 'number', 'label' => 'Max operators per tab', 'default' => 5 ),
		) ),
		'cta_card'       => array( 'Offer card', 'money-alt', array(
			'operator' => array( 'type' => 'post', 'source' => 'operators', 'label' => 'Operator' ),
			'offer'    => array( 'type' => 'post', 'source' => 'offers', 'label' => 'Offer (blank = best valid)' ),
			'tag'      => array( 'type' => 'text', 'label' => 'Tracking tag', 'default' => 'offer-card' ),
		) ),
		'bonus_card'     => array( 'Bonus spotlight', 'star-filled', array(
			'operator' => array( 'type' => 'post', 'source' => 'operators', 'label' => 'Operator' ),
			'offer'    => array( 'type' => 'post', 'source' => 'offers', 'label' => 'Offer (blank = best valid)' ),
			'title'    => array( 'type' => 'text', 'label' => 'Eyebrow (e.g. "Editor’s pick")' ),
		) ),
		'claim_button'   => array( 'Claim button', 'button', array(
			'operator' => array( 'type' => 'post', 'source' => 'operators', 'label' => 'Operator' ),
			'label'    => array( 'type' => 'text', 'label' => 'Label (blank = offer button label)' ),
			'style'    => array( 'type' => 'select', 'label' => 'Style', 'options' => array( 'green' => 'Green 3D', 'red' => 'Red', 'navy' => 'Navy' ), 'default' => 'green' ),
			'tag'      => array( 'type' => 'text', 'label' => 'Tracking tag', 'default' => 'inline-button' ),
		) ),
		'review_summary' => array( 'Review summary', 'awards', array(
			'operator' => array( 'type' => 'post', 'source' => 'operators', 'label' => 'Operator' ),
		) ),
		'comparison'     => array( 'Comparison table', 'editor-table', array(
			'title'     => array( 'type' => 'text', 'label' => 'Heading (optional)' ),
			'operators' => array( 'type' => 'posts', 'source' => 'operators', 'label' => 'Operators to compare' ),
		) ),
		'pros_cons'      => array( 'Pros & cons', 'yes-alt', array(
			'operator' => array( 'type' => 'post', 'source' => 'operators', 'label' => 'Use operator pros/cons' ),
			'pros'     => array( 'type' => 'textarea', 'label' => 'Or custom pros (one per line)' ),
			'cons'     => array( 'type' => 'textarea', 'label' => 'Custom cons (one per line)' ),
		) ),
		'faq'            => array( 'FAQ', 'editor-help', array(
			'title'  => array( 'type' => 'text', 'label' => 'Heading', 'default' => 'Frequently asked questions' ),
			'items'  => array( 'type' => 'textarea', 'label' => 'Questions: "Q: …" line then "A: …" line, blank line between' ),
			'schema' => array( 'type' => 'toggle', 'label' => 'Output FAQ schema', 'default' => true ),
		) ),
		'expert_insight' => array( 'Expert insight', 'format-quote', array(
			'author' => array( 'type' => 'post', 'source' => 'authors', 'label' => 'Expert' ),
			'quote'  => array( 'type' => 'textarea', 'label' => 'Insight' ),
		) ),
		'how_we_test'    => array( 'How we test', 'shield', array(
			'title' => array( 'type' => 'text', 'label' => 'Heading', 'default' => 'How we test every review' ),
			'steps' => array( 'type' => 'textarea', 'label' => 'Steps (one per line: Title|Description)' ),
		) ),
		'cta_banner'     => array( 'CTA banner', 'megaphone', array(
			'title'        => array( 'type' => 'text', 'label' => 'Heading' ),
			'text'         => array( 'type' => 'textarea', 'label' => 'Text' ),
			'button_label' => array( 'type' => 'text', 'label' => 'Button label' ),
			'button_url'   => array( 'type' => 'text', 'label' => 'Button URL' ),
			'style'        => array( 'type' => 'select', 'label' => 'Style', 'options' => array( 'navy' => 'Navy', 'gold' => 'Gold', 'light' => 'Light' ), 'default' => 'navy' ),
		) ),
		'key_takeaways'  => array( 'Key takeaways', 'lightbulb', array(
			'title' => array( 'type' => 'text', 'label' => 'Heading', 'default' => 'Key takeaways' ),
			'items' => array( 'type' => 'textarea', 'label' => 'Items (one per line)' ),
		) ),
		'news_feed'      => array( 'Post feed', 'rss', array(
			'title'     => array( 'type' => 'text', 'label' => 'Heading' ),
			'post_type' => array( 'type' => 'select', 'label' => 'Source', 'options' => array( 'post' => 'News', 'usg_blog' => 'Insights (blog)' ), 'default' => 'post' ),
			'category'  => array( 'type' => 'post', 'source' => 'categories', 'label' => 'News category (optional)' ),
			'count'     => array( 'type' => 'number', 'label' => 'Number of posts', 'default' => 4 ),
			'layout'    => array( 'type' => 'select', 'label' => 'Layout', 'options' => array( 'grid' => 'Cards', 'list' => 'List' ), 'default' => 'grid' ),
		) ),
		'slot_grid'      => array( 'Slot grid', 'games', array(
			'title'    => array( 'type' => 'text', 'label' => 'Heading' ),
			'provider' => array( 'type' => 'post', 'source' => 'providers', 'label' => 'Provider (optional)' ),
			'count'    => array( 'type' => 'number', 'label' => 'Number of slots', 'default' => 8 ),
			'orderby'  => array( 'type' => 'select', 'label' => 'Order', 'options' => array( 'rating' => 'Top rated', 'date' => 'Newest', 'title' => 'A–Z' ), 'default' => 'rating' ),
		) ),
		'team_grid'      => array( 'Team grid', 'groups', array(
			'title' => array( 'type' => 'text', 'label' => 'Heading', 'default' => 'Meet our team' ),
			'count' => array( 'type' => 'number', 'label' => 'Max people', 'default' => 8 ),
		) ),
		'state_map'      => array( 'US legal map', 'location-alt', array(
			'title'    => array( 'type' => 'text', 'label' => 'Heading' ),
			'vertical' => array( 'type' => 'select', 'label' => 'Show status for', 'options' => $vert, 'default' => 'casino' ),
		) ),
		'state_table'    => array( 'US state status table', 'editor-ul', array(
			'title'    => array( 'type' => 'text', 'label' => 'Heading' ),
			'vertical' => array( 'type' => 'select', 'label' => 'Show status for', 'options' => $vert, 'default' => 'casino' ),
			'filter'   => array( 'type' => 'select', 'label' => 'Only states with status', 'options' => array( '' => 'All', 'legal' => 'legal', 'pending' => 'pending', 'retail' => 'retail', 'allowed' => 'allowed', 'restricted' => 'restricted', 'unlikely' => 'unlikely', 'no' => 'no' ) ),
		) ),
		'link_grid'      => array( 'Link grid', 'grid-view', array(
			'title'   => array( 'type' => 'text', 'label' => 'Heading' ),
			'links'   => array( 'type' => 'textarea', 'label' => 'Links (one per line: Label|URL|Description)' ),
			'columns' => array( 'type' => 'select', 'label' => 'Columns', 'options' => array( '2' => '2', '3' => '3', '4' => '4' ), 'default' => '3' ),
		) ),
		'calculator'     => array( 'Calculator', 'calculator', array(
			'type' => array( 'type' => 'select', 'label' => 'Calculator', 'options' => array( 'bonus' => 'Casino bonus / wagering', 'odds' => 'Odds converter', 'implied' => 'Implied probability & vig', 'hedge' => 'Hedge bet', 'martingale' => 'Martingale' ), 'default' => 'bonus' ),
		) ),
		'casino_finder'  => array( 'Casino finder', 'search', array(
			'vertical' => array( 'type' => 'select', 'label' => 'Default vertical', 'options' => array( '' => 'All' ) + usg_verticals() ),
		) ),
		'bill_tracker'   => array( 'Bill tracker', 'clipboard', array(
			'title' => array( 'type' => 'text', 'label' => 'Heading', 'default' => 'Online casino legislation by state' ),
		) ),
		'user_reviews'   => array( 'User reviews', 'testimonial', array(
			'operator' => array( 'type' => 'post', 'source' => 'operators', 'label' => 'Operator' ),
			'title'    => array( 'type' => 'text', 'label' => 'Heading', 'default' => 'Player reviews' ),
		) ),
		'account'        => array( 'Account (join / log in / profile)', 'admin-users', array() ),
		'rewards'        => array( 'Smart Rewards', 'tickets-alt', array(
			'section' => array( 'type' => 'select', 'label' => 'Show', 'options' => array( 'both' => 'Tasks + catalogue', 'tasks' => 'Ways to earn', 'catalog' => 'Reward catalogue' ), 'default' => 'both' ),
		) ),
		'contact_form'   => array( 'Contact form', 'email', array() ),
	);
}

function usg_block_attr_schema( array $f ): array {
	$map = array( 'number' => 'number', 'toggle' => 'boolean', 'posts' => 'array' );
	$t   = $map[ $f['type'] ] ?? ( 'post' === $f['type'] && ! in_array( $f['source'] ?? '', array( 'states' ), true ) ? 'number' : 'string' );
	if ( 'select' === $f['type'] && 'states' === ( $f['source'] ?? '' ) ) {
		$t = 'string';
	}
	$s = array( 'type' => $t );
	if ( 'array' === $t ) {
		$s['items']   = array( 'type' => 'integer' );
		$s['default'] = array();
	} elseif ( array_key_exists( 'default', $f ) ) {
		$s['default'] = $f['default'];
	} else {
		$s['default'] = 'number' === $t ? 0 : ( 'boolean' === $t ? false : '' );
	}
	return $s;
}

function usg_block_attributes( string $key ): array {
	$attrs = array();
	foreach ( usg_block_defs()[ $key ][2] ?? array() as $k => $f ) {
		$attrs[ $k ] = usg_block_attr_schema( $f );
	}
	return $attrs;
}

/**
 * Normalise attributes against the registry (used for REST geo re-renders).
 */
function usg_block_clean_attrs( string $key, array $raw ): array {
	$out = array();
	foreach ( usg_block_attributes( $key ) as $k => $s ) {
		$v = $raw[ $k ] ?? $s['default'];
		switch ( $s['type'] ) {
			case 'number':
				$out[ $k ] = is_numeric( $v ) ? 0 + $v : 0;
				break;
			case 'boolean':
				$out[ $k ] = (bool) $v;
				break;
			case 'array':
				$out[ $k ] = array_map( 'absint', (array) $v );
				break;
			default:
				$out[ $k ] = sanitize_text_field( (string) $v );
		}
	}
	return $out;
}

function usg_render_block( string $key, array $attrs ): string {
	$fn = 'usg_block_' . $key;
	return function_exists( $fn ) ? (string) $fn( $attrs ) : '';
}

add_action(
	'init',
	function () {
		wp_register_script(
			'usg-editor',
			USG_CORE_URL . 'assets/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
			(string) filemtime( USG_CORE_DIR . 'assets/editor.js' ),
			true
		);
		foreach ( usg_block_defs() as $key => $def ) {
			register_block_type(
				'usg/' . str_replace( '_', '-', $key ),
				array(
					'api_version'           => 3,
					'title'                 => $def[0],
					'category'              => 'usg',
					'icon'                  => $def[1],
					'attributes'            => usg_block_attributes( $key ),
					'editor_script_handles' => array( 'usg-editor' ),
					'supports'              => array( 'html' => false ),
					'render_callback'       => function ( $attrs ) use ( $key ) {
						return usg_render_block( $key, (array) $attrs );
					},
				)
			);
		}
	}
);

add_filter(
	'block_categories_all',
	function ( $cats ) {
		array_unshift( $cats, array( 'slug' => 'usg', 'title' => 'USA Smart Gamers' ) );
		return $cats;
	}
);

add_action(
	'enqueue_block_editor_assets',
	function () {
		$opts = function ( array $assoc ): array {
			$out = array();
			foreach ( $assoc as $v => $l ) {
				$out[] = array( 'value' => (string) $v, 'label' => (string) $l );
			}
			return $out;
		};
		$cats = array( '' => '— Any —' );
		foreach ( get_categories( array( 'hide_empty' => false ) ) as $c ) {
			$cats[ $c->term_id ] = $c->name;
		}
		$provs = array( '' => '— Any —' );
		foreach ( get_terms( array( 'taxonomy' => 'usg_provider', 'hide_empty' => false ) ) as $t ) {
			$provs[ $t->term_id ] = $t->name;
		}
		$defs = array();
		foreach ( usg_block_defs() as $key => $def ) {
			$controls = array();
			foreach ( $def[2] as $k => $f ) {
				$c = array( 'key' => $k, 'type' => $f['type'], 'label' => $f['label'] );
				if ( isset( $f['options'] ) ) {
					$c['options'] = $opts( $f['options'] );
				}
				if ( isset( $f['source'] ) ) {
					$c['source'] = $f['source'];
				}
				$controls[] = $c;
			}
			$defs[] = array(
				'name'       => 'usg/' . str_replace( '_', '-', $key ),
				'title'      => $def[0],
				'icon'       => $def[1],
				'attributes' => usg_block_attributes( $key ),
				'controls'   => $controls,
			);
		}
		$data = array(
			'blocks'  => $defs,
			'sources' => array(
				'operators'  => $opts( usg_post_options( 'usg_operator' ) ),
				'offers'     => $opts( usg_post_options( 'usg_offer' ) ),
				'toplists'   => $opts( usg_post_options( 'usg_toplist' ) ),
				'authors'    => $opts( usg_author_options() ),
				'categories' => $opts( $cats ),
				'providers'  => $opts( $provs ),
				'states'     => $opts( array( '' => '— Auto —' ) + usg_states() ),
			),
		);
		wp_add_inline_script( 'usg-editor', 'window.usgEditor = ' . wp_json_encode( $data ) . ';', 'before' );
	}
);
