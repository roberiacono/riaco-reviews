<?php

namespace RIACO\Reviews\Tests\Integration;

use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Tests for taxonomy registration, term meta CRUD, and term assignment.
 */
class TaxonomyTest extends WP_UnitTestCase {

    public function test_source_taxonomy_registered(): void {
        $this->assertTrue( taxonomy_exists( 'riaco_review_source' ) );
    }

    public function test_product_taxonomy_registered(): void {
        $this->assertTrue( taxonomy_exists( 'riaco_review_product' ) );
    }

    public function test_source_term_meta_saved_and_retrieved(): void {
        $term_id  = PluginTestFactory::create_source( 'WordPress.org', 'https://example.com/logo.svg' );
        $retrieved = get_term_meta( $term_id, '_riaco_source_image', true );

        $this->assertSame( 'https://example.com/logo.svg', $retrieved );
    }

    public function test_product_term_meta_saved_and_retrieved(): void {
        $term_id = PluginTestFactory::create_product( 'My SaaS', [
            'url'  => 'https://mysaas.example.com',
            'type' => 'SoftwareApplication',
        ] );

        $this->assertSame( 'https://mysaas.example.com', get_term_meta( $term_id, '_riaco_product_url', true ) );
        $this->assertSame( 'SoftwareApplication',         get_term_meta( $term_id, '_riaco_product_type', true ) );
    }

    public function test_assign_source_to_review_and_retrieve(): void {
        $source_id = PluginTestFactory::create_source( 'G2' );
        $post_id   = wp_insert_post( [
            'post_type'   => 'riaco_review',
            'post_status' => 'publish',
            'post_title'  => 'Source Test',
        ] );

        wp_set_post_terms( $post_id, [ $source_id ], 'riaco_review_source' );
        $terms = wp_get_post_terms( $post_id, 'riaco_review_source', [ 'fields' => 'ids' ] );

        $this->assertSame( [ $source_id ], $terms );
    }

    public function test_assign_product_to_review_and_retrieve(): void {
        $prod_id = PluginTestFactory::create_product( 'My Book', [ 'type' => 'Book' ] );
        $post_id = wp_insert_post( [
            'post_type'   => 'riaco_review',
            'post_status' => 'publish',
            'post_title'  => 'Product Test',
        ] );

        wp_set_post_terms( $post_id, [ $prod_id ], 'riaco_review_product' );
        $terms = wp_get_post_terms( $post_id, 'riaco_review_product', [ 'fields' => 'ids' ] );

        $this->assertSame( [ $prod_id ], $terms );
    }

    public function test_multiple_source_terms_exist_independently(): void {
        $id1 = PluginTestFactory::create_source( 'Trustpilot' );
        $id2 = PluginTestFactory::create_source( 'Capterra' );

        $this->assertNotSame( $id1, $id2 );

        $t1 = get_term( $id1, 'riaco_review_source' );
        $t2 = get_term( $id2, 'riaco_review_source' );

        $this->assertSame( 'Trustpilot', $t1->name );
        $this->assertSame( 'Capterra',   $t2->name );
    }
}
