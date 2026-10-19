<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block locations<?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>"
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <div class="locations__wrapper container">

            <?php if (isset($pretitle) && $pretitle || isset($title) && $title || isset($main_content) && $main_content): ?>
                <div class="locations__content tx-center">
                    <?php
                    if (isset($pretitle) && $pretitle) print_title($pretitle, $pretitle_tag, "locations__pretitle pretitle");
                    if (isset($title) && $title) print_title($title, $title_tag, "locations__title tx-center");
                    echo $main_content;
                    ?>
                </div>
            <?php endif; ?>

            <div class="locations-cards">
                <?php
                if ($show_all_locations) {
                    $options = get_current_language_options();
                    $locations = !empty($options['offices']) ? $options['offices'] : [];
                } else {
                    $locations = !empty($offices) ? $offices : [];
                }
                if (!empty($locations)):
                ?>
                    <div class="locations-cards__grid">
                        <?php
                        foreach ($locations as $location) {
                            get_template_part(
                                'template-parts/location',
                                'card',
                                array(
                                    'location' => $location,
                                    'classes' => "locations-cards__card" . (isset($disable_accordion) && $disable_accordion ? ' disabled' : '')
                                )
                            );
                        }
                        ?>
                    </div>
                <?php
                endif;
                ?>

                <?php if (!empty($cta_link['url']) && count($locations) === 1): ?>
                    <a href="<?= esc_url($cta_link['url']) ?>" target="<?= esc_attr($cta_link['target']) ?>" class="cta-btn btn btn--secondary" aria-label="<?= esc_attr($cta_link['title']) ?>">
                        <span><?= esc_html($cta_link['title']) ?></span>
                    </a>
                <?php endif; ?>

            </div>

        </div>
    </section>

<?php
endif;
?>