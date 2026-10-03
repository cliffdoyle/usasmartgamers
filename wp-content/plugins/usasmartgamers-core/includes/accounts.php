<?php
/**
 * Front-end accounts: join, log in, profile, referrals. Members are "subscriber" users kept out of wp-admin.
 */

defined( 'ABSPATH' ) || exit;

function usg_account_url( array $args = array() ): string {
	return add_query_arg( $args, usg_page_url_by_path( 'account', '/account/' ) );
}

function usg_account_errors(): array {
	return array(
		'nonce'    => __( 'Your session expired. Please try again.', 'usasmartgamers-core' ),
		'rate'     => __( 'Too many sign-ups from your network. Please try again later.', 'usasmartgamers-core' ),
		'username' => __( 'Usernames must be 3–30 letters, numbers, dots, dashes or underscores.', 'usasmartgamers-core' ),
		'taken'    => __( 'That username or email is already registered. Try logging in instead.', 'usasmartgamers-core' ),
		'email'    => __( 'Please enter a valid email address.', 'usasmartgamers-core' ),
		'password' => __( 'Passwords must be at least 10 characters.', 'usasmartgamers-core' ),
		'age'      => __( 'You must confirm you are 21 or older and accept the terms.', 'usasmartgamers-core' ),
		'failed'   => __( 'We could not create your account. Please try again.', 'usasmartgamers-core' ),
	);
}

add_action( 'admin_post_nopriv_usg_register', 'usg_handle_register' );
add_action( 'admin_post_usg_register', 'usg_handle_register' );

