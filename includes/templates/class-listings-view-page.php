<?php
/**
 * Listings view page template.
 *
 * @template listings_view_page
 * @description Listings page in view context.
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listings view page template class.
 *
 * @class Listings_View_Page
 */
class Listings_View_Page extends Page_Sidebar_Left {

	/**
	 * Class constructor.
	 *
	 * @param array $args Template arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_trees(
			[
				'blocks' => [
					'page_container' => [
						'blocks' => [
							'page_header' => [
								'type'       => 'container',
								'tag'        => 'header',
								'_order'     => 5,

								'attributes' => [
									'class' => [ 'hp-page__header' ],
								],

								'blocks'     => [
									'listing_search_form' => [
										'type'   => 'listing_search_form',
										'_order' => 10,
									],
								],
							],
						],
					],

					'page_sidebar'   => [
						'attributes' => [
							'data-component' => 'sticky',
						],

						'blocks'     => [
							'listing_filter_form'  => [
								'type'       => 'form',
								'form'       => 'listing_filter',
								'_order'     => 10,

								'attributes' => [
									'class' => [ 'hp-form--narrow', 'hp-widget', 'widget' ],
								],
							],

							'page_sidebar_widgets' => [
								'type'   => 'widgets',
								'area'   => 'hp_listings_view_sidebar',
								'_order' => 20,
							],
						],
					],

					'page_content'   => [
						'blocks' => [
							'listings_container' => [
								'type'   => 'results',
								'_order' => 10,

								'blocks' => [
									'page_topbar'        => [
										'type'       => 'container',
										'_order'     => 10,

										'attributes' => [
											'class' => [ 'hp-page__topbar' ],
										],

										'blocks'     => [
											'listing_count' => [
												'type'   => 'result_count',
												'_order' => 10,
											],

											'listing_sort_form'    => [
												'type'   => 'form',
												'form'   => 'listing_sort',
												'_order' => 20,

												'attributes' => [
													'class' => [ 'hp-form--pivot' ],
												],
											],
										],
									],

									'listings'           => [
										'type'    => 'listings',
										'columns' => 2,
										'_order'  => 20,
									],

									'listing_pagination' => [
										'type'   => 'part',
										'path'   => 'page/pagination',
										'_order' => 30,
									],
								],
							],
						],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
