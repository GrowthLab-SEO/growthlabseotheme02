<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
    $background_type = get_valid_field_choice('background_type', $background_type ?? '');
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block selling-points
        <?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>
        <?= esc_attr($background_type !== null && $background_type === "dark" ? "bg-gradient" : $background_type) ?>"
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <?php
        if (isset($background_image) && $background_image && isset($background_type) && $background_type === 'image') img_print_picture_tag(img: $background_image, is_cover: true, classes: "selling-points__bg bg-image gradient-overlay", context: 'block/selling-points/background');
        ?>

        <div class=" selling-points__wrapper container">

            <?php
            if (isset($title) && $title) {
                print_title($title, $title_tag, "selling-points__title");
                get_template_part('template-parts/logo', 'separator', array('classes' => 'selling-points__separator'));
            }
            if (isset($text_content) && $text_content):
            ?>
                <div class="selling-points__content formatted-text tx-center">
                    <?= $text_content ?>
                </div>
            <?php
            endif
            ?>

            <?php if (!empty($items)): ?>
                <div class="selling-points__carousel">
                    <div class="splide">
                        <div class="splide__track">
                            <div class="splide__list selling-points__grid">

                                <?php
                                foreach ($items as $item) :
                                    if (!empty($item)):
                                ?>

                                        <div class="item-card splide__slide">
                                            <div class="item-card__inner">
                                                <div class="item-card__icon">
                                                    <?php img_print_picture_tag(img: $item['icon'] ?? '', classes: 'item-card__icon-img', max_size: "thumbnail", context: 'block/selling-points/icon') ?>
                                                </div>

                                                <?php
                                                $item_title_html = $item['title'] ?? '';
                                                if (!empty($item['title']) && !empty($item['link']['url'])) {
                                                    $link_target = $item['link']['target'] ?? '';
                                                    $item_title_html = '<a href="' . esc_url($item['link']['url']) . '"'
                                                        . ($link_target ? ' target="' . esc_attr($link_target) . '"' . ($link_target === '_blank' ? ' rel="noopener noreferrer"' : '') : '')
                                                        . ' class="item-card__link">' . $item['title'] . '</a>';
                                                }
                                                print_title($item_title_html, $item['title_tag'] ?? 'p', "item-card__title");
                                                ?>

                                                <?php if (!empty($item['content'])): ?>
                                                    <p class="item-card__description"><?= wp_kses_post($item['content']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                <?php
                                    endif;
                                endforeach;
                                ?>

                            </div>
                        </div>

                        <?php
                        get_template_part('template-parts/splide', 'navigation', array(
                            'nav_link' => isset($cta_link) && $cta_link ? $cta_link : null,
                            'classes' => 'selling-points__arrows'
                        ));
                        ?>

                    </div>
                </div>

            <?php endif; ?>
        </div>
    </section>

<?php
endif;
?>