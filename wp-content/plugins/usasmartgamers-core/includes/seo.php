<?php
/**
 * SEO & front-end plumbing: title tokens, robots, Google News sitemap, caching rules, front assets, consent + GTM.
 */

defined( 'ABSPATH' ) || exit;

/* Title tokens like "Best Online Casinos [month] [year]". */
add_filter( 'the_title', fn( $t ) => is_string( $t ) ? usg_tokens( $t ) : $t );
add_filter( 'document_title_parts', fn( $p ) => array_map( fn( $x ) => is_string( $x ) ? usg_tokens( $x ) : $x, $p ) );
foreach ( array( 'wpseo_title', 'wpseo_metadesc', 'wpseo_opengraph_title', 'wpseo_opengraph_desc', 'wpseo_twitter_title' ) as $usg_f ) {
	add_filter( $usg_f, fn( $t ) => is_string( $t ) ? usg_tokens( $t ) : $t );
}

add_filter(
	'robots_txt',
	function ( $out, $public ) {
		if ( $public ) {
			$out .= "Disallow: /claim/\nDisallow: /account/\nDisallow: /?s=\nDisallow: /search/\n";
			$out .= 'Sitemap: ' . home_url( '/news-sitemap.xml' ) . "\n";
		}
		return $out;
	},
	10,
	2
);

/* Private / dynamic pages: never cache, never index. */
add_action(
	'template_redirect',
	function () {
		if ( is_page( array( 'account' ) ) || is_search() ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			nocache_headers();
		}
	},
	0
);
add_filter(
	'wp_robots',
	function ( $r ) {
		if ( is_page( array( 'account' ) ) || is_search() ) {
			$r['noindex'] = true;
		}
		return $r;
	}
);

/* Google News sitemap: /news-sitemap.xml (posts from the last 48 hours). */
add_filter( 'redirect_canonical', fn( $redirect ) => get_query_var( 'usg_news_sitemap' ) ? false : $redirect );
add_action(
	'init',
	function () {
		add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?usg_news_sitemap=1', 'top' );
	}
);
add_filter(
	'query_vars',
	function ( $v ) {
		$v[] = 'usg_news_sitemap';
		return $v;
	}
);
add_action(
	'template_redirect',
	function () {
		if ( ! get_query_var( 'usg_news_sitemap' ) ) {
			return;
		}
		$posts = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 1000, 'date_query' => array( array( 'after' => '48 hours ago' ) ) ) );
		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';
		foreach ( $posts as $p ) {
			echo '<url><loc>' . esc_url( get_permalink( $p ) ) . '</loc><news:news><news:publication><news:name>' . esc_html( get_bloginfo( 'name' ) ) . '</news:name><news:language>en</news:language></news:publication><news:publication_date>' . esc_html( get_the_date( 'c', $p ) ) . '</news:publication_date><news:title>' . esc_html( get_the_title( $p ) ) . '</news:title></news:news></url>';
		}
		echo '</urlset>';
		exit;
	}
);

/* Front-end assets. */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'usg-front', USG_CORE_URL . 'assets/front.js', array(), (string) filemtime( USG_CORE_DIR . 'assets/front.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		$data = array(
			'rest'    => esc_url_raw( rest_url( 'usg/v1/' ) ),
			'account' => usg_page_url_by_path( 'account', '/account/' ),
			'gtm'     => preg_match( '/^GTM-[A-Z0-9]+$/', (string) usg_option( 'gtm_id' ) ) ? usg_option( 'gtm_id' ) : '',
			'states'  => usg_states(),
		);
		if ( is_user_logged_in() ) {
			$data['nonce'] = wp_create_nonce( 'wp_rest' );
		}
		wp_add_inline_script( 'usg-front', 'window.usgData=' . wp_json_encode( $data ) . ';', 'before' );
	}
);

/* Cookie consent banner (analytics load only after "Accept"). */
add_action(
	'wp_footer',
	function () {
		if ( ! usg_option( 'gtm_id' ) ) {
			return;
		}
		echo '<div class="usg-consent" data-usg-consent hidden><p>' . esc_html__( 'We use cookies for analytics to improve the site. Essential cookies are always on.', 'usasmartgamers-core' ) . ' <a href="' . esc_url( get_privacy_policy_url() ?: home_url( '/privacy-policy/' ) ) . '">' . esc_html__( 'Privacy policy', 'usasmartgamers-core' ) . '</a></p><div><button type="button" class="usg-btn usg-btn--sm usg-btn--navy" data-consent="no">' . esc_html__( 'Decline', 'usasmartgamers-core' ) . '</button> <button type="button" class="usg-btn usg-btn--sm usg-btn--green" data-consent="yes">' . esc_html__( 'Accept', 'usasmartgamers-core' ) . '</button></div></div>';
	}
);
