<?php

namespace RIACO\Reviews\Tests\Integration;

use RIACO\Reviews\ReviewSource;
use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Tests for ReviewSource — image meta save guards and term assignment.
 */
class ReviewSourceTest extends WP_UnitTestCase {

    private ReviewSource $review_source;
    private int $term_id;
    private int $admin_id;

    public function setUp(): void {
        parent::setUp();
        $this->review_source = new ReviewSource( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );
        $this->term_id  = PluginTestFactory::create_source( 'Test Source' );
        $this->admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $this->admin_id );
        $_POST = [];
    }

    public function tearDown(): void {
        $_POST = [];
        wp_set_current_user( 0 );
        parent::tearDown();
    }

    public function test_save_image_meta_does_nothing_without_nonce(): void {
        $_POST = [
            'riaco_source_image' => 'https://example.com/logo.png',
        ];
        $this->review_source->save_image_meta( $this->term_id );

        $this->assertSame( '', get_term_meta( $this->term_id, '_riaco_source_image', true ) );
    }

    public function test_save_image_meta_does_nothing_with_invalid_nonce(): void {
        $_POST = [
            'riaco_source_image_nonce' => 'bad',
            'riaco_source_image'       => 'https://example.com/logo.png',
        ];
        $this->review_source->save_image_meta( $this->term_id );

        $this->assertSame( '', get_term_meta( $this->term_id, '_riaco_source_image', true ) );
    }

    public function test_save_image_meta_denied_for_subscriber(): void {
        $subscriber = $this->factory->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber );

        $_POST = [
            'riaco_source_image_nonce' => wp_create_nonce( 'riaco_source_image_save' ),
            'riaco_source_image'       => 'https://example.com/logo.png',
        ];
        $this->review_source->save_image_meta( $this->term_id );

        $this->assertSame( '', get_term_meta( $this->term_id, '_riaco_source_image', true ) );
    }

    public function test_save_image_meta_stores_url_for_admin(): void {
        $_POST = [
            'riaco_source_image_nonce' => wp_create_nonce( 'riaco_source_image_save' ),
            'riaco_source_image'       => 'https://example.com/valid-logo.png',
        ];
        $this->review_source->save_image_meta( $this->term_id );

        $this->assertSame( 'https://example.com/valid-logo.png', get_term_meta( $this->term_id, '_riaco_source_image', true ) );
    }

    public function test_term_column_renders_img_when_logo_set(): void {
        update_term_meta( $this->term_id, '_riaco_source_image', 'https://example.com/logo.svg' );

        $output = $this->review_source->render_term_column( '', 'riaco_source_image', $this->term_id );
        $this->assertStringContainsString( '<img', $output );
        $this->assertStringContainsString( 'https://example.com/logo.svg', $output );
    }

    public function test_term_column_renders_dash_when_no_logo(): void {
        // term_id has no logo meta.
        $output = $this->review_source->render_term_column( '', 'riaco_source_image', $this->term_id );
        $this->assertSame( '—', $output );
    }
}
