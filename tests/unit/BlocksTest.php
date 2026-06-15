<?php

namespace RIACO\Reviews\Tests\Unit;

use RIACO\Reviews\Blocks;
use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Tests for Blocks — registration and camelCase→snake_case attribute mapping.
 */
class BlocksTest extends WP_UnitTestCase {

    private Blocks $blocks;

    public function setUp(): void {
        parent::setUp();
        remove_all_filters( 'riaco_reviews_block_render_atts' );
        $this->blocks = new Blocks( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );
    }

    public function test_block_registered(): void {
        $registry = \WP_Block_Type_Registry::get_instance();
        $this->assertTrue( $registry->is_registered( 'riaco-reviews/reviews-block' ) );
    }

    public function test_render_maps_camelcase_to_snakecase(): void {
        $captured = null;
        add_filter( 'riaco_reviews_atts', function ( $atts ) use ( &$captured ) {
            $captured = $atts;
            return $atts;
        } );

        $this->blocks->render( [
            'cardStyle'    => 'modern',
            'showRating'   => false,
            'headingLevel' => 4,
            'count'        => 3,
        ] );

        $this->assertNotNull( $captured );
        $this->assertSame( 'modern', $captured['card_style'] );
        $this->assertFalse( $captured['show_rating'] );
        $this->assertSame( 4, $captured['heading_level'] );
        $this->assertSame( 3, $captured['count'] );
    }

    public function test_product_filter_maps_to_product(): void {
        $captured = null;
        add_filter( 'riaco_reviews_atts', function ( $atts ) use ( &$captured ) {
            $captured = $atts;
            return $atts;
        } );

        $this->blocks->render( [ 'productFilter' => 'some-product' ] );

        $this->assertNotNull( $captured );
        $this->assertSame( 'some-product', $captured['product'] );
    }

    public function test_riaco_reviews_block_render_atts_filter_fires(): void {
        $fired = false;
        add_filter( 'riaco_reviews_block_render_atts', function ( $atts, $attributes ) use ( &$fired ) {
            $fired = true;
            return $atts;
        }, 10, 2 );

        $this->blocks->render( [] );
        $this->assertTrue( $fired );
    }
}
