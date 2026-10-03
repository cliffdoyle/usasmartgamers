<?php
/**
 * Contact form: submissions are stored as private "Contact messages" (no mail server needed) and emailed if mail works.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_nopriv_usg_contact', 'usg_handle_contact' );
add_action( 'admin_post_usg_contact', 'usg_handle_contact' );

function usg_handle_contact(): void {
	$back = wp_get_referer() ?: home_url( '/contact-us/' );
	$go   = function ( string $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'usg_contact', $status, $back ) . '#contact' );
		exit;
	};
	if ( ! isset( $_POST['usg_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['usg_nonce'] ), 'usg_contact' ) || ! empty( $_POST['website'] ) ) {
		$go( 'error' );
	}
	$key   = 'usg_ct_' . usg_client_ip_hash();
	$count = (int) get_transient( $key );
	if ( $count >= 5 ) {
		$go( 'rate' );
	}
	$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$topic   = sanitize_text_field( wp_unslash( $_POST['topic'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
	if ( ! $name || ! is_email( $email ) || mb_strlen( $message ) < 10 ) {
		$go( 'invalid' );
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	$id = wp_insert_post(
		array(
			'post_type'    => 'usg_message',
			'post_status'  => 'private',
			'post_title'   => mb_substr( "[$topic] $name <$email>", 0, 200 ),
			'post_content' => $message,
		)
	);
	if ( $id ) {
		wp_mail( get_option( 'admin_email' ), '[USA Smart Gamers] ' . $topic . ' — ' . $name, $message . "\n\n— " . $name . ' <' . $email . '>', array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );
	}
	$go( 'ok' );
}

function usg_block_contact_form( array $a ): string {
	// phpcs:ignore WordPress.Security.NonceVerification
	$st   = isset( $_GET['usg_contact'] ) ? sanitize_key( $_GET['usg_contact'] ) : '';
	$msgs = array(
		'ok'      => array( 'ok', __( 'Thanks — your message has been received. We reply within 2 business days.', 'usasmartgamers-core' ) ),
		'invalid' => array( 'error', __( 'Please fill in your name, a valid email and a message.', 'usasmartgamers-core' ) ),
		'rate'    => array( 'error', __( 'Too many messages — please try again later.', 'usasmartgamers-core' ) ),
		'error'   => array( 'error', __( 'Something went wrong. Please try again.', 'usasmartgamers-core' ) ),
	);
	$alert  = isset( $msgs[ $st ] ) ? '<p class="usg-alert usg-alert--' . $msgs[ $st ][0] . '">' . esc_html( $msgs[ $st ][1] ) . '</p>' : '';
	$topics = '';
	foreach ( array( 'General question', 'Report an issue with an operator', 'Correction request', 'Media inquiry', 'Advertising & partnerships' ) as $t ) {
		$topics .= '<option>' . esc_html( $t ) . '</option>';
	}
	return '<section class="usg-contact" id="contact">' . $alert . '<form class="usg-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="usg_contact">' . wp_nonce_field( 'usg_contact', 'usg_nonce', false, false ) . '<p class="usg-hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></p><label>' . esc_html__( 'Name', 'usasmartgamers-core' ) . '<input type="text" name="name" required></label><label>' . esc_html__( 'Email', 'usasmartgamers-core' ) . '<input type="email" name="email" required></label><label>' . esc_html__( 'Topic', 'usasmartgamers-core' ) . '<select name="topic">' . $topics . '</select></label><label>' . esc_html__( 'Message', 'usasmartgamers-core' ) . '<textarea name="message" rows="6" required minlength="10"></textarea></label><button type="submit" class="usg-btn usg-btn--navy">' . esc_html__( 'Send message', 'usasmartgamers-core' ) . '</button></form></section>';
}
