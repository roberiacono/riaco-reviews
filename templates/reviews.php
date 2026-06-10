<?php
/**
 * Template: reviews wrapper.
 *
 * Variables available:
 *   $reviews  WP_Query
 *   $atts     array (sanitized display options)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$layout          = $atts['layout'];
$container_class = 'riaco-reviews riaco-reviews--' . esc_attr( $layout );
$inner_class     = 'riaco-reviews__' . esc_attr( $layout );
$style_attr      = ! empty( $atts['custom_style'] ) ? ' style="' . esc_attr( $atts['custom_style'] ) . '"' : '';
?>
<?php do_action( 'riaco_reviews_before_loop', $atts ); ?>
<div class="<?php echo $container_class; ?>"<?php echo $style_attr; ?>>
    <div class="<?php echo $inner_class; ?>">
        <?php if ( $reviews->have_posts() ) : ?>
            <?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
                <?php
                $post_id      = get_the_ID();
                $source_terms  = get_the_terms( $post_id, 'riaco_review_source' );
                $source_term   = ( $source_terms && ! is_wp_error( $source_terms ) ) ? $source_terms[0] : null;
                $tag_terms = get_the_terms( $post_id, 'riaco_review_tag' );
                $tag_term  = ( $tag_terms && ! is_wp_error( $tag_terms ) ) ? $tag_terms[0] : null;
                $meta = [
                    'author_name'   => get_post_meta( $post_id, '_riaco_review_author_name',   true ),
                    'author_avatar' => get_post_meta( $post_id, '_riaco_review_author_avatar', true ),
                    'rating'        => (int) get_post_meta( $post_id, '_riaco_review_rating',  true ),
                    'review_date'   => get_post_meta( $post_id, '_riaco_review_date',          true ),
                    'source_image'  => $source_term ? get_term_meta( $source_term->term_id, '_riaco_source_image', true ) : '',
                    'source_name'   => $source_term ? $source_term->name : '',
                    'source_url'    => get_post_meta( $post_id, '_riaco_review_source_url',    true ),
                    'tag_name'      => $tag_term ? $tag_term->name : '',
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
                include $card_template;
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
