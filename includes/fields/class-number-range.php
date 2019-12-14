<?php
/**
 * Number range field.
 *
 * @package HivePress\Fields
 */

namespace HivePress\Fields;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Number range field class.
 *
 * @class Number_Range
 */
class Number_Range extends Number {

	/**
	 * Field title.
	 *
	 * @var string
	 */
	protected static $title;

	/**
	 * Field settings.
	 *
	 * @var array
	 */
	protected static $settings = [];

	/**
	 * Minimum field.
	 *
	 * @var object
	 */
	protected $min_field;

	/**
	 * Maximum field.
	 *
	 * @var object
	 */
	protected $max_field;

	/**
	 * Class initializer.
	 *
	 * @param array $args Field arguments.
	 */
	public static function init( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'title'    => esc_html__( 'Number Range', 'hivepress' ),

				'settings' => [
					'placeholder' => null,
				],
			],
			$args
		);

		parent::init( $args );
	}

	/**
	 * Bootstraps field properties.
	 */
	protected function bootstrap() {
		$attributes = [];

		// Create fields.
		$this->min_field = new Number(
			array_merge(
				$this->args,
				[
					'name'        => $this->name . '[]',
					'placeholder' => esc_html__( 'Min', 'hivepress' ),
					'required'    => false,
				]
			)
		);

		$this->max_field = new Number(
			array_merge(
				$this->args,
				[
					'name'        => $this->name . '[]',
					'placeholder' => esc_html__( 'Max', 'hivepress' ),
					'required'    => false,
				]
			)
		);

		// Set range slider.
		if ( ! is_null( $this->min_value ) && ! is_null( $this->max_value ) ) {
			$attributes['data-component'] = 'range-slider';
		}

		$this->attributes = hp\merge_arrays( $this->attributes, $attributes );

		Field::bootstrap();
	}

	/**
	 * Gets field display value.
	 *
	 * @return mixed
	 */
	public function get_display_value() {
		if ( ! is_null( $this->value ) ) {
			return $this->min_field->get_display_value() . ' - ' . $this->max_field->get_display_value();
		}
	}

	/**
	 * Adds field filters.
	 */
	protected function add_filters() {
		parent::add_filters();

		$this->filters['operator'] = 'BETWEEN';
	}

	/**
	 * Normalizes field value.
	 */
	protected function normalize() {
		Field::normalize();

		if ( is_array( $this->value ) && count( $this->value ) === 2 ) {
			sort( $this->value );
		} else {
			$this->value = null;
		}
	}

	/**
	 * Sanitizes field value.
	 */
	protected function sanitize() {

		// Set field values.
		$this->min_field->set_value( reset( $this->value ) );
		$this->max_field->set_value( end( $this->value ) );

		// Set range value.
		$this->value = array_filter( [ $this->min_field->get_value(), $this->max_field->get_value() ], 'strlen' );

		if ( count( $this->value ) !== 2 ) {
			$this->value = null;
		}
	}

	/**
	 * Validates field value.
	 *
	 * @return bool
	 */
	public function validate() {
		if ( Field::validate() && ! is_null( $this->value ) ) {

			// Validate fields.
			$this->min_field->validate();
			$this->max_field->validate();

			// Add errors.
			$this->add_errors( $this->min_field->get_errors() );
			$this->add_errors( $this->max_field->get_errors() );
		}

		return empty( $this->errors );
	}

	/**
	 * Renders field HTML.
	 *
	 * @return string
	 */
	public function render() {
		$output = '<div ' . hp\html_attributes( $this->attributes ) . '>';

		// Render fields.
		$output .= $this->min_field->render();
		$output .= $this->max_field->render();

		$output .= '</div>';

		return $output;
	}
}
