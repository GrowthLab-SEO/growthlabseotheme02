<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="team-card-grid <?= esc_attr($args["classes"] ?? '') ?>">
    <?php if (!empty($args['link_url'])): ?>
        <a href="<?= esc_url($args['link_url']) ?>"<?= !empty($args['link_target']) ? ' target="' . esc_attr($args['link_target']) . '"' . ($args['link_target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> class="team-card-grid__wrapper" aria-label="<?= esc_attr(wp_strip_all_tags($args['name'] ?? '')) ?>">
            <?php
            if (isset($args['picture']) && $args['picture'] && $args['picture'] !== '') {
                img_print_picture_tag(img: $args["picture"], max_size: "cover-mobile", min_size: "featured-small", classes: "team-card-grid__pic", context: 'card/team-grid/picture');
            } else {
                echo get_theme_svg('assets/icons/icon-file-image.svg');
            }
            ?>

            <div class="team-card-grid__inner tx-center">

                <p class="team-card-grid__title"><?= wp_kses_post($args["name"] ?? '') ?></p>

                <p class="team-card-grid__role"><?= wp_kses_post($args["role"] ?? '') ?></p>

            </div>
        </a>
    <?php endif ?>
</div>