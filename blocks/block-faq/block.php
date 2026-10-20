<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block faq <?php if (!$background_image && !$lightdark_background) echo "bg-gradient"; ?><?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>"
       <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "faq__bg bg-image gradient-overlay");
        ?>

        <div class="faq__wrapper container">
            <?php
            if (isset($side_images) && !empty($side_images)) :
            ?>
                <div class="faq__side-images">
                    <?php

                    foreach ($side_images as $image) {
                        img_print_picture_tag(img: $image['image'], max_size: "content", min_size: "featured-small", classes: "faq__side-image");
                    }

                    $options = get_field_options("options");

                    if ($options["logo_symbol"]) {
                        echo "<div class='faq__separator'>";
                        img_print_picture_tag(img: $options["logo_symbol"], max_size: "medium");
                        echo "</div>";
                    }
                    ?>
                </div>

            <?php
            endif;
            ?>

            <div class="faq__inner">
                <?php
                print_title($title, $title_tag, "faq__title");
                if (isset($subtitle) && $subtitle) print_title($subtitle, $subtitle_tag, "faq__subtitle pretitle");
                ?>

                <?php if ($text_content): ?>
                    <div class="faq__content formatted-text">
                        <?= wp_kses_post($text_content) ?>
                    </div>
                <?php endif ?>


                <div class="faq__sections">

                    <?php
                    if (isset($faq_sections) && !empty($faq_sections)) :
                        foreach ($faq_sections as $section) :
                    ?>

                            <div class="faq__section">
                                <?php
                                print_title($section['heading'] ?? '', $section['heading_tag'] ?? 'p', "faq__heading");
                                ?>

                                <?php
                                if (!empty($section['faq_items'])) :
                                    foreach ($section['faq_items'] as $item) :
                                        $answer_id = wp_unique_id('faq-answer-');
                                ?>

                                        <div class="faq__item accordion">
                                            <div class="faq__question accordion__heading" role="button" tabindex="0" aria-expanded="false" aria-controls="<?= esc_attr($answer_id) ?>">
                                                <?php print_title($item['question'] ?? '', $item['question_tag'] ?? 'p'); ?>
                                            </div>
                                            <div class="faq__answer accordion__content" id="<?= esc_attr($answer_id) ?>">
                                                <div class="formatted-text accordion__inner">
                                                    <?= wp_kses_post($item['answer'] ?? '') ?>
                                                </div>
                                            </div>
                                        </div>

                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                    <?php
                        endforeach;
                    endif;
                    ?>

                </div>


                <?php if ($cta_link): ?>
                    <div class="faq__btn">
                        <a href="<?= esc_url($cta_link['url']) ?>"<?= !empty($cta_link['target']) ? ' target="' . esc_attr($cta_link['target']) . '"' . ($cta_link['target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> class="btn btn--secondary" aria-label="<?= esc_attr($cta_link['title']) ?>">
                            <span><?= esc_html($cta_link['title']) ?></span>
                        </a>
                    </div>
                <?php endif ?>


            </div>
        </div>

    </section>

<?php
endif;
?>