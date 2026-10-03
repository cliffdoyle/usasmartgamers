<?php
/**
 * JSON-LD collected while rendering and printed once in the footer.
 */

defined( 'ABSPATH' ) || exit;

function usg_schema_add( array $node ): void {
	$GLOBALS['usg_schema_nodes'][] = $node;
}

function usg_schema_faq( string $q, string $a ): void {
	$GLOBALS['usg_schema_faq'][] = array(
		'@type'          => 'Question',
		'name'           => wp_strip_all_tags( $q ),
		'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $a ) ),
	);
}

add_action(
	'wp_footer',
	function () {
		$nodes = $GLOBALS['usg_schema_nodes'] ?? array();
		if ( ! empty( $GLOBALS['usg_schema_faq'] ) ) {
			$nodes[] = array( '@type' => 'FAQPage', 'mainEntity' => $GLOBALS['usg_schema_faq'] );
		}
		if ( $nodes ) {
			echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $nodes ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}
	},
	50
);
