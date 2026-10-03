<?php
/**
 * Slot review + demo.
 */

get_header();
while ( have_posts() ) :
	the_post();
	$usg_id    = get_the_ID();
	$usg_prov  = get_the_terms( $usg_id, 'usg_provider' );
	$usg_prov  = $usg_prov && ! is_wp_error( $usg_prov ) ? $usg_prov[0] : null;
	$usg_demo  = (string) usg_meta( $usg_id, 'demo_url' );
	$usg_stats = usg_review_stats( $usg_id );
	$usg_specs = array(
		'rtp'        => array( 'RTP', usg_meta( $usg_id, 'rtp' ) ? usg_meta( $usg_id, 'rtp' ) . '%' : '' ),
		'volatility' => array( __( 'Volatility', 'usasmartgamers' ), usg_meta( $usg_id, 'volatility' ) ),
		'max_win'    => array( __( 'Max win', 'usasmartgamers' ), usg_meta( $usg_id, 'max_win' ) ),
		'bet_range'  => array( __( 'Bet range', 'usasmartgamers' ), usg_meta( $usg_id, 'bet_range' ) ),
		'paylines'   => array( __( 'Paylines', 'usasmartgamers' ), usg_meta( $usg_id, 'paylines' ) ),
		'reels'      => array( __( 'Reels', 'usasmartgamers' ), usg_meta( $usg_id, 'reels' ) ),
		'release'    => array( __( 'Released', 'usasmartgamers' ), usg_meta( $usg_id, 'release' ) ? wp_date( 'M Y', strtotime( usg_meta( $usg_id, 'release' ) ) ) : '' ),
	);
	$usg_schema = array(
		'@type'     => 'VideoGame',
		'name'      => get_the_title(),
		'genre'     => 'Slot machine',
		'publisher' => $usg_prov ? array( '@type' => 'Organization', 'name' => $usg_prov->name ) : null,
		'image'     => has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : null,
	);
	if ( $usg_stats['count'] ) {
		$usg_schema['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => $usg_stats['avg'], 'reviewCount' => $usg_stats['count'], 'bestRating' => 5 );
	}
	usg_schema_add( array_filter( $usg_schema ) );
	?>
	<article <?php post_class( 'slot' ); ?>>
		<section class="slot-hero">
			<div class="container">
				<?php usg_breadcrumbs(); ?>
				<div class="slot-hero__grid">
					<div class="usg-demo">
						<div class="usg-demo__stage">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'large', array( 'class' => 'usg-demo__poster' ) ); ?>
							<?php else : ?>
								<span class="usg-demo__ph"><?php the_title(); ?></span>
							<?php endif; ?>
							<?php if ( $usg_demo ) : ?>
								<button type="button" class="usg-demo__play" data-usg-demo="<?php echo esc_url( $usg_demo ); ?>" data-slot="<?php echo (int) $usg_id; ?>"><?php echo usg_icon( 'play' ); // phpcs:ignore ?><span><?php esc_html_e( 'Play free demo', 'usasmartgamers' ); ?></span></button>
							<?php else : ?>
								<span class="usg-demo__soon"><?php esc_html_e( 'Free demo coming soon', 'usasmartgamers' ); ?></span>
							<?php endif; ?>
						</div>
						<p class="usg-demo__note"><?php echo usg_icon( 'shield' ); // phpcs:ignore ?> <?php esc_html_e( 'Demo play uses virtual credits only. 21+.', 'usasmartgamers' ); ?></p>
					</div>
					<div class="slot-hero__info">
						<?php if ( $usg_prov ) : ?><a class="kicker kicker--light" href="<?php echo esc_url( get_term_link( $usg_prov ) ); ?>"><?php echo esc_html( $usg_prov->name ); ?></a><?php endif; ?>
						<h1 class="page-title"><?php the_title(); ?></h1>
						<p class="slot-hero__rating"><?php echo usg_stars( (float) usg_meta( $usg_id, 'rating', 0 ) ); // phpcs:ignore ?> <a href="#player-reviews"><?php echo esc_html( sprintf( _n( '%s player rating', '%s player ratings', $usg_stats['count'], 'usasmartgamers' ), number_format_i18n( $usg_stats['count'] ) ) ); ?></a></p>
						<?php if ( usg_lines( usg_meta( $usg_id, 'features' ) ) ) : ?>
							<ul class="chips chips--light">
								<?php foreach ( usg_lines( usg_meta( $usg_id, 'features' ) ) as $usg_f ) : ?><li><?php echo esc_html( $usg_f ); ?></li><?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<dl class="slot-specs">
							<?php foreach ( $usg_specs as $usg_s ) : ?>
								<?php if ( $usg_s[1] ) : ?><div><dt><?php echo esc_html( $usg_s[0] ); ?></dt><dd><?php echo esc_html( $usg_s[1] ); ?></dd></div><?php endif; ?>
							<?php endforeach; ?>
						</dl>
						<?php if ( usg_rewards_enabled() ) : ?>
							<p class="slot-hero__coins"><?php echo usg_icon( 'coins' ); // phpcs:ignore ?> <span><?php echo is_user_logged_in() ? esc_html__( 'Earn coins every time you play a demo (up to 3 a day).', 'usasmartgamers' ) : wp_kses_post( sprintf( __( '<a href="%s">Join free</a> and earn coins every time you play a demo.', 'usasmartgamers' ), esc_url( usg_page_url_by_path( 'account', '/account/' ) ) ) ); ?></span></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>

		<?php
		$usg_ops = array_filter( array_map( 'absint', (array) usg_meta( $usg_id, 'operators', array() ) ) );
		if ( $usg_ops ) :
			?>
			<section class="container container--narrow slot-where">
				<h2 class="usg-block-title"><?php echo esc_html( sprintf( __( 'Where to play %s for real money', 'usasmartgamers' ), get_the_title() ) ); ?></h2>
				<div class="usg-offers usg-offers--rows">
					<?php
					$usg_rank = 0;
					foreach ( $usg_ops as $usg_op ) {
						if ( 'publish' === get_post_status( $usg_op ) ) {
							echo usg_offer_card( usg_offer_view( $usg_op, usg_best_offer( $usg_op, null ) ), ++$usg_rank, 'slot-' . get_post_field( 'post_name', $usg_id ), 'rows' ); // phpcs:ignore
						}
					}
					?>
				</div>
			</section>
		<?php endif; ?>

		<div class="container container--narrow">
			<?php usg_byline(); ?>
		</div>
		<div class="entry-content container container--narrow"><?php the_content(); ?></div>
		<div class="container container--narrow">
			<?php echo usg_render_user_reviews( $usg_id, __( 'Player reviews', 'usasmartgamers' ) ); // phpcs:ignore ?>
			<?php usg_author_box(); ?>
			<?php
			if ( $usg_prov ) {
				echo usg_block_slot_grid( array( 'title' => sprintf( __( 'More slots from %s', 'usasmartgamers' ), $usg_prov->name ), 'provider' => $usg_prov->term_id, 'count' => 4, 'orderby' => 'rating' ) ); // phpcs:ignore
			}
			?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
