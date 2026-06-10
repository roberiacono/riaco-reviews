<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;
use RIACO\Reviews\PostType;
use RIACO\Reviews\Admin;
use RIACO\Reviews\Shortcodes;
use RIACO\Reviews\Blocks;
use RIACO\Reviews\ReviewSource;
use RIACO\Reviews\ReviewTag;

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
        $this->set_service( 'blocks',        new Blocks( $this->file, $this->version ) );
        $this->set_service( 'reviewSource',  new ReviewSource( $this->file, $this->version ) );
        $this->set_service( 'reviewTag', new ReviewTag() );
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
