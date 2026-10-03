<?php
/**
 * Three-level mega navigation walker.
 * Level 0 = top items, level 1 = column headings, level 2 = links. Add CSS class badge-hot / badge-new / badge-trending to a menu item for a pill.
 */

defined( 'ABSPATH' ) || exit;

class USG_Mega_Walker extends Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= 0 === $depth ? '<div class="mega__panel"><ul class="mega__cols">' : '<ul class="mega__links">';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= 0 === $depth ? '</ul></div>' : '</ul>';
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = array_filter( (array) $item->classes );
		$has     = in_array( 'menu-item-has-children', $classes, true );
		$badge   = '';
		foreach ( array( 'badge-hot' => 'Hot', 'badge-new' => 'New', 'badge-trending' => 'Trending' ) as $cls => $label ) {
			if ( in_array( $cls, $classes, true ) ) {
				$badge = '<span class="mega__badge mega__badge--' . esc_attr( substr( $cls, 6 ) ) . '">' . esc_html( $label ) . '</span>';
			}
		}
		$current = array_intersect( $classes, array( 'current-menu-item', 'current-menu-ancestor', 'current-page-ancestor' ) ) ? ' is-current' : '';
		$output .= '<li class="mega__item mega__item--d' . (int) $depth . ( $has ? ' has-children' : '' ) . $current . '">';
		$url     = $item->url && '#' !== $item->url ? $item->url : '';
		$title   = esc_html( apply_filters( 'nav_menu_item_title', $item->title, $item, $args, $depth ) );
		$link    = $url ? '<a class="mega__link" href="' . esc_url( $url ) . '"' . ( $current && ! $has ? ' aria-current="page"' : '' ) . '>' . $title . $badge . '</a>' : '<span class="mega__link">' . $title . $badge . '</span>';
		if ( 0 === $depth && $has ) {
			$output .= '<div class="mega__top">' . $link . '<button type="button" class="mega__toggle" aria-expanded="false"><span class="screen-reader-text">' . esc_html__( 'Open submenu', 'usasmartgamers' ) . '</span><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button></div>';
		} elseif ( 1 === $depth ) {
			$output .= '<span class="mega__heading">' . $link . '</span>';
		} else {
			$output .= $link;
		}
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}
