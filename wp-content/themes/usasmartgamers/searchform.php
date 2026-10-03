<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label>
		<span class="screen-reader-text"><?php esc_html_e( 'Search for:', 'usasmartgamers' ); ?></span>
		<input type="search" class="search-form__input" placeholder="<?php esc_attr_e( 'Search casinos, slots, guides…', 'usasmartgamers' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s">
	</label>
	<button type="submit" class="search-form__btn"><?php esc_html_e( 'Search', 'usasmartgamers' ); ?></button>
</form>
