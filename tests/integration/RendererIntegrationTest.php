<?php

namespace RIACO\Reviews\Tests\Integration;

use RIACO\Reviews\Renderer;
use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Integration tests for Renderer::render() that require a live DB.
 * Covers query behaviour, hook firing, template filters, and result ordering.
 */
class RendererIntegrationTest extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        remove_all_filters( 'riaco_reviews_before_loop' );
        remove_all_filters( 'riaco_reviews_after_loop' );
        remove_all_filters( 'riaco_reviews_before_card' );
        remove_all_filters( 'riaco_reviews_after_card' );
        remove_all_filters( 'riaco_reviews_card_meta' );
        remove_all_filters( 'riaco_reviews_card_template_path' );
        remove_all_filters( 'riaco_reviews_query_args' );
    }

    // -------------------------------------------------------------------------
    // Query correctness
    // -------------------------------------------------------------------------

    public function test_render_only_returns_published_reviews(): void {
        PluginTestFactory::create_review( [ 'post_title' => 'Published Review' ] );
        wp_insert_post( [
            'post_type'    => 'riaco_review',
            'post_status'  => 'draft',
            'post_title'   => 'Draft Review',
            'post_content' => 'Not shown.',
        ] );

        $html = Renderer::render( [] );

        $this->assertStringContainsString( 'Published Review', $html );
        $this->assertStringNotContainsString( 'Draft Review', $html );
    }

    public function test_count_limits_number_of_cards(): void {
        for ( $i = 1; $i <= 6; $i++ ) {
            PluginTestFactory::create_review( [ 'post_title' => "Review $i" ] );
        }

        $html = Renderer::render( [ 'count' => 3 ] );
        $this->assertSame( 3, substr_count( $html, '<article' ) );
    }

    public function test_product_filter_returns_only_matching_reviews(): void {
        $prod_a = PluginTestFactory::create_product( 'Widget' );
        $prod_b = PluginTestFactory::create_product( 'Gadget' );

        PluginTestFactory::create_review( [ 'post_title' => 'Widget Review', 'product_term' => $prod_a ] );
        PluginTestFactory::create_review( [ 'post_title' => 'Gadget Review', 'product_term' => $prod_b ] );
        PluginTestFactory::create_review( [ 'post_title' => 'No Product Review' ] );

        $term = get_term( $prod_a, 'riaco_review_product' );
        $html = Renderer::render( [ 'product' => $term->slug ] );

        $this->assertStringContainsString( 'Widget Review',    $html );
        $this->assertStringNotContainsString( 'Gadget Review', $html );
        $this->assertStringNotContainsString( 'No Product Review', $html );
    }

    public function test_orderby_rating_desc_orders_correctly(): void {
        PluginTestFactory::create_review( [ 'post_title' => 'Low Rating',  'rating' => 1 ] );
        PluginTestFactory::create_review( [ 'post_title' => 'High Rating', 'rating' => 5 ] );
        PluginTestFactory::create_review( [ 'post_title' => 'Mid Rating',  'rating' => 3 ] );

        $html = Renderer::render( [ 'orderby' => 'rating', 'order' => 'DESC' ] );

        $pos_high = strpos( $html, 'High Rating' );
        $pos_mid  = strpos( $html, 'Mid Rating' );
        $pos_low  = strpos( $html, 'Low Rating' );

        $this->assertLessThan( $pos_mid, $pos_high );
        $this->assertLessThan( $pos_low,  $pos_mid );
    }

    // -------------------------------------------------------------------------
    // Hook firing
    // -------------------------------------------------------------------------

    public function test_before_loop_action_fires(): void {
        PluginTestFactory::create_review();
        $fired = false;
        add_action( 'riaco_reviews_before_loop', function () use ( &$fired ) {
            $fired = true;
        } );

        Renderer::render( [] );
        $this->assertTrue( $fired );
    }

    public function test_after_loop_action_fires(): void {
        PluginTestFactory::create_review();
        $fired = false;
        add_action( 'riaco_reviews_after_loop', function () use ( &$fired ) {
            $fired = true;
        } );

        Renderer::render( [] );
        $this->assertTrue( $fired );
    }

    public function test_before_card_action_fires_once_per_review(): void {
        PluginTestFactory::create_review();
        PluginTestFactory::create_review();
        $count = 0;
        add_action( 'riaco_reviews_before_card', function () use ( &$count ) {
            $count++;
        } );

        Renderer::render( [] );
        $this->assertSame( 2, $count );
    }

    // -------------------------------------------------------------------------
    // Filter hooks
    // -------------------------------------------------------------------------

    public function test_card_meta_filter_mutates_author_name_in_output(): void {
        PluginTestFactory::create_review( [ 'author_name' => 'Original Author' ] );

        add_filter( 'riaco_reviews_card_meta', function ( $meta ) {
            $meta['author_name'] = 'Filtered Author';
            return $meta;
        }, 10, 3 );

        $html = Renderer::render( [] );
        $this->assertStringContainsString( 'Filtered Author',  $html );
        $this->assertStringNotContainsString( 'Original Author', $html );
    }

    public function test_card_template_path_filter_uses_custom_template(): void {
        // Write a temporary custom template.
        $custom_tpl = sys_get_temp_dir() . '/riaco_custom_card.php';
        file_put_contents( $custom_tpl, '<div class="custom-template">Custom!</div>' );

        PluginTestFactory::create_review();

        add_filter( 'riaco_reviews_card_template_path', fn() => $custom_tpl );

        $html = Renderer::render( [] );

        unlink( $custom_tpl );
        $this->assertStringContainsString( 'custom-template', $html );
    }

    public function test_card_template_path_filter_nonexistent_path_silently_skips_card(): void {
        PluginTestFactory::create_review();

        add_filter( 'riaco_reviews_card_template_path', fn() => '/nonexistent/template.php' );

        // Per docs: a non-existent path "silently no-ops" — the card is skipped, no fallback.
        $html = Renderer::render( [] );
        $this->assertStringNotContainsString( '<article', $html );
        // The wrapper div still renders (loop ran but produced no cards).
        $this->assertStringContainsString( 'riaco-reviews', $html );
    }

    // -------------------------------------------------------------------------
    // Card styles
    // -------------------------------------------------------------------------

    public function test_modern_card_style_renders(): void {
        PluginTestFactory::create_review( [ 'author_name' => 'Modern Author', 'rating' => 4 ] );
        $html = Renderer::render( [ 'card_style' => 'modern' ] );
        $this->assertStringContainsString( 'riaco-reviews__card--modern', $html );
    }

    public function test_minimal_card_style_renders(): void {
        PluginTestFactory::create_review( [ 'author_name' => 'Minimal Author', 'rating' => 3 ] );
        $html = Renderer::render( [ 'card_style' => 'minimal' ] );
        $this->assertStringContainsString( 'riaco-reviews__card--minimal', $html );
    }
}
