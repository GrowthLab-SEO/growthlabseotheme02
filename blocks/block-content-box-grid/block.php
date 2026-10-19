<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_fields() as $key => $value) $$key = $value;
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block content-box-grid <?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>"
        <?= block_style_attribute($block); ?>
        <?php if (isset($extract_block_from_content) && $extract_block_from_content) echo "data-extract='$place'"; ?>>

        <div class="content-box-grid__wrapper container">

            <?php
            if (isset($pretitle) && $pretitle) print_title($pretitle, $pretitle_tag, "content-box-grid__pretitle pretitle");
            if (isset($title) && $title) print_title($title, $title_tag, "content-box-grid__title tx-center");
            ?>

            <?php if ($text_content): ?>
                <div class="content-box-grid__content formatted-text tx-center">
                    <?= $text_content ?>
                </div>
            <?php endif ?>

            <ul class="content-box-grid__grid">
                <?php
                if (!$is_a_menu && $items && count($items) > 0):

                    foreach ($items as $item):
                        foreach ($item as $item_key => $item_value) $$item_key = $item_value;
                ?>

                        <li class="box">
                            <div class="box__wrapper">
                                <div class="box__inner">

                                    <?php if ($title || $text_content): ?>

                                        <div class="box__content formatted-text">

                                            <?php
                                            $box_title_html = $title;
                                            if ($title && $title_link) $box_title_html = "<a href='" . esc_url($title_link['url']) . "' target='" . esc_attr($title_link['target']) . "' aria-label='" . esc_attr($title) . "'>" . $title . "</a>";
                                            print_title($box_title_html, $title_tag, "box__title");
                                            ?>
                                            <?= $text_content; ?>

                                        </div>

                                    <?php endif ?>

                                </div>
                            </div>
                        </li>

                    <?php
                    endforeach;

                elseif ($is_a_menu && $menu):
                    $menu_items = wp_get_nav_menu_items($menu);
                    foreach ($menu_items as $menu_item):
                    ?>

                        <li class="box box--menu-item">
                            <a href="<?= esc_url($menu_item->url) ?>" target="<?= esc_attr($menu_item->target) ?>" class="box__wrapper" aria-label="<?= esc_attr($menu_item->title) ?>">
                                <div class="box__inner">

                                    <div class="box__content formatted-text">

                                        <strong class="box__title">
                                            <?= esc_html($menu_item->title) ?>
                                        </strong>

                                    </div>

                                </div>
                            </a>
                        </li>
                <?php
                    endforeach;
                endif;
                ?>
            </ul>

        </div>

    </section>

<?php
endif;
?>