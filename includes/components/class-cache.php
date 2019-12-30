<?php
/**
 * Cache component.
 *
 * @package HivePress\Components
 */
// ok.
namespace HivePress\Components;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Cache component class.
 *
 * @class Cache
 */
final class Cache extends Component {

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Schedule events.
		add_action( 'hivepress/v1/activate', [ $this, 'schedule_events' ] );
		add_action( 'hivepress/v1/update', [ $this, 'schedule_events' ] );

		// Unschedule events.
		add_action( 'hivepress/v1/deactivate', [ $this, 'unschedule_events' ] );

		// Clear meta cache.
		add_action( 'hivepress/v1/events/daily', [ $this, 'clear_meta_cache' ] );

		// Clear post cache.
		add_action( 'hivepress/v1/models/post/create', [ $this, 'clear_post_cache' ] );
		add_action( 'hivepress/v1/models/post/update', [ $this, 'clear_post_cache' ] );
		add_action( 'hivepress/v1/models/post/delete', [ $this, 'clear_post_cache' ] );

		// Clear post term cache.
		add_action( 'set_object_terms', [ $this, 'clear_post_term_cache' ], 10, 6 );

		// Clear term cache.
		add_action( 'hivepress/v1/models/term/create', [ $this, 'clear_term_cache' ] );
		add_action( 'hivepress/v1/models/term/update', [ $this, 'clear_term_cache' ] );
		add_action( 'hivepress/v1/models/term/delete', [ $this, 'clear_term_cache' ] );

		// Clear comment cache.
		add_action( 'hivepress/v1/models/comment/create', [ $this, 'clear_comment_cache' ] );
		add_action( 'hivepress/v1/models/comment/update', [ $this, 'clear_comment_cache' ] );
		add_action( 'hivepress/v1/models/comment/delete', [ $this, 'clear_comment_cache' ] );

