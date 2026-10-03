<?php
/**
 * Smart Rewards: coin ledger, tasks, daily streaks, reward catalogue, redemptions, notifications.
 */

defined( 'ABSPATH' ) || exit;

function usg_rewards_enabled(): bool {
	return '1' === (string) usg_option( 'rewards_enabled', '1' );
}

function usg_tasks(): array {
	return apply_filters(
		'usg_tasks',
		array(
			'register'    => array( 'label' => __( 'Create your free account', 'usasmartgamers-core' ), 'desc' => __( 'One-time welcome bonus.', 'usasmartgamers-core' ), 'coins' => 500, 'once' => true ),
			'profile'     => array( 'label' => __( 'Complete your profile', 'usasmartgamers-core' ), 'desc' => __( 'Add your name and state.', 'usasmartgamers-core' ), 'coins' => 500, 'once' => true ),
			'daily_login' => array( 'label' => __( 'Daily visit', 'usasmartgamers-core' ), 'desc' => __( 'Just show up — once a day.', 'usasmartgamers-core' ), 'coins' => 25, 'per_day' => 1 ),
			'streak_7'    => array( 'label' => __( '7-day streak', 'usasmartgamers-core' ), 'desc' => __( 'Visit seven days in a row.', 'usasmartgamers-core' ), 'coins' => 200 ),
			'review'      => array( 'label' => __( 'Write a review', 'usasmartgamers-core' ), 'desc' => __( 'A written review of a casino or slot (approved). Up to 3 a day.', 'usasmartgamers-core' ), 'coins' => 150, 'per_day' => 3 ),
			'rating'      => array( 'label' => __( 'Rate a casino or slot', 'usasmartgamers-core' ), 'desc' => __( 'A quick star rating. Up to 3 a day.', 'usasmartgamers-core' ), 'coins' => 50, 'per_day' => 3 ),
			'slot_demo'   => array( 'label' => __( 'Play a slot demo', 'usasmartgamers-core' ), 'desc' => __( 'Try a free demo. Up to 3 a day.', 'usasmartgamers-core' ), 'coins' => 25, 'per_day' => 3 ),
			'referral'    => array( 'label' => __( 'Refer a friend', 'usasmartgamers-core' ), 'desc' => __( 'When a friend joins with your link and completes their profile.', 'usasmartgamers-core' ), 'coins' => 500 ),
		)
	);
}

function usg_coin_balance( int $user_id ): int {
	return (int) get_user_meta( $user_id, '_usg_coins', true );
}

function usg_ledger_add( int $user_id, int $amount, string $action, string $ref = '', string $note = '' ): void {
	global $wpdb;
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prefix . 'usg_coins',
		array(
			'user_id' => $user_id,
			'amount'  => $amount,
			'action'  => $action,
			'ref'     => substr( $ref, 0, 64 ),
			'note'    => substr( $note, 0, 255 ),
			'created' => current_time( 'mysql', true ),
		)
	);
	update_user_meta( $user_id, '_usg_coins', usg_coin_balance( $user_id ) + $amount );
}

/**
 * Award coins for a task if limits allow. Returns coins awarded (0 if not eligible).
 */
function usg_award( int $user_id, string $action, string $ref = '', string $note = '' ): int {
	$tasks = usg_tasks();
	if ( ! $user_id || ! usg_rewards_enabled() || ! isset( $tasks[ $action ] ) ) {
		return 0;
	}
	global $wpdb;
	$t     = $tasks[ $action ];
	$table = $wpdb->prefix . 'usg_coins';
	if ( ! empty( $t['once'] ) && $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE user_id=%d AND action=%s LIMIT 1", $user_id, $action ) ) ) { // phpcs:ignore
		return 0;
	}
	if ( '' !== $ref && $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE user_id=%d AND action=%s AND ref=%s AND created >= %s LIMIT 1", $user_id, $action, $ref, gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ) ) ) ) { // phpcs:ignore
		return 0;
	}
	if ( ! empty( $t['per_day'] ) ) {
		$since = get_gmt_from_date( wp_date( 'Y-m-d 00:00:00' ) );
		$n     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE user_id=%d AND action=%s AND created >= %s", $user_id, $action, $since ) ); // phpcs:ignore
		if ( $n >= (int) $t['per_day'] ) {
			return 0;
		}
	}
	usg_ledger_add( $user_id, (int) $t['coins'], $action, $ref, $note ?: $t['label'] );
	return (int) $t['coins'];
}

