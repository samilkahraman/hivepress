<?php
/**
 * User model.
 *
 * @package HivePress\Models
 */

namespace HivePress\Models;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * User model class.
 *
 * @class User
 */
class User extends Model {

	/**
	 * Class constructor.
	 *
	 * @param array $args Model arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'fields' => [
					'username'     => [
						'label'      => esc_html__( 'Username', 'hivepress' ),
						'type'       => 'text',
						'max_length' => 60,
						'required'   => true,
						'_alias'     => 'user_login',
					],

					'email'        => [
						'label'    => esc_html__( 'Email', 'hivepress' ),
						'type'     => 'email',
						'required' => true,
						'_alias'   => 'user_email',
					],

					'password'     => [
						'label'      => esc_html__( 'Password', 'hivepress' ),
						'type'       => 'password',
						'min_length' => 8,
						'_alias'     => 'user_pass',
					],

					'first_name'   => [
						'label'      => esc_html__( 'First Name', 'hivepress' ),
						'type'       => 'text',
						'max_length' => 64,
						'_alias'     => 'first_name',
						'_external'  => true,
					],

					'last_name'    => [
						'label'      => esc_html__( 'Last Name', 'hivepress' ),
						'type'       => 'text',
						'max_length' => 64,
						'_alias'     => 'last_name',
						'_external'  => true,
					],

					'display_name' => [
						'type'       => 'text',
						'max_length' => 256,
						'_alias'     => 'display_name',
					],

					'description'  => [
						'label'      => esc_html__( 'Profile Info', 'hivepress' ),
						'type'       => 'textarea',
						'max_length' => 2048,
						'_alias'     => 'description',
						'_external'  => true,
					],

					'image'        => [
						'label'     => esc_html__( 'Profile Image', 'hivepress' ),
						'caption'   => esc_html__( 'Select Image', 'hivepress' ),
						'type'      => 'attachment_upload',
						'formats'   => [ 'jpg', 'jpeg', 'png' ],
						'_model'    => 'attachment',
						'_external' => true,
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Gets image URL.
	 *
	 * @param string $size Image size.
	 * @return string
	 */
	final public function get_image__url( $size = 'thumbnail' ) {

		// Get field name.
		$name = 'image__url__' . $size;

		if ( ! isset( $this->values[ $name ] ) ) {
			$this->values[ $name ] = '';

			// Get image URL.
			if ( $this->get_image__id() ) {
				$urls = wp_get_attachment_image_src( $this->get_image__id(), $size );

				if ( $urls ) {
					$this->values[ $name ] = reset( $urls );
				}
			}
		}

		return $this->values[ $name ];
	}

	/**
	 * Gets object.
	 *
	 * @param int $id Object ID.
	 * @return mixed
	 */
	final public function get( $id ) {

		// Get user.
		$user = null;

		if ( is_object( $id ) ) {
			$user = get_object_vars( $id->data );
		} else {
			$data = get_userdata( absint( $id ) );

			if ( $data ) {
				$user = get_object_vars( $data->data );
			}
		}

		if ( empty( $user ) ) {
			return;
		}

		// Get user meta.
		$meta = array_map(
			function( $values ) {
				return reset( $values );
			},
			get_user_meta( $user['ID'] )
		);

		// Create object.
		$object = ( new static() )->set_id( $user['ID'] );

		// Get field values.
		$values = [];

		foreach ( $object->_get_fields() as $field_name => $field ) {
			if ( $field->get_arg( '_external' ) ) {

				// Get meta value.
				$values[ $field_name ] = hp\get_array_value( $meta, $field->get_arg( '_alias' ) );
			} else {

				// Get user value.
				$values[ $field_name ] = hp\get_array_value( $user, $field->get_arg( '_alias' ) );
			}
		}

		return $object->fill( $values );
	}

	/**
	 * Saves object.
	 *
	 * @return bool
	 */
	final public function save() {

		// Get user data.
		$user = [];
		$meta = [];

		foreach ( $this->fields as $field_name => $field ) {
			if ( $field->validate() ) {
				if ( $field->get_arg( '_external' ) ) {

					// Set meta value.
					$meta[ $field->get_arg( '_alias' ) ] = $field->get_value();
				} else {

					// Set user value.
					$user[ $field->get_arg( '_alias' ) ] = $field->get_value();
				}
			} else {
				$this->_add_errors( $field->get_errors() );
			}
		}

		if ( empty( $this->errors ) ) {

			// Create or update user.
			if ( empty( $this->id ) ) {
				$id = wp_insert_user( $user );

				if ( ! is_wp_error( $id ) ) {
					$this->set_id( $id );
				} else {
					return false;
				}
			} elseif ( is_wp_error( wp_update_user( array_merge( $user, [ 'ID' => $this->id ] ) ) ) ) {
				return false;
			}

			// Update user meta.
			foreach ( $meta as $meta_key => $meta_value ) {
				if ( is_null( $meta_value ) ) {
					delete_user_meta( $this->id, $meta_key );
				} else {
					update_user_meta( $this->id, $meta_key, $meta_value );
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Deletes object.
	 *
	 * @param int $id Object ID.
	 * @return bool
	 */
	final public function delete( $id = null ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';

		if ( is_null( $id ) ) {
			$id = $this->id;
		}

		return $id && wp_delete_user( absint( $id ) );
	}
}
