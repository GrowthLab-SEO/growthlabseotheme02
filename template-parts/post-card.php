<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php
// Link target attribute (and rel for new tabs), empty when no target is set
$target_attr = !empty($args['link_target']) ? ' target="' . esc_attr($args['link_target']) . '"' . ($args['link_target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '';
?>
<article class="post-card <?= esc_attr($args["classes"] ?? '') ?>">
    <div class="post-card__wrapper">

        <?php
        if ($args['link_url']) {
            echo '<a href="' . esc_url($args['link_url']) . '" class="post-card__pic-wrapper"' . $target_attr . ' aria-label="' . esc_attr(wp_strip_all_tags($args['title'])) . '">';
        } else {
            echo "<div class='post-card__pic-wrapper'>";
        }
        if (isset($args['picture']) && $args['picture']) {
            img_print_picture_tag(img: $args["picture"], max_size: "content", min_size: "featured-small", classes: "post-card__pic");
        } elseif (get_field_options("options")["posts_default_image"] && !empty(get_field_options("options")["posts_default_image"])) {
            img_print_picture_tag(img: get_field_options("options")["posts_default_image"], max_size: "content", min_size: "featured-small", classes: "post-card__pic");
        } else {
            echo get_theme_svg('assets/icons/icon-file-image.svg');
        }
        if ($args['link_url']) {
            echo "</a>";
        } else {
            echo "</div>";
        }

        ?>

        <div class="post-card__inner">
            <span class="post-card__meta"><?= esc_html($args["meta"] ?? '') ?></span>

            <?php print_title($args["title"], $args["title_tag"], "post-card__title"); ?>

            <p class="post-card__content"><?= wp_kses_post($args["excerpt"] ?? '') ?></p>

            <?php if ($args['link_url']): ?>
                <a href="<?= esc_url($args['link_url']) ?>"<?= $target_attr ?> class="post-card__link" aria-label="<?= /* translators: %s: post title */ esc_attr(sprintf(__('Read more about %s', 'growthlabseotheme02'), wp_strip_all_tags($args['title']))) ?>">
                    <span><?= esc_html__('Read More', 'growthlabseotheme02') ?>
                        <?php echo get_theme_svg('assets/icons/icon-circle-arrow.svg') ?>
                    </span>
                </a>
            <?php endif ?>
        </div>
    </div>
</article>