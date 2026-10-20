<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <?php wp_body_open(); ?>

    <?php
    $options = get_current_language_options();
    $options = fill_acf_group_defaults($options, 'options', 'option');
    foreach ($options as $key => $value) $$key = $value;

    // Per-language options store an empty logo_link when it was never set
    $logo_link = $logo_link ?: home_url('/' . get_current_language()['slug']);
    $phone_number = $contact_phone ?: $main_phone_number;

    if (get_page_field('custom_header')) {
        $logo_link = get_page_field('logo_link') ?: $logo_link;
        $phone_number = get_page_field("contact_phone") ?: null;
        $top_callout_first_line = get_page_field("top_callout_first_line") ?: '';
        $top_callout_second_line = get_page_field("top_callout_second_line") ?: '';
        $cta_button = get_page_field("cta_button") ?: null;
    }

    $hero_style = is_listing_page() ? 'default' : (get_page_field('hero_style') ?: 'default');
    ?>

    <header role="banner" class="site-header <?= !is_404() && $hero_style !== "nohero" && $sticky_header ? "site-header--sticky" : "" ?>">

        <div class="site-header__wrapper">

            <div class="site-header__logo">
                <a href="<?= esc_url($logo_link); ?>" class="site-logo" aria-label="<?= esc_attr(get_bloginfo('name')); ?>">
                    <?php
                    if (function_exists('the_custom_logo') && has_custom_logo()) {
                        $custom_logo_id = get_theme_mod('custom_logo');
                        $image = wp_get_attachment_image_url($custom_logo_id, "full");
                        echo img_generate_picture_tag(img: $image, max_size: "medium", alt_text: get_bloginfo('name'), is_priority: true, classes: "site-logo__image");
                    }
                    ?>
                    <span>Site Logo</span>
                </a>
            </div>

            <?php
            if (get_page_field("custom_header") || has_nav_menu('main' . get_current_language_suffix())) {
                $menu_args = array(
                    'container'          => 'nav',
                    'container_role'     => 'navigation',
                    'container_class' => 'main-nav',
                    'menu_class'      => 'main-nav__menu',
                    'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
                    'link_before'          => '<span>',
                    'link_after'              => '</span>',
                    // Never fall back to a list of every page when the menu location is empty
                    'fallback_cb'          => false
                );

                if (get_page_field("custom_header")) {
                    if (get_page_field("menu")) {
                        $menu_args['menu'] = get_page_field("menu");
                    } else {
                        $menu_args = [];
                    }
                } else {
                    $menu_args['theme_location'] = 'main' . get_current_language_suffix();
                }
            }
            ?>

            <?php if (!empty($menu_args)) : ?>
                <button class="site-header__mobile-btn" role="button" aria-label="Mobile Menu Button">
                    <div>
                        <hr>
                        <hr>
                        <hr>
                    </div>
                    <span>Menu</span>
                </button>
            <?php endif ?>

            <div class="site-header__navigation">

                <?php if (!empty($menu_args)) : ?>

                    <div class="site-header__close-btn hide-on-desktop" role="button" aria-label="Close Menu Button">
                        <hr>
                        <hr>
                    </div>

                    <nav class="main-nav" role="navigation">
                        <?php wp_nav_menu($menu_args); ?>
                    </nav>
                <?php endif ?>

                <?php if ($phone_number): ?>
                    <div class="site-header__callout">

                        <div class="callout">
                            <?php if (!empty(get_languages_map())): ?>
                                <div class="callout__languages">
                                    <a href="<?= get_site_url() ?>" class="language" aria-label="Select English language">
                                        EN
                                    </a>
                                    <?php foreach (get_languages_map() as $lang => $data): ?>
                                        <a href="<?= get_site_url() . '/' . $lang ?>" class="language" aria-label="<?= 'Select ' . esc_attr($lang) . ' language' ?>">
                                            <?= $lang ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($top_callout_first_line && $top_callout_first_line !== ''): ?>
                                <span class="callout__text callout__text--first"><?= wp_kses_post($top_callout_first_line) ?></span>
                            <?php endif; ?>
                            <?php if ($top_callout_second_line && $top_callout_second_line !== ''): ?>
                                <span class="callout__text callout__text--second"><?= wp_kses_post($top_callout_second_line) ?></span>
                            <?php endif; ?>
                            <a href="<?= get_phone_href($phone_number) ?>" class="callout__phone" aria-label="Call us at <?= esc_attr($phone_number) ?>">
                                <?php echo get_theme_svg('assets/icons/icon-phone.svg') ?>
                                <?= esc_html($phone_number) ?>
                            </a>
                        </div>

                    </div>
                <?php endif; ?>
            </div>

            <?php if ($cta_button): ?>
                <div class="site-header__cta <?= isset($cta_button_mobile_only) && $cta_button_mobile_only ? 'hide-on-mobile hide-on-tablet' : ''; ?>">
                    <a href="<?= esc_url($cta_button['url'] ?? '') ?>" class="cta-button btn--tertiary"<?= !empty($cta_button['target']) ? ' target="' . esc_attr($cta_button['target']) . '"' . ($cta_button['target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> aria-label="<?= esc_attr($cta_button['title'] ?? '') ?>">

                        <span class="cta-button__text">
                            <?= esc_html($cta_button['title'] ?? '') ?>
                        </span>

                    </a>
                </div>
            <?php endif; ?>

            <?php if ($cta_button_mobile_only): ?>
                <div class="site-header__cta--mobile-only hide-on-desktop">
                    <a href="<?= esc_url($cta_button_mobile_only['url'] ?? '') ?>" class="cta-button btn--tertiary"<?= !empty($cta_button_mobile_only['target']) ? ' target="' . esc_attr($cta_button_mobile_only['target']) . '"' . ($cta_button_mobile_only['target'] === '_blank' ? ' rel="noopener noreferrer"' : '') : '' ?> aria-label="<?= esc_attr($cta_button_mobile_only['title'] ?? '') ?>">

                        <span class="cta-button__text">
                            <?= esc_html($cta_button_mobile_only['title'] ?? '') ?>
                        </span>

                    </a>
                </div>
            <?php endif; ?>

        </div>
    </header>

    <?php
    $hero_title = get_page_field("hero_properties")["hero_title"] ?? null;

    // Without a custom hero title the page title is the page heading
    if (empty($hero_title)) {
        $hero_title = '<h1>' . wp_kses_post(get_page_hero_title()) . '</h1>';
    } else {
        $hero_title = get_hero_heading($hero_title);
    }


    $args = array(
        "hero_title" => $hero_title,
        "hero_image_desktop_default" => $hero_image_desktop,
        "hero_image_tablet_default" => $hero_image_tablet,
        "hero_image_mobile_default" => $hero_image_mobile,
        "hero_cta_button_default" => $hero_cta_button,
    );

    if (!is_404()) {

        switch ($hero_style) {
            case 'home':
                get_template_part('template-parts/hero', 'homepage', $args);
                break;
            case 'default':
                get_template_part('template-parts/hero', 'default', $args);
                break;
            case 'home_2':
                get_template_part('template-parts/hero', 'homepage-v2', $args);
                break;
            case 'home_3':
                get_template_part('template-parts/hero', 'homepage-v3', $args);
                break;
            case 'home_4':
                get_template_part('template-parts/hero', 'homepage-v4', $args);
                break;
            case 'home_5':
                get_template_part('template-parts/hero', 'homepage-v5', $args);
                break;
            case 'home_6':
                get_template_part('template-parts/hero', 'homepage-v6', $args);
                break;
            case 'nohero':
                break;
            default:
                get_template_part('template-parts/hero', 'default', $args);
                break;
        }
    }
    ?>