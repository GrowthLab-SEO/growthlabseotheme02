<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="testimonial-card <?= esc_attr($args["classes"] ?? '') ?>">
    <div class="testimonial-card__wrapper">
        <div class="testimonial-card__inner">

            <div class=" testimonial-card__stars">
                <?php
                $i = 1;
                $star_svg = get_theme_svg('assets/icons/icon-star.svg');
                while ($i <= 5) {
                    echo '<span class="star">';
                    echo $star_svg;
                    echo '</span>';
                    $i++;
                }
                ?>
            </div>

            <blockquote class="testimonial-card__content">
                <p>
                    "<?= wp_kses_post($args["content"] ?? '') ?>"
                </p>
            </blockquote>

            <p class="testimonial-card__author">
                <?= wp_kses_post($args["author"] ?? '') ?>
                <span><?= wp_kses_post($args["role"] ?? '') ?></span>
            </p>

        </div>
    </div>
</div>