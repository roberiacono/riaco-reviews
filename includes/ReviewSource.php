<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;

class ReviewSource implements ServiceInterface {

    private string $file;
    private string $version;

    public function __construct( string $file, string $version ) {
        $this->file    = $file;
        $this->version = $version;
    }

    public function register(): void {
        add_action( 'init',                                          [ $this, 'register_taxonomy' ] );
        add_action( 'riaco_review_source_add_form_fields',          [ $this, 'add_image_field' ] );
        add_action( 'riaco_review_source_edit_form_fields',         [ $this, 'edit_image_field' ] );
        add_action( 'created_riaco_review_source',                   [ $this, 'save_image_meta' ] );
        add_action( 'edited_riaco_review_source',                    [ $this, 'save_image_meta' ] );
        add_filter( 'manage_edit-riaco_review_source_columns',       [ $this, 'term_columns' ] );
        add_filter( 'manage_riaco_review_source_custom_column',      [ $this, 'render_term_column' ], 10, 3 );
        add_action( 'save_post_riaco_review',                        [ $this, 'save_term_assignment' ] );
        add_action( 'admin_enqueue_scripts',                         [ $this, 'enqueue_assets' ] );
    }

    public function register_taxonomy(): void {
        register_taxonomy( 'riaco_review_source', 'riaco_review', [
            'labels' => [
                'name'              => __( 'Sources',          'riaco-reviews' ),
                'singular_name'     => __( 'Source',           'riaco-reviews' ),
                'add_new_item'      => __( 'Add New Source',   'riaco-reviews' ),
                'edit_item'         => __( 'Edit Source',      'riaco-reviews' ),
                'search_items'      => __( 'Search Sources',   'riaco-reviews' ),
                'not_found'         => __( 'No sources found.','riaco-reviews' ),
                'menu_name'         => __( 'Sources',          'riaco-reviews' ),
            ],
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_admin_column' => false,
            'hierarchical'      => false,
            'show_in_rest'      => false,
            'rewrite'           => false,
            'meta_box_cb'       => [ $this, 'render_meta_box' ],
        ] );
    }

    public function render_meta_box( \WP_Post $post, array $box ): void {
        $terms    = get_terms( [ 'taxonomy' => 'riaco_review_source', 'hide_empty' => false ] );
        if ( is_wp_error( $terms ) ) {
            $terms = [];
        }
        $assigned = wp_get_post_terms( $post->ID, 'riaco_review_source', [ 'fields' => 'ids' ] );
        $current  = ( is_array( $assigned ) && ! empty( $assigned ) ) ? (int) $assigned[0] : 0;

        wp_nonce_field( 'riaco_source_meta_box', 'riaco_source_meta_box_nonce' );
        ?>
        <div style="padding:4px 0;">
            <select name="riaco_review_source_term" id="riaco_review_source_term" style="min-width:180px;">
                <option value="0"><?php esc_html_e( '— None —', 'riaco-reviews' ); ?></option>
                <?php foreach ( $terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->term_id ); ?>"
                        <?php selected( $current, $term->term_id ); ?>>
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=riaco_review_source&post_type=riaco_review' ) ); ?>"
               style="margin-left:8px;font-size:12px;" target="_blank">
                <?php esc_html_e( 'Manage Sources', 'riaco-reviews' ); ?>
            </a>
        </div>
        <?php
    }

    public function add_image_field( string $taxonomy ): void {
        ?>
        <div class="form-field">
            <label for="riaco_source_image"><?php esc_html_e( 'Logo / Image', 'riaco-reviews' ); ?></label>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <input type="url" id="riaco_source_image" name="riaco_source_image" class="regular-text" value="">
                <button type="button" id="riaco_source_image_upload_btn" class="button button-secondary">
                    <?php esc_html_e( 'Upload Image', 'riaco-reviews' ); ?>
                </button>
                <button type="button" id="riaco_source_image_remove_btn" class="button-link button-link-delete" style="display:none;">
                    <?php esc_html_e( 'Remove', 'riaco-reviews' ); ?>
                </button>
            </div>
            <img id="riaco_source_image_preview" src="" alt=""
                 style="height:40px;width:auto;object-fit:contain;display:none;">
            <?php wp_nonce_field( 'riaco_source_image_save', 'riaco_source_image_nonce', false ); ?>
        </div>
        <?php
    }

