<?php
/**
 * Render callbacks: editorial/content blocks + shared cards used by theme templates.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Shared cards ---------- */

function usg_author_avatar( int $user_id, int $size = 64, string $class = 'usg-avatar' ): string {
	return get_avatar( $user_id, $size, '', get_the_author_meta( 'display_name', $user_id ), array( 'class' => $class ) );
}

add_filter(
	'pre_get_avatar_data',
	function ( $args, $id_or_email ) {
		$uid = 0;
		if ( is_numeric( $id_or_email ) ) {
			$uid = (int) $id_or_email;
		} elseif ( $id_or_email instanceof WP_User ) {
			$uid = $id_or_email->ID;
		} elseif ( $id_or_email instanceof WP_Post ) {
			$uid = (int) $id_or_email->post_author;
		}
		$att = $uid ? (int) usg_user_meta( $uid, 'avatar' ) : 0;
		if ( $att ) {
			$src = wp_get_attachment_image_url( $att, array( $args['size'], $args['size'] ) );
			if ( $src ) {
				$args['url'] = $src;
			}
		}
		return $args;
	},
	10,
	2
);

function usg_author_socials( int $user_id ): string {
	$h = '';
	foreach ( array( 'social_linkedin' => 'linkedin', 'social_x' => 'twitter', 'social_facebook' => 'facebook', 'social_instagram' => 'instagram' ) as $k => $icon ) {
		$u = usg_user_meta( $user_id, $k );
		if ( $u ) {
			$h .= '<a href="' . esc_url( $u ) . '" rel="noopener me" target="_blank" aria-label="' . esc_attr( ucfirst( $icon ) ) . '">' . usg_icon( $icon ) . '</a>';
		}
	}
	return $h ? '<span class="usg-socials">' . $h . '</span>' : '';
}

function usg_post_card( $post, string $variant = 'grid' ): string {
	$post  = get_post( $post );
	$thumb = has_post_thumbnail( $post ) ? get_the_post_thumbnail( $post, 'medium_large', array( 'loading' => 'lazy' ) ) : '<span class="usg-card__ph">' . esc_html( mb_substr( get_the_title( $post ), 0, 1 ) ) . '</span>';
	$cats  = 'post' === $post->post_type ? get_the_category( $post->ID ) : array();
	$kick  = $cats ? '<span class="usg-card__kicker">' . esc_html( $cats[0]->name ) . '</span>' : ( 'usg_blog' === $post->post_type ? '<span class="usg-card__kicker">' . esc_html__( 'Insights', 'usasmartgamers-core' ) . '</span>' : '' );
	$meta  = '<span class="usg-card__meta">' . esc_html( get_the_author_meta( 'display_name', (int) $post->post_author ) ) . ' · ' . esc_html( get_the_date( '', $post ) ) . '</span>';
	if ( 'list' === $variant ) {
		return '<article class="usg-card usg-card--list"><a href="' . esc_url( get_permalink( $post ) ) . '"><h3 class="usg-card__title">' . esc_html( get_the_title( $post ) ) . '</h3></a>' . $kick . $meta . '</article>';
	}
	return '<article class="usg-card"><a class="usg-card__media" href="' . esc_url( get_permalink( $post ) ) . '" tabindex="-1">' . $thumb . '</a><div class="usg-card__body">' . $kick . '<h3 class="usg-card__title"><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3><p class="usg-card__excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $post ), 20 ) ) . '</p>' . $meta . '</div></article>';
}

function usg_slot_card( int $id ): string {
	$thumb  = has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, 'medium', array( 'loading' => 'lazy' ) ) : '<span class="usg-slot-card__ph">' . esc_html( get_the_title( $id ) ) . '</span>';
	$prov   = get_the_terms( $id, 'usg_provider' );
	$rating = (float) usg_meta( $id, 'rating', 0 );
	$rtp    = usg_meta( $id, 'rtp' );
	return '<article class="usg-slot-card"><a class="usg-slot-card__media" href="' . esc_url( get_permalink( $id ) ) . '">' . $thumb . '<span class="usg-slot-card__play">' . usg_icon( 'play' ) . esc_html__( 'Play demo', 'usasmartgamers-core' ) . '</span></a><div class="usg-slot-card__body"><h3><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></h3>' . ( $prov && ! is_wp_error( $prov ) ? '<span class="usg-slot-card__prov">' . esc_html( $prov[0]->name ) . '</span>' : '' ) . '<dl class="usg-slot-card__specs">' . ( $rtp ? '<div><dt>RTP</dt><dd>' . esc_html( $rtp ) . '%</dd></div>' : '' ) . ( usg_meta( $id, 'volatility' ) ? '<div><dt>' . esc_html__( 'Volatility', 'usasmartgamers-core' ) . '</dt><dd>' . esc_html( usg_meta( $id, 'volatility' ) ) . '</dd></div>' : '' ) . '</dl>' . ( $rating ? usg_stars( $rating ) : '' ) . '</div></article>';
}

