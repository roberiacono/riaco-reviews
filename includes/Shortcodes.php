<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;
use RIACO\Reviews\Renderer;

class Shortcodes implements ServiceInterface {

    private string $file;
    private string $version;

    public function __construct( string $file, string $version ) {
        $this->file    = $file;
        $this->version = $version;
    }

    public function register(): void {
        add_shortcode( 'riaco_reviews', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( $atts ): string {
        $sc_defaults = array_map( fn( $v ) => is_bool( $v ) ? (int) $v : $v, Renderer::defaults() );
        $atts = shortcode_atts( $sc_defaults, $atts, 'riaco_reviews' );

        $this->enqueue_assets();

        return Renderer::render( [
            'count'             => absint( $atts['count'] ),
            'layout'            => sanitize_key( $atts['layout'] ),
            'card_style'        => sanitize_key( $atts['card_style'] ),
            'heading_level'     => max( 2, min( 6, absint( $atts['heading_level'] ) ) ),
            'show_title'        => (bool) $atts['show_title'],
            'show_author_name'  => (bool) $atts['show_author_name'],
            'show_avatar'       => (bool) $atts['show_avatar'],
            'show_date'         => (bool) $atts['show_date'],
            'show_rating'        => (bool) $atts['show_rating'],
            'show_source'        => (bool) $atts['show_source'],
            'show_product'       => (bool) $atts['show_product'],
            'show_shadow'        => (bool) $atts['show_shadow'],
            'orderby'            => sanitize_key( $atts['orderby'] ),
            'order'              => in_array( strtoupper( $atts['order'] ), [ 'ASC', 'DESC' ], true )
                                    ? strtoupper( $atts['order'] ) : 'DESC',
            'product'            => sanitize_text_field( $atts['product'] ),
            'min_width'          => absint( $atts['min_width'] ),
            'card_bg'            => $atts['card_bg'],
            'card_text_color'    => $atts['card_text_color'],
            'card_border_color'  => $atts['card_border_color'],
            'star_color'         => $atts['star_color'],
            'product_bg'         => $atts['product_bg'],
            'product_text_color' => $atts['product_text_color'],
            'font_size'          => $atts['font_size'],
            'line_height'        => $atts['line_height'],
        ] );
    }

    public function enqueue_assets(): void {
        wp_enqueue_style(
            'riaco-reviews',
            plugin_dir_url( $this->file ) . 'assets/dist/reviews.css',
            [],
            RIACO_REVIEWS_VERSION
        );
    }
}
