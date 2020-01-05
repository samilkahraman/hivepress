<?php
/**
 * User component.
 *
 * @package HivePress\Components
 */
// todo.
namespace HivePress\Components;

use HivePress\Helpers as hp;
use HivePress\Models;
use HivePress\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * User component class.
 *
 * @class User
 */
final class User {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Register user.
		add_action( 'hivepress/v1/models/user/register', [ $this, 'register_user' ] );

		// Update user.
		add_action( 'hivepress/v1/models/user/update_first_name', [ $this, 'update_user' ] );

		// Render user image.
		add_filter( 'get_avatar', [ $this, 'render_user_image' ], 1, 5 );

		// Add registration fields.
		add_filter( 'hivepress/v1/forms/user_register', [ $this, 'add_register_fields' ] );

		// todo.
		add_filter( 'hivepress/v1/templates/site_footer_block', [ $this, 'alter_site_footer_block' ] );
	}

	// todo.
	public function alter_site_footer_block( $template ) {
		if ( ! is_user_logged_in() ) {
			$template = hp\merge_trees(
				[
					'blocks' => [
						'user_login_modal'            => [
							'type'   => 'modal',
							'title'  => esc_html__( 'Sign In', 'hivepress' ),

							'blocks' => [
								'user_login_form' => [
									'type'   => 'user_login_form',
									'_order' => 10,
								],
							],
						],

						'user_register_modal'         => [
							'type'   => 'modal',
							'title'  => esc_html__( 'Register', 'hivepress' ),

							'blocks' => [
								'user_register_form' => [
									'type'       => 'form',
									'form'       => 'user_register',
									'_order'     => 10,

									'attributes' => [
										'class' => [ 'hp-form--narrow' ],
									],

									'footer'     => [
										'form_actions' => [
											'type'       => 'container',
											'_order'     => 10,

											'attributes' => [
												'class' => [ 'hp-form__actions' ],
											],

											'blocks'     => [
												'user_login_link' => [
													'type' => 'part',
													'path' => 'user/register/user-login-link',
													'_order' => 10,
												],
											],
										],
									],
								],
							],
						],

						'user_password_request_modal' => [
							'type'   => 'modal',
							'title'  => esc_html__( 'Reset Password', 'hivepress' ),

							'blocks' => [
								'user_password_request_form' => [
									'type'       => 'form',
									'form'       => 'user_password_request',
									'_order'     => 10,

									'attributes' => [
										'class' => [ 'hp-form--narrow' ],
									],
								],
							],
						],
					],
				],
				$template
			);
		}

		return $template;
	}

	/**
	 * Registers user.
	 *
	 * @param int $user_id User ID.
	 */
	public function register_user( $user_id ) {

		// Get user.
		$user = Models\User::query()->get_by_id( $user_id );

		// Hide admin bar.
		update_user_meta( $user_id, 'show_admin_bar_front', 'false' );

		// Send emails.
		wp_new_user_notification( $user_id );

		( new Emails\User_Register(
			[
				'recipient' => $user->get_email(),

				'tokens'    => [
					'user_name'     => $user->get_display_name(),
					'user_password' => $user->get_password(),
				],
			]
		) )->send();
	}

	/**
	 * Updates user.
	 *
	 * @param int $user_id User ID.
	 */
	public function update_user( $user_id ) {

		// Get user.
		$user = Models\User::query()->get_by_id( $user_id );

		// Update user.
		$user->fill(
			[
				'display_name' => $user->get_first_name() ? $user->get_first_name() : $user->get_username(),
			]
		)->save();
	}

	/**
	 * Renders user image.
	 *
	 * @param string $image Image HTML.
	 * @param mixed  $id_or_email User ID.
	 * @param int    $size Image size.
	 * @param string $default Default image.
	 * @param string $alt Image description.
	 * @return string
	 */
	public function render_user_image( $image, $id_or_email, $size, $default, $alt ) {

		// Get user.
		$user = null;

		if ( is_numeric( $id_or_email ) ) {
			$user = Models\User::query()->get_by_id( $id_or_email );
		} elseif ( is_object( $id_or_email ) ) {
			$user = Models\User::query()->get_by_id( $id_or_email->user_id );
		} elseif ( is_email( $id_or_email ) ) {
			$user = Models\User::query()->filter( [ 'email' => $id_or_email ] )->get_first();
		}

		// Render image.
		if ( $user && $user->get_image_url( 'thumbnail' ) ) {
			$image = '<img src="' . esc_url( $user->get_image_url( 'thumbnail' ) ) . '" class="avatar avatar-' . esc_attr( $size ) . ' photo" height="' . esc_attr( $size ) . '" width="' . esc_attr( $size ) . '" alt="' . esc_attr( $alt ) . '">';
		}

		return $image;
	}

	/**
	 * Adds registration fields.
	 *
	 * @param array $form Form arguments.
	 * @return array
	 */
	public function add_register_fields( $form ) {

		// Get terms page ID.
		$page_id = reset(
			( get_posts(
				[
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post__in'       => [ absint( get_option( 'hp_page_user_registration_terms' ) ) ],
					'posts_per_page' => 1,
					'fields'         => 'ids',
				]
			) )
		);

		if ( $page_id ) {

			// Add terms field.
			$form['fields']['registration_terms'] = [
				'caption'  => sprintf( hp\sanitize_html( __( 'I agree to the <a href="%s" target="_blank">terms and conditions</a>', 'hivepress' ) ), esc_url( get_permalink( $page_id ) ) ),
				'type'     => 'checkbox',
				'required' => true,
				'_order'   => 1000,
			];
		}

		return $form;
	}
}
