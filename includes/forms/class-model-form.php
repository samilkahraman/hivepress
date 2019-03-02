<?php
/**
 * Model form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Model form class.
 *
 * @class Model_Form
 */
abstract class Model_Form extends Form {

	/**
	 * Form model.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Sets form model.
	 *
	 * @param string $model Form model.
	 */
	final protected function set_model( $model ) {
		$this->model = $model;
	}

	/**
	 * Sets form fields.
	 *
	 * @param array $fields Form fields.
	 */
	final protected function set_fields( $fields ) {
		$this->fields = [];

		// Get model class.
		$model_class = '\HivePress\Models\\' . $this->model;

		// Get model fields.
		$model_fields = $model_class::get_fields();

		foreach ( hp_sort_array( $fields ) as $field_name => $field_args ) {
			if ( isset( $field['type'] ) ) {

				// Get field class.
				$field_class = '\HivePress\Fields\\' . $field_args['type'];

				// Create field.
				$this->fields[ $field_name ] = new $field_class( array_merge( $field_args, [ 'name' => $field_name ] ) );
			} elseif ( isset( $model_fields[ $field_name ] ) ) {
				$this->fields[ $field_name ] = $model_fields[ $field_name ];
			}
		}
	}
}