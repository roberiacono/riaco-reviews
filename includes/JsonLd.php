<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;

class JsonLd implements ServiceInterface {

    private array $reviews = [];

    public function register(): void {
        add_action( 'riaco_reviews_after_card', [ $this, 'collect' ], 10, 3 );
        add_action( 'wp_footer',                [ $this, 'output' ] );
    }

    public function collect( int $post_id, array $meta, array $atts ): void {
        $data = $this->build( $post_id, $meta );
        $data = apply_filters( 'riaco_reviews_json_ld_data', $data, $post_id, $meta, $atts );
        if ( $data ) {
            $this->reviews[] = $data;
        }
    }

    public function output(): void {
        if ( empty( $this->reviews ) ) return;

        if ( count( $this->reviews ) === 1 ) {
            $payload = array_merge( [ '@context' => 'https://schema.org' ], $this->reviews[0] );
        } else {
            $payload = [
                '@context' => 'https://schema.org',
                '@graph'   => $this->reviews,
            ];
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }

    private function build( int $post_id, array $meta ): array {
        $data = [ '@type' => 'Review' ];

        $body = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
        if ( '' !== $body ) {
            $data['reviewBody'] = $body;
        }

        $rating = (int) $meta['rating'];
        if ( $rating >= 1 && $rating <= 5 ) {
            $data['reviewRating'] = [
                '@type'       => 'Rating',
                'ratingValue' => $rating,
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }

        if ( ! empty( $meta['author_name'] ) ) {
            $data['author'] = [
                '@type' => 'Person',
                'name'  => $meta['author_name'],
            ];
        }

        if ( ! empty( $meta['review_date'] ) ) {
            $data['datePublished'] = $meta['review_date'];
        }

        if ( ! empty( $meta['source_url'] ) ) {
            $data['url'] = $meta['source_url'];
        }

        $item_name = ! empty( $meta['tag_name'] ) ? $meta['tag_name'] : get_the_title( $post_id );
        $item_type = ! empty( $meta['tag_type'] ) ? $meta['tag_type'] : 'Thing';

        $item = [
            '@type' => $item_type,
            'name'  => $item_name,
        ];
        if ( ! empty( $meta['tag_url'] ) ) {
            $item['url'] = $meta['tag_url'];
        }
        $data['itemReviewed'] = $item;

        return $data;
    }
}
