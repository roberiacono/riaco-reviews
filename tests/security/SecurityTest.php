<?php

namespace RIACO\Reviews\Tests\Security;

use RIACO\Reviews\Admin;
use RIACO\Reviews\Renderer;
use RIACO\Reviews\ReviewProduct;
use RIACO\Reviews\ReviewSource;
use RIACO\Reviews\Tests\Helpers\PluginTestFactory;
use WP_UnitTestCase;

/**
 * Security-focused tests: XSS, SQL injection, CSRF, and privilege escalation.
 *
 * Each test verifies that a known attack vector is neutralised before reaching
 * the output layer or database.
 */
class SecurityTest extends WP_UnitTestCase {

    private int $editor_id;
    private Admin $admin;

    public function setUp(): void {
        parent::setUp();
        $this->editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
        $this->admin     = new Admin( RIACO_REVIEWS_FILE );
        wp_set_current_user( $this->editor_id );
        $_POST = [];
        remove_all_filters( 'riaco_reviews_atts' );
    }

    public function tearDown(): void {
        $_POST = [];
        wp_set_current_user( 0 );
        parent::tearDown();
    }

    // =========================================================================
    // XSS
    // =========================================================================

    public function test_xss_payload_in_card_bg_attribute_stripped(): void {
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'card_bg' => '<script>alert(1)</script>' ] );

        $this->assertStringNotContainsString( '<script>', $html );
        $this->assertStringNotContainsString( 'alert(1)', $html );
    }

    public function test_xss_payload_in_author_name_escaped(): void {
        PluginTestFactory::create_review( [ 'author_name' => '<script>alert("xss")</script>' ] );
        $html = Renderer::render( [] );

        $this->assertStringNotContainsString( '<script>alert', $html );
        // The escaped version may appear but not as executable HTML.
        $this->assertStringNotContainsString( '</script>', substr_count( $html, '<script' ) > 0 ? $html : '' );
    }

    public function test_xss_payload_in_source_name_escaped(): void {
        // Create source with a safe name, then force-update to an XSS payload via wpdb
        // (simulates a compromised term name stored directly in the DB).
        global $wpdb;
        $source_id = PluginTestFactory::create_source( 'Safe Source Name' );
        $xss_name  = 'Source <script>alert(1)</script>';
        $wpdb->update( $wpdb->terms, [ 'name' => $xss_name ], [ 'term_id' => $source_id ] );
        clean_term_cache( $source_id, 'riaco_review_source' );

        PluginTestFactory::create_review( [ 'source_term' => $source_id ] );

        $html = Renderer::render( [] );
        $this->assertStringNotContainsString( '<script>alert', $html );
    }

    public function test_xss_payload_in_product_name_escaped(): void {
        $prod_id = PluginTestFactory::create_product( '<b onmouseover=alert(1)>Product</b>' );
        PluginTestFactory::create_review( [ 'product_term' => $prod_id ] );

        $html = Renderer::render( [] );
        $this->assertStringNotContainsString( 'onmouseover=alert', $html );
    }

    public function test_xss_in_post_content_sanitised_by_wp_kses_post(): void {
        $post_id = wp_insert_post( [
            'post_type'    => 'riaco_review',
            'post_status'  => 'publish',
            'post_title'   => 'XSS Content Test',
            'post_content' => '<p>Good review.</p><script>alert("xss")</script>',
        ] );

        $html = Renderer::render( [] );
        // Script tag in post_content should be sanitised before display.
        $this->assertStringNotContainsString( '<script>alert("xss")', $html );
    }

    // =========================================================================
    // SQL injection
    // =========================================================================

    public function test_sql_injection_via_product_filter_is_neutralised(): void {
        // sanitize_title() is applied to every product slug. This payload becomes
        // an empty string after sanitisation, so the tax_query is never built.
        $html = Renderer::render( [ 'product' => "' OR 1=1 --" ] );

        // If we reach here without a DB error, the injection was neutralised.
        $this->assertIsString( $html );
        // Also ensure the raw SQL characters don't appear in the output.
        $this->assertStringNotContainsString( "OR 1=1", $html );
    }

    public function test_sql_injection_via_count_is_neutralised(): void {
        // absint() converts any non-numeric/negative input to 0, then max(1,0)=1.
        $html = Renderer::render( [ 'count' => "1; DROP TABLE {$GLOBALS['wpdb']->posts}" ] );

        // The table must still exist — we can query it.
        $count = $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts}" );
        $this->assertNotNull( $count );
        $this->assertIsString( $html );
    }

    // =========================================================================
    // CSRF / nonce checks
    // =========================================================================

    public function test_save_meta_without_nonce_writes_nothing(): void {
        $post_id = PluginTestFactory::create_review();
        $_POST   = [ 'riaco_author_name' => 'CSRF Attacker', 'riaco_rating' => '5' ];

        $this->admin->save_meta( $post_id );

        // Without a nonce, save_meta must abort — the attacker value must NOT be stored.
        $this->assertNotSame( 'CSRF Attacker', get_post_meta( $post_id, '_riaco_review_author_name', true ) );
    }

    public function test_save_source_image_without_nonce_writes_nothing(): void {
        $rs      = new ReviewSource( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );
        $term_id = PluginTestFactory::create_source( 'CSRF Source' );
        $_POST   = [ 'riaco_source_image' => 'https://evil.com/logo.png' ];

        $rs->save_image_meta( $term_id );

        $this->assertSame( '', get_term_meta( $term_id, '_riaco_source_image', true ) );
    }

    // =========================================================================
    // Privilege escalation
    // =========================================================================

    public function test_subscriber_cannot_save_review_meta(): void {
        $subscriber = $this->factory->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber );

        $post_id = PluginTestFactory::create_review();
        $_POST   = [
            'riaco_review_meta_nonce' => wp_create_nonce( 'riaco_review_meta' ),
            'riaco_author_name'       => 'Subscriber Hacker',
            'riaco_rating'            => '5',
        ];

        $this->admin->save_meta( $post_id );

        // Subscriber cannot edit_post — the hacker value must NOT be stored.
        $this->assertNotSame( 'Subscriber Hacker', get_post_meta( $post_id, '_riaco_review_author_name', true ) );
    }

    public function test_subscriber_cannot_save_source_image(): void {
        $subscriber = $this->factory->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber );

        $rs      = new ReviewSource( RIACO_REVIEWS_FILE, RIACO_REVIEWS_VERSION );
        $term_id = PluginTestFactory::create_source( 'Priv Test' );
        $_POST   = [
            'riaco_source_image_nonce' => wp_create_nonce( 'riaco_source_image_save' ),
            'riaco_source_image'       => 'https://evil.com/logo.png',
        ];

        $rs->save_image_meta( $term_id );

        $this->assertSame( '', get_term_meta( $term_id, '_riaco_source_image', true ) );
    }

    public function test_subscriber_cannot_save_product_meta(): void {
        $subscriber = $this->factory->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber );

        $rp      = new ReviewProduct();
        $term_id = PluginTestFactory::create_product( 'Priv Product' );
        $_POST   = [
            'riaco_product_meta_nonce' => wp_create_nonce( 'riaco_product_meta_save' ),
            'riaco_product_url'        => 'https://evil.com',
            'riaco_product_type'       => 'Thing',
        ];

        $rp->save_term_meta( $term_id );

        $this->assertSame( '', get_term_meta( $term_id, '_riaco_product_url', true ) );
    }

    // =========================================================================
    // Colour injection via filter bypass
    // =========================================================================

    public function test_css_expression_in_card_bg_via_filter_stripped(): void {
        add_filter( 'riaco_reviews_atts', function ( $atts ) {
            $atts['card_bg'] = 'expression(alert(document.cookie))';
            return $atts;
        } );

        PluginTestFactory::create_review();
        $html = Renderer::render( [] );

        $this->assertStringNotContainsString( '--riaco-card-bg', $html );
        $this->assertStringNotContainsString( 'expression(', $html );
    }

    // =========================================================================
    // Input boundary — heading level
    // =========================================================================

    public function test_heading_level_99_defaults_to_h3(): void {
        // Out-of-range heading_level (99) is rejected by Renderer and falls back to default (3).
        PluginTestFactory::create_review();
        $html = Renderer::render( [ 'heading_level' => 99 ] );

        $this->assertStringNotContainsString( '<h99', $html );
        $this->assertStringContainsString( '<h3', $html );
    }

    // =========================================================================
    // Product type allowlist
    // =========================================================================

    public function test_invalid_product_type_not_saved(): void {
        $admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $admin_id );

        $rp      = new ReviewProduct();
        $term_id = PluginTestFactory::create_product( 'Allowlist Test' );
        $_POST   = [
            'riaco_product_meta_nonce' => wp_create_nonce( 'riaco_product_meta_save' ),
            'riaco_product_type'       => 'ScriptInjection',
        ];

        $rp->save_term_meta( $term_id );

        // ScriptInjection is not in ALLOWED_TYPES; default 'Thing' should remain.
        $stored = get_term_meta( $term_id, '_riaco_product_type', true );
        $this->assertNotSame( 'ScriptInjection', $stored );
    }
}
