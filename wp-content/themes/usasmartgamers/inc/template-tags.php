<?php
/**
 * Template tags: logo, breadcrumbs, byline, author box, sticky CTA, pagination.
 */

defined( 'ABSPATH' ) || exit;

function usg_core_active(): bool {
	return function_exists( 'usg_meta' );
}

function usg_theme_icon( string $name ): string {
	return usg_core_active() ? usg_icon( $name ) : '';
}

function usg_theme_logo(): void {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	echo '<a class="usg-wordmark" href="' . esc_url( home_url( '/' ) ) . '" rel="home" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '"><svg class="usg-wordmark__star" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="m12 2 2.9 6.6 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 21l1.6-7L2 9.2l7.1-.6z"/></svg><span><b>USA</b>Smart<i>Gamers</i></span></a>';
}

function usg_crumb_title( int $id ): string {
	$short = usg_core_active() ? (string) usg_meta( $id, 'short_title' ) : '';
	if ( $short ) {
		return $short;
	}
	$t = wp_strip_all_tags( get_the_title( $id ) );
	return trim( preg_split( '/:\s| \(| – | — /u', $t )[0] );
}

function usg_breadcrumbs(): void {
	if ( is_front_page() ) {
		return;
	}
	$c    = array( array( __( 'Home', 'usasmartgamers' ), home_url( '/' ) ) );
	$page = function ( string $path, string $label ) use ( &$c ) {
		$p   = get_page_by_path( $path );
		$c[] = array( $p ? usg_crumb_title( $p->ID ) : $label, $p ? get_permalink( $p ) : home_url( '/' . $path . '/' ) );
	};
	if ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $a ) {
			$c[] = array( usg_crumb_title( $a ), get_permalink( $a ) );
		}
		$c[] = array( usg_crumb_title( get_the_ID() ), '' );
	} elseif ( is_singular( 'post' ) ) {
		$page( 'news', 'News' );
		$cats = get_the_category();
		if ( $cats ) {
			$c[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
		}
		$c[] = array( get_the_title(), '' );
	} elseif ( is_singular( 'usg_blog' ) ) {
		$c[] = array( __( 'Insights', 'usasmartgamers' ), get_post_type_archive_link( 'usg_blog' ) );
		$c[] = array( get_the_title(), '' );
	} elseif ( is_singular( 'usg_slot' ) ) {
		$page( 'slots', 'Slots' );
		$t = get_the_terms( get_the_ID(), 'usg_provider' );
		if ( $t && ! is_wp_error( $t ) ) {
			$c[] = array( $t[0]->name, get_term_link( $t[0] ) );
		}
		$c[] = array( get_the_title(), '' );
	} elseif ( is_tax( 'usg_provider' ) ) {
		$page( 'slots', 'Slots' );
		$c[] = array( single_term_title( '', false ), '' );
	} elseif ( is_category() ) {
		$page( 'news', 'News' );
		$c[] = array( single_cat_title( '', false ), '' );
	} elseif ( is_author() ) {
		$page( 'our-team', 'Our team' );
		$c[] = array( get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ), '' );
	} elseif ( is_post_type_archive( 'usg_blog' ) ) {
		$c[] = array( __( 'Insights', 'usasmartgamers' ), '' );
	} elseif ( is_search() ) {
		$c[] = array( __( 'Search', 'usasmartgamers' ), '' );
	} elseif ( is_404() ) {
		$c[] = array( __( 'Page not found', 'usasmartgamers' ), '' );
	} elseif ( is_archive() ) {
		$c[] = array( wp_strip_all_tags( get_the_archive_title() ), '' );
	}
	echo '<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'usasmartgamers' ) . '"><ol>';
	$last = count( $c ) - 1;
	foreach ( $c as $i => $crumb ) {
		echo '<li>' . ( $crumb[1] && $i !== $last ? '<a href="' . esc_url( $crumb[1] ) . '">' . esc_html( $crumb[0] ) . '</a>' : '<span aria-current="page">' . esc_html( $crumb[0] ) . '</span>' ) . '</li>';
	}
	echo '</ol></nav>';
}