function usg_spend( int $user_id, int $amount, string $ref, string $note ): bool {
	global $wpdb;
	$lock = 'usg_coins_' . $user_id;
	$wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ); // phpcs:ignore
	wp_cache_delete( $user_id, 'user_meta' );
	$ok = usg_coin_balance( $user_id ) >= $amount;
	if ( $ok ) {
		usg_ledger_add( $user_id, -$amount, 'redeem', $ref, $note );
	}
	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); // phpcs:ignore
	return $ok;
}

function usg_ledger( int $user_id, int $limit = 20 ): array {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( "SELECT amount, action, note, created FROM {$wpdb->prefix}usg_coins WHERE user_id=%d ORDER BY id DESC LIMIT %d", $user_id, $limit ) ); // phpcs:ignore
}

/* Daily visit + streak (front-end page views of logged-in users). */
add_action(
	'template_redirect',
	function () {
		$uid = get_current_user_id();
		if ( ! $uid || ! usg_rewards_enabled() ) {
			return;
		}
		$today = wp_date( 'Y-m-d' );
		$last  = (string) get_user_meta( $uid, '_usg_last_visit', true );
		if ( $last === $today ) {
			return;
		}
		$streak = ( wp_date( 'Y-m-d', strtotime( '-1 day' ) ) === $last ) ? (int) get_user_meta( $uid, '_usg_streak', true ) + 1 : 1;
		update_user_meta( $uid, '_usg_last_visit', $today );
		update_user_meta( $uid, '_usg_streak', $streak );
		usg_award( $uid, 'daily_login', $today );
		if ( 0 === $streak % 7 ) {
			usg_award( $uid, 'streak_7', 'streak-' . $today );
		}
	}
);

/* Redemptions: refund once when an admin marks a redemption as rejected. */
add_action(
	'usg_meta_saved',
	function ( $post_id, $post ) {
		if ( 'usg_redemption' !== $post->post_type || 'rejected' !== usg_meta( $post_id, 'status' ) || usg_meta( $post_id, 'refunded' ) ) {
			return;
		}
		$uid  = (int) usg_meta( $post_id, 'user' );
		$cost = (int) usg_meta( $post_id, 'cost' );
		if ( $uid && $cost ) {
			usg_ledger_add( $uid, $cost, 'refund', 'redemption-' . $post_id, __( 'Redemption refunded', 'usasmartgamers-core' ) );
			update_post_meta( $post_id, '_usg_refunded', '1' );
		}
	},
	10,
	2
);

add_filter(
	'manage_usg_redemption_posts_columns',
	function ( $cols ) {
		return array_merge( $cols, array( 'usg_status' => __( 'Status', 'usasmartgamers-core' ), 'usg_cost' => __( 'Coins', 'usasmartgamers-core' ) ) );
	}
);
add_action(
	'manage_usg_redemption_posts_custom_column',
	function ( $col, $id ) {
		if ( 'usg_status' === $col ) {
			echo esc_html( ucfirst( (string) usg_meta( $id, 'status', 'pending' ) ) );
		} elseif ( 'usg_cost' === $col ) {
			echo esc_html( (string) usg_meta( $id, 'cost' ) );
		}
	},
	10,
	2
);

