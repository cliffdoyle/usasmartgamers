<?php
/**
 * Shared helpers.
 */

defined( 'ABSPATH' ) || exit;

function usg_meta( $post_id, string $key, $default = '' ) {
	$v = get_post_meta( (int) $post_id, '_usg_' . $key, true );
	return ( '' === $v || null === $v || false === $v || array() === $v ) ? $default : $v;
}

function usg_term_meta( $term_id, string $key, $default = '' ) {
	$v = get_term_meta( (int) $term_id, '_usg_' . $key, true );
	return ( '' === $v || null === $v || false === $v ) ? $default : $v;
}

function usg_user_meta( $user_id, string $key, $default = '' ) {
	$v = get_user_meta( (int) $user_id, '_usg_' . $key, true );
	return ( '' === $v || null === $v || false === $v ) ? $default : $v;
}

function usg_lines( $text ): array {
	if ( is_array( $text ) ) {
		return array_values( array_filter( array_map( 'trim', $text ), 'strlen' ) );
	}
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
}

/**
 * Parse "Label|URL|Extra" lines.
 */
function usg_pipe_lines( $text ): array {
	$out = array();
	foreach ( usg_lines( $text ) as $line ) {
		$out[] = array_map( 'trim', explode( '|', $line ) );
	}
	return $out;
}

function usg_option( string $key, $default = '' ) {
	$o = get_option( 'usg_settings', array() );
	return ( is_array( $o ) && isset( $o[ $key ] ) && '' !== $o[ $key ] ) ? $o[ $key ] : $default;
}

function usg_verticals(): array {
	return array(
		'casino'      => __( 'Real-money casino', 'usasmartgamers-core' ),
		'sweepstakes' => __( 'Sweepstakes casino', 'usasmartgamers-core' ),
		'sportsbook'  => __( 'Sportsbook', 'usasmartgamers-core' ),
		'poker'       => __( 'Online poker', 'usasmartgamers-core' ),
		'lottery'     => __( 'Online lottery', 'usasmartgamers-core' ),
		'prediction'  => __( 'Prediction market', 'usasmartgamers-core' ),
	);
}

function usg_tokens( string $text ): string {
	if ( false === strpos( $text, '[' ) ) {
		return $text;
	}
	return str_replace(
		array( '[month]', '[year]', '[Month]' ),
		array( wp_date( 'F' ), wp_date( 'Y' ), wp_date( 'F' ) ),
		$text
	);
}

function usg_stars( $rating, bool $with_number = true ): string {
	$r   = max( 0, min( 5, (float) $rating ) );
	$pct = round( $r / 5 * 100, 1 );
	/* translators: %s: rating */
	$label = sprintf( __( 'Rated %s out of 5', 'usasmartgamers-core' ), number_format_i18n( $r, 1 ) );
	$html  = '<span class="usg-stars" role="img" aria-label="' . esc_attr( $label ) . '"><span class="usg-stars__bg" aria-hidden="true">★★★★★</span><span class="usg-stars__fg" aria-hidden="true" style="width:' . esc_attr( $pct ) . '%">★★★★★</span></span>';
	if ( $with_number ) {
		$html .= '<span class="usg-stars__num">' . esc_html( number_format_i18n( $r, 1 ) ) . '/5</span>';
	}
	return $html;
}

