<?php
/**
 * HivePress uninstaller.
 *
 * @package HivePress
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $wpdb;

if ( defined( 'HP_DELETE_DATA' ) && HP_DELETE_DATA ) {

	// Trash pages.
	$page_ids = $wpdb->get_results( "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'hp\_page\_%';", ARRAY_A );

	foreach ( wp_list_pluck( $page_ids, 'option_value' ) as $page_id ) {
		wp_trash_post( absint( $page_id ) );
	}

	// Delete posts.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type LIKE 'hp\_%';" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'hp\_%';" );

	// Delete comments.
	$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_type LIKE 'hp\_%';" );
	$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE 'hp\_%';" );

	// Delete terms.
	$wpdb->query( "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy LIKE 'hp\_%';" );
	$wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE 'hp\_%';" );

	$wpdb->query( "DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} posts ON posts.ID = tr.object_id WHERE posts.ID IS NULL;" );
	$wpdb->query( "DELETE t FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.term_id IS NULL;" );
	$wpdb->query( "DELETE tm FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id WHERE tt.term_id IS NULL;" );

	// Delete user meta.
	$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'hp\_%';" );

	// Delete options.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hp\_%';" );

	// Flush cache.
	wp_cache_flush();
}
