<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block content-box-grid <?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>"
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

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
                ?>

                        <li class="box">
                            <div class="box__wrapper">
                                <div class="box__inner">

                                    <?php if (!empty($item['title']) || !empty($item['text_content'])): ?>

                                        <div class="box__content formatted-text">

                                            <?php
                                            $box_title_html = $item['title'] ?? '';
                                            if (!empty($item['title']) && !empty($item['title_link']['url'])) {
                                                $title_target = $item['title_link']['target'] ?? '';
                                                $box_title_html = '<a href="' . esc_url($item['title_link']['url']) . '"'
                                                    . ($title_target ? ' target="' . esc_attr($title_target) . '"' . ($title_target === '_blank' ? ' rel="noopener noreferrer"' : '') : '')
                                                    . ' aria-label="' . esc_attr(wp_strip_all_tags($item['title'])) . '">' . $item['title'] . '</a>';
                                            }
                                            print_title($box_title_html, $item['title_tag'] ?? 'p', "box__title");
                                            ?>
                                            <?= $item['text_content'] ?? ''; ?>

                                        </div>

                                    <?php endif ?>

                                </div>
                            </div>
                        </li>

                    <?php
                    endforeach;

                elseif ($is_a_menu && $menu):
                    // A deleted menu returns false
                    $menu_items = wp_get_nav_menu_items($menu) ?: [];
                    foreach ($menu_items as $menu_item):
                    ?>

                        <li class="box box--menu-item">
                            <a href="<?= esc_url($menu_item->url) ?>"<?= !empty($menu_item->target) ? ' target="' . esc_attr($menu_item->target) . '"' . ($menu_item->target === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> class="box__wrapper" aria-label="<?= esc_attr($menu_item->title) ?>">
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