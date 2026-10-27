<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block logos-carousel<?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>"
       <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <?php
        if (isset($side_image) && $side_image) img_print_picture_tag(
            img: $side_image,
            max_size: "cover-mobile",
            min_size: "featured-small",
            classes: "logos-carousel__image",
            context: 'block/logos-carousel/side-image'
        );
        ?>

        <div class="logos-carousel__wrapper container tx-center">
            <?php print_title($title, $title_tag, "logos-carousel__title") ?>

            <?php
            if (!empty($logos)):
            ?>
                <div class="logos-carousel__carousel">
                    <div class="splide">
                        <div class="splide__track">
                            <div class="splide__list">
                                <?php foreach ($logos as $logo):
                                    if (empty($logo)) continue;
                                    $has_link = !empty($logo['link']['url']);
                                ?>
                                    <div class="logo splide__slide">
                                        <?php if ($has_link): ?>
                                            <a href="<?= esc_url($logo['link']['url']); ?>"
                                                <?= !empty($logo['link']['target']) ? ' target="' . esc_attr($logo['link']['target']) . '"' . ($logo['link']['target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?>
                                                class="logo__link"
                                                aria-label="<?= esc_attr($logo['link']['title'] ?? ''); ?>">
                                            <?php endif; ?>

                                            <?= img_generate_picture_tag(
                                                img: $logo['logo'],
                                                max_size: "thumbnail",
                                                classes: "logo__pic",
                                                context: 'block/logos-carousel/logo'
                                            ); ?>


                                            <?php if ($has_link): ?>
                                                <span><?= esc_html($logo['link']['title'] ?? '') ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            endif;
            ?>
        </div>
    </section>

<?php
endif;
?>