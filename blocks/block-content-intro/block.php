<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
    $background_type = get_valid_field_choice('background_type', $background_type ?? '');
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="
        block 
        content-intro
        <?= esc_attr(isset($background_type) && $background_type ? $background_type : 'bg-gradient') ?>
        <?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>
        "
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <?php
        if (isset($background_image) && $background_image && isset($background_type) && $background_type === 'image') img_print_picture_tag(img: $background_image, is_cover: true, classes: "content-intro__bg bg-image gradient-overlay");
        ?>

        <div class="content-intro__wrapper container">
            <?php if ($title): ?>
                <div class="content-intro__title tx-center">
                    <?= transform_inline_font_sizes(wp_kses_post($title)) ?>
                </div>
            <?php endif ?>

            <?php $columns = is_array($columns ?? null) ? $columns : []; ?>

            <?php if (!empty($columns['featured_image'])): ?>
                <div class="col col--image">
                    <?php img_print_picture_tag(
                        img: $columns['featured_image'],
                        max_size: "content",
                        min_size: "featured-small",
                        classes: "col__image"
                    );
                    ?>
                </div>
            <?php endif ?>

            <div class="col">
                <?php
                if (!empty($columns['pretitle'])) print_title($columns['pretitle'], $columns['pretitle_tag'] ?? 'p', "col__pretitle pretitle");
                if (!empty($columns['title']))  print_title($columns['title'], $columns['title_tag'] ?? 'p', "col__title");
                ?>
                <div class="col__content formatted-text">
                    <?= $columns['content'] ?? "" ?>
                </div>
            </div>

            <?php
            $cta_box = is_array($cta_box ?? null) ? $cta_box : [];
            if (!empty($cta_box['cta_link']["url"]) || !empty($cta_box['cta_link_2']["url"]) || !empty($cta_box['content'])):
            ?>
                <div class="inner-cta-box">

                    <?php if (!empty($cta_box['content'])): ?>
                        <?php get_template_part("template-parts/logo", "separator", ["classes" => "inner-cta-box__separator"]); ?>

                        <div class="inner-cta-box__content formatted-text">
                            <?= $cta_box['content'] ?>
                        </div>
                    <?php endif ?>

                    <div class="inner-cta-box__buttons">
                        <?php if (!empty($cta_box['cta_link']["url"])): ?>
                            <a href="<?= esc_url($cta_box['cta_link']["url"]) ?>"<?= !empty($cta_box['cta_link']['target']) ? ' target="' . esc_attr($cta_box['cta_link']['target']) . '"' . ($cta_box['cta_link']['target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> class="btn btn--tertiary" aria-label="<?= esc_attr($cta_box['cta_link']["title"]) ?>">
                                <span><?= esc_html($cta_box['cta_link']["title"]) ?></span>
                            </a>
                        <?php endif ?>
                        <?php if (!empty($cta_box['cta_link_2']["url"])): ?>
                            <a href="<?= esc_url($cta_box['cta_link_2']["url"]) ?>"<?= !empty($cta_box['cta_link_2']['target']) ? ' target="' . esc_attr($cta_box['cta_link_2']['target']) . '"' . ($cta_box['cta_link_2']['target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> class="btn btn--primary" aria-label="<?= esc_attr($cta_box['cta_link_2']["title"]) ?>">
                                <span><?= esc_html($cta_box['cta_link_2']["title"]) ?></span>
                            </a>
                        <?php endif ?>
                    </div>
                </div>
            <?php endif ?>

        </div>

    </section>

<?php
endif;
?>