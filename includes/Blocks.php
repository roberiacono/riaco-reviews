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
        add_action( 'init',                 [ $this, 'register_block' ] );
        add_action( 'enqueue_block_assets', [ $this, 'enqueue_frontend_assets' ] );
    }

    public function register_block(): void {
        $build_dir = RIACO_REVIEWS_DIR . 'build/reviews-block';
        if ( ! file_exists( $build_dir . '/block.json' ) ) return;

        register_block_type( $build_dir, [
            'render_callback' => [ $this, 'render' ],
        ] );
    }

    public function render( array $attributes ): string {
        $atts = [
            'count'            => $attributes['count']          ?? 6,
            'layout'           => $attributes['layout']         ?? 'grid',
            'card_style'       => $attributes['cardStyle']      ?? 'default',
            'show_author_name' => $attributes['showAuthorName'] ?? true,
            'show_avatar'      => $attributes['showAvatar']     ?? true,
            'show_date'        => $attributes['showDate']        ?? false,
            'show_rating'      => $attributes['showRating']     ?? true,
            'show_source'      => $attributes['showSource']     ?? true,
            'show_tag'         => $attributes['showTag']         ?? true,
            'orderby'          => $attributes['orderby']        ?? 'date',
            'order'            => $attributes['order']          ?? 'DESC',
        ];

        return Renderer::render( $atts );
    }

    public function enqueue_frontend_assets(): void {
        if ( is_admin() ) return;
        if ( ! has_block( 'riaco-reviews/reviews-block' ) ) return;

        wp_enqueue_style(
            'riaco-reviews',
            plugin_dir_url( $this->file ) . 'assets/dist/reviews.css',
            [],
            $this->version
        );
    }
}
