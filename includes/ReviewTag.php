<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;

class ReviewTag implements ServiceInterface {

    public function register(): void {
        add_action( 'init',                   [ $this, 'register_taxonomy' ] );
        add_action( 'save_post_riaco_review', [ $this, 'save_term_assignment' ] );
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
        $assigned = wp_get_post_terms( $post->ID, 'riaco_review_tag', [ 'fields' => 'ids' ] );
        $current  = ! empty( $assigned ) ? (int) $assigned[0] : 0;

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
