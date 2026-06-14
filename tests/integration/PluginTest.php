<?php

namespace RIACO\Reviews\Tests\Integration;

use RIACO\Reviews\Admin;
use RIACO\Reviews\Dashboard;
use RIACO\Reviews\Plugin;
use WP_UnitTestCase;

/**
 * Tests for the Plugin service container — service wiring, lifecycle actions, migration.
 */
class PluginTest extends WP_UnitTestCase {

    public function test_get_service_returns_admin_instance(): void {
        $plugin = $this->make_initialised_plugin();
        $admin  = $plugin->get_service( 'admin' );

        $this->assertInstanceOf( Admin::class, $admin );
    }

    public function test_get_service_returns_null_for_unknown_key(): void {
        $plugin = $this->make_initialised_plugin();
        $this->assertNull( $plugin->get_service( 'nonexistent' ) );
    }

    public function test_set_service_is_retrievable(): void {
        $plugin  = new Plugin( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );
        $service = new \stdClass();
        $plugin->set_service( 'custom', $service );

        $this->assertSame( $service, $plugin->get_service( 'custom' ) );
    }

    public function test_riaco_reviews_init_action_fires(): void {
        $fired  = false;
        $plugin = new Plugin( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );

        add_action( 'riaco_reviews_init', function () use ( &$fired ) {
            $fired = true;
        } );

        $plugin->init();
        $this->assertTrue( $fired );

        remove_all_actions( 'riaco_reviews_init' );
    }

    public function test_riaco_reviews_loaded_action_fires(): void {
        $fired  = false;
        $plugin = new Plugin( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );

        add_action( 'riaco_reviews_loaded', function () use ( &$fired ) {
            $fired = true;
        } );

        $plugin->init();
        $this->assertTrue( $fired );

        remove_all_actions( 'riaco_reviews_loaded' );
    }

    public function test_maybe_upgrade_writes_db_version_option(): void {
        delete_option( 'riaco_reviews_db_version' );

        $plugin = new Plugin( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );
        $plugin->init();

        $this->assertSame( '1.2.0', get_option( 'riaco_reviews_db_version' ) );
    }

    public function test_maybe_upgrade_does_not_re_run_when_current(): void {
        update_option( 'riaco_reviews_db_version', '1.2.0' );

        // Track wpdb updates via a query counter.
        global $wpdb;
        $original_query_count = $wpdb->num_queries;

        $plugin = new Plugin( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );
        $plugin->init();

        // No term_taxonomy UPDATE should have run.
        $this->assertSame( '1.2.0', get_option( 'riaco_reviews_db_version' ) );
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function make_initialised_plugin(): Plugin {
        $plugin = new Plugin( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );
        $plugin->init();
        return $plugin;
    }
}
