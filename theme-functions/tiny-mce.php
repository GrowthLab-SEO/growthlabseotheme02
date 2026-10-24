<?php

/**
 * TinyMCE settings of the classic editor and the ACF WYSIWYG fields: theme fonts, font sizes, line heights, the theme
 * color palette and the toolbar. ACF builds its WYSIWYG editors from the same tiny_mce_before_init settings.
 */

if (!function_exists('_theme_get_tinymce_colors')) {
    /**
     * Colors of the Customizer color scheme (defaults in theme-config.php), as hex => name.
     */
    function _theme_get_tinymce_colors(): array
    {
        global $default_colors;

        $settings = [
            'primary_color'         => [$default_colors['primary']['default'], __('Primary Color', 'growthlabseotheme02')],
            'primary_color_dark'    => [$default_colors['primary']['dark'], __('Primary Dark', 'growthlabseotheme02')],
            'primary_color_light'   => [$default_colors['primary']['light'], __('Primary Light', 'growthlabseotheme02')],
            'secondary_color'       => [$default_colors['secondary']['default'], __('Secondary Color', 'growthlabseotheme02')],
            'secondary_color_dark'  => [$default_colors['secondary']['dark'], __('Secondary Dark', 'growthlabseotheme02')],
            'secondary_color_light' => [$default_colors['secondary']['light'], __('Secondary Light', 'growthlabseotheme02')],
            'tertiary_color'        => [$default_colors['tertiary']['default'], __('Tertiary Color', 'growthlabseotheme02')],
            'tertiary_color_dark'   => [$default_colors['tertiary']['dark'], __('Tertiary Dark', 'growthlabseotheme02')],
            'tertiary_color_light'  => [$default_colors['tertiary']['light'], __('Tertiary Light', 'growthlabseotheme02')],
            'text_color'            => [$default_colors['text'], __('Text Color', 'growthlabseotheme02')],
        ];

        $colors = [];
        foreach ($settings as $mod => [$default, $name]) {
            $colors[$mod] = [
                'name'  => $name,
                'color' => sanitize_hex_color(get_theme_mod($mod, $default)) ?: $default,
            ];
        }
        return $colors;
    }
}

if (!function_exists('_theme_get_tinymce_color_map')) {
    /**
     * The colors in TinyMCE's textcolor_map format: hex without "#" followed by its name.
     */
    function _theme_get_tinymce_color_map(): array
    {
        $map = [];
        foreach (_theme_get_tinymce_colors() as $color) {
            $map[] = ltrim($color['color'], '#');
            $map[] = $color['name'];
        }
        return $map;
    }
}

if (!function_exists('_theme_get_tinymce_font_formats')) {
    /**
     * The font_formats string for TinyMCE. Order: theme fonts (theme.json) → custom fonts (Font Library) → system.
     */
    function _theme_get_tinymce_font_formats(): string
    {
        $entries = [];
        $seen    = [];

        if (class_exists('WP_Theme_JSON_Resolver')) {
            $settings = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
            $families = $settings['typography']['fontFamilies'] ?? [];

            foreach (['theme', 'custom'] as $source) {
                foreach (($families[$source] ?? []) as $font) {
                    $name   = $font['name'] ?? '';
                    $family = $font['fontFamily'] ?? $name;
                    if (!$name || isset($seen[$name])) {
                        continue;
                    }

                    $family = str_replace('"', '', $family);
                    $seen[$name] = true;
                    $entries[] = "{$name}={$family}";
                }
            }
        }

        // Without theme.json data, the default fonts of this theme (theme-config.php)
        if (empty($entries)) {
            global $default_fonts;
            $entries[] = "{$default_fonts['primary']}={$default_fonts['primary']},sans-serif";
            $entries[] = "{$default_fonts['secondary']}={$default_fonts['secondary']},serif";
        }

        $entries[] = 'Arial=Arial,Helvetica,sans-serif';
        $entries[] = 'Times New Roman=Times New Roman,Times,serif';

        return implode(';', $entries);
    }
}

// Register the line height plugin for TinyMCE
add_filter('mce_external_plugins', function ($plugins) {
    $plugins['lineheight'] = get_template_directory_uri() . '/js/vendor/tiny-mce/lineheight-min.js';
    return $plugins;
});

// Editor CSS
if (!function_exists('my_acf_editor_styles')) {
    function my_acf_editor_styles($mce_css)
    {
        $editor_style = get_template_directory_uri() . '/styles/vendor/tiny-mce/tiny-mce-styles-min.css';
        $editor_style .= '?ver=' . filemtime(get_template_directory() . '/styles/vendor/tiny-mce/tiny-mce-styles-min.css');

        if (!empty($mce_css)) {
            $mce_css .= ',' . $editor_style;
        } else {
            $mce_css = $editor_style;
        }
        return $mce_css;
    }
}
add_filter('mce_css', 'my_acf_editor_styles');

