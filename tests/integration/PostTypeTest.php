<?php

namespace RIACO\Reviews\Tests\Integration;

use RIACO\Reviews\Plugin;
use WP_UnitTestCase;

/**
 * Tests for the riaco_review custom post type registration and CRUD.
 */
class PostTypeTest extends WP_UnitTestCase {

    public function test_post_type_registered(): void {
        $this->assertTrue( post_type_exists( 'riaco_review' ) );
    }

    public function test_post_type_not_publicly_viewable(): void {
        $this->assertFalse( is_post_type_viewable( 'riaco_review' ) );
    }

    public function test_post_type_excluded_from_search(): void {
        $obj = get_post_type_object( 'riaco_review' );
        $this->assertTrue( $obj->exclude_from_search );
    }

    public function test_crud_review(): void {
        // Create
        $post_id = wp_insert_post( [
            'post_type'    => 'riaco_review',
            'post_status'  => 'publish',
            'post_title'   => 'CRUD Test',
            'post_content' => 'Original content.',
        ] );
        $this->assertIsInt( $post_id );
        $this->assertGreaterThan( 0, $post_id );

        // Read
        $post = get_post( $post_id );
        $this->assertSame( 'CRUD Test', $post->post_title );
        $this->assertSame( 'riaco_review', $post->post_type );

        // Update
        wp_update_post( [ 'ID' => $post_id, 'post_title' => 'Updated Title' ] );
        $updated = get_post( $post_id );
        $this->assertSame( 'Updated Title', $updated->post_title );

        // Delete
        wp_delete_post( $post_id, true );
        $this->assertNull( get_post( $post_id ) );
    }

    public function test_activation_does_not_throw(): void {
        // Plugin::on_activation() calls register_post_type + flush_rewrite_rules.
        $plugin = new Plugin( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );
        $plugin->on_activation();

        // If we reach here without exception, the activation hook is safe.
        $this->assertTrue( post_type_exists( 'riaco_review' ) );
    }
}
