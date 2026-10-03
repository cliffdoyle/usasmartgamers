<?php
/**
 * News article / Insights article.
 */

get_header();
while ( have_posts() ) :
	the_post();
	$usg_cats = get_the_category();
	if ( function_exists( 'usg_schema_add' ) ) {
		usg_schema_add(
			array(
				'@type'         => 'post' === get_post_type() ? 'NewsArticle' : 'BlogPosting',
				'headline'      => get_the_title(),
				'datePublished' => get_the_date( 'c' ),
				'dateModified'  => get_the_modified_date( 'c' ),
				'author'        => array( '@type' => 'Person', 'name' => get_the_author(), 'url' => get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ),
				'image'         => has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : null,
				'publisher'     => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ) ),
			)
		);
	}
	?>
	<article <?php post_class( 'single-article' ); ?>>
		<header class="page-header container container--narrow">
			<?php usg_breadcrumbs(); ?>
			<?php if ( $usg_cats ) : ?>
				<a class="kicker" href="<?php echo esc_url( get_category_link( $usg_cats[0] ) ); ?>"><?php echo esc_html( $usg_cats[0]->name ); ?></a>
			<?php endif; ?>
			<h1 class="page-title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?><p class="page-lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
			<div class="single-article__meta">
				<?php usg_byline(); ?>
				<div class="share">
					<?php
					$usg_u = rawurlencode( get_permalink() );
					$usg_t = rawurlencode( get_the_title() );
					?>
					<a href="<?php echo esc_url( 'https://x.com/intent/tweet?url=' . $usg_u . '&text=' . $usg_t ); ?>" rel="noopener" target="_blank" aria-label="Share on X"><?php echo usg_theme_icon( 'twitter' ); // phpcs:ignore ?></a>
					<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . $usg_u ); ?>" rel="noopener" target="_blank" aria-label="Share on Facebook"><?php echo usg_theme_icon( 'facebook' ); // phpcs:ignore ?></a>
					<a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . $usg_u ); ?>" rel="noopener" target="_blank" aria-label="Share on LinkedIn"><?php echo usg_theme_icon( 'linkedin' ); // phpcs:ignore ?></a>
					<a href="<?php echo esc_url( 'mailto:?subject=' . $usg_t . '&body=' . $usg_u ); ?>" aria-label="Share by email"><?php echo usg_theme_icon( 'mail' ); // phpcs:ignore ?></a>
				</div>
			</div>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="container container--narrow single-article__hero"><?php the_post_thumbnail( 'large' ); ?></figure>
		<?php endif; ?>
		<div class="entry-content container container--narrow"><?php the_content(); ?></div>
		<div class="container container--narrow"><?php usg_author_box(); ?></div>
		<?php
		$usg_related = new WP_Query(
			array(
				'post_type'           => get_post_type(),
				'posts_per_page'      => 3,
				'post__not_in'        => array( get_the_ID() ),
				'category__in'        => $usg_cats ? array( $usg_cats[0]->term_id ) : array(),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		if ( $usg_related->have_posts() && function_exists( 'usg_post_card' ) ) :
			?>
			<section class="container related"><h2 class="usg-block-title"><?php esc_html_e( 'Related stories', 'usasmartgamers' ); ?></h2><div class="usg-cards usg-cards--grid">
			<?php
			foreach ( $usg_related->posts as $usg_p ) {
				echo usg_post_card( $usg_p ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
			</div></section>
		<?php endif; ?>
	</article>
	<?php
endwhile;
get_footer();