function usg_redeem( int $user_id, int $reward_id ) {
	if ( 'usg_reward' !== get_post_type( $reward_id ) || 'publish' !== get_post_status( $reward_id ) ) {
		return new WP_Error( 'usg_reward', __( 'That reward is not available.', 'usasmartgamers-core' ) );
	}
	$cost  = (int) usg_meta( $reward_id, 'cost' );
	$stock = usg_meta( $reward_id, 'stock' );
	if ( $cost <= 0 ) {
		return new WP_Error( 'usg_reward', __( 'That reward is not available.', 'usasmartgamers-core' ) );
	}
	if ( '' !== $stock && (int) $stock <= 0 ) {
		return new WP_Error( 'usg_stock', __( 'Sorry, this reward is out of stock.', 'usasmartgamers-core' ) );
	}
	if ( ! usg_spend( $user_id, $cost, 'reward-' . $reward_id, get_the_title( $reward_id ) ) ) {
		return new WP_Error( 'usg_balance', __( 'You do not have enough coins yet.', 'usasmartgamers-core' ) );
	}
	if ( '' !== $stock ) {
		update_post_meta( $reward_id, '_usg_stock', max( 0, (int) $stock - 1 ) );
	}
	$user = get_userdata( $user_id );
	$rid  = wp_insert_post(
		array(
			'post_type'   => 'usg_redemption',
			'post_status' => 'publish',
			'post_title'  => $user->user_login . ' → ' . get_the_title( $reward_id ),
		)
	);
	foreach ( array( 'user' => $user_id, 'reward' => $reward_id, 'cost' => $cost, 'status' => 'pending' ) as $k => $v ) {
		update_post_meta( $rid, '_usg_' . $k, $v );
	}
	return usg_coin_balance( $user_id );
}

/* ---------- Blocks / UI ---------- */

