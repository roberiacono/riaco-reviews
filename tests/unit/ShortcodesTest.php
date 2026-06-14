<?php

namespace RIACO\Reviews\Tests\Unit;

use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Tests for the [riaco_reviews] shortcode.
 */
class ShortcodesTest extends WP_UnitTestCase {

    public function test_shortcode_registered(): void {
        $this->assertTrue( shortcode_exists( 'riaco_reviews' ) );
    }

    public function test_shortcode_returns_string(): void {
        $result = do_shortcode( '[riaco_reviews]' );
        $this->assertIsString( $result );
    }

    public function test_boolean_attr_zero_treated_as_false(): void {
        PluginTestFactory::create_review();
        $html = do_shortcode( '[riaco_reviews show_rating="0"]' );
        // Stars are rendered as ★ spans with the rating class; when hidden there should be none.
        $this->assertStringNotContainsString( 'riaco-reviews__rating', $html );
    }

    public function test_boolean_attr_one_treated_as_true(): void {
        PluginTestFactory::create_review();
        $html = do_shortcode( '[riaco_reviews show_rating="1"]' );
        $this->assertStringContainsString( 'riaco-reviews__rating', $html );
    }

    public function test_deprecated_tag_attr_maps_to_product(): void {
        $prod = PluginTestFactory::create_product( 'My Plugin' );
        $term = get_term( $prod, 'riaco_review_product' );

        PluginTestFactory::create_review( [
            'post_title'   => 'Tagged Review',
            'product_term' => $prod,
        ] );

        // Using old tag= attribute should still filter by product.
        $html_tag     = do_shortcode( '[riaco_reviews tag="' . $term->slug . '"]' );
        $html_product = do_shortcode( '[riaco_reviews product="' . $term->slug . '"]' );

        $this->assertStringContainsString( 'Tagged Review', $html_tag );
        $this->assertSame( $html_tag, $html_product );
    }

    public function test_order_normalised_to_uppercase(): void {
        // Both "asc" and "ASC" should produce the same valid output (no PHP error).
        $html_lower = do_shortcode( '[riaco_reviews order="asc"]' );
        $html_upper = do_shortcode( '[riaco_reviews order="ASC"]' );
        $this->assertIsString( $html_lower );
        $this->assertIsString( $html_upper );
    }

    public function test_invalid_order_falls_back_gracefully(): void {
        $html = do_shortcode( '[riaco_reviews order="SIDEWAYS"]' );
        $this->assertIsString( $html );
    }
}
