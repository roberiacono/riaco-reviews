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
        add_action( 'add_meta_boxes',                                                      [ $this, 'add_meta_box' ] );
        add_action( 'save_post_riaco_review',                                              [ $this, 'save_meta' ] );
        add_filter( 'manage_riaco_review_posts_columns',                                   [ $this, 'columns' ] );
        add_action( 'manage_riaco_review_posts_custom_column',                             [ $this, 'render_column' ], 10, 2 );
        add_action( 'admin_enqueue_scripts',                                               [ $this, 'enqueue_assets' ] );
        add_filter( 'plugin_action_links_' . plugin_basename( $this->file ),              [ $this, 'action_links' ] );
        add_filter( 'admin_footer_text',                                                   [ $this, 'footer_text' ] );
        add_action( 'current_screen',                                                      [ $this, 'add_help_tab' ] );
    }

    public function action_links( array $links ): array {
        $manage = '<a href="' . esc_url( admin_url( 'edit.php?post_type=riaco_review' ) ) . '">'
            . esc_html__( 'Manage Reviews', 'riaco-reviews' )
            . '</a>';

        array_unshift( $links, $manage );

        return $links;
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
                                <?php if ( ! $fields['author_avatar'] ) : ?>style="display:none;"<?php endif; ?>>
                            <?php esc_html_e( 'Remove', 'riaco-reviews' ); ?>
                        </button>
                    </div>
                    <img id="riaco_avatar_preview"
                         src="<?php echo esc_url( $fields['author_avatar'] ); ?>"
                         alt="" style="width:48px;height:48px;border-radius:50%;margin-top:8px;object-fit:cover;<?php if ( ! $fields['author_avatar'] ) : ?>display:none;<?php endif; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="riaco_rating"><?php esc_html_e( 'Star Rating', 'riaco-reviews' ); ?></label></th>
                <td>
                    <select id="riaco_rating" name="riaco_rating">
                        <option value="0" <?php selected( $fields['rating'], 0 ); ?>>— <?php esc_html_e( 'None', 'riaco-reviews' ); ?> —</option>
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $fields['rating'], $i ); ?>>
                                <?php echo esc_html( $i ); ?> ★
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
        <?php do_action( 'riaco_reviews_meta_box_after_fields', $post ); ?>
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
            $rating = absint( $_POST['riaco_rating'] );
            if ( $rating === 0 ) {
                delete_post_meta( $post_id, '_riaco_review_rating' );
            } else {
                update_post_meta( $post_id, '_riaco_review_rating', max( 1, min( 5, $rating ) ) );
            }
        }

        do_action( 'riaco_reviews_save_meta', $post_id );
    }

    public function columns( array $columns ): array {
        return [
            'cb'                 => '<input type="checkbox">',
            'title'              => __( 'Review',        'riaco-reviews' ),
            'riaco_author'       => __( 'Author',        'riaco-reviews' ),
            'riaco_rating'       => __( 'Rating',        'riaco-reviews' ),
            'riaco_source_term'  => __( 'Source',        'riaco-reviews' ),
            'riaco_product_term' => __( 'Product',        'riaco-reviews' ),
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
                    echo '<span style="color:' . esc_attr( $color ) . ';font-size:16px;">★</span>';
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

            case 'riaco_product_term':
                $terms = get_the_terms( $post_id, 'riaco_review_product' );
                echo ( $terms && ! is_wp_error( $terms ) ) ? esc_html( $terms[0]->name ) : '—';
                break;

            case 'riaco_review_date':
                $date = get_post_meta( $post_id, '_riaco_review_date', true );
                if ( $date ) {
                    $ts = strtotime( $date );
                    echo false !== $ts ? esc_html( wp_date( get_option( 'date_format' ), $ts ) ) : '—';
                } else {
                    echo '—';
                }
                break;

            default:
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

        if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            wp_enqueue_media();
        }
        wp_enqueue_script(
            'riaco-reviews-admin',
            plugin_dir_url( $this->file ) . 'assets/dist/admin.js',
            [],
            RIACO_REVIEWS_VERSION,
            true
        );
        wp_add_inline_script(
            'riaco-reviews-admin',
            'var riacoAdminI18n = ' . wp_json_encode( [
                'selectAvatar' => __( 'Select Avatar', 'riaco-reviews' ),
                'useThisImage' => __( 'Use this image', 'riaco-reviews' ),
                'selectLogo'   => __( 'Select Logo',   'riaco-reviews' ),
            ] ) . ';',
            'before'
        );
    }

    public function footer_text( string $text ): string {
        $screen = get_current_screen();

        if ( ! $screen ) {
            return $text;
        }

        $plugin_screens = [ 'riaco_review', 'edit-riaco_review', 'edit-riaco_review_source', 'edit-riaco_review_product' ];

        if ( ! in_array( $screen->id, $plugin_screens, true ) ) {
            return $text;
        }

        return wp_kses_post( sprintf(
            /* translators: 1: plugin name 2: opening <a> tag 3: closing </a> tag */
            __( 'If you like %1$s please leave us a %2$s★★★★★%3$s rating. A huge thanks in advance!', 'riaco-reviews' ),
            '<strong>' . esc_html( 'RIACO Reviews' ) . '</strong>',
            '<a href="' . esc_url( 'https://wordpress.org/support/plugin/riaco-reviews/reviews/' ) . '" target="_blank" rel="noopener noreferrer">',
            '</a>'
        ) );
    }

    public function add_help_tab(): void {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'edit-riaco_review' ) return;

        $screen->add_help_tab( [
            'id'      => 'riaco-shortcode-reference',
            'title'   => __( 'Shortcode Reference', 'riaco-reviews' ),
            'content' => $this->get_help_tab_content(),
        ] );
    }

    private function get_help_tab_content(): string {
        $rows = [
            [ 'count',             '6',        __( 'Number of reviews to display.', 'riaco-reviews' ) ],
            [ 'layout',            'grid',      __( 'Layout style: <code>grid</code> or <code>masonry</code>.', 'riaco-reviews' ) ],
            [ 'card_style',        'default',   __( 'Card design: <code>default</code>, <code>modern</code>, or <code>minimal</code>.', 'riaco-reviews' ) ],
            [ 'orderby',           'date',      __( 'Sort field: <code>date</code>, <code>rating</code>, or <code>rand</code>.', 'riaco-reviews' ) ],
            [ 'order',             'DESC',      __( 'Sort direction: <code>ASC</code> or <code>DESC</code>.', 'riaco-reviews' ) ],
            [ 'product',           '',          __( 'Product slug to filter by. Comma-separate multiple slugs.', 'riaco-reviews' ) ],
            [ 'heading_level',     '3',         __( 'HTML heading level for the review title: <code>2</code>–<code>6</code>.', 'riaco-reviews' ) ],
            [ 'show_title',        '1',         __( 'Show review title.', 'riaco-reviews' ) ],
            [ 'show_author_name',  '1',         __( 'Show author name.', 'riaco-reviews' ) ],
            [ 'show_avatar',       '1',         __( 'Show author avatar.', 'riaco-reviews' ) ],
            [ 'show_date',         '0',         __( 'Show review date.', 'riaco-reviews' ) ],
            [ 'show_rating',       '1',         __( 'Show star rating.', 'riaco-reviews' ) ],
            [ 'show_source',       '1',         __( 'Show source logo.', 'riaco-reviews' ) ],
            [ 'show_product',       '1',         __( 'Show product badge.', 'riaco-reviews' ) ],
            [ 'show_shadow',       '1',         __( 'Card drop shadow.', 'riaco-reviews' ) ],
            [ 'min_width',         '280',       __( 'Minimum card width in px.', 'riaco-reviews' ) ],
            [ 'card_bg',           '',          __( 'Card background colour (hex, e.g. <code>#ffffff</code>).', 'riaco-reviews' ) ],
            [ 'card_text_color',   '',          __( 'Review text colour (hex).', 'riaco-reviews' ) ],
            [ 'card_border_color', '',          __( 'Card border colour (hex).', 'riaco-reviews' ) ],
            [ 'star_color',        '',          __( 'Star rating colour (hex).', 'riaco-reviews' ) ],
            [ 'product_bg',         '',          __( 'Product badge background colour (hex).', 'riaco-reviews' ) ],
            [ 'product_text_color', '',          __( 'Product badge text colour (hex).', 'riaco-reviews' ) ],
            [ 'font_size',         '',          __( 'Review text size in rem (e.g. <code>1</code>).', 'riaco-reviews' ) ],
            [ 'line_height',       '',          __( 'Line height (e.g. <code>1.7</code>).', 'riaco-reviews' ) ],
        ];

        $html = '<p><strong>' . esc_html__( 'Usage:', 'riaco-reviews' ) . '</strong> <code>[riaco_reviews]</code></p>';
        $html .= '<p>' . wp_kses( __( '<strong>Example:</strong> <code>[riaco_reviews count="9" layout="masonry" card_style="modern" product="my-product"]</code>', 'riaco-reviews' ), [ 'strong' => [], 'code' => [] ] ) . '</p>';
        $html .= '<table class="widefat striped" style="max-width:720px;">';
        $html .= '<thead><tr>';
        $html .= '<th>' . esc_html__( 'Parameter', 'riaco-reviews' ) . '</th>';
        $html .= '<th>' . esc_html__( 'Default', 'riaco-reviews' ) . '</th>';
        $html .= '<th>' . esc_html__( 'Description', 'riaco-reviews' ) . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ( $rows as [ $param, $default, $desc ] ) {
            $html .= '<tr>';
            $html .= '<td><code>' . esc_html( $param ) . '</code></td>';
            $html .= '<td>' . ( '' !== $default ? '<code>' . esc_html( $default ) . '</code>' : '<em>' . esc_html__( 'empty', 'riaco-reviews' ) . '</em>' ) . '</td>';
            $html .= '<td>' . wp_kses( $desc, [ 'code' => [] ] ) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    private function get_meta( int $post_id ): array {
        return [
            'author_name'   => get_post_meta( $post_id, '_riaco_review_author_name',   true ),
            'author_avatar' => get_post_meta( $post_id, '_riaco_review_author_avatar', true ),
            'rating'        => (int) get_post_meta( $post_id, '_riaco_review_rating',  true ),
            'review_date'   => get_post_meta( $post_id, '_riaco_review_date',          true ),
            'source_url'    => get_post_meta( $post_id, '_riaco_review_source_url',    true ),
        ];
    }
}
