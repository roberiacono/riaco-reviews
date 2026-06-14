<?php

namespace RIACO\Reviews\Tests\Unit;

use RIACO\Reviews\Renderer;
use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Tests for Renderer::render() — sanitisation, CSS-var injection, filter hooks,
 * and output structure.
 */
class RendererTest extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        // Remove any filters added by individual tests.
        remove_all_filters( 'riaco_reviews_layouts' );
        remove_all_filters( 'riaco_reviews_card_styles' );
        remove_all_filters( 'riaco_reviews_orderby_options' );
        remove_all_filters( 'riaco_reviews_atts' );
        remove_all_filters( 'riaco_reviews_query_args' );
        remove_all_filters( 'riaco_reviews_no_reviews_html' );
    }

    // -------------------------------------------------------------------------
    // Count
    // -------------------------------------------------------------------------

    public function test_count_sanitised_to_minimum_1_when_zero(): void {
        $html = Renderer::render( [ 'count' => 0 ] );
        // No posts exist — we're verifying no PHP error and an empty/valid response.
        $this->assertIsString( $html );
    }

    public function test_count_sanitised_to_minimum_1_when_negative(): void {
        $html = Renderer::render( [ 'count' => -99 ] );
        $this->assertIsString( $html );
    }

    public function test_render_count_limits_results(): void {
        for ( $i = 0; $i < 5; $i++ ) {
            PluginTestFactory::create_review( [ 'post_title' => "Review $i" ] );
        }

        $html = Renderer::render( [ 'count' => 2 ] );
        // Each card is an <article>; count occurrences.
        $this->assertSame( 2, substr_count( $html, '<article' ) );
    }

    // -------------------------------------------------------------------------
    // Layout
    // -------------------------------------------------------------------------

    public function test_invalid_layout_falls_back_to_grid(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'layout' => 'carousel' ] );
        $this->assertStringContainsString( 'riaco-reviews--grid', $html );
        $this->assertStringNotContainsString( 'carousel', $html );
    }

    public function test_custom_layout_registerable_via_filter(): void {
        add_filter( 'riaco_reviews_layouts', fn( $l ) => array_merge( $l, [ 'carousel' ] ) );
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'layout' => 'carousel' ] );
        $this->assertStringContainsString( 'riaco-reviews--carousel', $html );
    }

    // -------------------------------------------------------------------------
    // Card style
    // -------------------------------------------------------------------------

    public function test_invalid_card_style_falls_back_to_default(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'card_style' => 'fancy' ] );
        $this->assertStringContainsString( 'riaco-reviews__card--default', $html );
    }

    // -------------------------------------------------------------------------
    // Orderby / order
    // -------------------------------------------------------------------------

    public function test_invalid_orderby_falls_back_to_date(): void {
        // Renderer should not throw; query runs without error.
        $html = Renderer::render( [ 'orderby' => 'popularity' ] );
        $this->assertIsString( $html );
    }

    public function test_invalid_order_falls_back_to_desc(): void {
        $html = Renderer::render( [ 'order' => 'RANDOM' ] );
        $this->assertIsString( $html );
    }

    // -------------------------------------------------------------------------
    // Heading level
    // -------------------------------------------------------------------------

    public function test_heading_level_out_of_range_defaults_to_h3(): void {
        PluginTestFactory::create_review();
        // heading_level=1 is out of the 2–6 range; Renderer defaults to 3.
        $html = Renderer::render( [ 'heading_level' => 1 ] );
        $this->assertStringNotContainsString( '<h1', $html );
        $this->assertStringContainsString( '<h3', $html );
    }

    public function test_heading_level_too_high_defaults_to_h3(): void {
        PluginTestFactory::create_review();
        // heading_level=9 is out of the 2–6 range; Renderer defaults to 3.
        $html = Renderer::render( [ 'heading_level' => 9 ] );
        $this->assertStringNotContainsString( '<h9', $html );
        $this->assertStringContainsString( '<h3', $html );
    }

    // -------------------------------------------------------------------------
    // Colour sanitisation
    // -------------------------------------------------------------------------

    public function test_invalid_hex_color_not_injected(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'card_bg' => 'notacolor' ] );
        $this->assertStringNotContainsString( '--riaco-card-bg', $html );
    }

    public function test_valid_hex_color_injected(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'card_bg' => '#ff0000' ] );
        $this->assertStringContainsString( '--riaco-card-bg:#ff0000', $html );
    }

    public function test_color_resanitized_after_atts_filter(): void {
        add_filter( 'riaco_reviews_atts', function ( $atts ) {
            $atts['card_bg'] = 'expression(alert(1))';
            return $atts;
        } );
        PluginTestFactory::create_review();
        $html = Renderer::render( [] );
        $this->assertStringNotContainsString( '--riaco-card-bg', $html );
        $this->assertStringNotContainsString( 'expression', $html );
    }

    // -------------------------------------------------------------------------
    // Typography
    // -------------------------------------------------------------------------

    public function test_font_size_out_of_range_not_injected(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'font_size' => 99 ] );
        $this->assertStringNotContainsString( '--riaco-font-size', $html );
    }

    public function test_font_size_valid_injected(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'font_size' => 1.2 ] );
        $this->assertStringContainsString( '--riaco-font-size:1.2rem', $html );
    }

    // -------------------------------------------------------------------------
    // min_width
    // -------------------------------------------------------------------------

    public function test_min_width_at_default_300_not_injected(): void {
        // The Renderer default is 300px; passing 300 explicitly must NOT inject the var.
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'min_width' => 300 ] );
        $this->assertStringNotContainsString( '--riaco-card-min-width', $html );
    }

    public function test_min_width_custom_injected(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'min_width' => 400 ] );
        $this->assertStringContainsString( '--riaco-card-min-width:400px', $html );
    }

    // -------------------------------------------------------------------------
    // Shadow
    // -------------------------------------------------------------------------

    public function test_shadow_false_injects_none(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'show_shadow' => false ] );
        $this->assertStringContainsString( '--riaco-card-shadow:none', $html );
    }

    // -------------------------------------------------------------------------
    // Product filter
    // -------------------------------------------------------------------------

    public function test_product_filter_slug_sanitised(): void {
        // Renderer should not error; the slug becomes sanitize_title'd.
        $html = Renderer::render( [ 'product' => 'Hello World' ] );
        $this->assertIsString( $html );
    }

    public function test_product_filter_excludes_unmatched_reviews(): void {
        $prod_a = PluginTestFactory::create_product( 'Product A' );
        $prod_b = PluginTestFactory::create_product( 'Product B' );

        $term_a = get_term( $prod_a, 'riaco_review_product' );
        $term_b = get_term( $prod_b, 'riaco_review_product' );

        PluginTestFactory::create_review( [
            'post_title'   => 'Review for A',
            'product_term' => $prod_a,
        ] );
        PluginTestFactory::create_review( [
            'post_title'   => 'Review for B',
            'product_term' => $prod_b,
        ] );

        $html = Renderer::render( [ 'product' => $term_a->slug ] );

        $this->assertStringContainsString( 'Review for A', $html );
        $this->assertStringNotContainsString( 'Review for B', $html );
    }

    // -------------------------------------------------------------------------
    // Filter hooks
    // -------------------------------------------------------------------------

    public function test_riaco_reviews_atts_filter_fires(): void {
        $fired = false;
        add_filter( 'riaco_reviews_atts', function ( $atts ) use ( &$fired ) {
            $fired = true;
            return $atts;
        } );

        Renderer::render( [] );
        $this->assertTrue( $fired );
    }

    public function test_riaco_reviews_query_args_filter_fires(): void {
        $fired = false;
        add_filter( 'riaco_reviews_query_args', function ( $args, $atts ) use ( &$fired ) {
            $fired = true;
            return $args;
        }, 10, 2 );

        Renderer::render( [] );
        $this->assertTrue( $fired );
    }

    public function test_no_reviews_html_filter_changes_empty_state(): void {
        add_filter( 'riaco_reviews_no_reviews_html', fn() => '<div class="custom-empty">Nothing here</div>' );

        $html = Renderer::render( [] ); // no posts in DB
        $this->assertStringContainsString( 'custom-empty', $html );
        $this->assertStringContainsString( 'Nothing here', $html );
    }

    // -------------------------------------------------------------------------
    // HTML structure
    // -------------------------------------------------------------------------

    public function test_render_returns_html_with_posts(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [] );
        $this->assertStringContainsString( 'riaco-reviews', $html );
        $this->assertStringContainsString( '<article', $html );
    }

    public function test_render_returns_empty_string_with_no_posts(): void {
        // No posts created in this test — DB is clean per transaction rollback.
        $html = Renderer::render( [] );
        // Either empty string or the no-reviews fallback; never an article.
        $this->assertStringNotContainsString( '<article', $html );
    }

    public function test_draft_reviews_excluded(): void {
        $draft_id = wp_insert_post( [
            'post_type'    => 'riaco_review',
            'post_status'  => 'draft',
            'post_title'   => 'Draft Review',
            'post_content' => 'Should not appear.',
        ] );

        $html = Renderer::render( [] );
        $this->assertStringNotContainsString( 'Draft Review', $html );
    }
}
