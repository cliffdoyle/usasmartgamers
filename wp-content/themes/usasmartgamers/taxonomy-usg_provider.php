<?php
/**
 * Slot provider page: /slots/{provider}/
 */

get_header();
$usg_term = get_queried_object();
$usg_logo = (int) usg_term_meta( $usg_term->term_id, 'logo' );
?>
<header class="page-header page-header--band">
	<div class="container">
		<?php usg_breadcrumbs(); ?>
		<div class="provider-head">
			<?php if ( $usg_logo ) : ?><span class="provider-head__logo"><?php echo wp_get_attachment_image( $usg_logo, 'medium' ); ?></span><?php endif; ?>
			<div>
				<h1 class="page-title"><?php echo esc_html( sprintf( __( '%s slots', 'usasmartgamers' ), single_term_title( '', false ) ) ); ?></h1>
				<p class="page-lead"><?php echo esc_html( sprintf( _n( '%s game reviewed', '%s games reviewed', $usg_term->count, 'usasmartgamers' ), number_format_i18n( $usg_term->count ) ) ); ?>
				<?php
				foreach ( array( 'founded' => __( 'Founded', 'usasmartgamers' ), 'hq' => __( 'HQ', 'usasmartgamers' ) ) as $usg_k => $usg_l ) {
					$usg_v = usg_term_meta( $usg_term->term_id, $usg_k );
					if ( $usg_v ) {
						echo ' · ' . esc_html( $usg_l . ': ' . $usg_v );
					}
				}
				?>
				</p>
			</div>
		</div>
	</div>
</header>
<div class="container">
	<?php if ( have_posts() ) : ?>
		<div class="usg-slot-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				echo usg_slot_card( get_the_ID() ); // phpcs:ignore
			endwhile;
			?>
		</div>
		<?php usg_pagination(); ?>
	<?php endif; ?>
	<?php if ( term_description() ) : ?>
		<div class="entry-content container--narrow term-description"><?php echo wp_kses_post( wpautop( term_description() ) ); ?></div>
	<?php endif; ?>
</div>
<?php
get_footer();