// Fonts, font sizes and line heights
if (!function_exists('my_acf_wysiwyg_custom_settings')) {
    function my_acf_wysiwyg_custom_settings($init)
    {
        $init['font_formats']       = _theme_get_tinymce_font_formats();
        $init['fontsize_formats']   = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 42px 44px 46px 48px 50px 52px 54px 56px 58px 60px 62px 64px 72px 80px 88px 96px 104px 124px 148px 156px 168px';
        $init['lineheight_formats'] = '.5 .55 .6 .65 .7 .75 .8 .85 .9 .95 1 1.1 1.2 1.3 1.4 1.5 1.6 1.7 1.8 2 2.5 3';
        return $init;
    }
}
add_filter('tiny_mce_before_init', 'my_acf_wysiwyg_custom_settings', 1);

// Toolbar of the ACF WYSIWYG fields
if (!function_exists('my_acf_override_full_toolbar')) {
    function my_acf_override_full_toolbar($toolbars)
    {
        $toolbars['Full'][1] = [
            'formatselect',
            'fontselect',
            'fontsizeselect',
            'lineheightselect',
            'bold',
            'italic',
            'underline',
            'forecolor',
            'backcolor',
            'bullist',
            'numlist',
            'alignleft',
            'aligncenter',
            'alignright',
            'link',
            'unlink',
            'removeformat',
            'undo',
            'redo',
        ];
        return $toolbars;
    }
}
add_filter('acf/fields/wysiwyg/toolbars', 'my_acf_override_full_toolbar');

// Theme colors in the text and background color pickers. WordPress prints string settings that start with "[" as
// JavaScript literals, so the map is passed as JSON (an array would be printed as the text "Array").
if (!function_exists('my_wp_editor_colors_direct')) {
    function my_wp_editor_colors_direct($init)
    {
        $init['textcolor_map']  = wp_json_encode(_theme_get_tinymce_color_map());
        $init['textcolor_cols'] = 5;
        return $init;
    }
}
add_filter('tiny_mce_before_init', 'my_wp_editor_colors_direct', 10);

// Editor styles and the color palette of the block editor
if (!function_exists('my_wp_editor_formats')) {
    function my_wp_editor_formats()
    {
        add_editor_style(get_template_directory_uri() . '/styles/vendor/tiny-mce/tiny-mce-styles-min.css?ver=' . filemtime(get_template_directory() . '/styles/vendor/tiny-mce/tiny-mce-styles-min.css'));

        $slugs = [
            'primary_color'         => 'primary',
            'primary_color_dark'    => 'primary-dark',
            'primary_color_light'   => 'primary-light',
            'secondary_color'       => 'secondary',
            'secondary_color_dark'  => 'secondary-dark',
            'secondary_color_light' => 'secondary-light',
            'tertiary_color'        => 'tertiary',
            'tertiary_color_dark'   => 'tertiary-dark',
            'tertiary_color_light'  => 'tertiary-light',
            'text_color'            => 'text',
        ];

        $palette = [];
        foreach (_theme_get_tinymce_colors() as $mod => $color) {
            $palette[] = ['name' => $color['name'], 'slug' => $slugs[$mod], 'color' => $color['color']];
        }
        add_theme_support('editor-color-palette', $palette);
    }
}
add_action('after_setup_theme', 'my_wp_editor_formats');

// Toolbar of the classic editor, and the plugins of the settings above
if (!function_exists('my_wp_editor_default_settings')) {
    function my_wp_editor_default_settings($init)
    {
        $init['font_formats']       = _theme_get_tinymce_font_formats();
        $init['fontsize_formats']   = '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 42px 44px 46px 48px 50px 52px 54px 56px 58px 60px 62px 64px 72px 80px 88px 96px 104px 124px 148px 156px 168px';
        $init['lineheight_formats'] = '.5 .55 .6 .65 .7 .75 .8 .85 .9 .95 1 1.1 1.2 1.3 1.4 1.5 1.6 1.7 1.8 2 2.5 3';
        $init['toolbar1']           = 'formatselect,fontselect,fontsizeselect,lineheightselect,bold,italic,underline,forecolor,backcolor,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,removeformat,undo,redo';
        $init['toolbar2']           = '';
        // The theme colors when another filter removed them
        if (empty($init['textcolor_map'])) $init['textcolor_map'] = wp_json_encode(_theme_get_tinymce_color_map());
        $init['textcolor_cols']     = 5;

        $plugins = array_filter(explode(' ', str_replace(',', ' ', (string) ($init['plugins'] ?? ''))));
        $init['plugins'] = implode(',', array_unique(array_merge($plugins, ['textcolor', 'lineheight'])));
        return $init;
    }
}
add_filter('tiny_mce_before_init', 'my_wp_editor_default_settings', 20);
