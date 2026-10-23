<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
    $box_style = get_valid_field_choice('box_style', $box_style ?? '');
    $background_type = get_valid_field_choice('background_type', $background_type ?? '');
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block cta-box 
        <?= esc_attr($box_style) ?>
        <?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>
        <?= esc_attr($background_type ?? "dark") ?>"
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <?php
        if (isset($background_image) && $background_image && isset($background_type) && $background_type === 'image') img_print_picture_tag(img: $background_image, is_cover: true, classes: "cta-box__bg bg-image gradient-overlay");
        ?>

        <div class="cta-box__wrapper container">

            <?php
            if ($box_style === "full" && $side_image) img_print_picture_tag(img: $side_image, max_size: "large", min_size: "featured-small", classes: "cta-box__side-img");
            ?>

            <div class="cta-box__inner">

                <div class="cta-box__heading">
                    <?php
                    if ($box_style === "full" && isset($pretitle) && $pretitle) {
                        print_title($pretitle, $pretitle_tag, "cta-box__pretitle pretitle tx-center");
                    }
                    if (isset($title) && $title) {
                        print_title($title, $title_tag, "cta-box__title tx-center");
                    }
                    if (isset($subtitle) && $subtitle) {
                        print_title($subtitle, $subtitle_tag, "cta-box__subtitle pretitle tx-center");
                    }
                    ?>
                </div>

                <div class="cta-box__main">
                    <?php if ($box_style === "full" && isset($text_content) && $text_content): ?>
                        <div class="cta-box__content tx-center formatted-text">
                            <?= $text_content ?>
                        </div>
                    <?php endif ?>

                    <?php
                    if (isset($cta_buttons) && !empty($cta_buttons)):
                    ?>
                        <div class="cta-box__buttons">
                            <?php
                            $i = 0;
                            foreach ($cta_buttons as $button):
                                $cta_link = $button['link'];
                                $btn_style = $i === 0 ? "tertiary" : "primary";
                                if (!empty($cta_link['title']) && !empty($cta_link['url'])):
                            ?>
                                    <a href="<?= esc_url($cta_link['url']) ?>"<?= !empty($cta_link['target']) ? ' target="' . esc_attr($cta_link['target']) . '"' . ($cta_link['target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> class="btn btn--<?= $btn_style ?>" aria-label="<?= esc_attr($cta_link['title']) ?>">
                                        <span><?= esc_html($cta_link['title']) ?></span>
                                    </a>
                            <?php
                                    $i++;
                                endif;
                            endforeach;
                            ?>
                        </div>
                    <?php
                    endif;
                    ?>
                </div>

            </div>
        </div>

    </section>

<?php
endif;
?>