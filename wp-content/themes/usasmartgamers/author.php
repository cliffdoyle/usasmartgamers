<?php
/**
 * Author profile.
 */

get_header();
$usg_uid = (int) get_query_var( 'author' );
$usg_u   = get_userdata( $usg_uid );
$usg_exp = array_filter( array_map( 'trim', explode( ',', (string) usg_user_meta( $usg_uid, 'expertise' ) ) ) );
if ( function_exists( 'usg_schema_add' ) ) {
	usg_schema_add( array( '@type' => 'ProfilePage', 'mainEntity' => array( '@type' => 'Person', 'name' => $usg_u->display_name, 'jobTitle' => usg_user_meta( $usg_uid, 'job_title' ), 'description' => wp_strip_all_tags( $usg_u->description ) ) ) );
}
?>
<header class="page-header page-header--band">
	<div class="container">
		<?php usg_breadcrumbs(); ?>
		<div class="profile-head">
			<?php echo get_avatar( $usg_uid, 128, '', '', array( 'class' => 'profile-head__avatar' ) ); ?>
			<div>
				<h1 class="page-title"><?php echo esc_html( $usg_u->display_name ); ?></h1>
				<p class="page-lead"><?php echo esc_html( usg_user_meta( $usg_uid, 'job_title' ) ); ?><?php echo usg_user_meta( $usg_uid, 'credentials' ) ? ' · ' . esc_html( usg_user_meta( $usg_uid, 'credentials' ) ) : ''; ?></p>
				<?php echo usg_author_socials( $usg_uid ); // phpcs:ignore ?>
			</div>
		</div>
	</div>
</header>
<div class="container container--narrow profile">
	<?php if ( $usg_u->description ) : ?>
		<h2><?php echo esc_html( sprintf( __( 'Getting to know %s', 'usasmartgamers' ), $usg_u->first_name ?: $usg_u->display_name ) ); ?></h2>
		<?php echo wp_kses_post( wpautop( $usg_u->description ) ); ?>
	<?php endif; ?>
	<?php if ( $usg_exp ) : ?>
		<ul class="chips"><?php foreach ( $usg_exp as $usg_e ) : ?><li><?php echo esc_html( $usg_e ); ?></li><?php endforeach; ?></ul>
	<?php endif; ?>
	<?php $usg_facts = usg_lines( usg_user_meta( $usg_uid, 'fun_facts' ) ); ?>
	<?php if ( $usg_facts ) : ?>
		<h2><?php esc_html_e( 'Fun facts', 'usasmartgamers' ); ?></h2>
		<ul class="usg-bullets"><?php foreach ( $usg_facts as $usg_f ) : ?><li><?php echo usg_icon( 'star' ) . esc_html( $usg_f ); // phpcs:ignore ?></li><?php endforeach; ?></ul>
	<?php endif; ?>
	<?php $usg_qa = usg_pipe_lines( usg_user_meta( $usg_uid, 'qa' ) ); ?>
	<?php if ( $usg_qa ) : ?>
		<section class="usg-faq"><h2 class="usg-block-title"><?php echo esc_html( sprintf( __( 'Q&A with %s', 'usasmartgamers' ), $usg_u->first_name ?: $usg_u->display_name ) ); ?></h2>
		<?php foreach ( $usg_qa as $usg_q ) : ?>
			<details class="usg-faq__item"><summary><?php echo esc_html( $usg_q[0] ); ?><?php echo usg_icon( 'chevron' ); // phpcs:ignore ?></summary><div class="usg-faq__a"><p><?php echo esc_html( $usg_q[1] ?? '' ); ?></p></div></details>
		<?php endforeach; ?>
		</section>
	<?php endif; ?>
</div>
<section class="container">
	<h2 class="usg-block-title"><?php echo esc_html( sprintf( __( 'Latest from %s', 'usasmartgamers' ), $usg_u->display_name ) ); ?></h2>
	<?php
	$usg_q = new WP_Query( array( 'author' => $usg_uid, 'post_type' => array( 'post', 'usg_blog', 'page', 'usg_slot' ), 'posts_per_page' => 12, 'paged' => max( 1, (int) get_query_var( 'paged' ) ) ) );
	if ( $usg_q->have_posts() ) :
		echo '<div class="usg-cards usg-cards--grid">';
		foreach ( $usg_q->posts as $usg_p ) {
			echo usg_post_card( $usg_p ); // phpcs:ignore
		}
		echo '</div>';
	else :
		echo '<p class="usg-notice">' . esc_html__( 'No articles yet.', 'usasmartgamers' ) . '</p>';
	endif;
	?>
</section>
<?php
get_footer();