		parent::__construct( $args );
	}

	/**
	 * Schedules events.
	 */
	public function schedule_events() {
		$periods = [ 'hourly', 'twicedaily', 'daily' ];

		foreach ( $periods as $period ) {
			if ( ! wp_next_scheduled( 'hivepress/v1/events/' . $period ) ) {
				wp_schedule_event( time(), $period, 'hivepress/v1/events/' . $period );
			}
		}
	}

	/**
	 * Unschedules events.
	 */
	public function unschedule_events() {
		$periods = [ 'hourly', 'twicedaily', 'daily' ];

		foreach ( $periods as $period ) {
			$timestamp = wp_next_scheduled( 'hivepress/v1/events/' . $period );

			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'hivepress/v1/events/' . $period );
			}
		}
	}

	/**
	 * Routes methods.
	 *
	 * @param string $name Method name.
	 * @param array  $args Method arguments.
	 * @throws \BadMethodCallException Invalid method.
	 * @return mixed
	 */
	public function __call( $name, $args ) {
		preg_match( '/^(get|set|delete)_([a-z_]+)_cache$/', $name, $matches );

		if ( is_array( $matches ) && count( $matches ) === 3 ) {
			array_shift( $matches );

			$method = reset( $matches ) . '_meta_cache';
			$type   = end( $matches );

			if ( method_exists( $this, $method ) ) {
				return call_user_func_array( [ $this, $method ], array_merge( [ $type ], $args ) );
			}
		}

		throw new \BadMethodCallException();
	}

	/**
	 * Gets transient cache.
	 *
	 * @param mixed  $key Cache key.
	 * @param string $group Cache group.
	 * @return mixed
	 */
	public function get_cache( $key, $group = null ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Get value.
		$cache = get_transient( $this->get_cache_name( $key, $group ) );

		// Normalize value.
		if ( false === $cache ) {
			$cache = null;
		}

		return $cache;
	}

	/**
	 * Gets meta cache.
	 *
	 * @param string $type Meta type.
	 * @param int    $id Object ID.
	 * @param mixed  $key Cache key.
	 * @param string $group Cache group.
	 * @return mixed
	 */
	protected function get_meta_cache( $type, $id, $key, $group = null ) {
		$cache = null;

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Set callback.
		$callback = 'get_' . $type . '_meta';

		if ( function_exists( $callback ) ) {

			// Get name.
			$name = $this->get_meta_cache_name( $type, $id, $key, $group );

			// Get timeout.
			$timeout = absint( call_user_func_array( $callback, [ $id, '_transient_timeout_' . $name, true ] ) );

			if ( 0 !== $timeout && $timeout <= time() ) {

				// Delete value.
				$this->delete_meta_cache( $type, $id, $key, $group );
			} else {

				// Get value.
				$cache = call_user_func_array( $callback, [ $id, '_transient_' . $name, true ] );

				// Normalize value.
				if ( '' === $cache ) {
					$cache = null;
				}
			}
		}

		return $cache;
	}

	/**
	 * Sets transient cache.
	 *
	 * @param mixed  $key Cache key.
	 * @param string $group Cache group.
	 * @param mixed  $value Cache value.
	 * @param int    $expiration Expiration period.
	 */
	public function set_cache( $key, $group, $value, $expiration = 0 ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Get expiration period.
		if ( 0 === $expiration ) {
			if ( ! wp_using_ext_object_cache() ) {
				$expiration = DAY_IN_SECONDS;
			} else {
				$expiration = WEEK_IN_SECONDS;
			}
		}

		$expiration = absint( $expiration );

		// Set value.
		set_transient( $this->get_cache_name( $key, $group ), $value, $expiration );
	}

	/**
	 * Sets meta cache.
	 *
	 * @param string $type Meta type.
	 * @param int    $id Object ID.
	 * @param mixed  $key Cache key.
	 * @param string $group Cache group.
	 * @param mixed  $value Cache value.
	 * @param int    $expiration Expiration period.
	 */
	protected function set_meta_cache( $type, $id, $key, $group, $value, $expiration = DAY_IN_SECONDS ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Set callback.
		$callback = 'update_' . $type . '_meta';

		if ( function_exists( $callback ) ) {

			// Get name.
			$name = $this->get_meta_cache_name( $type, $id, $key, $group );

			// Set value.
			call_user_func_array( $callback, [ $id, '_transient_' . $name, $value ] );

			// Set timeout.
			if ( $expiration > 0 ) {
				call_user_func_array( $callback, [ $id, '_transient_timeout_' . $name, time() + $expiration ] );
			}
		}
	}

	/**
	 * Deletes transient cache.
	 *
	 * @param mixed  $key Cache key.
	 * @param string $group Cache group.
	 */
	public function delete_cache( $key, $group = null ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		if ( is_null( $key ) && ! is_null( $group ) ) {

			// Update version.
			$this->update_cache_version( $group );
		} else {

			// Delete value.
			delete_transient( $this->get_cache_name( $key, $group ) );
		}
	}

	/**
	 * Deletes meta cache.
	 *
	 * @param string $type Meta type.
	 * @param int    $id Object ID.
	 * @param mixed  $key Cache key.
	 * @param string $group Cache group.
	 */
	protected function delete_meta_cache( $type, $id, $key, $group = null ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Set callback.
		$callback = 'delete_' . $type . '_meta';

		if ( function_exists( $callback ) ) {
			if ( is_null( $key ) && ! is_null( $group ) ) {

				// Update version.
				$this->update_meta_cache_version( $type, $id, $group );
			} else {

				// Get name.
				$name = $this->get_meta_cache_name( $type, $id, $key, $group );

				// Delete value.
				call_user_func_array( $callback, [ $id, '_transient_' . $name ] );

				// Delete timeout.
				call_user_func_array( $callback, [ $id, '_transient_timeout_' . $name ] );
			}
		}
	}

	/**
	 * Gets transient cache name.
	 *
	 * @param mixed  $key Cache key.
	 * @param string $group Cache group.
	 * @return string
	 */
	protected function get_cache_name( $key, $group = null ) {
		$name = $this->serialize_cache_key( $key );

		if ( ! is_null( $group ) ) {
			$name .= $this->get_cache_version( $group );

			$name = $group . '/' . md5( $name );
		}

		return hp\prefix( $name );
	}

	/**
	 * Gets meta cache name.
	 *
	 * @param string $type Meta type.
	 * @param int    $id Object ID.
	 * @param mixed  $key Cache key.
	 * @param string $group Cache group.
	 * @return string
	 */
	protected function get_meta_cache_name( $type, $id, $key, $group = null ) {
		$name = $this->serialize_cache_key( $key );

		if ( ! is_null( $group ) ) {
			$name .= $this->get_meta_cache_version( $type, $id, $group );

			$name = $group . '/' . md5( $name );
		}

		return hp\prefix( $name );
	}

	/**
	 * Gets transient cache version.
	 *
	 * @param string $group Cache group.
	 * @return string
	 */
	public function get_cache_version( $group ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Get version.
		$version = $this->get_cache( $group . '/version' );

		if ( is_null( $version ) ) {

			// Set version.
			$version = $this->update_cache_version( $group );
		}

		return $version;
	}

	/**
	 * Gets meta cache version.
	 *
	 * @param string $type Meta type.
	 * @param int    $id Object ID.
	 * @param string $group Cache group.
	 * @return string
	 */
	protected function get_meta_cache_version( $type, $id, $group ) {
		$version = $this->get_meta_cache( $type, $id, $group . '/version' );

		if ( is_null( $version ) ) {
			$version = $this->update_meta_cache_version( $type, $id, $group );
		}

		return $version;
	}

	/**
	 * Updates transient cache version.
	 *
	 * @param string $group Cache group.
	 * @return string
	 */
	protected function update_cache_version( $group ) {

		// Get version.
		$version = uniqid( '', true );

		// Get expiration period.
		$expiration = false;

		if ( ! wp_using_ext_object_cache() ) {
			$expiration = WEEK_IN_SECONDS;
		}

		// Set version.
		$this->set_cache( $group . '/version', null, $version, $expiration );

		return $version;
	}

	/**
	 * Updates meta cache version.
	 *
	 * @param string $type Meta type.
	 * @param int    $id Object ID.
	 * @param string $group Cache group.
	 * @return string
	 */
	protected function update_meta_cache_version( $type, $id, $group ) {
		$version = uniqid( '', true );

		$this->set_meta_cache( $type, $id, $group . '/version', null, $version, WEEK_IN_SECONDS );

		return $version;
	}

	/**
	 * Serializes cache key.
	 *
	 * @param mixed $key Cache key.
	 * @return string
	 */
	protected function serialize_cache_key( $key ) {
		if ( is_array( $key ) ) {
			$key = wp_json_encode( $this->sort_cache_key( $key ) );
		}

		return $key;
	}

	/**
	 * Sorts cache key.
	 *
	 * @param mixed $key Cache key.
	 * @return mixed
	 */
	protected function sort_cache_key( $key ) {
		if ( is_array( $key ) ) {
			ksort( $key );

			foreach ( $key as $name => $value ) {
				$key[ $name ] = $this->sort_cache_key( $value );
			}
		}

		return $key;
	}

	/**
	 * Checks cache status.
	 *
	 * @return bool
	 */
	protected function is_cache_enabled() {
		return ! defined( 'HP_CACHE' ) || HP_CACHE;
	}

	/**
	 * Clears meta cache.
	 */
	public function clear_meta_cache() {
		global $wpdb;

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Set types.
		$types = [ 'user', 'post', 'term', 'comment' ];

		foreach ( $types as $type ) {

			// Set callback.
			$callback = 'delete_' . $type . '_meta';

			if ( function_exists( $callback ) ) {

				// Get values.
				$table  = $wpdb->prefix . $type . 'meta';
				$column = $type . '_id';

				$meta_values = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT {$column}, meta_key FROM {$table} WHERE meta_key LIKE %s AND CAST(meta_value AS SIGNED) <= %d;",
						'\_transient\_timeout\_%',
						time()
					),
					ARRAY_A
				);

				// Delete values.
				if ( $meta_values ) {
					foreach ( $meta_values as $meta_value ) {
						call_user_func_array( $callback, [ $meta_value[ $column ], $meta_value['meta_key'] ] );
						call_user_func_array( $callback, [ $meta_value[ $column ], preg_replace( '/^_transient_timeout/', '_transient', $meta_value['meta_key'] ) ] );
					}
				}
			}
		}
	}

	/**
	 * Clears post cache.
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_post_cache( $post_id ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Get post.
		$post = get_post( $post_id );

		// Delete transient cache.
		$this->delete_cache( null, hp\unprefix( $post->post_type ) );

		// Delete meta cache.
		if ( $post->post_author ) {
			$this->delete_user_cache( $post->post_author, null, hp\unprefix( $post->post_type ) );
		}
	}

	/**
	 * Clears post term cache.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $terms Terms.
	 * @param array  $term_taxonomy_ids Term taxonomy IDs.
	 * @param string $taxonomy Taxonomy name.
	 * @param bool   $append Append property.
	 * @param array  $old_term_taxonomy_ids Old term taxonomy IDs.
	 */
	public function clear_post_term_cache( $post_id, $terms, $term_taxonomy_ids, $taxonomy, $append, $old_term_taxonomy_ids ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		if ( strpos( $taxonomy, 'hp_' ) === 0 ) {
			$term_taxonomy_ids = array_unique( array_merge( $term_taxonomy_ids, $old_term_taxonomy_ids ) );

			// Get post type.
			$post_type = get_post_type( $post_id );

			foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {

				// Get term.
				$term = get_term_by( 'term_taxonomy_id', $term_taxonomy_id );

				// Delete meta cache.
				if ( $term ) {
					$this->delete_term_cache( $term->term_id, null, hp\unprefix( $post_type ) );
				}
			}

			// Delete meta cache.
			$this->delete_post_cache( $post_id, null, hp\unprefix( $taxonomy ) );
		}
	}

	/**
	 * Clears term cache.
	 *
	 * @param int $term_id Term ID.
	 */
	public function clear_term_cache( $term_id ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Get term.
		$term = get_term( $term_id );

		// Delete transient cache.
		$this->delete_cache( null, hp\unprefix( $term->taxonomy ) );
	}

	/**
	 * Clears comment cache.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function clear_comment_cache( $comment_id ) {

		// Check status.
		if ( ! $this->is_cache_enabled() ) {
			return;
		}

		// Get comment.
		$comment = get_comment( $comment_id );

		// Delete transient cache.
		$this->delete_cache( null, hp\unprefix( $comment->comment_type ) );

		// Delete meta cache.
		if ( $comment->user_id ) {
			$this->delete_user_cache( $comment->user_id, null, hp\unprefix( $comment->comment_type ) );
		}

		if ( $comment->comment_post_ID ) {
			$this->delete_post_cache( $comment->comment_post_ID, null, hp\unprefix( $comment->comment_type ) );
		}
	}
}
