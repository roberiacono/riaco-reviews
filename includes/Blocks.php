<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;
use RIACO\Reviews\Renderer;

class Blocks implements ServiceInterface {

    private string $file;
    private string $version;

    public function __construct( string $file, string $version ) {
        $this->file    = $file;
        $this->version = $version;
    }

    public function register(): void {
        add_action( 'init', [ $this, 'register_block' ] );
    }

    public function register_block(): void {
        $build_dir = RIACO_REVIEWS_DIR . 'build/reviews-block';
        if ( ! file_exists( $build_dir . '/block.json' ) ) return;

        register_block_type( $build_dir, [
            'render_callback' => [ $this, 'render' ],
        ] );

        // Loads reviews.css on the frontend (only when block is present) AND inside
        // the editor canvas iframe — the correct hook for ServerSideRender previews.
        wp_enqueue_block_style( 'riaco-reviews/reviews-block', [
            'handle' => 'riaco-reviews',
            'src'    => plugin_dir_url( $this->file ) . 'assets/dist/reviews.css',
            'ver'    => $this->version,
            'path'   => RIACO_REVIEWS_DIR . 'assets/dist/reviews.css',
        ] );

        add_action( 'enqueue_block_editor_assets', [ $this, 'localize_editor_data' ] );
    }

    public function localize_editor_data(): void {
        $terms        = get_terms( [ 'taxonomy' => 'riaco_review_product', 'hide_empty' => false ] );
        $product_data = [];
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $product_data[] = [ 'slug' => $term->slug, 'name' => $term->name ];
            }
        }
        wp_add_inline_script(
            'riaco-reviews-reviews-block-editor-script',
            'window.riacoReviewsData = ' . wp_json_encode( [ 'products' => $product_data ] ) . ';',
            'before'
        );
    }

    public function render( array $attributes ): string {
        $atts = [
            'count'              => $attributes['count']            ?? 6,
            'layout'             => $attributes['layout']           ?? 'grid',
            'card_style'         => $attributes['cardStyle']        ?? 'default',
            'heading_level'      => $attributes['headingLevel']     ?? 3,
            'show_author_name'   => $attributes['showAuthorName']   ?? true,
            'show_avatar'        => $attributes['showAvatar']       ?? true,
            'show_date'          => $attributes['showDate']         ?? false,
            'show_rating'        => $attributes['showRating']       ?? true,
            'show_source'        => $attributes['showSource']       ?? true,
            'show_product'       => $attributes['showProduct']      ?? true,
            'show_title'         => $attributes['showTitle']        ?? true,
            'show_shadow'        => $attributes['showShadow']       ?? true,
            'min_width'          => $attributes['minWidth']         ?? 300,
            'orderby'            => $attributes['orderby']          ?? 'date',
            'order'              => $attributes['order']            ?? 'DESC',
            'product'            => $attributes['productFilter']    ?? '',
            'card_bg'            => $attributes['cardBg']           ?? '',
            'card_text_color'    => $attributes['cardTextColor']    ?? '',
            'card_border_color'  => $attributes['cardBorderColor']  ?? '',
            'star_color'         => $attributes['starColor']        ?? '',
            'font_size'          => $attributes['fontSize']         ?? '',
            'line_height'        => $attributes['lineHeight']       ?? '',
            'product_bg'         => $attributes['productBg']        ?? '',
            'product_text_color' => $attributes['productTextColor'] ?? '',
        ];

        $atts = apply_filters( 'riaco_reviews_block_render_atts', $atts, $attributes );

        return Renderer::render( $atts );
    }
}
