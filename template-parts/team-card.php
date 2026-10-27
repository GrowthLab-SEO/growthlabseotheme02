<?php
if (!defined('ABSPATH')) {
    exit;
}
$options = get_field_options("options");

?>
<div class="team-card <?= esc_attr($args["classes"] ?? '') ?>">
    <div class="team-card__wrapper">

        <div class='team-card__pic-wrapper'>
            <?php
            if (isset($args['picture']) && $args['picture']) {
                img_print_picture_tag(img: $options["logo_symbol"], classes: "team-card__symbol", context: 'card/team/logo-symbol');
                img_print_picture_tag(img: $args["picture"], max_size: "content", min_size: "featured-small", classes: "team-card__pic", context: 'card/team/picture');
            } else {
                echo get_theme_svg('assets/icons/icon-file-image.svg');
            }
            ?>
        </div>

        <div class="team-card__inner">
            <p class="team-card__role pretitle"><?= wp_kses_post($args["role"] ?? '') ?></p>

            <?php print_title($args["title"], $args["title_tag"], "team-card__title"); ?>

            <p class="team-card__content formatted-text"><?php if (!empty($args["content"])) echo wp_kses_post($args["content"]) ?></p>

            <?php if ($args['link_url']): ?>
                <div class="team-card__btn">
                    <a href="<?= esc_url($args['link_url']) ?>"<?= !empty($args['link_target']) ? ' target="' . esc_attr($args['link_target']) . '"' . ($args['link_target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> class="btn btn--tertiary" aria-label="<?= /* translators: %s: team member first name */ esc_attr(sprintf(__('Meet %s', 'growthlabseotheme02'), $args['first_name'] ?? '')) ?>">
                        <span><?= /* translators: %s: team member first name */ esc_html(sprintf(__('MEET %s', 'growthlabseotheme02'), $args['first_name'] ?? '')) ?></span>
                    </a>
                </div>
            <?php endif ?>
        </div>
    </div>

    <?php get_template_part("template-parts/logo", "separator", ["classes" => "team-card__separator"]); ?>
</div>