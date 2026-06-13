<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;

class Dashboard implements ServiceInterface {

    public function register(): void {
        add_action( 'wp_dashboard_setup', [ $this, 'add_widget' ] );
        add_action( 'save_post_riaco_review', [ $this, 'clear_rating_cache' ] );
        add_action( 'before_delete_post',     [ $this, 'clear_rating_cache' ] );
    }

    public function clear_rating_cache(): void {
        wp_cache_delete( 'riaco_avg_rating', 'riaco_reviews' );
    }

    public function add_widget(): void {
        wp_add_dashboard_widget(
            'riaco_reviews_overview',
            __( 'Reviews Overview', 'riaco-reviews' ),
            [ $this, 'render_widget' ]
        );
    }

    public function render_widget(): void {
        $counts = wp_count_posts( 'riaco_review' );
        $total  = isset( $counts->publish ) ? (int) $counts->publish : 0;
        $avg    = $this->get_average_rating();

        echo '<ul style="margin:0;padding:0;list-style:none;">';

        printf(
            '<li style="padding:6px 0;border-bottom:1px solid #f0f0f0;"><strong>%s</strong> %s</li>',
            esc_html( number_format_i18n( $total ) ),
            esc_html( _n( 'published review', 'published reviews', $total, 'riaco-reviews' ) )
        );

        if ( null !== $avg ) {
            printf(
                '<li style="padding:6px 0;border-bottom:1px solid #f0f0f0;"><strong>%s / 5 ★</strong> %s</li>',
                esc_html( number_format( $avg, 1 ) ),
                esc_html__( 'average rating', 'riaco-reviews' )
            );
        }

        echo '</ul>';

        printf(
            '<p style="margin:12px 0 0;"><a href="%s" class="button button-small">%s</a></p>',
            esc_url( admin_url( 'edit.php?post_type=riaco_review' ) ),
            esc_html__( 'Manage Reviews', 'riaco-reviews' )
        );
    }

    private function get_average_rating(): ?float {
        global $wpdb;

        $cache_key = 'riaco_avg_rating';
        $cached    = wp_cache_get( $cache_key, 'riaco_reviews' );
        if ( false !== $cached ) {
            return 'null' === $cached ? null : (float) $cached;
        }

        $avg = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- AVG aggregate cannot be expressed with WP_Query.
            "SELECT AVG(CAST(pm.meta_value AS DECIMAL(10,2)))
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_riaco_review_rating'
               AND p.post_type = 'riaco_review'
               AND p.post_status = 'publish'"
        );

        wp_cache_set( $cache_key, null === $avg ? 'null' : $avg, 'riaco_reviews', HOUR_IN_SECONDS );

        return null !== $avg ? (float) $avg : null;
    }
}
