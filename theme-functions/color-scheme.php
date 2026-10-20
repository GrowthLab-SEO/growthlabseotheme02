<?php
// Define default colors
$default_colors = [
    'primary' => [
        'default' => '#15253f',
        'dark'    => '#08182f',
        'light'   => '#2C3D5B'
    ],
    'secondary' => [
        'default' => '#F4F3EE',
        'dark'    => '#E7E5DF',
        'light'   => '#FFFFFF'
    ],
    'tertiary' => [
        'default' => '#BC9061',
        'dark'    => '#9D7A55',
        'light'   => '#DCAB77'
    ],
    'text' => '#15253f'
];

if (!function_exists('theme_customize_register')) {
    function theme_customize_register($wp_customize)
    {
        global $default_colors;

        foreach ($default_colors as $color_name => $variants) {
            if ($color_name === 'text') {
                register_color_setting($wp_customize, 'text_color', $variants);
                continue;
            }

            foreach (['default' => '', 'dark' => '_dark', 'light' => '_light'] as $variant => $suffix) {
                $setting_name = "{$color_name}_color{$suffix}";
                $default      = $variants[$variant] ?? '';
                register_color_setting($wp_customize, $setting_name, $default);
            }
        }
    }
}

if (!function_exists('register_color_setting')) {
    function register_color_setting($wp_customize, $name, $default)
    {
        $wp_customize->add_setting($name, [
            'default'           => $default,
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color'
        ]);

        $wp_customize->add_control(
            new WP_Customize_Color_Control($wp_customize, $name, [
                'section' => 'colors',
                'label'   => esc_html__(ucwords(str_replace('_', ' ', $name)), get_stylesheet())
            ])
        );
    }
}

if (!function_exists('theme_get_customizer_css')) {
    function theme_get_customizer_css()
    {
        global $default_colors;

        $css_vars = [];
        foreach ($default_colors as $color_name => $variants) {
            if ($color_name === 'text') {
                $hex              = get_theme_mod('text_color', $variants);
                $css_vars["--text"] = hex_to_rgb($hex, $variants);
                continue;
            }

            foreach (['default' => '', 'dark' => '-dark', 'light' => '-light'] as $variant => $suffix) {
                $setting_name       = "{$color_name}_color" . ($variant === 'default' ? '' : '_' . $variant);
                $hex                = get_theme_mod($setting_name, $variants[$variant]);
                $css_vars["--{$color_name}{$suffix}"] = hex_to_rgb($hex, $variants[$variant]);
            }
        }

        $css = ":root {\n";
        foreach ($css_vars as $var => $value) {
            $css .= "    {$var}: {$value};\n";
        }
        $css .= "}";

        return $css;
    }
}

if (!function_exists('hex_to_rgb')) {
    // "#rrggbb" or "#rgb" to "r, g, b" for the rgb(var(--color)) CSS variables. Invalid or empty values use $fallback
    // (another hex color), so a bad Customizer value can't print an empty variable that breaks every rgb() using it.
    function hex_to_rgb($hex, $fallback = '#000000')
    {
        $hex = ltrim(trim((string) $hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return $fallback !== null ? hex_to_rgb($fallback, null) : '0, 0, 0';
        }

        return implode(', ', array_map('hexdec', str_split($hex, 2)));
    }
}

add_action('customize_register', 'theme_customize_register');