/* ---------- Blocks ---------- */

function usg_block_hero( array $a ): string {
	$pills = '';
	foreach ( usg_pipe_lines( $a['buttons'] ?? '' ) as $p ) {
		if ( ! empty( $p[1] ) ) {
			$pills .= '<a class="usg-hero__pill" href="' . esc_url( $p[1] ) . '">' . esc_html( $p[0] ) . usg_icon( 'arrow' ) . '</a>';
		}
	}
	$style = 'light' === ( $a['style'] ?? '' ) ? 'light' : 'gradient';
	return '<section class="usg-hero usg-hero--' . $style . ' alignfull"><div class="usg-hero__inner">' . ( ! empty( $a['title'] ) ? '<h1>' . esc_html( usg_tokens( $a['title'] ) ) . '</h1>' : '' ) . ( ! empty( $a['text'] ) ? '<p class="usg-hero__text">' . esc_html( usg_tokens( $a['text'] ) ) . '</p>' : '' ) . ( $pills ? '<nav class="usg-hero__pills" aria-label="' . esc_attr__( 'Popular sections', 'usasmartgamers-core' ) . '">' . $pills . '</nav>' : '' ) . '</div></section>';
}

function usg_block_faq( array $a ): string {
	$items = array();
	foreach ( preg_split( '/\n\s*\n/', trim( (string) ( $a['items'] ?? '' ) ) ) as $chunk ) {
		if ( preg_match( '/^\s*Q:\s*(.+?)\s*\n\s*A:\s*(.+)$/s', $chunk, $m ) ) {
			$items[] = array( trim( $m[1] ), trim( $m[2] ) );
		}
	}
	if ( ! $items ) {
		return '';
	}
	$h = ! empty( $a['title'] ) ? '<h2 class="usg-block-title">' . esc_html( usg_tokens( $a['title'] ) ) . '</h2>' : '';
	foreach ( $items as $i => $qa ) {
		$h .= '<details class="usg-faq__item"' . ( 0 === $i ? ' open' : '' ) . '><summary>' . esc_html( $qa[0] ) . usg_icon( 'chevron' ) . '</summary><div class="usg-faq__a">' . wpautop( esc_html( $qa[1] ) ) . '</div></details>';
		if ( ! empty( $a['schema'] ) && ! wp_is_json_request() ) {
			usg_schema_faq( $qa[0], $qa[1] );
		}
	}
	return '<section class="usg-faq">' . $h . '</section>';
}

function usg_block_expert_insight( array $a ): string {
	$uid = (int) ( $a['author'] ?? 0 );
	if ( ! $uid || empty( $a['quote'] ) ) {
		return '';
	}
	return '<figure class="usg-insight"><span class="usg-insight__label">' . esc_html__( 'Expert insight', 'usasmartgamers-core' ) . '</span><blockquote>' . wpautop( esc_html( $a['quote'] ) ) . '</blockquote><figcaption>' . usg_author_avatar( $uid, 48 ) . '<span><a href="' . esc_url( get_author_posts_url( $uid ) ) . '">' . esc_html( get_the_author_meta( 'display_name', $uid ) ) . '</a><small>' . esc_html( usg_user_meta( $uid, 'job_title' ) ) . '</small></span></figcaption></figure>';
}

