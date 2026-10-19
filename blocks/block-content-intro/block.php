<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="
        block 
        content-intro
        <?= isset($background_type) && $background_type ? $background_type : 'bg-gradient' ?>
        <?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>
        "
        <?= block_style_attribute($block); ?>
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <?php
        if (isset($background_image) && $background_image && isset($background_type) && $background_type === 'image') img_print_picture_tag(img: $background_image, is_cover: true, classes: "content-intro__bg bg-image gradient-overlay");
        ?>

        <div class="content-intro__wrapper container">
            <?php if ($title): ?>
                <div class="content-intro__title tx-center">
                    <?= $title ?>
                </div>
            <?php endif ?>

            <?php foreach (get_field("columns") as $column => $col) $$column = $col; ?>

            <?php if (isset($featured_image) && $featured_image): ?>
                <div class="col col--image">
                    <?php img_print_picture_tag(
                        img: $featured_image,
                        max_size: "content",
                        min_size: "featured-small",
                        classes: "col__image"
                    );
                    ?>
                </div>
            <?php endif ?>

            <div class="col">
                <?php
                if (isset($pretitle) && $pretitle) print_title($pretitle, $pretitle_tag, "col__pretitle pretitle");
                if (isset($title) && $title)  print_title($title, $title_tag, "col__title");
                ?>
                <div class="col__content formatted-text">
                    <?= $content ?? "" ?>
                </div>
            </div>

            <?php
            foreach (get_field("cta_box") as $box => $data) $$box = $data;
            if (isset($cta_link) && !empty($cta_link["url"]) || isset($cta_link_2) && !empty($cta_link_2["url"]) || isset($content) && $content):
            ?>
                <div class="inner-cta-box">

                    <?php if ($content && isset($content)): ?>
                        <?php get_template_part("template-parts/logo", "separator", ["classes" => "inner-cta-box__separator"]); ?>

                        <div class="inner-cta-box__content formatted-text">
                            <?= $content ?>
                        </div>
                    <?php endif ?>

                    <div class="inner-cta-box__buttons">
                        <?php if ($cta_link && isset($cta_link) && !empty($cta_link["url"])): ?>
                            <a href="<?= esc_url($cta_link["url"]) ?>" target="<?= esc_attr($cta_link["target"]) ?>" class="btn btn--tertiary" aria-label="<?= esc_attr($cta_link["title"]) ?>">
                                <span><?= esc_html($cta_link["title"]) ?></span>
                            </a>
                        <?php endif ?>
                        <?php if ($cta_link_2 && isset($cta_link_2) && !empty($cta_link_2["url"])): ?>
                            <a href="<?= esc_url($cta_link_2["url"]) ?>" target="<?= esc_attr($cta_link_2["target"]) ?>" class="btn btn--primary" aria-label="<?= esc_attr($cta_link_2["title"]) ?>">
                                <span><?= esc_html($cta_link_2["title"]) ?></span>
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