<?php
/**
 * Post types, taxonomies and URL structure.
 */

defined( 'ABSPATH' ) || exit;

define( 'USG_REWRITE_VERSION', '4' );

add_action(
	'init',
	function () {
		$data_cpt = function ( string $singular, string $plural, array $extra = array() ): array {
			return array_merge(
				array(
					'labels'          => array(
						'name'          => $plural,
						'singular_name' => $singular,
						'add_new_item'  => sprintf( __( 'Add %s', 'usasmartgamers-core' ), $singular ),
						'edit_item'     => sprintf( __( 'Edit %s', 'usasmartgamers-core' ), $singular ),
						'all_items'     => $plural,
					),
					'public'          => false,
					'show_ui'         => true,
					'show_in_rest'    => false,
					'supports'        => array( 'title' ),
					'capability_type' => 'post',
					'map_meta_cap'    => true,
				),
				$extra
			);
		};

		register_post_type( 'usg_operator', $data_cpt( 'Operator', 'Operators', array( 'menu_icon' => 'dashicons-awards', 'menu_position' => 21, 'supports' => array( 'title', 'excerpt' ), 'labels' => array( 'name' => 'Operators', 'singular_name' => 'Operator', 'menu_name' => 'Operators & Offers', 'all_items' => 'Operators', 'add_new_item' => 'Add operator', 'edit_item' => 'Edit operator' ) ) ) );
		register_post_type( 'usg_offer', $data_cpt( 'Offer', 'Offers', array( 'show_in_menu' => 'edit.php?post_type=usg_operator', 'supports' => array( 'title', 'page-attributes' ) ) ) );
		register_post_type( 'usg_toplist', $data_cpt( 'Toplist', 'Toplists', array( 'show_in_menu' => 'edit.php?post_type=usg_operator' ) ) );

		register_post_type( 'usg_reward', $data_cpt( 'Reward', 'Rewards', array( 'menu_icon' => 'dashicons-tickets-alt', 'menu_position' => 23, 'supports' => array( 'title', 'excerpt', 'thumbnail' ), 'labels' => array( 'name' => 'Rewards', 'singular_name' => 'Reward', 'menu_name' => 'Smart Rewards', 'all_items' => 'Reward catalogue', 'add_new_item' => 'Add reward', 'edit_item' => 'Edit reward' ) ) ) );
		register_post_type( 'usg_redemption', $data_cpt( 'Redemption', 'Redemptions', array( 'show_in_menu' => 'edit.php?post_type=usg_reward', 'capabilities' => array( 'create_posts' => 'do_not_allow' ) ) ) );
		register_post_type( 'usg_message', $data_cpt( 'Message', 'Contact messages', array( 'menu_icon' => 'dashicons-email', 'menu_position' => 26, 'supports' => array( 'title', 'editor' ), 'capabilities' => array( 'create_posts' => 'do_not_allow' ) ) ) );

		register_post_type(
			'usg_slot',
			array(
				'labels'        => array( 'name' => 'Slots', 'singular_name' => 'Slot', 'add_new_item' => 'Add slot', 'edit_item' => 'Edit slot', 'menu_name' => 'Slots' ),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-games',
				'menu_position' => 22,
				'has_archive'   => false,
				'rewrite'       => array( 'slug' => 'slots/%usg_provider%', 'with_front' => false ),
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions' ),
			)
		);
		register_post_type(
			'usg_blog',
			array(
				'labels'        => array( 'name' => 'Insights', 'singular_name' => 'Insight', 'add_new_item' => 'Add insight article', 'edit_item' => 'Edit insight', 'menu_name' => 'Insights (blog)' ),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-lightbulb',
				'menu_position' => 6,
				'has_archive'   => 'insights',
				'rewrite'       => array( 'slug' => 'insights', 'with_front' => false ),
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions' ),
			)
		);

		register_taxonomy(
			'usg_provider',
			array( 'usg_slot' ),
			array(
				'labels'            => array( 'name' => 'Providers', 'singular_name' => 'Provider' ),
				'public'            => true,
				'show_in_rest'      => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'slots', 'with_front' => false ),
			)
		);
		register_taxonomy(
			'usg_payment',
			array( 'usg_operator' ),
			array(
				'labels'            => array( 'name' => 'Payment methods', 'singular_name' => 'Payment method' ),
				'public'            => false,
				'show_ui'           => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
			)
		);
		register_taxonomy(
			'usg_state',
			array( 'usg_operator' ),
			array(
				'labels'       => array( 'name' => 'US States', 'singular_name' => 'US State', 'menu_name' => 'US States (legal status)' ),
				'public'       => false,
				'show_ui'      => true,
				'meta_box_cb'  => false,
				'hierarchical' => false,
			)
		);
	}
);

add_filter(
	'post_type_link',
	function ( $link, $post ) {
		if ( 'usg_slot' === $post->post_type && false !== strpos( $link, '%usg_provider%' ) ) {
			$terms = get_the_terms( $post, 'usg_provider' );
			$slug  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : 'other';
			$link  = str_replace( '%usg_provider%', $slug, $link );
		}
		return $link;
	},
	10,
	2
);

/**
 * /slots/{x}/ can be a provider OR a child page (e.g. /slots/free/); /slots/{x}/{y}/ a slot OR a page.
 */
add_filter(
	'request',
	function ( $qv ) {
		if ( is_admin() ) {
			return $qv;
		}
		if ( ! empty( $qv['usg_provider'] ) && empty( $qv['usg_slot'] ) && empty( $qv['paged'] ) ) {
			$path = 'slots/' . $qv['usg_provider'];
			if ( get_page_by_path( $path ) ) {
				return array( 'pagename' => $path );
			}
		}
		if ( ! empty( $qv['usg_slot'] ) && ! empty( $qv['usg_provider'] ) ) {
			$path = 'slots/' . $qv['usg_provider'] . '/' . $qv['usg_slot'];
			if ( ! get_page_by_path( $qv['usg_slot'], OBJECT, 'usg_slot' ) && get_page_by_path( $path ) ) {
				return array( 'pagename' => $path );
			}
		}
		return $qv;
	}
);

add_action(
	'init',
	function () {
		if ( get_option( 'usg_rewrite_version' ) !== USG_REWRITE_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'usg_rewrite_version', USG_REWRITE_VERSION );
		}
	},
	99
);