function usg_block_how_we_test( array $a ): string {
	$steps = usg_pipe_lines( $a['steps'] ?? '' );
	if ( ! $steps ) {
		$steps = array(
			array( 'Real accounts', 'We sign up, verify and deposit our own money.' ),
			array( 'Bonus claimed', 'We claim the welcome offer and read every term.' ),
			array( 'Games & app', 'We play on desktop and mobile and time the load speeds.' ),
			array( 'Withdrawals', 'We cash out and record exactly how long it takes.' ),
			array( 'Support', 'We contact support via live chat, email and phone.' ),
			array( 'Re-tested', 'Reviews are re-checked regularly and when offers change.' ),
		);
	}
	$h = '';
	foreach ( $steps as $i => $s ) {
		$h .= '<li><span class="usg-step__n">' . ( $i + 1 ) . '</span><strong>' . esc_html( $s[0] ) . '</strong>' . ( ! empty( $s[1] ) ? '<p>' . esc_html( $s[1] ) . '</p>' : '' ) . '</li>';
	}
	$about = usg_page_url_by_path( 'about/how-we-rate', '/about/' );
	return '<section class="usg-howtest"><h2 class="usg-block-title">' . esc_html( $a['title'] ?: __( 'How we test every review', 'usasmartgamers-core' ) ) . '</h2><p class="usg-howtest__lead">' . esc_html( (string) usg_option( 'how_we_test' ) ) . '</p><ol class="usg-steps">' . $h . '</ol><p class="usg-howtest__disc"><strong>' . esc_html__( 'Full transparency:', 'usasmartgamers-core' ) . '</strong> ' . esc_html( (string) usg_option( 'disclosure' ) ) . ' <a href="' . esc_url( $about ) . '">' . esc_html__( 'Read our rating methodology', 'usasmartgamers-core' ) . '</a></p></section>';
}

function usg_block_cta_banner( array $a ): string {
	$style = in_array( $a['style'] ?? '', array( 'navy', 'gold', 'light' ), true ) ? $a['style'] : 'navy';
	$btn   = ! empty( $a['button_url'] ) ? '<a class="usg-btn usg-btn--' . ( 'navy' === $style ? 'gold' : 'navy' ) . '" href="' . esc_url( $a['button_url'] ) . '">' . esc_html( $a['button_label'] ?: __( 'Learn more', 'usasmartgamers-core' ) ) . usg_icon( 'arrow' ) . '</a>' : '';
	return '<section class="usg-banner usg-banner--' . $style . '"><div><h2>' . esc_html( usg_tokens( (string) ( $a['title'] ?? '' ) ) ) . '</h2>' . ( ! empty( $a['text'] ) ? '<p>' . esc_html( $a['text'] ) . '</p>' : '' ) . '</div>' . $btn . '</section>';
}

function usg_block_key_takeaways( array $a ): string {
	$items = usg_lines( $a['items'] ?? '' );
	if ( ! $items ) {
		return '';
	}
	$h = '';
	foreach ( $items as $i ) {
		$h .= '<li>' . usg_icon( 'check' ) . esc_html( $i ) . '</li>';
	}
	return '<aside class="usg-takeaways"><h2>' . usg_icon( 'star' ) . esc_html( $a['title'] ?: __( 'Key takeaways', 'usasmartgamers-core' ) ) . '</h2><ul>' . $h . '</ul></aside>';
}

function usg_block_news_feed( array $a ): string {
	$pt   = 'usg_blog' === ( $a['post_type'] ?? '' ) ? 'usg_blog' : 'post';
	$args = array( 'post_type' => $pt, 'posts_per_page' => max( 1, min( 24, (int) ( $a['count'] ?? 4 ) ) ), 'no_found_rows' => true, 'ignore_sticky_posts' => true );
	if ( 'post' === $pt && ! empty( $a['category'] ) ) {
		$args['cat'] = (int) $a['category'];
	}
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		return usg_coming_soon( (string) ( $a['title'] ?? '' ) );
	}
	$layout = 'list' === ( $a['layout'] ?? '' ) ? 'list' : 'grid';
	$h      = '';
	foreach ( $q->posts as $p ) {
		$h .= usg_post_card( $p, $layout );
	}
	$more = 'post' === $pt ? ( ! empty( $a['category'] ) ? get_category_link( (int) $a['category'] ) : usg_page_url_by_path( 'news', '/news/' ) ) : get_post_type_archive_link( 'usg_blog' );
	$head = ! empty( $a['title'] ) ? '<div class="usg-block-head"><h2 class="usg-block-title">' . esc_html( usg_tokens( $a['title'] ) ) . '</h2><a class="usg-more" href="' . esc_url( (string) $more ) . '">' . esc_html__( 'View all', 'usasmartgamers-core' ) . usg_icon( 'arrow' ) . '</a></div>' : '';
	return '<section class="usg-feed">' . $head . '<div class="usg-cards usg-cards--' . $layout . '">' . $h . '</div></section>';
}

