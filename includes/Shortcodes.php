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
        $atts = shortcode_atts( [
            'count'             => 6,
            'layout'            => 'grid',
            'card_style'        => 'default',
            'show_title'        => 1,
            'show_author_name'  => 1,
            'show_avatar'       => 1,
            'show_date'         => 0,
            'show_rating'       => 1,
            'show_source'       => 1,
            'show_tag'          => 1,
            'show_shadow'       => 1,
            'orderby'           => 'date',
            'order'             => 'DESC',
            'tag'               => '',
            'min_width'         => 280,
            'card_bg'           => '',
            'card_text_color'   => '',
            'card_border_color' => '',
            'star_color'        => '',
            'tag_bg'            => '',
            'tag_text_color'    => '',
            'font_size'         => '',
            'line_height'       => '',
        ], $atts, 'riaco_reviews' );

        $this->enqueue_assets();

        return Renderer::render( [
            'count'             => absint( $atts['count'] ),
            'layout'            => sanitize_key( $atts['layout'] ),
            'card_style'        => sanitize_key( $atts['card_style'] ),
            'show_title'        => (bool) $atts['show_title'],
            'show_author_name'  => (bool) $atts['show_author_name'],
            'show_avatar'       => (bool) $atts['show_avatar'],
            'show_date'         => (bool) $atts['show_date'],
            'show_rating'       => (bool) $atts['show_rating'],
            'show_source'       => (bool) $atts['show_source'],
            'show_tag'          => (bool) $atts['show_tag'],
            'show_shadow'       => (bool) $atts['show_shadow'],
            'orderby'           => sanitize_key( $atts['orderby'] ),
            'order'             => in_array( strtoupper( $atts['order'] ), [ 'ASC', 'DESC' ], true )
                                    ? strtoupper( $atts['order'] ) : 'DESC',
            'tag'               => sanitize_text_field( $atts['tag'] ),
            'min_width'         => absint( $atts['min_width'] ),
            'card_bg'           => $atts['card_bg'],
            'card_text_color'   => $atts['card_text_color'],
            'card_border_color' => $atts['card_border_color'],
            'star_color'        => $atts['star_color'],
            'tag_bg'            => $atts['tag_bg'],
            'tag_text_color'    => $atts['tag_text_color'],
            'font_size'         => $atts['font_size'],
            'line_height'       => $atts['line_height'],
        ] );
    }

    public function enqueue_assets(): void {
        wp_enqueue_style(
            'riaco-reviews',
            plugin_dir_url( $this->file ) . 'assets/dist/reviews.css',
            [],
            $this->version
        );
    }
}
