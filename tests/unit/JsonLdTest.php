<?php

namespace RIACO\Reviews\Tests\Unit;

use RIACO\Reviews\JsonLd;
use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Tests for JsonLd — schema structure, filter hook, and output validity.
 */
class JsonLdTest extends WP_UnitTestCase {

    private JsonLd $json_ld;

    public function setUp(): void {
        parent::setUp();
        $this->json_ld = new JsonLd();
        remove_all_filters( 'riaco_reviews_json_ld_data' );
    }

    // -------------------------------------------------------------------------
    // collect() / output()
    // -------------------------------------------------------------------------

    public function test_single_review_produces_review_type(): void {
        $post_id = PluginTestFactory::create_review( [ 'author_name' => 'Alice', 'rating' => 4 ] );
        $meta    = PluginTestFactory::build_meta( $post_id );
        $atts    = [];

        $this->json_ld->collect( $post_id, $meta, $atts );

        ob_start();
        $this->json_ld->output();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'application/ld+json', $output );
        $decoded = $this->parse_json_ld( $output );
        $this->assertNotNull( $decoded );
        $this->assertSame( 'Review', $decoded['@type'] );
        $this->assertSame( 'https://schema.org', $decoded['@context'] );
    }

    public function test_multiple_reviews_use_graph(): void {
        $ids = [
            PluginTestFactory::create_review( [ 'post_title' => 'R1', 'rating' => 5 ] ),
            PluginTestFactory::create_review( [ 'post_title' => 'R2', 'rating' => 3 ] ),
        ];
        foreach ( $ids as $id ) {
            $this->json_ld->collect( $id, PluginTestFactory::build_meta( $id ), [] );
        }

        ob_start();
        $this->json_ld->output();
        $output = ob_get_clean();

        $decoded = $this->parse_json_ld( $output );
        $this->assertArrayHasKey( '@graph', $decoded );
        $this->assertCount( 2, $decoded['@graph'] );
    }

    public function test_rating_schema_values_correct(): void {
        $post_id = PluginTestFactory::create_review( [ 'rating' => 4 ] );
        $meta    = PluginTestFactory::build_meta( $post_id );

        $this->json_ld->collect( $post_id, $meta, [] );

        ob_start();
        $this->json_ld->output();
        $decoded = $this->parse_json_ld( ob_get_clean() );

        $rating = $decoded['reviewRating'];
        $this->assertSame( 'Rating', $rating['@type'] );
        $this->assertSame( 4, $rating['ratingValue'] );
        $this->assertSame( 5, $rating['bestRating'] );
        $this->assertSame( 1, $rating['worstRating'] );
    }

    public function test_review_without_rating_omits_rating_schema(): void {
        $post_id = PluginTestFactory::create_review( [ 'rating' => 0 ] );
        // Override meta to have no rating.
        $meta = PluginTestFactory::build_meta( $post_id );
        $meta['rating'] = 0;

        $this->json_ld->collect( $post_id, $meta, [] );

        ob_start();
        $this->json_ld->output();
        $decoded = $this->parse_json_ld( ob_get_clean() );

        $this->assertArrayNotHasKey( 'reviewRating', $decoded );
    }

    public function test_item_reviewed_uses_product_meta(): void {
        $prod_id = PluginTestFactory::create_product( 'Awesome Plugin', [
            'url'  => 'https://example.com/plugin',
            'type' => 'SoftwareApplication',
        ] );
        $post_id = PluginTestFactory::create_review( [ 'product_term' => $prod_id ] );
        $meta    = PluginTestFactory::build_meta( $post_id );

        $this->json_ld->collect( $post_id, $meta, [] );

        ob_start();
        $this->json_ld->output();
        $decoded = $this->parse_json_ld( ob_get_clean() );

        $item = $decoded['itemReviewed'];
        $this->assertSame( 'SoftwareApplication', $item['@type'] );
        $this->assertSame( 'Awesome Plugin', $item['name'] );
        $this->assertSame( 'https://example.com/plugin', $item['url'] );
    }

    public function test_json_ld_filter_fires_and_mutates_data(): void {
        add_filter( 'riaco_reviews_json_ld_data', function ( $data ) {
            $data['custom_field'] = 'injected';
            return $data;
        } );

        $post_id = PluginTestFactory::create_review();
        $this->json_ld->collect( $post_id, PluginTestFactory::build_meta( $post_id ), [] );

        ob_start();
        $this->json_ld->output();
        $decoded = $this->parse_json_ld( ob_get_clean() );

        $this->assertArrayHasKey( 'custom_field', $decoded );
        $this->assertSame( 'injected', $decoded['custom_field'] );
    }

    public function test_output_is_valid_json(): void {
        $post_id = PluginTestFactory::create_review();
        $this->json_ld->collect( $post_id, PluginTestFactory::build_meta( $post_id ), [] );

        ob_start();
        $this->json_ld->output();
        $output = ob_get_clean();

        // Extract JSON from the <script> tag.
        preg_match( '/<script[^>]*>(.*?)<\/script>/s', $output, $matches );
        $this->assertNotEmpty( $matches );
        $decoded = json_decode( $matches[1], true );
        $this->assertNotNull( $decoded, 'JSON-LD output is not valid JSON: ' . json_last_error_msg() );
    }

    public function test_output_empty_when_no_reviews_collected(): void {
        ob_start();
        $this->json_ld->output();
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function parse_json_ld( string $html ): ?array {
        preg_match( '/<script[^>]*>(.*?)<\/script>/s', $html, $matches );
        if ( empty( $matches[1] ) ) return null;
        return json_decode( $matches[1], true );
    }
}