function usg_byline( $post = null ): void {
	$post = get_post( $post );
	if ( ! $post || ( usg_core_active() && usg_meta( $post->ID, 'hide_byline' ) ) ) {
		return;
	}
	$author = (int) $post->post_author;
	$fc     = usg_core_active() ? (int) usg_meta( $post->ID, 'fact_checker' ) : 0;
	echo '<div class="byline">' . get_avatar( $author, 44, '', '', array( 'class' => 'byline__avatar' ) ) . '<div class="byline__text"><p>' . esc_html__( 'Written by', 'usasmartgamers' ) . ' <a href="' . esc_url( get_author_posts_url( $author ) ) . '">' . esc_html( get_the_author_meta( 'display_name', $author ) ) . '</a>';
	if ( $fc ) {
		echo ' <span class="byline__sep">·</span> <span class="byline__fc">' . usg_theme_icon( 'check' ) . esc_html__( 'Fact-checked by', 'usasmartgamers' ) . ' <a href="' . esc_url( get_author_posts_url( $fc ) ) . '">' . esc_html( get_the_author_meta( 'display_name', $fc ) ) . '</a></span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</p><p class="byline__meta">' . esc_html__( 'Last updated:', 'usasmartgamers' ) . ' <time datetime="' . esc_attr( get_the_modified_date( 'c', $post ) ) . '">' . esc_html( get_the_modified_date( 'M j, Y', $post ) ) . '</time>';
	if ( in_array( $post->post_type, array( 'post', 'usg_blog' ), true ) && usg_core_active() ) {
		/* translators: %d: minutes */
		echo ' <span class="byline__sep">·</span> ' . esc_html( sprintf( __( '%d min read', 'usasmartgamers' ), usg_reading_time( $post ) ) );
	}
	echo '</p></div></div>';
}

function usg_author_box( $post = null ): void {
	$post = get_post( $post );
	if ( ! $post || ( usg_core_active() && usg_meta( $post->ID, 'hide_author_box' ) ) ) {
		return;
	}
	$uid   = (int) $post->post_author;
	$title = usg_core_active() ? usg_user_meta( $uid, 'job_title' ) : '';
	$exp   = usg_core_active() ? array_filter( array_map( 'trim', explode( ',', (string) usg_user_meta( $uid, 'expertise' ) ) ) ) : array();
	echo '<aside class="author-box">' . get_avatar( $uid, 88, '', '', array( 'class' => 'author-box__avatar' ) ) . '<div><p class="author-box__eyebrow">' . esc_html__( 'About the author', 'usasmartgamers' ) . '</p><h2 class="author-box__name"><a href="' . esc_url( get_author_posts_url( $uid ) ) . '">' . esc_html( get_the_author_meta( 'display_name', $uid ) ) . '</a></h2>' . ( $title ? '<p class="author-box__title">' . esc_html( $title ) . '</p>' : '' ) . '<p>' . esc_html( get_the_author_meta( 'description', $uid ) ) . '</p>';
	if ( $exp ) {
		echo '<ul class="chips">';
		foreach ( $exp as $e ) {
			echo '<li>' . esc_html( $e ) . '</li>';
		}
		echo '</ul>';
	}
	echo ( usg_core_active() ? usg_author_socials( $uid ) : '' ) . '</div></aside>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

function usg_sticky_footer_cta(): void {
	if ( ! usg_core_active() || ! is_singular() ) {
		return;
	}
	$op = (int) usg_meta( get_the_ID(), 'sticky_operator' );
	if ( ! $op && is_page() && ! is_page( array( 'account', 'contact-us', 'privacy-policy', 'terms', 'responsible-gambling' ) ) ) {
		$op = (int) usg_option( 'default_sticky_operator' );
	}
	echo usg_sticky_cta( $op ); // phpcs:ignore WordPress.Security.EscapeOutput
}

function usg_pagination(): void {
	the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '‹', 'next_text' => '›' ) );
}

function usg_toc_enabled(): bool {
	return is_singular() && ! is_front_page() && ! ( usg_core_active() && usg_meta( get_the_ID(), 'hide_toc' ) );
}
