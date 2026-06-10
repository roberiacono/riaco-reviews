<?php
/**
 * Template: single review card.
 *
 * Variables available:
 *   $post_id  int
 *   $meta     array (author_name, author_handle, author_avatar, rating, review_date)
 *   $atts     array (show_author_name, show_avatar, show_date, show_rating, show_source, show_tag)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$rating      = max( 0, min( 5, (int) $meta['rating'] ) );
$review_text = get_the_content();
$has_avatar  = $atts['show_avatar'] && ! empty( $meta['author_avatar'] );
$has_source  = $atts['show_source'] && ! empty( $meta['source_image'] );
$has_footer  = $atts['show_author_name'] || $atts['show_date'] || $atts['show_avatar'];
?>
<article class="riaco-reviews__card riaco-reviews__card--<?php echo esc_attr( $atts['card_style'] ); ?>">

    <?php if ( $has_source ) : ?>
        <div class="riaco-reviews__source">
            <?php if ( ! empty( $meta['source_url'] ) ) : ?>
                <a href="<?php echo esc_url( $meta['source_url'] ); ?>" target="_blank" rel="noopener noreferrer nofollow" class="riaco-reviews__source-link" aria-label="<?php echo esc_attr( $meta['source_name'] ); ?>">
                    <img class="riaco-reviews__source-logo"
                         src="<?php echo esc_url( $meta['source_image'] ); ?>"
                         alt="<?php echo esc_attr( $meta['source_name'] ); ?>"
                         loading="lazy">
                </a>
            <?php else : ?>
                <img class="riaco-reviews__source-logo"
                     src="<?php echo esc_url( $meta['source_image'] ); ?>"
                     alt="<?php echo esc_attr( $meta['source_name'] ); ?>"
                     loading="lazy">
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ( $atts['show_tag'] && ! empty( $meta['tag_name'] ) ) : ?>
        <div class="riaco-reviews__card-tag">
            <?php echo esc_html( $meta['tag_name'] ); ?>
        </div>
    <?php endif; ?>

    <?php if ( $atts['card_style'] !== 'minimal' ) : ?>
        <div class="riaco-reviews__quote-mark" aria-hidden="true">&ldquo;</div>
    <?php endif; ?>

    <div class="riaco-reviews__body">
        <p class="riaco-reviews__text"><?php echo wp_kses_post( $review_text ); ?></p>
    </div>

    <?php if ( $atts['show_rating'] && $rating > 0 ) : ?>
        <div class="riaco-reviews__rating" aria-label="<?php echo esc_attr( sprintf( __( '%d out of 5 stars', 'riaco-reviews' ), $rating ) ); ?>">
            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                <span class="riaco-reviews__star<?php echo ( $i <= $rating ) ? ' riaco-reviews__star--filled' : ''; ?>" aria-hidden="true">★</span>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <?php if ( $has_footer ) : ?>
        <footer class="riaco-reviews__footer">

            <?php if ( $has_avatar ) : ?>
                <div class="riaco-reviews__avatar-wrap">
                    <img
                        class="riaco-reviews__avatar"
                        src="<?php echo esc_url( $meta['author_avatar'] ); ?>"
                        alt="<?php echo esc_attr( $meta['author_name'] ); ?>"
                        loading="lazy"
                        width="48"
                        height="48"
                    >
                </div>
            <?php elseif ( $atts['show_avatar'] && ! empty( $meta['author_name'] ) ) : ?>
                <div class="riaco-reviews__avatar-wrap">
                    <div class="riaco-reviews__avatar riaco-reviews__avatar--initials" aria-hidden="true">
                        <?php echo esc_html( mb_strtoupper( mb_substr( $meta['author_name'], 0, 1 ) ) ); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $atts['show_author_name'] || $atts['show_date'] ) : ?>
                <div class="riaco-reviews__author">
                    <?php if ( $atts['show_author_name'] && ! empty( $meta['author_name'] ) ) : ?>
                        <span class="riaco-reviews__author-name"><?php echo esc_html( $meta['author_name'] ); ?></span>
                    <?php endif; ?>
                    <?php if ( $atts['show_date'] && ! empty( $meta['review_date'] ) ) : ?>
                        <time class="riaco-reviews__date" datetime="<?php echo esc_attr( $meta['review_date'] ); ?>">
                            <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $meta['review_date'] ) ) ); ?>
                        </time>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </footer>
    <?php endif; ?>

</article>
