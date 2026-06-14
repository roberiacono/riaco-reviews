<?php
/**
 * Test factory for RIACO Reviews.
 *
 * Provides static helpers to create plugin-specific data (reviews, sources,
 * products) in known states. The WordPress test harness rolls back every DB
 * transaction between tests, so no explicit teardown is needed for data created
 * here — just call these methods inside test methods or setUp().
 */

namespace RIACO\Reviews\Tests\Helpers;

class PluginTestFactory {

    /**
     * Create a published riaco_review post with optional meta and taxonomy.
     *
     * @param array $args {
     *   @type string $post_title    Post title. Default 'Test Review'.
     *   @type string $post_content  Review body. Default 'Great product!'.
     *   @type string $author_name   _riaco_review_author_name. Default 'Jane Doe'.
     *   @type int    $rating        _riaco_review_rating (1-5). Default 5.
     *   @type string $review_date   _riaco_review_date (Y-m-d). Default today.
     *   @type string $author_avatar _riaco_review_author_avatar URL.
     *   @type string $source_url    _riaco_review_source_url URL.
     *   @type int    $source_term   term_id of riaco_review_source to assign.
     *   @type int    $product_term  term_id of riaco_review_product to assign.
     * }
     * @return int Post ID.
     */
    public static function create_review( array $args = [] ): int {
        $defaults = [
            'post_title'   => 'Test Review',
            'post_content' => 'Great product!',
            'author_name'  => 'Jane Doe',
            'rating'       => 5,
            'review_date'  => gmdate( 'Y-m-d' ),
            'author_avatar' => '',
            'source_url'   => '',
            'source_term'  => 0,
            'product_term' => 0,
        ];
        $args = array_merge( $defaults, $args );

        $post_id = wp_insert_post( [
            'post_type'    => 'riaco_review',
            'post_status'  => 'publish',
            'post_title'   => $args['post_title'],
            'post_content' => $args['post_content'],
        ] );

        if ( is_wp_error( $post_id ) ) {
            throw new \RuntimeException( 'PluginTestFactory: could not create review: ' . $post_id->get_error_message() );
        }

        update_post_meta( $post_id, '_riaco_review_author_name',   $args['author_name'] );
        update_post_meta( $post_id, '_riaco_review_rating',        (int) $args['rating'] );
        update_post_meta( $post_id, '_riaco_review_date',          $args['review_date'] );

        if ( $args['author_avatar'] ) {
            update_post_meta( $post_id, '_riaco_review_author_avatar', $args['author_avatar'] );
        }
        if ( $args['source_url'] ) {
            update_post_meta( $post_id, '_riaco_review_source_url', $args['source_url'] );
        }
        if ( $args['source_term'] ) {
            wp_set_post_terms( $post_id, [ (int) $args['source_term'] ], 'riaco_review_source' );
        }
        if ( $args['product_term'] ) {
            wp_set_post_terms( $post_id, [ (int) $args['product_term'] ], 'riaco_review_product' );
        }

        return (int) $post_id;
    }

    /**
     * Create a riaco_review_source term with an optional logo URL.
     *
     * @param string $name     Term name.
     * @param string $logo_url Logo URL stored in _riaco_source_image.
     * @return int Term ID.
     */
    public static function create_source( string $name, string $logo_url = '' ): int {
        $result = wp_insert_term( $name, 'riaco_review_source' );

        if ( is_wp_error( $result ) ) {
            throw new \RuntimeException( 'PluginTestFactory: could not create source: ' . $result->get_error_message() );
        }

        $term_id = (int) $result['term_id'];

        if ( $logo_url ) {
            update_term_meta( $term_id, '_riaco_source_image', $logo_url );
        }

        return $term_id;
    }

    /**
     * Create a riaco_review_product term with optional meta.
     *
     * @param string $name Term name.
     * @param array  $meta {
     *   @type string $url  _riaco_product_url
     *   @type string $type _riaco_product_type (default 'Thing')
     * }
     * @return int Term ID.
     */
    public static function create_product( string $name, array $meta = [] ): int {
        $result = wp_insert_term( $name, 'riaco_review_product' );

        if ( is_wp_error( $result ) ) {
            throw new \RuntimeException( 'PluginTestFactory: could not create product: ' . $result->get_error_message() );
        }

        $term_id = (int) $result['term_id'];

        if ( ! empty( $meta['url'] ) ) {
            update_term_meta( $term_id, '_riaco_product_url', $meta['url'] );
        }

        $type = $meta['type'] ?? 'Thing';
        update_term_meta( $term_id, '_riaco_product_type', $type );

        return $term_id;
    }

    /**
     * Build a meta array in the same shape as templates/reviews.php produces
     * for a given post, so JSON-LD and card tests can work without a full render.
     */
    public static function build_meta( int $post_id ): array {
        $source_image = '';
        $source_name  = '';
        $source_url   = get_post_meta( $post_id, '_riaco_review_source_url', true );
        $s_terms      = get_the_terms( $post_id, 'riaco_review_source' );
        if ( $s_terms && ! is_wp_error( $s_terms ) ) {
            $source_image = get_term_meta( $s_terms[0]->term_id, '_riaco_source_image', true );
            $source_name  = $s_terms[0]->name;
        }

        $product_name = '';
        $product_url  = '';
        $product_type = 'Thing';
        $p_terms      = get_the_terms( $post_id, 'riaco_review_product' );
        if ( $p_terms && ! is_wp_error( $p_terms ) ) {
            $product_name = $p_terms[0]->name;
            $product_url  = get_term_meta( $p_terms[0]->term_id, '_riaco_product_url', true );
            $product_type = get_term_meta( $p_terms[0]->term_id, '_riaco_product_type', true ) ?: 'Thing';
        }

        return [
            'author_name'   => get_post_meta( $post_id, '_riaco_review_author_name',   true ),
            'author_avatar' => get_post_meta( $post_id, '_riaco_review_author_avatar', true ),
            'rating'        => (int) get_post_meta( $post_id, '_riaco_review_rating',  true ),
            'review_date'   => get_post_meta( $post_id, '_riaco_review_date',          true ),
            'source_image'  => $source_image,
            'source_name'   => $source_name,
            'source_url'    => $source_url,
            'product_name'  => $product_name,
            'product_url'   => $product_url,
            'product_type'  => $product_type,
        ];
    }
}
