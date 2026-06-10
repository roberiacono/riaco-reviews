<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

class Renderer {

    public static function render( array $atts ): string {
        $atts = wp_parse_args( $atts, [
            'count'            => 6,
            'layout'           => 'grid',
            'show_author_name' => true,
            'show_avatar'      => true,
            'show_date'        => false,
            'show_rating'      => true,
            'show_source'      => true,
            'show_tag'         => true,
            'orderby'          => 'date',
            'order'            => 'DESC',
        ] );

        // Sanitize
        $atts['count']  = max( 1, absint( $atts['count'] ) );
        $atts['layout'] = in_array( $atts['layout'], [ 'grid', 'masonry' ], true )
            ? $atts['layout'] : 'grid';
        $atts['orderby'] = in_array( $atts['orderby'], [ 'date', 'rating', 'rand' ], true )
            ? $atts['orderby'] : 'date';
        $atts['order'] = in_array( strtoupper( $atts['order'] ), [ 'ASC', 'DESC' ], true )
            ? strtoupper( $atts['order'] ) : 'DESC';

        foreach ( [ 'show_author_name', 'show_avatar', 'show_date', 'show_rating', 'show_source', 'show_tag' ] as $key ) {
            $atts[ $key ] = filter_var( $atts[ $key ], FILTER_VALIDATE_BOOLEAN );
        }

        // Build query
        $query_args = [
            'post_type'      => 'riaco_review',
            'posts_per_page' => $atts['count'],
            'post_status'    => 'publish',
            'orderby'        => $atts['orderby'] === 'rating' ? 'meta_value_num' : $atts['orderby'],
            'order'          => $atts['order'],
            'no_found_rows'  => true,
        ];

        if ( $atts['orderby'] === 'rating' ) {
            $query_args['meta_key'] = '_riaco_review_rating';
        }

        $reviews = new \WP_Query( $query_args );

        ob_start();
        include RIACO_REVIEWS_DIR . 'templates/reviews.php';
        wp_reset_postdata();

        return ob_get_clean();
    }
}
