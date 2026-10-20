<?php
if (!defined('ABSPATH')) {
    exit;
}
foreach ($args as $key => $value) $$key = $value;
?>

<div class="logo-separator <?= esc_attr($classes); ?>">
    <hr>
    <?php
    $options = get_field_options("options");

    if ($options["logo_symbol"]) {
        img_print_picture_tag(img: $options["logo_symbol"], max_size: "medium", classes: "logo-separator__logo");
    } else {
        echo get_theme_svg('assets/img/ampersand-symbol.svg');
    } ?>
    <hr>
</div>