function usg_block_rewards( array $a ): string {
	if ( ! usg_rewards_enabled() ) {
		return '';
	}
	$section = $a['section'] ?? 'both';
	$uid     = get_current_user_id();
	$h       = '';
	if ( 'catalog' !== $section ) {
		$cards = '';
		foreach ( usg_tasks() as $t ) {
			$cards .= '<li class="usg-task"><span class="usg-task__coins">' . usg_icon( 'coins' ) . '+' . (int) $t['coins'] . '</span><strong>' . esc_html( $t['label'] ) . '</strong><p>' . esc_html( $t['desc'] ) . '</p></li>';
		}
		$h .= '<div class="usg-rewards__tasks"><h2 class="usg-block-title">' . esc_html__( 'Ways to earn coins', 'usasmartgamers-core' ) . '</h2><ul class="usg-tasks">' . $cards . '</ul></div>';
	}
	if ( 'tasks' !== $section ) {
		$items = get_posts( array( 'post_type' => 'usg_reward', 'posts_per_page' => 24, 'orderby' => 'meta_value_num', 'meta_key' => '_usg_cost', 'order' => 'ASC' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$cards = '';
		foreach ( $items as $r ) {
			$cost  = (int) usg_meta( $r->ID, 'cost' );
			$stock = usg_meta( $r->ID, 'stock' );
			$out   = '' !== $stock && (int) $stock <= 0;
			$btn   = $uid
				? '<button type="button" class="usg-btn usg-btn--navy usg-btn--sm" data-usg-redeem="' . (int) $r->ID . '"' . ( $out ? ' disabled' : '' ) . '>' . esc_html( $out ? __( 'Out of stock', 'usasmartgamers-core' ) : __( 'Redeem', 'usasmartgamers-core' ) ) . '</button>'
				: '<a class="usg-btn usg-btn--navy usg-btn--sm" href="' . esc_url( usg_page_url_by_path( 'account', '/account/' ) ) . '">' . esc_html__( 'Join to redeem', 'usasmartgamers-core' ) . '</a>';
			$cards .= '<article class="usg-reward">' . ( has_post_thumbnail( $r ) ? get_the_post_thumbnail( $r, 'medium', array( 'loading' => 'lazy' ) ) : '<span class="usg-reward__ph">' . usg_icon( 'gift' ) . '</span>' ) . '<div class="usg-reward__body"><span class="usg-pill usg-pill--gold">' . esc_html( ucwords( str_replace( '_', ' ', (string) usg_meta( $r->ID, 'reward_type', 'reward' ) ) ) ) . '</span><h3>' . esc_html( get_the_title( $r ) ) . '</h3><p>' . esc_html( get_the_excerpt( $r ) ) . '</p><p class="usg-reward__cost">' . usg_icon( 'coins' ) . esc_html( number_format_i18n( $cost ) ) . ' ' . esc_html__( 'coins', 'usasmartgamers-core' ) . '</p>' . $btn . '<p class="usg-reward__msg" aria-live="polite"></p></div></article>';
		}
		if ( $cards ) {
			$h .= '<div class="usg-rewards__catalog"><h2 class="usg-block-title">' . esc_html__( 'Turn coins into rewards', 'usasmartgamers-core' ) . '</h2>' . ( $uid ? '<p class="usg-balance">' . esc_html__( 'Your balance:', 'usasmartgamers-core' ) . ' <strong data-usg-balance>' . esc_html( number_format_i18n( usg_coin_balance( $uid ) ) ) . '</strong> ' . esc_html__( 'coins', 'usasmartgamers-core' ) . '</p>' : '' ) . '<div class="usg-rewards__grid">' . $cards . '</div><p class="usg-small">' . esc_html__( 'Coins are promotional, have no cash value and cannot be transferred. US residents 21+ only. Rewards are subject to availability and fraud checks.', 'usasmartgamers-core' ) . '</p></div>';
		} else {
			$h .= usg_coming_soon( __( 'Turn coins into rewards', 'usasmartgamers-core' ) );
		}
	}
	return '<section class="usg-rewards">' . $h . '</section>';
}

/**
 * Header account area: bell with recent coin activity for members, join CTA for visitors.
 */
function usg_header_account(): string {
	$account = usg_page_url_by_path( 'account', '/account/' );
	$uid     = get_current_user_id();
	if ( ! $uid ) {
		$join = usg_rewards_enabled() ? __( 'Join · 500 coins', 'usasmartgamers-core' ) : __( 'Log in', 'usasmartgamers-core' );
		return '<a class="usg-header-join" href="' . esc_url( $account ) . '">' . usg_icon( 'user' ) . '<span>' . esc_html( $join ) . '</span></a>';
	}
	$rows  = usg_ledger( $uid, 8 );
	$seen  = (string) get_user_meta( $uid, '_usg_seen', true );
	$new   = 0;
	$items = '';
	foreach ( $rows as $r ) {
		if ( $r->created > $seen ) {
			$new++;
		}
		$items .= '<li><span class="' . ( $r->amount >= 0 ? 'is-plus' : 'is-minus' ) . '">' . ( $r->amount >= 0 ? '+' : '' ) . (int) $r->amount . '</span> ' . esc_html( $r->note ?: $r->action ) . '<time>' . esc_html( human_time_diff( strtotime( $r->created . ' UTC' ) ) ) . '</time></li>';
	}
	return '<div class="usg-bell"><button type="button" class="usg-bell__btn" aria-expanded="false" aria-label="' . esc_attr__( 'Notifications', 'usasmartgamers-core' ) . '" data-usg-bell>' . usg_icon( 'bell' ) . ( $new ? '<span class="usg-bell__badge">' . ( $new > 9 ? '9+' : $new ) . '</span>' : '' ) . '</button><div class="usg-bell__panel" hidden><p class="usg-bell__bal">' . usg_icon( 'coins' ) . '<strong data-usg-balance>' . esc_html( number_format_i18n( usg_coin_balance( $uid ) ) ) . '</strong> ' . esc_html__( 'coins', 'usasmartgamers-core' ) . '</p><ul>' . ( $items ?: '<li>' . esc_html__( 'No activity yet.', 'usasmartgamers-core' ) . '</li>' ) . '</ul><a href="' . esc_url( $account ) . '">' . esc_html__( 'My account', 'usasmartgamers-core' ) . '</a></div></div>';
}
