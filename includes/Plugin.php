<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;
use RIACO\Reviews\PostType;
use RIACO\Reviews\Admin;
use RIACO\Reviews\Shortcodes;
use RIACO\Reviews\Blocks;
use RIACO\Reviews\ReviewSource;
use RIACO\Reviews\ReviewProduct;
use RIACO\Reviews\Dashboard;
use RIACO\Reviews\JsonLd;

class Plugin {

    public string $version;
    public string $file;
    private array $services = [];
    private bool $loaded = false;

    public function __construct( string $file, string $version ) {
        $this->file    = $file;
        $this->version = $version;
    }

    public function load(): void {
        if ( $this->loaded ) return;
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init(): void {
        $this->maybe_upgrade();
        $this->load_services();
        do_action( 'riaco_reviews_init', $this );
        $this->register();
        $this->loaded = true;
        do_action( 'riaco_reviews_loaded', $this );
    }

    public function load_services(): void {
        $this->set_service( 'postType',   new PostType() );
        $this->set_service( 'admin',      new Admin( $this->file ) );
        $this->set_service( 'shortcodes', new Shortcodes( $this->file, $this->version ) );
        $this->set_service( 'blocks',      new Blocks( $this->file, $this->version ) );
        $this->set_service( 'reviewSource', new ReviewSource( $this->file, $this->version ) );
        $this->set_service( 'reviewProduct', new ReviewProduct() );
        $this->set_service( 'dashboard',  new Dashboard() );
        $this->set_service( 'jsonLd',     new JsonLd() );
    }

    public function set_service( string $key, $service ): void {
        $this->services[ $key ] = $service;
    }

    public function get_service( string $key ) {
        return $this->services[ $key ] ?? null;
    }

    public function register(): void {
        foreach ( $this->services as $service ) {
            if ( $service instanceof ServiceInterface ) {
                $service->register();
            }
        }
    }

    private function maybe_upgrade(): void {
        $db_version = get_option( 'riaco_reviews_db_version', '0' );
        if ( version_compare( $db_version, '1.2.0', '<' ) ) {
            $this->migrate_tag_to_product();
            update_option( 'riaco_reviews_db_version', '1.2.0' );
        }
    }

    private function migrate_tag_to_product(): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $wpdb->term_taxonomy,
            [ 'taxonomy' => 'riaco_review_product' ],
            [ 'taxonomy' => 'riaco_review_tag' ]
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            "UPDATE {$wpdb->termmeta}
             SET meta_key = '_riaco_product_url'
             WHERE meta_key = '_riaco_tag_url'"
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            "UPDATE {$wpdb->termmeta}
             SET meta_key = '_riaco_product_type'
             WHERE meta_key = '_riaco_tag_type'"
        );

        wp_cache_flush();
    }

    public function on_activation(): void {
        if ( empty( $this->services ) ) {
            $this->load_services();
        }

        $post_type = $this->get_service( 'postType' );

        if ( $post_type instanceof PostType ) {
            $post_type->register_post_type();
        }

        flush_rewrite_rules();
    }
}
