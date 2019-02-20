<?php
/**
 * User request password form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * User request password form class.
 *
 * @class User_Request_Password
 */
class User_Request_Password extends Form {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->fields = [
			'username' => [
				'label'      => esc_html__( 'Username or Email', 'hivepress' ),
				'type'       => 'text',
				'max_length' => 254,
				'required'   => true,
				'order'      => 10,
			],
		];

		parent::__construct();
	}

	/**
	 * Submits form.
	 *
	 * @param array $values Field values.
	 */
	public function submit( $values ) {

		// Get user.
		$user = false;

		if ( is_email( $values['username'] ) ) {
			$user = get_user_by( 'email', $values['username'] );
		} else {
			$user = get_user_by( 'login', $values['username'] );
		}

		if ( false !== $user ) {

			// Get URL.
			$url = add_query_arg(
				[
					'username' => $user->user_login,
					'key'      => get_password_reset_key( $user ),
				],
				'todo reset page URL here'
			);

			// Send email.
			// todo send email.
		} else {
			if ( is_email( $values['username'] ) ) {
				$this->errors[] = esc_html__( "User with this email doesn't exist.", 'hivepress' );
			} else {
				$this->errors[] = esc_html__( "User with this username doesn't exist.", 'hivepress' );
			}
		}
	}
}
