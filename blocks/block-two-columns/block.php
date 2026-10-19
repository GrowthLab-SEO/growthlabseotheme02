<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
    $background_type = get_valid_field_choice('background_type', $background_type ?? '');
    $reverse =  $reverse_order ? "reverse" : "";
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="
        block 
        two-columns
        <?= esc_attr($background_type) ?>
        <?= $background_type === "dark" ? "bg-gradient" : "" ?>
        <?php if ($reverse_order) echo "reverse" ?> 
        <?php if ($show_top_separator) echo "separator" ?>
        <?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>
        "
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <?php
        if ($show_top_separator) get_template_part("template-parts/logo", "separator", ["classes" => "two-columns__separator $reverse"]);
        if (isset($background_type) && $background_type === "img") img_print_picture_tag(img: $background_image, is_cover: true, classes: "two-columns__bg bg-image gradient-overlay");
        ?>

        <div class="
        two-columns__wrapper container
        <?php if ($reverse_order) echo "reverse" ?> 
        <?php if ($show_top_separator) echo "separator" ?>
        ">
            <?php $title ? print_title($title, $title_tag, "two-columns__title") : "" ?>

            <?php if (isset($text_content) && $text_content): ?>
                <div class="two-columns__col">
                    <div class="formatted-text two-columns__content">
                        <?= $text_content ?? "" ?>

                        <?php if ($cta_link && isset($cta_link) && !empty($cta_link["url"])): ?>
                            <a href="<?= esc_url($cta_link["url"]) ?>" target="<?= esc_attr($cta_link["target"]) ?>" class="btn btn--tertiary" aria-label="<?= esc_attr($cta_link["title"]) ?>">
                                <span><?= esc_html($cta_link["title"]) ?></span>
                            </a>
                        <?php endif ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($image) && $image && !empty($image)): ?>
                <div class="two-columns__col two-columns__col--image">
                    <?php
                    if (isset($image) && $image) img_print_picture_tag(
                        img: $image,
                        max_size: "large",
                        min_size: "content",
                        classes: "two-columns__image $reverse"
                    );
                    ?>
                </div>
            <?php endif; ?>
        </div>

    </section>

<?php
endif;
?>