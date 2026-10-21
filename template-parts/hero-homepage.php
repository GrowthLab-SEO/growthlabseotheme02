<?php
if (!defined('ABSPATH')) {
    exit;
}

//Internal Fields
foreach (fill_acf_group_defaults(get_page_field('hero_properties'), 'hero_properties') as $key => $value) $$key = $value;
$cta_button = isset($hero_cta_button) && $hero_cta_button ? $hero_cta_button :   $args['hero_cta_button_default'];

//Hero pictures (read from the hero_pictures group)
$bg_desktop = !empty($hero_pictures['background_desktop']) ? $hero_pictures['background_desktop'] :  $args['hero_image_desktop_default'];
$bg_tablet = !empty($hero_pictures['background_tablet']) ? $hero_pictures['background_tablet'] :  $args['hero_image_tablet_default'];
$bg_mobile = !empty($hero_pictures['background_mobile']) ? $hero_pictures['background_mobile'] :  $args['hero_image_mobile_default'];

if (!$bg_desktop) $bg_desktop = [];
if (!$bg_tablet) $bg_tablet = [];
if (!$bg_mobile) $bg_mobile = [];

?>
<section id="hero-homepage" class="hero hero--homepage">

    <?php if (!empty($bg_desktop)) img_print_picture_tag(
        img: $bg_desktop,
        tablet_img: $bg_tablet,
        mobile_img: $bg_mobile,
        is_cover: true,
        classes: "hero__bg-image bg-image",
        is_priority: true
    ); ?>

    <div class="hero__wrapper container">
        <?php if (!empty($hero_pictures['side_portrait'])) img_print_picture_tag(img: $hero_pictures['side_portrait'], max_size: "cover-mobile-portrait", min_size: "featured-small", classes: "hero__side-portrait", is_priority: true, fetch_priority: false); ?>

        <div class="hero__content">
            <?php if (isset($hero_tagline) && $hero_tagline) {
                print_title(
                    title: $hero_tagline,
                    tag: $hero_tagline_tag,
                    classes: "hero__tagline"
                );
            } ?>

            <div class="hero__title">
                <?= wp_kses_post($args["hero_title"] ?? "") ?>
            </div>

            <?php if ($cta_button): ?>
                <a href="<?= esc_url($cta_button['url'] ?? '') ?>"<?= !empty($cta_button['target']) ? ' target="' . esc_attr($cta_button['target']) . '"' . ($cta_button['target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> class="hero__btn btn btn--tertiary" aria-label="<?= esc_attr($cta_button['title'] ?? '') ?>">
                    <span><?= esc_html($cta_button['title'] ?? '') ?></span>
                </a>
            <?php endif ?>

            <?php if (!empty($hero_pictures['badges'])): ?>
                <div class="hero__badges">
                    <?php foreach ($hero_pictures['badges'] as $badge) {
                        $pic = $badge['badge'];
                        img_print_picture_tag(img: $pic, classes: "hero__badge", is_priority: false, max_size: "thumbnail");
                    } ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</section>