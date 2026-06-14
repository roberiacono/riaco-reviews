<?php

namespace RIACO\Reviews\Tests\Unit;

use RIACO\Reviews\Dashboard;
use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Tests for Dashboard — average rating calculation, caching, and widget registration.
 */
class DashboardTest extends WP_UnitTestCase {

    private Dashboard $dashboard;

    public function setUp(): void {
        parent::setUp();
        $this->dashboard = new Dashboard();
        // Always start with a cold cache.
        wp_cache_delete( 'riaco_avg_rating', 'riaco_reviews' );
    }

    public function tearDown(): void {
        wp_cache_delete( 'riaco_avg_rating', 'riaco_reviews' );
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Average rating
    // -------------------------------------------------------------------------

    public function test_average_rating_calculated_correctly(): void {
        PluginTestFactory::create_review( [ 'rating' => 3 ] );
        PluginTestFactory::create_review( [ 'rating' => 4 ] );
        PluginTestFactory::create_review( [ 'rating' => 5 ] );

        $avg = $this->get_average_rating();
        $this->assertEqualsWithDelta( 4.0, $avg, 0.01 );
    }

    public function test_average_rating_null_when_no_reviews(): void {
        $avg = $this->get_average_rating();
        $this->assertNull( $avg );
    }

    public function test_average_rating_ignores_drafts(): void {
        PluginTestFactory::create_review( [ 'rating' => 5 ] );
        wp_insert_post( [
            'post_type'    => 'riaco_review',
            'post_status'  => 'draft',
            'post_title'   => 'Draft',
            'post_content' => 'Draft review.',
            'meta_input'   => [ '_riaco_review_rating' => 1 ],
        ] );

        $avg = $this->get_average_rating();
        // Only the published review (5★) should be counted.
        $this->assertEqualsWithDelta( 5.0, $avg, 0.01 );
    }

    // -------------------------------------------------------------------------
    // Caching
    // -------------------------------------------------------------------------

    public function test_cache_hit_used_on_second_call(): void {
        PluginTestFactory::create_review( [ 'rating' => 4 ] );

        // Prime the cache.
        $first = $this->get_average_rating();

        // Nuke all posts — if cache is used, result stays the same.
        $posts = get_posts( [ 'post_type' => 'riaco_review', 'posts_per_page' => -1, 'post_status' => 'any' ] );
        foreach ( $posts as $p ) {
            wp_delete_post( $p->ID, true );
        }

        // Re-prime cache manually (simulates what the method does internally).
        wp_cache_set( 'riaco_avg_rating', (string) $first, 'riaco_reviews', HOUR_IN_SECONDS );

        $second = $this->get_average_rating();
        $this->assertEqualsWithDelta( (float) $first, (float) $second, 0.01 );
    }

    public function test_clear_rating_cache_removes_cached_value(): void {
        wp_cache_set( 'riaco_avg_rating', '4.5', 'riaco_reviews', HOUR_IN_SECONDS );

        $this->dashboard->clear_rating_cache();

        $cached = wp_cache_get( 'riaco_avg_rating', 'riaco_reviews' );
        $this->assertFalse( $cached );
    }

    public function test_clear_rating_cache_fires_on_save_post(): void {
        wp_cache_set( 'riaco_avg_rating', '3.0', 'riaco_reviews', HOUR_IN_SECONDS );

        $this->dashboard->register();
        $post_id = PluginTestFactory::create_review();

        // Saving the post should clear the cache.
        do_action( 'save_post_riaco_review', $post_id );
        $this->assertFalse( wp_cache_get( 'riaco_avg_rating', 'riaco_reviews' ) );
    }

    // -------------------------------------------------------------------------
    // Widget
    // -------------------------------------------------------------------------

    public function test_widget_hook_registered_after_register(): void {
        $this->dashboard->register();
        $this->assertGreaterThan(
            0,
            has_action( 'wp_dashboard_setup', [ $this->dashboard, 'add_widget' ] ),
            'add_widget should be hooked to wp_dashboard_setup after register()'
        );
    }

    // -------------------------------------------------------------------------
    // Helper: call the private get_average_rating via render capture
    // -------------------------------------------------------------------------

    /**
     * Dashboard::get_average_rating() is private, so we invoke it indirectly
     * by calling render_widget() and capturing stdout. A simpler approach:
     * call the public render method and check the output for a null condition.
     * We expose the value via a public test helper that calls get_average_rating
     * through Reflection.
     */
    private function get_average_rating(): ?float {
        $ref    = new \ReflectionMethod( Dashboard::class, 'get_average_rating' );
        $ref->setAccessible( true );
        return $ref->invoke( $this->dashboard );
    }
}
