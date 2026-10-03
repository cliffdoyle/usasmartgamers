<?php
/**
 * Admin: click report, helpful list columns, dashboard widget.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'edit.php?post_type=usg_operator',
			__( 'Click report', 'usasmartgamers-core' ),
			__( 'Click report', 'usasmartgamers-core' ),
			'edit_posts',
			'usg-clicks',
			'usg_render_click_report'
		);
	}
);

function usg_render_click_report(): void {
	global $wpdb;
	$days  = isset( $_GET['days'] ) ? max( 1, min( 365, absint( $_GET['days'] ) ) ) : 30; // phpcs:ignore WordPress.Security.NonceVerification
	$since = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
	$t     = $wpdb->prefix . 'usg_clicks';
	$by_op = $wpdb->get_results( $wpdb->prepare( "SELECT operator_id, COUNT(*) n FROM $t WHERE created >= %s GROUP BY operator_id ORDER BY n DESC LIMIT 50", $since ) ); // phpcs:ignore
	$by_pg = $wpdb->get_results( $wpdb->prepare( "SELECT page, COUNT(*) n FROM $t WHERE created >= %s GROUP BY page ORDER BY n DESC LIMIT 25", $since ) ); // phpcs:ignore
	$by_tg = $wpdb->get_results( $wpdb->prepare( "SELECT tag, COUNT(*) n FROM $t WHERE created >= %s GROUP BY tag ORDER BY n DESC LIMIT 25", $since ) ); // phpcs:ignore
	$by_st = $wpdb->get_results( $wpdb->prepare( "SELECT state, COUNT(*) n FROM $t WHERE created >= %s GROUP BY state ORDER BY n DESC LIMIT 25", $since ) ); // phpcs:ignore
	$table = function ( string $title, array $rows, callable $label ) {
		echo '<div style="flex:1;min-width:280px"><h2>' . esc_html( $title ) . '</h2><table class="widefat striped usg-report-table"><tbody>';
		foreach ( $rows as $r ) {
			echo '<tr><td>' . esc_html( $label( $r ) ) . '</td><td style="text-align:right"><strong>' . (int) $r->n . '</strong></td></tr>';
		}
		if ( ! $rows ) {
			echo '<tr><td>' . esc_html__( 'No clicks yet.', 'usasmartgamers-core' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	};
	echo '<div class="wrap"><h1>' . esc_html__( 'Affiliate click report', 'usasmartgamers-core' ) . '</h1><p>';
	foreach ( array( 7, 30, 90, 365 ) as $d ) {
		echo '<a class="button' . ( $d === $days ? ' button-primary' : '' ) . '" href="' . esc_url( add_query_arg( 'days', $d ) ) . '">' . (int) $d . ' ' . esc_html__( 'days', 'usasmartgamers-core' ) . '</a> ';
	}
	echo '</p><div style="display:flex;gap:24px;flex-wrap:wrap">';
	$table( __( 'By operator', 'usasmartgamers-core' ), $by_op, fn( $r ) => get_the_title( (int) $r->operator_id ) ?: '#' . $r->operator_id );
	$table( __( 'By page', 'usasmartgamers-core' ), $by_pg, fn( $r ) => $r->page ?: '(direct)' );
	$table( __( 'By placement', 'usasmartgamers-core' ), $by_tg, fn( $r ) => $r->tag ?: '(none)' );
	$table( __( 'By visitor state', 'usasmartgamers-core' ), $by_st, fn( $r ) => $r->state ? usg_state_name( $r->state ) : __( 'Unknown', 'usasmartgamers-core' ) );
	echo '</div></div>';
}

/* Operator list columns. */
add_filter(
	'manage_usg_operator_posts_columns',
	fn( $c ) => array_merge( $c, array( 'usg_vertical' => 'Vertical', 'usg_rating' => 'Score', 'usg_offer' => 'Current offer', 'usg_avail' => 'Availability' ) )
);
add_action(
	'manage_usg_operator_posts_custom_column',
	function ( $col, $id ) {
		switch ( $col ) {
			case 'usg_vertical':
				echo esc_html( usg_verticals()[ usg_meta( $id, 'vertical', 'casino' ) ] ?? '' );
				break;
			case 'usg_rating':
				echo esc_html( (string) usg_meta( $id, 'rating', '—' ) );
				break;
			case 'usg_offer':
				$o = usg_best_offer( (int) $id, null );
				echo $o ? '<a href="' . esc_url( get_edit_post_link( $o ) ) . '">' . esc_html( (string) usg_meta( $o, 'headline', get_the_title( $o ) ) ) . '</a>' : '<span style="color:#b32d2e">' . esc_html__( 'No valid offer', 'usasmartgamers-core' ) . '</span>';
				break;
			case 'usg_avail':
				echo esc_html( usg_availability_text( (int) $id ) );
				break;
		}
	},
	10,
	2
);
add_filter( 'manage_usg_offer_posts_columns', fn( $c ) => array_merge( $c, array( 'usg_op' => 'Operator', 'usg_code' => 'Code', 'usg_end' => 'Ends' ) ) );
add_action(
	'manage_usg_offer_posts_custom_column',
	function ( $col, $id ) {
		if ( 'usg_op' === $col ) {
			echo esc_html( get_the_title( (int) usg_meta( $id, 'operator' ) ) );
		} elseif ( 'usg_code' === $col ) {
			echo esc_html( (string) usg_meta( $id, 'promo_code', '—' ) );
		} elseif ( 'usg_end' === $col ) {
			$end = (string) usg_meta( $id, 'end' );
			echo $end && $end < current_time( 'Y-m-d' ) ? '<span style="color:#b32d2e">' . esc_html( $end ) . ' (expired)</span>' : esc_html( $end ?: '—' );
		}
	},
	10,
	2
);

/* Dashboard: offers expiring within 7 days. */
add_action(
	'wp_dashboard_setup',
	function () {
		wp_add_dashboard_widget(
			'usg_expiring',
			__( 'Offers expiring soon', 'usasmartgamers-core' ),
			function () {
				$soon = get_posts(
					array(
						'post_type'      => 'usg_offer',
						'posts_per_page' => 20,
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
							array( 'key' => '_usg_end', 'value' => array( current_time( 'Y-m-d' ), wp_date( 'Y-m-d', strtotime( '+7 days' ) ) ), 'compare' => 'BETWEEN', 'type' => 'DATE' ),
						),
					)
				);
				if ( ! $soon ) {
					echo '<p>' . esc_html__( 'No offers expire in the next 7 days.', 'usasmartgamers-core' ) . '</p>';
					return;
				}
				echo '<ul>';
				foreach ( $soon as $o ) {
					echo '<li><a href="' . esc_url( get_edit_post_link( $o ) ) . '">' . esc_html( get_the_title( $o ) ) . '</a> — ' . esc_html( (string) usg_meta( $o->ID, 'end' ) ) . '</li>';
				}
				echo '</ul>';
			}
		);
	}
);