function usg_block_slot_grid( array $a ): string {
	$args = array( 'post_type' => 'usg_slot', 'posts_per_page' => max( 1, min( 48, (int) ( $a['count'] ?? 8 ) ) ), 'no_found_rows' => true, 'fields' => 'ids' );
	switch ( $a['orderby'] ?? 'rating' ) {
		case 'date':
			break;
		case 'title':
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
			break;
		default:
			$args['meta_key'] = '_usg_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['orderby']  = 'meta_value_num';
	}
	if ( ! empty( $a['provider'] ) ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'usg_provider', 'terms' => (int) $a['provider'] ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$ids = get_posts( $args );
	if ( ! $ids ) {
		return usg_coming_soon( (string) ( $a['title'] ?? '' ) );
	}
	$h = '';
	foreach ( $ids as $id ) {
		$h .= usg_slot_card( (int) $id );
	}
	$head = ! empty( $a['title'] ) ? '<div class="usg-block-head"><h2 class="usg-block-title">' . esc_html( usg_tokens( $a['title'] ) ) . '</h2><a class="usg-more" href="' . esc_url( ! empty( $a['provider'] ) ? (string) get_term_link( (int) $a['provider'], 'usg_provider' ) : usg_page_url_by_path( 'slots', '/slots/' ) ) . '">' . esc_html__( 'All slots', 'usasmartgamers-core' ) . usg_icon( 'arrow' ) . '</a></div>' : '';
	return '<section class="usg-slots">' . $head . '<div class="usg-slot-grid">' . $h . '</div></section>';
}

function usg_team_users( int $count = 50 ): array {
	return get_users(
		array(
			'meta_key'   => '_usg_show_in_team', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			'number'     => $count,
			'orderby'    => 'display_name',
		)
	);
}

function usg_block_team_grid( array $a ): string {
	$users = usg_team_users( max( 1, (int) ( $a['count'] ?? 8 ) ) );
	usort( $users, fn( $x, $y ) => (int) usg_user_meta( $x->ID, 'team_order', 99 ) <=> (int) usg_user_meta( $y->ID, 'team_order', 99 ) );
	if ( ! $users ) {
		return '';
	}
	$h = '';
	foreach ( $users as $u ) {
		$h .= '<a class="usg-person" href="' . esc_url( get_author_posts_url( $u->ID ) ) . '">' . usg_author_avatar( $u->ID, 96 ) . '<strong>' . esc_html( $u->display_name ) . '</strong><span>' . esc_html( usg_user_meta( $u->ID, 'job_title' ) ) . '</span></a>';
	}
	return '<section class="usg-team">' . ( ! empty( $a['title'] ) ? '<h2 class="usg-block-title">' . esc_html( $a['title'] ) . '</h2>' : '' ) . '<div class="usg-team__grid">' . $h . '</div></section>';
}

function usg_state_status_map( string $vertical ): array {
	$key = array( 'casino' => 'casino_status', 'sports' => 'sports_status', 'sweeps' => 'sweeps_status' )[ $vertical ] ?? 'casino_status';
	$out = array();
	foreach ( get_terms( array( 'taxonomy' => 'usg_state', 'hide_empty' => false ) ) as $t ) {
		$out[ strtoupper( $t->slug ) ] = array(
			'status' => (string) usg_term_meta( $t->term_id, $key ),
			'hub'    => (int) usg_term_meta( $t->term_id, 'hub_page' ),
			'term'   => $t,
		);
	}
	return $out;
}

function usg_block_state_map( array $a ): string {
	$vert   = in_array( $a['vertical'] ?? '', array( 'casino', 'sports', 'sweeps' ), true ) ? $a['vertical'] : 'casino';
	$labels = usg_state_statuses()[ $vert ];
	$data   = usg_state_status_map( $vert );
	$tiles  = '';
	foreach ( usg_state_tiles() as $code => $pos ) {
		$st     = $data[ $code ]['status'] ?? '';
		$hub    = $data[ $code ]['hub'] ?? 0;
		$label  = usg_state_name( $code ) . ( isset( $labels[ $st ] ) ? ': ' . $labels[ $st ] : '' );
		$style  = 'grid-row:' . ( $pos[0] + 1 ) . ';grid-column:' . ( $pos[1] + 1 );
		$inner  = '<span>' . esc_html( $code ) . '</span>';
		$tiles .= $hub && 'publish' === get_post_status( $hub )
			? '<a class="usg-map__tile is-' . esc_attr( $st ?: 'none' ) . '" style="' . esc_attr( $style ) . '" href="' . esc_url( get_permalink( $hub ) ) . '" title="' . esc_attr( $label ) . '">' . $inner . '</a>'
			: '<span class="usg-map__tile is-' . esc_attr( $st ?: 'none' ) . '" style="' . esc_attr( $style ) . '" title="' . esc_attr( $label ) . '">' . $inner . '</span>';
	}
	$legend = '';
	foreach ( $labels as $k => $l ) {
		$legend .= '<li><span class="usg-map__swatch is-' . esc_attr( $k ) . '"></span>' . esc_html( $l ) . '</li>';
	}
	return '<section class="usg-map">' . ( ! empty( $a['title'] ) ? '<h2 class="usg-block-title">' . esc_html( usg_tokens( $a['title'] ) ) . '</h2>' : '' ) . '<div class="usg-map__grid" role="img" aria-label="' . esc_attr__( 'Map of US states by legal status', 'usasmartgamers-core' ) . '">' . $tiles . '</div><ul class="usg-map__legend">' . $legend . '</ul></section>';
}

function usg_block_state_table( array $a ): string {
	$vert   = in_array( $a['vertical'] ?? '', array( 'casino', 'sports', 'sweeps' ), true ) ? $a['vertical'] : 'casino';
	$labels = usg_state_statuses()[ $vert ];
	$filter = (string) ( $a['filter'] ?? '' );
	$rows   = '';
	foreach ( usg_state_status_map( $vert ) as $code => $d ) {
		if ( $filter && $filter !== $d['status'] ) {
			continue;
		}
		$name  = $d['hub'] && 'publish' === get_post_status( $d['hub'] ) ? '<a href="' . esc_url( get_permalink( $d['hub'] ) ) . '">' . esc_html( $d['term']->name ) . '</a>' : esc_html( $d['term']->name );
		$rows .= '<tr><td>' . $name . '</td><td><span class="usg-status is-' . esc_attr( $d['status'] ?: 'none' ) . '">' . esc_html( $labels[ $d['status'] ] ?? '—' ) . '</span></td><td>' . esc_html( usg_term_meta( $d['term']->term_id, 'regulator', '—' ) ) . '</td><td>' . esc_html( usg_term_meta( $d['term']->term_id, 'legal_age', '21+' ) ) . '</td></tr>';
	}
	if ( ! $rows ) {
		return '';
	}
	return '<section class="usg-state-table">' . ( ! empty( $a['title'] ) ? '<h2 class="usg-block-title">' . esc_html( usg_tokens( $a['title'] ) ) . '</h2>' : '' ) . '<div class="usg-table-wrap"><table class="usg-table"><thead><tr><th>' . esc_html__( 'State', 'usasmartgamers-core' ) . '</th><th>' . esc_html__( 'Status', 'usasmartgamers-core' ) . '</th><th>' . esc_html__( 'Regulator', 'usasmartgamers-core' ) . '</th><th>' . esc_html__( 'Age', 'usasmartgamers-core' ) . '</th></tr></thead><tbody>' . $rows . '</tbody></table></div></section>';
}

function usg_block_link_grid( array $a ): string {
	$links = usg_pipe_lines( $a['links'] ?? '' );
	if ( ! $links ) {
		return '';
	}
	$h = '';
	foreach ( $links as $l ) {
		if ( empty( $l[1] ) ) {
			continue;
		}
		$h .= '<a class="usg-linkcard" href="' . esc_url( $l[1] ) . '"><strong>' . esc_html( $l[0] ) . '</strong>' . ( ! empty( $l[2] ) ? '<span>' . esc_html( $l[2] ) . '</span>' : '' ) . usg_icon( 'arrow' ) . '</a>';
	}
	$cols = in_array( (string) ( $a['columns'] ?? '3' ), array( '2', '3', '4' ), true ) ? (string) $a['columns'] : '3';
	return '<section class="usg-links">' . ( ! empty( $a['title'] ) ? '<h2 class="usg-block-title">' . esc_html( usg_tokens( $a['title'] ) ) . '</h2>' : '' ) . '<div class="usg-links__grid usg-cols-' . $cols . '">' . $h . '</div></section>';
}
