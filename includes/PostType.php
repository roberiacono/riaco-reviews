<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;

class PostType implements ServiceInterface {

    public function register(): void {
        add_action( 'init',    [ $this, 'register_post_type' ] );
        add_action( 'wp_head', [ $this, 'noindex' ] );
    }

    public function register_post_type(): void {
        register_post_type( 'riaco_review', [
            'labels' => [
                'name'               => __( 'Reviews',        'riaco-reviews' ),
                'singular_name'      => __( 'Review',         'riaco-reviews' ),
                'add_new'            => __( 'Add Review',     'riaco-reviews' ),
                'add_new_item'       => __( 'Add New Review', 'riaco-reviews' ),
                'edit_item'          => __( 'Edit Review',    'riaco-reviews' ),
                'new_item'           => __( 'New Review',     'riaco-reviews' ),
                'view_item'          => __( 'View Review',    'riaco-reviews' ),
                'search_items'       => __( 'Search Reviews', 'riaco-reviews' ),
                'not_found'          => __( 'No reviews found.', 'riaco-reviews' ),
                'not_found_in_trash' => __( 'No reviews found in trash.', 'riaco-reviews' ),
                'menu_name'          => __( 'Reviews',        'riaco-reviews' ),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => false,
            'capability_type'     => 'post',
            'supports'            => [ 'title', 'editor', 'custom-fields' ],
            'has_archive'         => false,
            'exclude_from_search' => true,
            'menu_icon'           => 'dashicons-star-filled',
            'rewrite'             => false,
        ] );
    }

    public function noindex(): void {
        if ( is_singular( 'riaco_review' ) ) {
            echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        }
    }
}
