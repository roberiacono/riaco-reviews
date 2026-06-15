<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;

class ReviewProduct implements ServiceInterface {

    private const ALLOWED_TYPES = [
        'Thing', 'Product', 'SoftwareApplication', 'LocalBusiness',
        'Organization', 'Book', 'Movie', 'Course', 'Event',
    ];

    public function register(): void {
        add_action( 'init',                                        [ $this, 'register_taxonomy' ] );
        add_action( 'save_post_riaco_review',                      [ $this, 'save_term_assignment' ] );
        add_action( 'riaco_review_product_add_form_fields',        [ $this, 'add_term_meta_fields' ] );
        add_action( 'riaco_review_product_edit_form_fields',       [ $this, 'edit_term_meta_fields' ] );
        add_action( 'created_riaco_review_product',                [ $this, 'save_term_meta' ] );
        add_action( 'edited_riaco_review_product',                 [ $this, 'save_term_meta' ] );
    }

    public function register_taxonomy(): void {
        register_taxonomy( 'riaco_review_product', 'riaco_review', [
            'labels' => [
                'name'          => __( 'Products',            'riaco-reviews' ),
                'singular_name' => __( 'Product',             'riaco-reviews' ),
                'add_new_item'  => __( 'Add New Product',     'riaco-reviews' ),
                'edit_item'     => __( 'Edit Product',        'riaco-reviews' ),
                'search_items'  => __( 'Search Products',     'riaco-reviews' ),
                'not_found'     => __( 'No products found.',  'riaco-reviews' ),
                'menu_name'     => __( 'Products',            'riaco-reviews' ),
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
        $terms    = get_terms( [ 'taxonomy' => 'riaco_review_product', 'hide_empty' => false ] );
        if ( is_wp_error( $terms ) ) {
            $terms = [];
        }
        $assigned = wp_get_post_terms( $post->ID, 'riaco_review_product', [ 'fields' => 'ids' ] );
        $current  = ( is_array( $assigned ) && ! empty( $assigned ) ) ? (int) $assigned[0] : 0;

        wp_nonce_field( 'riaco_product_meta_box', 'riaco_product_meta_box_nonce' );
        ?>
        <div style="padding:4px 0;">
            <select name="riaco_review_product_term" id="riaco_review_product_term" style="min-width:180px;">
                <option value="0"><?php esc_html_e( '— None —', 'riaco-reviews' ); ?></option>
                <?php foreach ( $terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->term_id ); ?>"
                        <?php selected( $current, $term->term_id ); ?>>
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=riaco_review_product&post_type=riaco_review' ) ); ?>"
               style="margin-left:8px;font-size:12px;" target="_blank">
                <?php esc_html_e( 'Manage Products', 'riaco-reviews' ); ?>
            </a>
        </div>
        <?php
    }

    public function add_term_meta_fields( string $taxonomy ): void {
        ?>
        <div class="form-field">
            <label for="riaco_product_url"><?php esc_html_e( 'Product / Subject URL', 'riaco-reviews' ); ?></label>
            <input type="url" id="riaco_product_url" name="riaco_product_url" class="regular-text" value="">
            <p class="description"><?php esc_html_e( 'URL of the product or subject being reviewed (used for JSON-LD structured data).', 'riaco-reviews' ); ?></p>
        </div>
        <div class="form-field">
            <label for="riaco_product_type"><?php esc_html_e( 'Schema.org Type', 'riaco-reviews' ); ?></label>
            <select id="riaco_product_type" name="riaco_product_type">
                <?php foreach ( self::ALLOWED_TYPES as $type ) : ?>
                    <option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type ); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e( 'Schema.org type for the reviewed item. Use "Thing" if unsure.', 'riaco-reviews' ); ?></p>
            <?php wp_nonce_field( 'riaco_product_meta_save', 'riaco_product_meta_nonce', false ); ?>
        </div>
        <?php
    }

    public function edit_term_meta_fields( \WP_Term $term ): void {
        $url  = get_term_meta( $term->term_id, '_riaco_product_url',  true );
        $type = get_term_meta( $term->term_id, '_riaco_product_type', true );
        if ( ! $type ) {
            $type = 'Thing';
        }
        ?>
        <tr class="form-field">
            <th><label for="riaco_product_url"><?php esc_html_e( 'Product / Subject URL', 'riaco-reviews' ); ?></label></th>
            <td>
                <input type="url" id="riaco_product_url" name="riaco_product_url"
                       class="regular-text" value="<?php echo esc_attr( $url ); ?>">
                <p class="description"><?php esc_html_e( 'URL of the product or subject being reviewed (used for JSON-LD structured data).', 'riaco-reviews' ); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th><label for="riaco_product_type"><?php esc_html_e( 'Schema.org Type', 'riaco-reviews' ); ?></label></th>
            <td>
                <select id="riaco_product_type" name="riaco_product_type">
                    <?php foreach ( self::ALLOWED_TYPES as $t ) : ?>
                        <option value="<?php echo esc_attr( $t ); ?>" <?php selected( $type, $t ); ?>>
                            <?php echo esc_html( $t ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e( 'Schema.org type for the reviewed item. Use "Thing" if unsure.', 'riaco-reviews' ); ?></p>
                <?php wp_nonce_field( 'riaco_product_meta_save', 'riaco_product_meta_nonce', false ); ?>
            </td>
        </tr>
        <?php
    }

    public function save_term_meta( int $term_id ): void {
        $nonce = isset( $_POST['riaco_product_meta_nonce'] )
            ? wp_unslash( $_POST['riaco_product_meta_nonce'] )
            : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'riaco_product_meta_save' ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        if ( isset( $_POST['riaco_product_url'] ) ) {
            update_term_meta( $term_id, '_riaco_product_url', esc_url_raw( wp_unslash( $_POST['riaco_product_url'] ) ) );
        }

        if ( isset( $_POST['riaco_product_type'] ) ) {
            $type = sanitize_text_field( wp_unslash( $_POST['riaco_product_type'] ) );
            if ( in_array( $type, self::ALLOWED_TYPES, true ) ) {
                update_term_meta( $term_id, '_riaco_product_type', $type );
            }
        }
    }

    public function save_term_assignment( int $post_id ): void {
        $nonce = isset( $_POST['riaco_product_meta_box_nonce'] )
            ? wp_unslash( $_POST['riaco_product_meta_box_nonce'] )
            : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'riaco_product_meta_box' ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) )                       return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )                     return;

        $term_id = isset( $_POST['riaco_review_product_term'] )
            ? absint( $_POST['riaco_review_product_term'] )
            : 0;

        wp_set_post_terms( $post_id, $term_id ? [ $term_id ] : [], 'riaco_review_product' );
    }
}