function usg_handle_register(): void {
	$fail = function ( string $code ) {
		wp_safe_redirect( usg_account_url( array( 'tab' => 'join', 'usg_error' => $code ) ) );
		exit;
	};
	if ( is_user_logged_in() ) {
		wp_safe_redirect( usg_account_url() );
		exit;
	}
	if ( ! isset( $_POST['usg_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['usg_nonce'] ), 'usg_register' ) ) {
		$fail( 'nonce' );
	}
	if ( ! empty( $_POST['website'] ) ) { // honeypot
		$fail( 'failed' );
	}
	$rate_key = 'usg_reg_' . usg_client_ip_hash();
	$count    = (int) get_transient( $rate_key );
	if ( $count >= 5 ) {
		$fail( 'rate' );
	}
	$username = sanitize_user( wp_unslash( $_POST['username'] ?? '' ), true );
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$pass     = (string) wp_unslash( $_POST['password'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$state    = strtoupper( sanitize_key( wp_unslash( $_POST['state'] ?? '' ) ) );
	if ( ! preg_match( '/^[A-Za-z0-9._-]{3,30}$/', $username ) ) {
		$fail( 'username' );
	}
	if ( ! is_email( $email ) ) {
		$fail( 'email' );
	}
	if ( strlen( $pass ) < 10 ) {
		$fail( 'password' );
	}
	if ( empty( $_POST['age'] ) ) {
		$fail( 'age' );
	}
	if ( username_exists( $username ) || email_exists( $email ) ) {
		$fail( 'taken' );
	}
	set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );
	$uid = wp_insert_user(
		array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => $pass,
			'display_name' => $username,
			'role'         => 'subscriber',
		)
	);
	if ( is_wp_error( $uid ) ) {
		$fail( 'failed' );
	}
	if ( isset( usg_states()[ $state ] ) ) {
		update_user_meta( $uid, '_usg_state', $state );
	}
	$ref = sanitize_user( wp_unslash( $_POST['ref'] ?? '' ), true );
	if ( $ref ) {
		$referrer = get_user_by( 'login', $ref );
		if ( $referrer && $referrer->ID !== $uid ) {
			update_user_meta( $uid, '_usg_referred_by', $referrer->ID );
		}
	}
	update_user_meta( $uid, '_usg_terms_accepted', current_time( 'mysql', true ) );
	usg_award( $uid, 'register' );
	wp_set_current_user( $uid );
	wp_set_auth_cookie( $uid, true, is_ssl() );
	wp_safe_redirect( usg_account_url( array( 'welcome' => 1 ) ) );
	exit;
}

add_action(
	'admin_post_usg_profile',
	function () {
		$uid = get_current_user_id();
		if ( ! $uid || ! isset( $_POST['usg_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['usg_nonce'] ), 'usg_profile' ) ) {
			wp_safe_redirect( usg_account_url() );
			exit;
		}
		$first   = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$display = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
		$state   = strtoupper( sanitize_key( wp_unslash( $_POST['state'] ?? '' ) ) );
		$fav     = sanitize_key( wp_unslash( $_POST['favorite'] ?? '' ) );
		wp_update_user( array( 'ID' => $uid, 'first_name' => $first, 'display_name' => $display ?: wp_get_current_user()->user_login ) );
		update_user_meta( $uid, '_usg_state', isset( usg_states()[ $state ] ) ? $state : '' );
		update_user_meta( $uid, '_usg_favorite', $fav );
		if ( $first && isset( usg_states()[ $state ] ) && $fav ) {
			if ( usg_award( $uid, 'profile' ) ) {
				$referrer = (int) get_user_meta( $uid, '_usg_referred_by', true );
				if ( $referrer && ! get_user_meta( $uid, '_usg_referral_paid', true ) ) {
					usg_award( $referrer, 'referral', 'u' . $uid, __( 'A friend joined with your link', 'usasmartgamers-core' ) );
					update_user_meta( $uid, '_usg_referral_paid', 1 );
				}
			}
		}
		wp_safe_redirect( usg_account_url( array( 'saved' => 1 ) ) );
		exit;
	}
);

/* Keep members out of wp-admin and the admin bar. */
add_filter( 'show_admin_bar', fn( $show ) => current_user_can( 'edit_posts' ) ? $show : false );
add_action(
	'admin_init',
	function () {
		global $pagenow;
		if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) && ! wp_doing_ajax() && 'admin-post.php' !== $pagenow ) {
			wp_safe_redirect( usg_account_url() );
			exit;
		}
	}
);
add_filter(
	'login_redirect',
	function ( $to, $requested, $user ) {
		return ( $user instanceof WP_User && ! user_can( $user, 'edit_posts' ) ) ? usg_account_url() : $to;
	},
	10,
	3
);

function usg_block_account( array $a ): string {
	$uid = get_current_user_id();
	$msg = '';
	// phpcs:disable WordPress.Security.NonceVerification
	if ( isset( $_GET['usg_error'] ) ) {
		$errs = usg_account_errors();
		$code = sanitize_key( $_GET['usg_error'] );
		$msg  = '<p class="usg-alert usg-alert--error">' . esc_html( $errs[ $code ] ?? $errs['failed'] ) . '</p>';
	} elseif ( isset( $_GET['welcome'] ) ) {
		$msg = '<p class="usg-alert usg-alert--ok">' . esc_html__( 'Welcome aboard! Your account is ready.', 'usasmartgamers-core' ) . ( usg_rewards_enabled() ? ' ' . esc_html__( 'Your 500 welcome coins are in your wallet — complete your profile for 500 more.', 'usasmartgamers-core' ) : '' ) . '</p>';
	} elseif ( isset( $_GET['saved'] ) ) {
		$msg = '<p class="usg-alert usg-alert--ok">' . esc_html__( 'Profile saved.', 'usasmartgamers-core' ) . '</p>';
	}
	$tab = isset( $_GET['tab'] ) && 'login' === $_GET['tab'] ? 'login' : 'join';
	$ref = isset( $_GET['ref'] ) ? sanitize_user( wp_unslash( $_GET['ref'] ), true ) : '';
	// phpcs:enable

	$state_opts = function ( string $cur ): string {
		$h = '<option value="">' . esc_html__( 'Select your state', 'usasmartgamers-core' ) . '</option>';
		foreach ( usg_states() as $k => $l ) {
			$h .= '<option value="' . esc_attr( $k ) . '"' . selected( $k, $cur, false ) . '>' . esc_html( $l ) . '</option>';
		}
		return $h;
	};

	if ( ! $uid ) {
		$join = '<form class="usg-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="usg_register">' . wp_nonce_field( 'usg_register', 'usg_nonce', false, false ) . '<input type="hidden" name="ref" value="' . esc_attr( $ref ) . '"><p class="usg-hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>'
			. '<label>' . esc_html__( 'Username', 'usasmartgamers-core' ) . '<input type="text" name="username" required minlength="3" maxlength="30" autocomplete="username"></label>'
			. '<label>' . esc_html__( 'Email', 'usasmartgamers-core' ) . '<input type="email" name="email" required autocomplete="email"></label>'
			. '<label>' . esc_html__( 'Password (10+ characters)', 'usasmartgamers-core' ) . '<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>'
			. '<label>' . esc_html__( 'State', 'usasmartgamers-core' ) . '<select name="state">' . $state_opts( '' ) . '</select></label>'
			. '<label class="usg-check"><input type="checkbox" name="age" value="1" required> ' . sprintf( esc_html__( 'I am 21 or older and accept the %1$sTerms%2$s and %3$sPrivacy Policy%4$s.', 'usasmartgamers-core' ), '<a href="' . esc_url( usg_page_url_by_path( 'terms', '/terms/' ) ) . '">', '</a>', '<a href="' . esc_url( get_privacy_policy_url() ?: home_url( '/privacy-policy/' ) ) . '">', '</a>' ) . '</label>'
			. '<button type="submit" class="usg-btn usg-btn--green usg-btn--block">' . esc_html( usg_rewards_enabled() ? __( 'Join free & get 500 coins', 'usasmartgamers-core' ) : __( 'Create account', 'usasmartgamers-core' ) ) . '</button><p class="usg-small">' . esc_html__( 'US residents 21+. Coins are promotional and hold no cash value.', 'usasmartgamers-core' ) . '</p></form>';
		$login = wp_login_form( array( 'echo' => false, 'redirect' => usg_account_url(), 'remember' => true ) ) . '<p><a href="' . esc_url( wp_lostpassword_url( usg_account_url( array( 'tab' => 'login' ) ) ) ) . '">' . esc_html__( 'Forgot your password?', 'usasmartgamers-core' ) . '</a></p>';
		return '<section class="usg-account">' . $msg . '<div class="usg-tabs" data-usg-tabs><div class="usg-tabs__nav" role="tablist"><button type="button" role="tab" class="usg-tabs__tab" aria-selected="' . ( 'join' === $tab ? 'true' : 'false' ) . '" aria-controls="usg-acc-join" id="usg-acc-join-t">' . esc_html__( 'Join free', 'usasmartgamers-core' ) . '</button><button type="button" role="tab" class="usg-tabs__tab" aria-selected="' . ( 'login' === $tab ? 'true' : 'false' ) . '" aria-controls="usg-acc-login" id="usg-acc-login-t">' . esc_html__( 'Log in', 'usasmartgamers-core' ) . '</button></div><div class="usg-tabs__panel" role="tabpanel" id="usg-acc-join" aria-labelledby="usg-acc-join-t"' . ( 'join' === $tab ? '' : ' hidden' ) . '>' . $join . '</div><div class="usg-tabs__panel usg-form" role="tabpanel" id="usg-acc-login" aria-labelledby="usg-acc-login-t"' . ( 'login' === $tab ? '' : ' hidden' ) . '>' . $login . '</div></div></section>';
	}

	$user    = wp_get_current_user();
	$state   = (string) get_user_meta( $uid, '_usg_state', true );
	$fav     = (string) get_user_meta( $uid, '_usg_favorite', true );
	$favs    = array( '' => __( 'Select…', 'usasmartgamers-core' ), 'slots' => 'Slots', 'blackjack' => 'Blackjack', 'roulette' => 'Roulette', 'poker' => 'Poker', 'sports' => 'Sports betting', 'sweeps' => 'Sweepstakes casinos' );
	$fav_h   = '';
	foreach ( $favs as $k => $l ) {
		$fav_h .= '<option value="' . esc_attr( $k ) . '"' . selected( $k, $fav, false ) . '>' . esc_html( $l ) . '</option>';
	}
	$profile = '<form class="usg-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="usg_profile">' . wp_nonce_field( 'usg_profile', 'usg_nonce', false, false )
		. '<label>' . esc_html__( 'First name', 'usasmartgamers-core' ) . '<input type="text" name="first_name" value="' . esc_attr( $user->first_name ) . '"></label>'
		. '<label>' . esc_html__( 'Display name (shown on reviews)', 'usasmartgamers-core' ) . '<input type="text" name="display_name" value="' . esc_attr( $user->display_name ) . '"></label>'
		. '<label>' . esc_html__( 'State', 'usasmartgamers-core' ) . '<select name="state">' . $state_opts( $state ) . '</select></label>'
		. '<label>' . esc_html__( 'Favourite game', 'usasmartgamers-core' ) . '<select name="favorite">' . $fav_h . '</select></label>'
		. '<button type="submit" class="usg-btn usg-btn--navy">' . esc_html__( 'Save profile', 'usasmartgamers-core' ) . '</button></form>';

	$wallet = '';
	if ( usg_rewards_enabled() ) {
		$rows = '';
		foreach ( usg_ledger( $uid, 20 ) as $r ) {
			$rows .= '<tr><td>' . esc_html( wp_date( get_option( 'date_format' ), strtotime( $r->created . ' UTC' ) ) ) . '</td><td>' . esc_html( $r->note ?: $r->action ) . '</td><td class="' . ( $r->amount >= 0 ? 'is-plus' : 'is-minus' ) . '">' . ( $r->amount >= 0 ? '+' : '' ) . (int) $r->amount . '</td></tr>';
		}
		$reflink = add_query_arg( 'ref', $user->user_login, usg_account_url() );
		$wallet  = '<div class="usg-wallet"><p class="usg-wallet__bal">' . usg_icon( 'coins' ) . '<strong data-usg-balance>' . esc_html( number_format_i18n( usg_coin_balance( $uid ) ) ) . '</strong> ' . esc_html__( 'coins', 'usasmartgamers-core' ) . '</p><p><a class="usg-btn usg-btn--gold usg-btn--sm" href="' . esc_url( usg_page_url_by_path( 'rewards', '/rewards/' ) ) . '">' . esc_html__( 'Redeem rewards', 'usasmartgamers-core' ) . '</a></p><label class="usg-reflink">' . esc_html__( 'Your referral link', 'usasmartgamers-core' ) . '<span><input type="text" readonly value="' . esc_attr( $reflink ) . '"><button type="button" class="usg-code__btn" data-usg-copy="' . esc_attr( $reflink ) . '">' . usg_icon( 'copy' ) . '<span class="usg-code__done">' . esc_html__( 'Copied!', 'usasmartgamers-core' ) . '</span></button></span></label><h3>' . esc_html__( 'Recent activity', 'usasmartgamers-core' ) . '</h3>' . ( $rows ? '<div class="usg-table-wrap"><table class="usg-table usg-table--ledger"><tbody>' . $rows . '</tbody></table></div>' : '<p>' . esc_html__( 'No activity yet.', 'usasmartgamers-core' ) . '</p>' ) . '</div>';
	}
	return '<section class="usg-account usg-account--member">' . $msg . '<div class="usg-account__head">' . get_avatar( $uid, 64 ) . '<div><h2>' . esc_html( sprintf( __( 'Hi, %s', 'usasmartgamers-core' ), $user->first_name ?: $user->display_name ) ) . '</h2><a href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">' . esc_html__( 'Log out', 'usasmartgamers-core' ) . '</a></div></div><div class="usg-account__grid"><div><h3>' . esc_html__( 'Your profile', 'usasmartgamers-core' ) . '</h3>' . $profile . '</div>' . $wallet . '</div></section>';
}
