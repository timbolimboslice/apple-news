<?php
/**
 * Publish to Apple News: \Apple_Exporter\Components\Link class
 *
 * @package Apple_News
 * @subpackage Apple_Exporter\Components
 */

namespace Apple_Exporter\Components;

/**
 * A Link/Anchor component.
 *
 * @since 2.0.0
 */
class Link_Button extends Component {

	/**
	 * Look for node matches for this component.
	 *
	 * @param \DOMElement $node The node to examine for matches.
	 * @access public
	 * @return array|null The node on success, or null on no match.
	 */
	public static function node_matches( $node ) {

		if ( 'a' !== $node->nodeName ) {
			return null;
		}

		// If the node is a AND it's empty, just ignore.
		if ( empty( $node->nodeValue ) ) {
			return null;
		}

		return $node;
	}

	/**
	 * Register all specs for the component.
	 *
	 * @access public
	 */
	public function register_specs() {
		$this->register_spec(
			'json',
			__( 'JSON', 'apple-news' ),
			array(
				'role'   => 'link_button',
				'text'   => '#text#',
        'URL' => '#url#',
        'style' => 'default-link-button',
        'layout' => 'link-button-layout',
			)
		);

		// Register the JSON for the table layout.
		$this->register_spec(
			'link-button-layout',
			__( 'Button Layout', 'apple-news' ),
			array(
				'margin' => array(
					'bottom' => 20,
				),
				'padding' => array(
					'top' => 10,
					'bottom' => 10,
					'left' => 15,
					'right' => 15,
				),
			)
		);

		// Register the JSON for the table style.
		$this->register_spec(
			'default-link-button',
			__( 'Link Button Style', 'apple-news' ),
			array(
				'backgroundColor' => '#DDD',
				'mask' => array(
					'type' => 'corners',
					'radius' => 25,
				),
			)
		);
	}

	/**
	 * Build the component.
	 *
	 * @param string $html The HTML to parse into text for processing.
	 * @access protected
	 */
	protected function build( $html ) {

		// If there is no text for this element, bail.
		$check = trim( $html );
		if ( empty( $check ) ) {
			return;
		}

		if ( preg_match( '/^(<a.*?href="([^"]+)".*?>([^<]+)|<<\/a>)/', $html, $link_button_match ) ) {
			$this->register_json(
				'json',
				array(
					'#url#' => $link_button_match[2],
					'#text#' => $link_button_match[3],
				)
			);
		}

    // Register the layout for the table.
		$this->register_layout( 'link-button-layout', 'link-button-layout' );

		// Register the style for the table.
		$this->register_component_style(
			'default-link-button',
			'default-link-button'
		);
	}
}

