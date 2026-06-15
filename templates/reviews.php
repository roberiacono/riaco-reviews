<?php
/**
 * Template: reviews wrapper.
 *
 * Variables available:
 *   $reviews  WP_Query
 *   $atts     array (sanitized display options)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template included inside Renderer::render(); all variables are local to that function scope.
$layout          = $atts['layout'];
$container_class = 'riaco-reviews riaco-reviews--' . $layout;
$inner_class     = 'riaco-reviews__' . $layout;
?>
<?php do_action( 'riaco_reviews_before_loop', $atts ); ?>
<div class="<?php echo esc_attr( $container_class ); ?>"<?php if ( ! empty( $atts['custom_style'] ) ) : ?> style="<?php echo esc_attr( $atts['custom_style'] ); ?>"<?php endif; ?>>
    <div class="<?php echo esc_attr( $inner_class ); ?>">
        <?php if ( $reviews->have_posts() ) : ?>
            <?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
                <?php
                $post_id      = get_the_ID();
                $source_terms  = get_the_terms( $post_id, 'riaco_review_source' );
                $source_term   = ( $source_terms && ! is_wp_error( $source_terms ) ) ? $source_terms[0] : null;
                $product_terms = get_the_terms( $post_id, 'riaco_review_product' );
                $product_term  = ( $product_terms && ! is_wp_error( $product_terms ) ) ? $product_terms[0] : null;
                $meta = [
                    'author_name'   => get_post_meta( $post_id, '_riaco_review_author_name',   true ),
                    'author_avatar' => get_post_meta( $post_id, '_riaco_review_author_avatar', true ),
                    'rating'        => (int) get_post_meta( $post_id, '_riaco_review_rating',  true ),
                    'review_date'   => get_post_meta( $post_id, '_riaco_review_date',          true ),
                    'source_image'  => $source_term ? get_term_meta( $source_term->term_id, '_riaco_source_image', true ) : '',
                    'source_name'   => $source_term ? $source_term->name : '',
                    'source_url'    => get_post_meta( $post_id, '_riaco_review_source_url',    true ),
                    'product_name'  => $product_term ? $product_term->name : '',
                    'product_url'   => $product_term ? get_term_meta( $product_term->term_id, '_riaco_product_url',  true ) : '',
                    'product_type'  => $product_term ? get_term_meta( $product_term->term_id, '_riaco_product_type', true ) : '',
                ];
                $meta = apply_filters( 'riaco_reviews_card_meta', $meta, $post_id, $atts );
                do_action( 'riaco_reviews_before_card', $post_id, $meta, $atts );
                $card_template = apply_filters(
                    'riaco_reviews_card_template_path',
                    RIACO_REVIEWS_DIR . 'templates/partials/card.php',
                    $atts['card_style'],
                    $post_id,
                    $meta
                );
                if ( is_file( $card_template ) ) {
                    $real       = realpath( $card_template );
                    $plugin_dir = realpath( RIACO_REVIEWS_DIR );
                    $theme_dir  = realpath( get_theme_root() );
                    $in_plugin  = $real && $plugin_dir && strpos( $real, $plugin_dir ) === 0;
                    $in_theme   = $real && $theme_dir  && strpos( $real, $theme_dir )  === 0;
                    if ( $in_plugin || $in_theme ) {
                        include $real;
                    }
                }
                do_action( 'riaco_reviews_after_card', $post_id, $meta, $atts );
                ?>
            <?php endwhile; ?>
        <?php else : ?>
            <?php
            $empty_html = '<p class="riaco-reviews__empty">' . esc_html__( 'No reviews found.', 'riaco-reviews' ) . '</p>';
            echo wp_kses_post( apply_filters( 'riaco_reviews_no_reviews_html', $empty_html, $atts ) );
            ?>
        <?php endif; ?>
    </div>
</div>
<?php do_action( 'riaco_reviews_after_loop', $atts ); ?>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
