<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;

class ReviewTag implements ServiceInterface {

    private const ALLOWED_TYPES = [
        'Thing', 'Product', 'SoftwareApplication', 'LocalBusiness',
        'Organization', 'Book', 'Movie', 'Course', 'Event',
    ];

    public function register(): void {
        add_action( 'init',                                    [ $this, 'register_taxonomy' ] );
        add_action( 'save_post_riaco_review',                  [ $this, 'save_term_assignment' ] );
        add_action( 'riaco_review_tag_add_form_fields',        [ $this, 'add_term_meta_fields' ] );
        add_action( 'riaco_review_tag_edit_form_fields',       [ $this, 'edit_term_meta_fields' ] );
        add_action( 'created_riaco_review_tag',                [ $this, 'save_term_meta' ] );
        add_action( 'edited_riaco_review_tag',                 [ $this, 'save_term_meta' ] );
    }

    public function register_taxonomy(): void {
        register_taxonomy( 'riaco_review_tag', 'riaco_review', [
            'labels' => [
                'name'          => __( 'Tags',            'riaco-reviews' ),
                'singular_name' => __( 'Tag',             'riaco-reviews' ),
                'add_new_item'  => __( 'Add New Tag',     'riaco-reviews' ),
                'edit_item'     => __( 'Edit Tag',        'riaco-reviews' ),
                'search_items'  => __( 'Search Tags',     'riaco-reviews' ),
                'not_found'     => __( 'No tags found.',  'riaco-reviews' ),
                'menu_name'     => __( 'Tags',            'riaco-reviews' ),
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
        $terms    = get_terms( [ 'taxonomy' => 'riaco_review_tag', 'hide_empty' => false ] );
        if ( is_wp_error( $terms ) ) {
            $terms = [];
        }
        $assigned = wp_get_post_terms( $post->ID, 'riaco_review_tag', [ 'fields' => 'ids' ] );
        $current  = ( is_array( $assigned ) && ! empty( $assigned ) ) ? (int) $assigned[0] : 0;

        wp_nonce_field( 'riaco_tag_meta_box', 'riaco_tag_meta_box_nonce' );
        ?>
        <div style="padding:4px 0;">
            <select name="riaco_review_tag_term" id="riaco_review_tag_term" style="min-width:180px;">
                <option value="0"><?php esc_html_e( '— None —', 'riaco-reviews' ); ?></option>
                <?php foreach ( $terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->term_id ); ?>"
                        <?php selected( $current, $term->term_id ); ?>>
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=riaco_review_tag&post_type=riaco_review' ) ); ?>"
               style="margin-left:8px;font-size:12px;" target="_blank">
                <?php esc_html_e( 'Manage Tags', 'riaco-reviews' ); ?>
            </a>
        </div>
        <?php
    }

    public function add_term_meta_fields( string $taxonomy ): void {
        ?>
        <div class="form-field">
            <label for="riaco_tag_url"><?php esc_html_e( 'Product / Subject URL', 'riaco-reviews' ); ?></label>
            <input type="url" id="riaco_tag_url" name="riaco_tag_url" class="regular-text" value="">
            <p class="description"><?php esc_html_e( 'URL of the product or subject being reviewed (used for JSON-LD structured data).', 'riaco-reviews' ); ?></p>
        </div>
        <div class="form-field">
            <label for="riaco_tag_type"><?php esc_html_e( 'Schema.org Type', 'riaco-reviews' ); ?></label>
            <select id="riaco_tag_type" name="riaco_tag_type">
                <?php foreach ( self::ALLOWED_TYPES as $type ) : ?>
                    <option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type ); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e( 'Schema.org type for the reviewed item. Use "Thing" if unsure.', 'riaco-reviews' ); ?></p>
            <?php wp_nonce_field( 'riaco_tag_meta_save', 'riaco_tag_meta_nonce', false ); ?>
        </div>
        <?php
    }

    public function edit_term_meta_fields( \WP_Term $term ): void {
        $url  = get_term_meta( $term->term_id, '_riaco_tag_url',  true );
        $type = get_term_meta( $term->term_id, '_riaco_tag_type', true );
        if ( ! $type ) {
            $type = 'Thing';
        }
        ?>
        <tr class="form-field">
            <th><label for="riaco_tag_url"><?php esc_html_e( 'Product / Subject URL', 'riaco-reviews' ); ?></label></th>
            <td>
                <input type="url" id="riaco_tag_url" name="riaco_tag_url"
                       class="regular-text" value="<?php echo esc_attr( $url ); ?>">
                <p class="description"><?php esc_html_e( 'URL of the product or subject being reviewed (used for JSON-LD structured data).', 'riaco-reviews' ); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th><label for="riaco_tag_type"><?php esc_html_e( 'Schema.org Type', 'riaco-reviews' ); ?></label></th>
            <td>
                <select id="riaco_tag_type" name="riaco_tag_type">
                    <?php foreach ( self::ALLOWED_TYPES as $t ) : ?>
                        <option value="<?php echo esc_attr( $t ); ?>" <?php selected( $type, $t ); ?>>
                            <?php echo esc_html( $t ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e( 'Schema.org type for the reviewed item. Use "Thing" if unsure.', 'riaco-reviews' ); ?></p>
                <?php wp_nonce_field( 'riaco_tag_meta_save', 'riaco_tag_meta_nonce', false ); ?>
            </td>
        </tr>
        <?php
    }

    public function save_term_meta( int $term_id ): void {
        $nonce = isset( $_POST['riaco_tag_meta_nonce'] )
            ? sanitize_text_field( wp_unslash( $_POST['riaco_tag_meta_nonce'] ) )
            : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'riaco_tag_meta_save' ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        if ( isset( $_POST['riaco_tag_url'] ) ) {
            update_term_meta( $term_id, '_riaco_tag_url', esc_url_raw( wp_unslash( $_POST['riaco_tag_url'] ) ) );
        }

        if ( isset( $_POST['riaco_tag_type'] ) ) {
            $type = sanitize_text_field( wp_unslash( $_POST['riaco_tag_type'] ) );
            if ( in_array( $type, self::ALLOWED_TYPES, true ) ) {
                update_term_meta( $term_id, '_riaco_tag_type', $type );
            }
        }
    }

    public function save_term_assignment( int $post_id ): void {
        $nonce = isset( $_POST['riaco_tag_meta_box_nonce'] )
            ? sanitize_text_field( wp_unslash( $_POST['riaco_tag_meta_box_nonce'] ) )
            : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'riaco_tag_meta_box' ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) )                    return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )                  return;

        $term_id = isset( $_POST['riaco_review_tag_term'] )
            ? absint( $_POST['riaco_review_tag_term'] )
            : 0;

        wp_set_post_terms( $post_id, $term_id ? [ $term_id ] : [], 'riaco_review_tag' );
    }
}
