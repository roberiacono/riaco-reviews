<?php
/**
 * Runs when the plugin is deleted via WP Admin.
 * Removes all riaco_review posts, taxonomy terms, term meta, and plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to the uninstall script context; no global namespace conflict.
// Register taxonomies so wp_delete_term() can operate on them.
register_taxonomy( 'riaco_review_source', 'riaco_review' );
register_taxonomy( 'riaco_review_product', 'riaco_review' );

// Delete taxonomy terms and their meta (wp_delete_term() handles term meta cleanup).
foreach ( [ 'riaco_review_source', 'riaco_review_product' ] as $taxonomy ) {
    $term_ids = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids' ] );
    if ( is_array( $term_ids ) ) {
        foreach ( $term_ids as $term_id ) {
            wp_delete_term( (int) $term_id, $taxonomy );
        }
    }
}

// Delete all riaco_review posts and their meta.
$posts = get_posts( [
    'post_type'      => 'riaco_review',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
] );

foreach ( $posts as $post_id ) {
    wp_delete_post( $post_id, true );
}

// Clean up options.
foreach ( [ 'riaco_reviews_version', 'riaco_reviews_db_version' ] as $option ) {
    delete_option( $option );
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
