<?php
/**
 * User delete form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * User delete form class.
 *
 * @class User_Delete
 */
class User_Delete extends Form {

	/**
	 * Class constructor.
	 *
	 * @param array $args Form arguments.
	 */
	public function __construct( $args = [] ) {
		$args = array_replace_recursive(
			[
				'method' => 'DELETE',
				'fields' => [
					'password' => [
						'label'    => esc_html__( 'Password', 'hivepress' ),
						'type'     => 'password',
						'required' => true,
						'order'    => 10,
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
