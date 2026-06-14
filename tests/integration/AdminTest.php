<?php

namespace RIACO\Reviews\Tests\Integration;

use RIACO\Reviews\Admin;
use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Tests for Admin — meta box save, nonce/capability guards, and column definitions.
 */
class AdminTest extends WP_UnitTestCase {

    private Admin $admin;
    private int $post_id;
    private int $editor_id;

    public function setUp(): void {
        parent::setUp();
        $this->admin    = new Admin( RIACO_REVIEWS_FILE );
        $this->post_id  = PluginTestFactory::create_review();
        $this->editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
        wp_set_current_user( $this->editor_id );
        $_POST = [];
    }

    public function tearDown(): void {
        $_POST = [];
        wp_set_current_user( 0 );
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // save_meta — guards
    // -------------------------------------------------------------------------

    public function test_save_meta_does_nothing_without_nonce(): void {
        $_POST = [
            'riaco_author_name' => 'Hacker Attempt',
            'riaco_rating'      => '5',
        ];
        // No nonce — save_meta must abort and NOT write the hacker value.
        $this->admin->save_meta( $this->post_id );

        $this->assertNotSame( 'Hacker Attempt', get_post_meta( $this->post_id, '_riaco_review_author_name', true ) );
    }

    public function test_save_meta_does_nothing_with_invalid_nonce(): void {
        $_POST = [
            'riaco_review_meta_nonce' => 'bad_nonce_value',
            'riaco_author_name'       => 'Hacker Attempt',
        ];
        $this->admin->save_meta( $this->post_id );

        $this->assertNotSame( 'Hacker Attempt', get_post_meta( $this->post_id, '_riaco_review_author_name', true ) );
    }

    public function test_save_meta_denied_for_subscriber(): void {
        $subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber_id );

        $_POST = [
            'riaco_review_meta_nonce' => wp_create_nonce( 'riaco_review_meta' ),
            'riaco_author_name'       => 'Subscriber Hacker',
            'riaco_rating'            => '5',
        ];
        $this->admin->save_meta( $this->post_id );

        // Subscriber cannot edit_post, so the hacker value must NOT have been stored.
        $this->assertNotSame( 'Subscriber Hacker', get_post_meta( $this->post_id, '_riaco_review_author_name', true ) );
    }

    // -------------------------------------------------------------------------
    // save_meta — happy path
    // -------------------------------------------------------------------------

    public function test_save_meta_stores_all_fields(): void {
        $_POST = [
            'riaco_review_meta_nonce' => wp_create_nonce( 'riaco_review_meta' ),
            'riaco_author_name'       => 'Jane Doe',
            'riaco_author_avatar'     => 'https://example.com/avatar.jpg',
            'riaco_rating'            => '4',
            'riaco_review_date'       => '2024-06-01',
            'riaco_source_url'        => 'https://example.com/review',
        ];

        $this->admin->save_meta( $this->post_id );

        $this->assertSame( 'Jane Doe',                          get_post_meta( $this->post_id, '_riaco_review_author_name',   true ) );
        $this->assertSame( 'https://example.com/avatar.jpg',   get_post_meta( $this->post_id, '_riaco_review_author_avatar', true ) );
        $this->assertSame( '4',                                 get_post_meta( $this->post_id, '_riaco_review_rating',        true ) );
        $this->assertSame( '2024-06-01',                        get_post_meta( $this->post_id, '_riaco_review_date',          true ) );
        $this->assertSame( 'https://example.com/review',        get_post_meta( $this->post_id, '_riaco_review_source_url',    true ) );
    }

    public function test_save_meta_rating_zero_deletes_meta_key(): void {
        // Seed a rating first.
        update_post_meta( $this->post_id, '_riaco_review_rating', 5 );

        $_POST = [
            'riaco_review_meta_nonce' => wp_create_nonce( 'riaco_review_meta' ),
            'riaco_rating'            => '0',
        ];
        $this->admin->save_meta( $this->post_id );

        $this->assertSame( '', get_post_meta( $this->post_id, '_riaco_review_rating', true ) );
    }

    public function test_save_meta_rating_clamped_to_max_5(): void {
        $_POST = [
            'riaco_review_meta_nonce' => wp_create_nonce( 'riaco_review_meta' ),
            'riaco_rating'            => '9999',
        ];
        $this->admin->save_meta( $this->post_id );

        $rating = (int) get_post_meta( $this->post_id, '_riaco_review_rating', true );
        $this->assertSame( 5, $rating );
    }

    // -------------------------------------------------------------------------
    // columns()
    // -------------------------------------------------------------------------

    public function test_columns_returns_expected_keys(): void {
        $cols = $this->admin->columns( [] );

        $this->assertArrayHasKey( 'riaco_author',       $cols );
        $this->assertArrayHasKey( 'riaco_rating',       $cols );
        $this->assertArrayHasKey( 'riaco_source_term',  $cols );
        $this->assertArrayHasKey( 'riaco_product_term', $cols );
        $this->assertArrayHasKey( 'riaco_review_date',  $cols );
    }

    // -------------------------------------------------------------------------
    // Custom action hook
    // -------------------------------------------------------------------------

    public function test_riaco_reviews_save_meta_action_fires(): void {
        $fired = false;
        add_action( 'riaco_reviews_save_meta', function () use ( &$fired ) {
            $fired = true;
        } );

        $_POST = [
            'riaco_review_meta_nonce' => wp_create_nonce( 'riaco_review_meta' ),
        ];
        $this->admin->save_meta( $this->post_id );

        $this->assertTrue( $fired );
    }
}
