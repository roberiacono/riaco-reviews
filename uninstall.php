<?php
/**
 * Runs when the plugin is deleted via WP Admin.
 * Removes all riaco_review posts and plugin options.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Delete all riaco_review posts and their meta
$posts = get_posts( [
    'post_type'      => 'riaco_review',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
] );

foreach ( $posts as $post_id ) {
    wp_delete_post( $post_id, true );
}

// Clean up options
$options = [
    'riaco_reviews_version',
];
foreach ( $options as $option ) {
    delete_option( $option );
}