function usg_icon( string $name ): string {
	static $paths = array(
		'arrow'    => '<path d="M5 12h14M13 5l7 7-7 7"/>',
		'external' => '<path d="M7 17 17 7M8 7h9v9"/>',
		'copy'     => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h8"/>',
		'check'    => '<path d="M20 6 9 17l-5-5"/>',
		'x'        => '<path d="M18 6 6 18M6 6l12 12"/>',
		'bell'     => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
		'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		'chevron'  => '<path d="m6 9 6 6 6-6"/>',
		'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
		'coins'    => '<ellipse cx="9" cy="7" rx="6" ry="3"/><path d="M3 7v5c0 1.7 2.7 3 6 3s6-1.3 6-3V7M9 15v4c0 1.7 2.7 3 6 3s6-1.3 6-3v-5c0-1.7-2.7-3-6-3"/>',
		'gift'     => '<rect x="3" y="8" width="18" height="13" rx="1"/><path d="M12 8v13M3 12h18M12 8S10 3 7.5 3a2.5 2.5 0 0 0 0 5M12 8s2-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
		'play'     => '<path d="M7 4v16l13-8z"/>',
		'star'     => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>',
		'pin'      => '<path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'up'       => '<path d="M12 19V5M5 12l7-7 7 7"/>',
		'twitter'  => '<path d="M4 4l16 16M20 4 4 20"/>',
		'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>',
		'facebook' => '<path d="M15 3h-2a4 4 0 0 0-4 4v3H7v4h2v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h2z"/>',
		'instagram'=> '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5v.01"/>',
		'youtube'  => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
		'tiktok'   => '<path d="M14 3v11a4 4 0 1 1-4-4M14 3c0 3 2 5 5 5"/>',
		'reddit'   => '<circle cx="12" cy="14" r="7"/><path d="M9 13v.01M15 13v.01M9.5 16.5s1 1 2.5 1 2.5-1 2.5-1M16 4l-3 3"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="usg-icon usg-icon--' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

function usg_operator_logo( int $op_id, string $class = '' ): string {
	$name = get_the_title( $op_id );
	$logo = (int) usg_meta( $op_id, 'logo' );
	$bg   = sanitize_hex_color( (string) usg_meta( $op_id, 'brand_color', '#13294b' ) ) ?: '#13294b';
	if ( $logo ) {
		$inner = wp_get_attachment_image( $logo, 'medium', false, array( 'alt' => $name, 'loading' => 'lazy' ) );
	} else {
		$inner = '<span class="usg-logo__text">' . esc_html( $name ) . '</span>';
	}
	return '<span class="usg-logo ' . esc_attr( $class ) . '" style="--usg-logo-bg:' . esc_attr( $bg ) . '">' . $inner . '</span>';
}

function usg_reading_time( $post = null ): int {
	$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post ) ) );
	return max( 1, (int) ceil( $words / 230 ) );
}

function usg_page_url_by_path( string $path, string $fallback = '/' ): string {
	$p = get_page_by_path( $path );
	return $p ? get_permalink( $p ) : home_url( $fallback );
}

function usg_post_options( string $post_type, bool $empty = true ): array {
	$opts = $empty ? array( '' => __( '— Select —', 'usasmartgamers-core' ) ) : array();
	$ids  = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => array( 'publish', 'draft', 'private', 'future', 'pending' ),
			'posts_per_page' => 500,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	foreach ( $ids as $id ) {
		$title = get_the_title( $id );
		if ( 'usg_offer' === $post_type ) {
			$op     = (int) usg_meta( $id, 'operator' );
			$title .= $op ? ' (' . get_the_title( $op ) . ')' : '';
		}
		$opts[ $id ] = $title ?: '#' . $id;
	}
	return $opts;
}

function usg_author_options(): array {
	$opts = array( '' => __( '— Select —', 'usasmartgamers-core' ) );
	foreach ( get_users( array( 'role__in' => array( 'administrator', 'editor', 'author', 'contributor' ), 'orderby' => 'display_name' ) ) as $u ) {
		$opts[ $u->ID ] = $u->display_name;
	}
	return $opts;
}

function usg_client_ip_hash(): string {
	$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? ( $_SERVER['REMOTE_ADDR'] ?? '' );
	return substr( hash_hmac( 'sha256', (string) $ip, wp_salt( 'nonce' ) ), 0, 32 );
}

add_shortcode( 'month', fn() => wp_date( 'F' ) );
add_shortcode( 'year', fn() => wp_date( 'Y' ) );
