<?php

namespace RIACO\Reviews;

if ( ! defined( 'ABSPATH' ) ) exit;

use RIACO\Reviews\Interfaces\ServiceInterface;

class Admin implements ServiceInterface {

    private string $file;

    public function __construct( string $file ) {
        $this->file = $file;
    }

    public function register(): void {
        add_action( 'add_meta_boxes',                           [ $this, 'add_meta_box' ] );
        add_action( 'save_post_riaco_review',                   [ $this, 'save_meta' ] );
        add_filter( 'manage_riaco_review_posts_columns',        [ $this, 'columns' ] );
        add_action( 'manage_riaco_review_posts_custom_column',  [ $this, 'render_column' ], 10, 2 );
        add_action( 'admin_enqueue_scripts',                    [ $this, 'enqueue_assets' ] );
    }

    public function add_meta_box(): void {
        add_meta_box(
            'riaco_review_details',
            __( 'Review Details', 'riaco-reviews' ),
            [ $this, 'render_meta_box' ],
            'riaco_review',
            'normal',
            'high'
        );
    }

    public function render_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'riaco_review_meta', 'riaco_review_meta_nonce' );

        $fields = $this->get_meta( $post->ID );
        ?>
        <table class="form-table riaco-reviews-meta-table">
            <tr>
                <th><label for="riaco_author_name"><?php esc_html_e( 'Author Name', 'riaco-reviews' ); ?></label></th>
                <td><input type="text" id="riaco_author_name" name="riaco_author_name"
                           class="regular-text" value="<?php echo esc_attr( $fields['author_name'] ); ?>"></td>
            </tr>
            <tr>
                <th><label for="riaco_author_avatar"><?php esc_html_e( 'Avatar', 'riaco-reviews' ); ?></label></th>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="url" id="riaco_author_avatar" name="riaco_author_avatar"
                               class="regular-text" value="<?php echo esc_attr( $fields['author_avatar'] ); ?>">
                        <button type="button" id="riaco_avatar_upload_btn" class="button button-secondary">
                            <?php esc_html_e( 'Upload Image', 'riaco-reviews' ); ?>
                        </button>
                        <button type="button" id="riaco_avatar_remove_btn" class="button-link button-link-delete"
                                <?php echo $fields['author_avatar'] ? '' : 'style="display:none;"'; ?>>
                            <?php esc_html_e( 'Remove', 'riaco-reviews' ); ?>
                        </button>
                    </div>
                    <img id="riaco_avatar_preview"
                         src="<?php echo esc_url( $fields['author_avatar'] ); ?>"
                         alt="" style="width:48px;height:48px;border-radius:50%;margin-top:8px;object-fit:cover;<?php echo $fields['author_avatar'] ? '' : 'display:none;'; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="riaco_rating"><?php esc_html_e( 'Star Rating', 'riaco-reviews' ); ?></label></th>
                <td>
                    <select id="riaco_rating" name="riaco_rating">
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <option value="<?php echo $i; ?>" <?php selected( (int) $fields['rating'], $i ); ?>>
                                <?php echo $i; ?> ★
                            </option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="riaco_review_date"><?php esc_html_e( 'Review Date', 'riaco-reviews' ); ?></label></th>
                <td><input type="date" id="riaco_review_date" name="riaco_review_date"
                           value="<?php echo esc_attr( $fields['review_date'] ); ?>"></td>
            </tr>
            <tr>
                <th><label for="riaco_source_url"><?php esc_html_e( 'Source URL', 'riaco-reviews' ); ?></label></th>
                <td>
                    <input type="url" id="riaco_source_url" name="riaco_source_url"
                           class="regular-text" value="<?php echo esc_attr( $fields['source_url'] ); ?>">
                    <p class="description"><?php esc_html_e( 'Link to the original review.', 'riaco-reviews' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta( int $post_id ): void {
        $nonce = isset( $_POST['riaco_review_meta_nonce'] )
            ? sanitize_text_field( wp_unslash( $_POST['riaco_review_meta_nonce'] ) )
            : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'riaco_review_meta' ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) )                  return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )                return;

        $map = [
            'riaco_author_name'   => '_riaco_review_author_name',
            'riaco_review_date'   => '_riaco_review_date',
        ];

        foreach ( $map as $input => $meta_key ) {
            if ( isset( $_POST[ $input ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $input ] ) ) );
            }
        }

        if ( isset( $_POST['riaco_author_avatar'] ) ) {
            update_post_meta( $post_id, '_riaco_review_author_avatar', esc_url_raw( wp_unslash( $_POST['riaco_author_avatar'] ) ) );
        }

        if ( isset( $_POST['riaco_source_url'] ) ) {
            update_post_meta( $post_id, '_riaco_review_source_url', esc_url_raw( wp_unslash( $_POST['riaco_source_url'] ) ) );
        }

        if ( isset( $_POST['riaco_rating'] ) ) {
            $rating = max( 1, min( 5, absint( $_POST['riaco_rating'] ) ) );
            update_post_meta( $post_id, '_riaco_review_rating', $rating );
        }

    }

    public function columns( array $columns ): array {
        return [
            'cb'                 => '<input type="checkbox">',
            'title'              => __( 'Review',        'riaco-reviews' ),
            'riaco_author'       => __( 'Author',        'riaco-reviews' ),
            'riaco_rating'       => __( 'Rating',        'riaco-reviews' ),
            'riaco_source_term'  => __( 'Source',        'riaco-reviews' ),
            'riaco_tag_term'     => __( 'Tag',            'riaco-reviews' ),
            'riaco_review_date'  => __( 'Review Date',   'riaco-reviews' ),
            'date'               => __( 'Added',         'riaco-reviews' ),
        ];
    }

    public function render_column( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'riaco_author':
                $name = get_post_meta( $post_id, '_riaco_review_author_name', true );
                echo esc_html( $name );
                break;

            case 'riaco_rating':
                $rating = (int) get_post_meta( $post_id, '_riaco_review_rating', true );
                for ( $i = 1; $i <= 5; $i++ ) {
                    $color = ( $i <= $rating ) ? '#f59e0b' : '#ddd';
                    echo '<span style="color:' . $color . ';font-size:16px;">★</span>';
                }
                break;

            case 'riaco_source_term':
                $terms = get_the_terms( $post_id, 'riaco_review_source' );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    $term  = $terms[0];
                    $image = get_term_meta( $term->term_id, '_riaco_source_image', true );
                    if ( $image ) {
                        echo '<img src="' . esc_url( $image ) . '" alt="" style="height:20px;width:auto;vertical-align:middle;margin-right:4px;">';
                    }
                    echo esc_html( $term->name );
                } else {
                    echo '—';
                }
                break;

            case 'riaco_tag_term':
                $terms = get_the_terms( $post_id, 'riaco_review_tag' );
                echo ( $terms && ! is_wp_error( $terms ) ) ? esc_html( $terms[0]->name ) : '—';
                break;

            case 'riaco_review_date':
                $date = get_post_meta( $post_id, '_riaco_review_date', true );
                echo $date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ) : '—';
                break;
        }
    }

    public function enqueue_assets( string $hook ): void {
        global $post_type;
        if ( $post_type !== 'riaco_review' ) return;

        wp_enqueue_style(
            'riaco-reviews-admin',
            plugin_dir_url( $this->file ) . 'assets/dist/admin.css',
            [],
            RIACO_REVIEWS_VERSION
        );

        wp_enqueue_media();
        wp_enqueue_script(
            'riaco-reviews-admin',
            plugin_dir_url( $this->file ) . 'assets/dist/admin.js',
            [],
            RIACO_REVIEWS_VERSION,
            true
        );
    }

    private function get_meta( int $post_id ): array {
        return [
            'author_name'   => get_post_meta( $post_id, '_riaco_review_author_name',   true ),
            'author_avatar' => get_post_meta( $post_id, '_riaco_review_author_avatar', true ),
            'rating'        => (int) get_post_meta( $post_id, '_riaco_review_rating',  true ) ?: 5,
            'review_date'   => get_post_meta( $post_id, '_riaco_review_date',          true ),
            'source_url'    => get_post_meta( $post_id, '_riaco_review_source_url',    true ),
        ];
    }
}
