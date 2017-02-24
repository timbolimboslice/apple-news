<?php
/**
 * Publish to Apple News Includes: Apple_Exporter\Component_Spec class
 *
 * Defines a JSON spec for a component.
 *
 * @package Apple_News
 * @subpackage Apple_Exporter
 * @since 1.2.4
 */

namespace Apple_Exporter;


/**
 * A class that parses raw HTML into either Apple News HTML or Markdown format.
 *
 * @since 1.2.1
 */
class Component_Spec {

	/**
	 * The component for this spec.
	 *
	 * @access public
	 * @var string
	 */
	public $component;

	/**
	 * The name for this spec.
	 *
	 * @access public
	 * @var string
	 */
	public $name;

	/**
	 * The label for this spec.
	 *
	 * @access public
	 * @var string
	 */
	public $label;

	/**
	 * The spec.
	 *
	 * @access public
	 * @var array
	 */
	public $spec;

	/**
	 * Prefix for the key for storing custom JSON.
	 *
	 * @var string
	 * @const
	 */
	const JSON_KEY_PREFIX = 'apple_news_json_';

	/**
	 * Initializes the object with the name, label and the spec.
	 *
	 * @access public
	 */
	public function __construct( $component, $name, $label, $spec ) {
		$this->component = $component;
		$this->name = $name;
		$this->label = $label;
		$this->spec = $spec;
	}

	/**
	 * Using the provided spec and array of values, build the component's JSON.
	 *
	 * @param array $values
	 * @return array
	 * @access public
	 */
	public function substitute_values( $values ) {
		// TODO - need a function for pulling in spec overrides from the database
		// Call a recursive function to substitute the values
		return $this->value_iterator( $this->spec, $values );
	}

	/**
	 * Substitute values recursively for a given spec
	 *
	 * @param array $spec
	 * @param array $values
	 * @return array
	 * @access public
	 */
	public function value_iterator( $spec, $values ) {
		// Go through this level of the iterator
		foreach ( $spec as $key => $value ) {

			// If the current element has children, call this recursively
			if ( is_array( $value ) ) {
				// Pass through only the values for this child array
				$child_values = ( isset( $values[ $key ] ) ) ? $values[ $key ] : array();

				// Call this function recursively to handle the substitution on this child array
				$spec[ $key ] = $this->value_iterator( $spec[ $key ], $child_values );
			} else if ( ! is_array( $value ) && $this->is_token( $value ) ) {
				// This element is a token, so substitute its value
				// If no value exists, it should be removed to not produce invalid JSON
				if ( isset( $values[ $key ] ) ) {
					$spec[ $key ] = $values[ $key ];
				} else {
					unset( $spec[ $key ] );
				}
			}
		}

		return $spec;
	}

	/**
	 * Validate the provided spec against the built-in spec.
	 *
	 * @param array $spec
	 * @return boolean
	 * @access public
	 */
	public function validate( $spec ) {
		// Iterate recursively over the built-in spec and get all the tokens
		// Do the same for the provided spec.
		// Removing tokens is fine, but new tokens cannot be added.
	}

	/**
	 * Save the provided spec override.
	 *
	 * @param array $spec
	 * @return boolean
	 * @access public
	 */
	public function save( $spec ) {
		// Validate the JSON
		$json = json_decode( $spec );
		if ( empty( $spec ) ) {
			\Admin_Apple_Notice::error( sprintf(
				__( 'The JSON for %s was invalid and cannot be saved', 'apple-news' ),
				$name
			) );
		}

		// Compare this JSON to the built-in JSON.
		// If they are the same, there is no reason to save this.
		$custom_json = $this->get_json( $json );
		$default_json = $this->get_json();
		if ( $custom_json === $default_json ) {
			// Delete the spec in case we've reverted back to default.
			// No need to keep it in storage.
			$this->delete();
			return;
		}

		// If we've gotten to this point, save the JSON.
		$option_name = $this->key_from_name( $this->component );
		$spec_key = $this->key_from_name( $this->name );
		$overrides = get_option( $option_name, array() );
		$overrides[ $spec_key ] = $json;
		update_option( $option_name, $overrides );

		// Indicate success
		$component = new $this->component;
		\Admin_Apple_Notice::error( sprintf(
			__( 'Saved custom %s for the %s component', 'apple-news' ),
			$this->name,
			ucwords( $component->get_component_name() )
		) );
	}

	/**
	 * Delete the current spec override.
	 *
	 * @access private
	 */
	private function delete() {
		$option_name = $this->key_from_name( $this->component );
		$spec_key = $this->key_from_name( $this->name );
		$overrides = get_option( $option_name, array() );
		if ( isset( $overrides[ $spec_key ] ) ) {
			unset( $overrides[ $spec_key ] );
		}

		if ( empty( $overrides ) ) {
			delete_option( $option_name );
		} else {
			update_option( $option_name, $overrides );
		}
	}

	/**
	 * Get the spec for this component as JSON.
	 *
	 * @return string
	 * @access public
	 */
	public function get_spec() {
		$override = $this->get_override();
		if ( ! empty( $override ) ) {
			return $override;
		} else {
			return $this->spec;
		}
	}

	/**
	 * Get the spec for this component as JSON.
	 *
	 * @param string $spec
	 * @return string
	 * @access public
	 */
	public function get_json( $spec = null ) {
		if ( empty( $spec ) ) {
			$spec = $this->get_spec();
		}

		return json_encode( $spec, JSON_PRETTY_PRINT );
	}

	/**
	 * Get the override for this component spec.
	 *
	 * @return array
	 * @access public
	 */
	public function get_override() {
		$option_name = $this->key_from_name( $this->component );
		$spec_key = $this->key_from_name( $this->name );
		$overrides = get_option( $option_name, array() );
		if ( isset( $overrides[ $spec_key ] ) ) {
			return $overrides[ $spec_key ];
		} else {
			return null;
		}
	}

	/**
	 * Determines whether or not the spec value is a token.
	 *
	 * @param string $value
	 * @return boolean
	 * @access public
	 */
	public function is_token( $value ) {
		return ( 1 === preg_match( '/%%(.*?)%%/', $value ) );
	}

	/**
	 * Generates a key for the JSON from the provided component or spec
	 *
	 * @param string $component
	 * @return string
	 * @access public
	 */
	public function key_from_name( $name ) {
		return self::JSON_KEY_PREFIX . sanitize_key( $name );
	}
}