    public function edit_image_field( \WP_Term $term ): void {
        $image = get_term_meta( $term->term_id, '_riaco_source_image', true );
        ?>
        <tr class="form-field">
            <th><label for="riaco_source_image"><?php esc_html_e( 'Logo / Image', 'riaco-reviews' ); ?></label></th>
            <td>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                    <input type="url" id="riaco_source_image" name="riaco_source_image"
                           class="regular-text" value="<?php echo esc_attr( $image ); ?>">
                    <button type="button" id="riaco_source_image_upload_btn" class="button button-secondary">
                        <?php esc_html_e( 'Upload Image', 'riaco-reviews' ); ?>
                    </button>
                    <button type="button" id="riaco_source_image_remove_btn" class="button-link button-link-delete"
                            <?php if ( ! $image ) : ?>style="display:none;"<?php endif; ?>>
                        <?php esc_html_e( 'Remove', 'riaco-reviews' ); ?>
                    </button>
                </div>
                <img id="riaco_source_image_preview" src="<?php echo esc_url( $image ); ?>"
                     alt="" style="height:40px;width:auto;object-fit:contain;<?php if ( ! $image ) : ?>display:none;<?php endif; ?>">
                <?php wp_nonce_field( 'riaco_source_image_save', 'riaco_source_image_nonce', false ); ?>
            </td>
        </tr>
        <?php
    }

    public function save_term_assignment( int $post_id ): void {
        $nonce = isset( $_POST['riaco_source_meta_box_nonce'] )
            ? sanitize_text_field( wp_unslash( $_POST['riaco_source_meta_box_nonce'] ) )
            : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'riaco_source_meta_box' ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) )                       return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )                     return;

        $term_id = isset( $_POST['riaco_review_source_term'] )
            ? absint( $_POST['riaco_review_source_term'] )
            : 0;

        wp_set_post_terms( $post_id, $term_id ? [ $term_id ] : [], 'riaco_review_source' );
    }

    public function save_image_meta( int $term_id ): void {
        $nonce = isset( $_POST['riaco_source_image_nonce'] )
            ? sanitize_text_field( wp_unslash( $_POST['riaco_source_image_nonce'] ) )
            : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'riaco_source_image_save' ) ) return;
        if ( ! current_user_can( 'manage_categories' ) ) return;

        if ( isset( $_POST['riaco_source_image'] ) ) {
            update_term_meta( $term_id, '_riaco_source_image', esc_url_raw( wp_unslash( $_POST['riaco_source_image'] ) ) );
        }
    }

    public function term_columns( array $columns ): array {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'name' ) {
                $new['riaco_source_image'] = __( 'Logo', 'riaco-reviews' );
            }
        }
        return $new;
    }

    public function render_term_column( string $content, string $column, int $term_id ): string {
        if ( $column !== 'riaco_source_image' ) return $content;

        $image = get_term_meta( $term_id, '_riaco_source_image', true );
        if ( $image ) {
            return '<img src="' . esc_url( $image ) . '" alt="" style="height:32px;width:auto;object-fit:contain;">';
        }
        return '—';
    }

    public function enqueue_assets( string $hook ): void {
        $taxonomy      = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
        $on_tax_screen = in_array( $hook, [ 'edit-tags.php', 'term.php' ], true )
            && $taxonomy === 'riaco_review_source';

        if ( ! $on_tax_screen ) return;

        wp_enqueue_media();
        wp_enqueue_script(
            'riaco-reviews-admin',
            plugin_dir_url( $this->file ) . 'assets/dist/admin.js',
            [],
            $this->version,
            true
        );
    }
}
