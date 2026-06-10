<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

class Renderer {

    public static function render( array $atts ): string {
        $atts = wp_parse_args( $atts, [
            'count'             => 6,
            'layout'            => 'grid',
            'card_style'        => 'default',
            'show_author_name'  => true,
            'show_avatar'       => true,
            'show_date'         => false,
            'show_rating'       => true,
            'show_source'       => true,
            'show_tag'          => true,
            'show_title'        => true,
            'min_width'         => 280,
            'orderby'           => 'date',
            'order'             => 'DESC',
            'card_bg'           => '',
            'card_text_color'   => '',
            'card_border_color' => '',
            'star_color'        => '',
            'font_size'         => '',
            'line_height'       => '',
            'tag_bg'            => '',
            'tag_text_color'    => '',
        ] );

        // Sanitize display options
        $atts['count']  = max( 1, absint( $atts['count'] ) );

        $allowed_layouts     = apply_filters( 'riaco_reviews_layouts',         [ 'grid', 'masonry' ] );
        $allowed_card_styles = apply_filters( 'riaco_reviews_card_styles',     [ 'default', 'modern' ] );
        $allowed_orderby     = apply_filters( 'riaco_reviews_orderby_options', [ 'date', 'rating', 'rand' ] );

        $atts['layout']     = in_array( $atts['layout'],     $allowed_layouts,     true ) ? $atts['layout']     : 'grid';
        $atts['card_style'] = in_array( $atts['card_style'], $allowed_card_styles, true ) ? $atts['card_style'] : 'default';
        $atts['orderby']    = in_array( $atts['orderby'],    $allowed_orderby,     true ) ? $atts['orderby']    : 'date';
        $atts['order'] = in_array( strtoupper( $atts['order'] ), [ 'ASC', 'DESC' ], true )
            ? strtoupper( $atts['order'] ) : 'DESC';

        foreach ( [ 'show_author_name', 'show_avatar', 'show_date', 'show_rating', 'show_source', 'show_tag', 'show_title' ] as $key ) {
            $atts[ $key ] = filter_var( $atts[ $key ], FILTER_VALIDATE_BOOLEAN );
        }

        // Sanitize colours (hex only)
        foreach ( [ 'card_bg', 'card_text_color', 'card_border_color', 'star_color', 'tag_bg', 'tag_text_color' ] as $key ) {
            $atts[ $key ] = ! empty( $atts[ $key ] ) ? ( sanitize_hex_color( $atts[ $key ] ) ?? '' ) : '';
        }

        // Sanitize typography (positive floats within reasonable bounds)
        $font_size   = (float) $atts['font_size'];
        $line_height = (float) $atts['line_height'];
        $atts['font_size']   = ( $font_size > 0 && $font_size <= 5 ) ? $font_size : '';
        $atts['line_height'] = ( $line_height > 0 && $line_height <= 5 ) ? $line_height : '';

        $atts = apply_filters( 'riaco_reviews_atts', $atts );

        // Build CSS custom-property string for per-block colour/typography overrides
        $style_parts = [];
        $color_vars  = [
            'card_bg'           => '--riaco-card-bg',
            'card_text_color'   => '--riaco-card-text',
            'card_border_color' => '--riaco-card-border',
            'star_color'        => '--riaco-star-color',
            'tag_bg'            => '--riaco-tag-bg',
            'tag_text_color'    => '--riaco-tag-text',
        ];
        foreach ( $color_vars as $att_key => $css_var ) {
            if ( ! empty( $atts[ $att_key ] ) ) {
                $style_parts[] = $css_var . ':' . $atts[ $att_key ];
            }
        }
        if ( '' !== $atts['font_size'] ) {
            $style_parts[] = '--riaco-font-size:' . $atts['font_size'] . 'rem';
        }
        if ( '' !== $atts['line_height'] ) {
            $style_parts[] = '--riaco-line-height:' . $atts['line_height'];
        }
        $min_width = absint( $atts['min_width'] );
        if ( $min_width > 0 && $min_width !== 280 ) {
            $style_parts[] = '--riaco-card-min-width:' . $min_width . 'px';
        }
        $atts['custom_style'] = $style_parts ? implode( ';', $style_parts ) : '';

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

        $query_args = apply_filters( 'riaco_reviews_query_args', $query_args, $atts );

        $reviews = new \WP_Query( $query_args );

        ob_start();
        include RIACO_REVIEWS_DIR . 'templates/reviews.php';
        wp_reset_postdata();

        return ob_get_clean();
    }
}
