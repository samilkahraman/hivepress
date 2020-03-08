<?php
/**
 * Listing manage menu.
 *
 * @package HivePress\Menus
 */

namespace HivePress\Menus;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listing manage menu class.
 *
 * @class Listing_Manage
 */
class Listing_Manage extends Menu {

	/**
	 * Class constructor.
	 *
	 * @param array $args Menu arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'items'      => [],

				'attributes' => [
					'class' => [ 'hp-menu--tabbed' ],
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
