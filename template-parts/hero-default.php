<?php
if (!defined('ABSPATH')) {
    exit;
}

//Internal Fields
foreach (fill_acf_group_defaults(get_page_field('hero_properties'), 'hero_properties') as $key => $value) $$key = $value;
$cta_button = isset($hero_cta_button) && $hero_cta_button ? $hero_cta_button : $args['hero_cta_button_default'];

//Hero pictures (read from the hero_pictures group)
$bg_desktop = !empty($hero_pictures['background_desktop']) ? $hero_pictures['background_desktop'] :  $args['hero_image_desktop_default'];
$bg_tablet = !empty($hero_pictures['background_tablet']) ? $hero_pictures['background_tablet'] :  $args['hero_image_tablet_default'];
$bg_mobile = !empty($hero_pictures['background_mobile']) ? $hero_pictures['background_mobile'] :  $args['hero_image_mobile_default'];

if (!$bg_desktop) $bg_desktop = [];
if (!$bg_tablet) $bg_tablet = [];
if (!$bg_mobile) $bg_mobile = [];
?>
<section id="hero" class="hero">

    <?php if (!empty($bg_desktop)) img_print_picture_tag(img: $bg_desktop, tablet_img: $bg_tablet, mobile_img: $bg_mobile, is_cover: true, classes: "hero__bg-image bg-image gradient-overlay", is_priority: true); ?>

    <div class="hero__wrapper container">
        <div class="hero__content tx-center">

            <div class="hero__title">
                <?= $args['hero_title'] ?>
            </div>

            <?php if ($cta_button): ?>
                <a href="<?= $cta_button['url'] ?>" target="<?= $cta_button['target'] ?>" class="hero__btn btn btn--tertiary" aria-label="<?= esc_attr($cta_button['title']) ?>">
                    <span><?= $cta_button['title'] ?></span>
                </a>
            <?php endif ?>
        </div>
    </div>

</section>