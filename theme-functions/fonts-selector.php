<?php

// ─── THEME DEFAULTS ───────────────────────────────────────────────────────────
$default_fonts = [
    'primary'   => 'Mona Sans',
    'secondary' => 'Roboto Serif',
];

// ─── CORE FUNCTION: reads the Font Library + theme fonts ─────────────────────
if (! function_exists('theme_get_all_fonts')) {
    /**
     * Returns all available fonts by combining:
     *   1. The theme's base fonts (hardcoded — always present).
     *   2. Fonts installed through the WP 7+ Font Library ('custom' sub-key
     *      in WP_Theme_JSON_Resolver).
     *
     * It does NOT read the resolver's 'theme' sub-key, to avoid race
     * conditions with the wp_theme_json_data_theme filter: in some contexts
     * (e.g. customize_register) that filter has not run yet and the resolver
     * returns the data of the theme.json file on disk.
     *
     * @return array<string, array{ name: string, font_family: string, slug: string }>
     *   Keyed by slug. Custom fonts from the Font Library come first.
     */
    function theme_get_all_fonts(): array
    {
        // 1. The theme's base fonts — always available, keyed by slug
        $fonts = [
            'primary' => [
                'name'        => 'Mona Sans',
                'font_family' => '"Mona Sans", sans-serif',
                'slug'        => 'primary',
            ],
            'secondary' => [
                'name'        => 'Roboto Serif',
                'font_family' => '"Roboto Serif", serif',
                'slug'        => 'secondary',
            ],
        ];

        // 2. Fonts installed through the Font Library ('custom' only)
        if (! class_exists('WP_Theme_JSON_Resolver')) {
            return $fonts;
        }

        $settings = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
        $custom   = $settings['typography']['fontFamilies']['custom'] ?? [];

        // Insert custom fonts AT THE START so they are listed first
        $custom_fonts = [];
        foreach ($custom as $font) {
            $slug = $font['slug'] ?? sanitize_title($font['name'] ?? '');
            if (! $slug || isset($fonts[$slug])) {
                continue;
            }
            $custom_fonts[$slug] = [
                'name'        => $font['name']       ?? $slug,
                'font_family' => $font['fontFamily'] ?? $font['name'],
                'slug'        => $slug,
            ];
        }

        // custom first, then the theme's
        return array_merge($custom_fonts, $fonts);
    }
}

// ─── CUSTOMIZER ──────────────────────────────────────────────────────────────

if (! function_exists('theme_customize_register_fonts')) {
    function theme_customize_register_fonts($wp_customize)
    {
        global $default_fonts;

        $wp_customize->add_section('fonts_section', [
            'title'       => esc_html__('Fonts', get_stylesheet()),
            'priority'    => 30,
            'description' => esc_html__('Select the fonts for your theme', get_stylesheet()),
        ]);

        register_font_setting($wp_customize, 'font_primary',   $default_fonts['primary'],   'Primary Font');
        register_font_setting($wp_customize, 'font_secondary', $default_fonts['secondary'], 'Secondary Font');
    }
}

if (! function_exists('register_font_setting')) {
    function register_font_setting($wp_customize, $name, $default, $label)
    {
        // Build the choices from all available fonts.
        // Called here (in customize_register) so that WP_Theme_JSON_Resolver
        // already has the DB data loaded.
        $all_fonts = theme_get_all_fonts();

        // Fallback: if for some reason there are no fonts yet, show the default
        if (empty($all_fonts)) {
            $choices = [$default => $default];
        } else {
            $choices = [];
            foreach ($all_fonts as $slug => $data) {
                // Use the font name as the stored value (same as before)
                // to stay compatible with existing get_theme_mod values.
                $choices[$data['name']] = $data['name'];
            }
        }

        $wp_customize->add_setting($name, [
            'default'           => $default,
            'transport'         => 'refresh',
            'sanitize_callback' => function ($value) use ($choices) {
                return isset($choices[$value]) ? $value : array_key_first($choices);
            },
        ]);

        $wp_customize->add_control($name, [
            'section' => 'fonts_section',
            'label'   => esc_html__($label, get_stylesheet()),
            'type'    => 'select',
            'choices' => $choices,
        ]);
    }
}

// ─── BUNDLED DEFAULT FONT FILES ──────────────────────────────────────────────

if (! function_exists('theme_get_default_font_faces')) {
    /**
     * Font files bundled with the theme (in /fonts), keyed by family name. They are only loaded and
     * preloaded while the family is selected as the primary or secondary font in the Customizer.
     */
    function theme_get_default_font_faces(): array
    {
        return [
            'Roboto Serif' => [
                ['weight' => '100', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-100.woff2', 'display' => 'swap', 'preload' => true],
                ['weight' => '100', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-100italic.woff2', 'display' => 'swap', 'preload' => false],
                ['weight' => '300', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-300.woff2', 'display' => 'swap', 'preload' => false],
                ['weight' => '300', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-300italic.woff2', 'display' => 'swap', 'preload' => false],
                ['weight' => '400', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-regular.woff2', 'display' => 'swap', 'preload' => true],
                ['weight' => '400', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-italic.woff2', 'display' => 'swap', 'preload' => false],
                ['weight' => '500', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-500.woff2', 'display' => 'swap', 'preload' => true],
                ['weight' => '500', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-500italic.woff2', 'display' => 'swap', 'preload' => false],
                ['weight' => '600', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-600.woff2', 'display' => 'swap', 'preload' => true],
                ['weight' => '600', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-600italic.woff2', 'display' => 'swap', 'preload' => false],
                ['weight' => '700', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-700.woff2', 'display' => 'swap', 'preload' => true],
                ['weight' => '700', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-700italic.woff2', 'display' => 'swap', 'preload' => false],
            ],
            'Mona Sans' => [
                ['weight' => '400', 'style' => 'normal', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-regular.woff2', 'display' => 'swap', 'preload' => true],
                ['weight' => '400', 'style' => 'italic', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-italic.woff2', 'display' => 'swap', 'preload' => false],
                ['weight' => '600', 'style' => 'normal', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-600.woff2', 'display' => 'swap', 'preload' => true],
                ['weight' => '600', 'style' => 'italic', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-600italic.woff2', 'display' => 'swap', 'preload' => false],
                ['weight' => '700', 'style' => 'normal', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-700.woff2', 'display' => 'swap', 'preload' => true],
                ['weight' => '700', 'style' => 'italic', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-700italic.woff2', 'display' => 'swap', 'preload' => false],
            ],
        ];
    }
}

if (! function_exists('theme_get_selected_default_font_faces')) {
    /**
     * Bundled font families currently selected in the Customizer, with their faces.
     * Returns an empty array when other fonts (e.g. from the Font Library) are selected.
     */
    function theme_get_selected_default_font_faces(): array
    {
        global $default_fonts;

        $selected = array_map('strtolower', [
            (string) get_theme_mod('font_primary',   $default_fonts['primary']),
            (string) get_theme_mod('font_secondary', $default_fonts['secondary']),
        ]);

        return array_filter(
            theme_get_default_font_faces(),
            fn($family) => in_array(strtolower($family), $selected, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}

if (! function_exists('theme_get_default_font_faces_css')) {
    /**
     * @font-face rules for the selected bundled fonts only.
     */
    function theme_get_default_font_faces_css(): string
    {
        $base = get_template_directory_uri() . '/fonts/';
        $css  = '';

        foreach (theme_get_selected_default_font_faces() as $family => $faces) {
            foreach ($faces as $face) {
                $css .= sprintf(
                    '@font-face{font-display:%s;font-family:"%s";font-style:%s;font-weight:%s;src:url("%s") format("woff2");}',
                    $face['display'],
                    $family,
                    $face['style'],
                    $face['weight'],
                    esc_url($base . $face['file'])
                );
            }
        }

        return $css;
    }
}

// ─── CSS CUSTOM PROPERTIES ───────────────────────────────────────────────────

if (! function_exists('theme_get_fonts_css')) {
    /**
     * Generates the :root block with the font custom properties.
     * Used in wp_head and for the Customizer live preview.
     */
    function theme_get_fonts_css(): string
    {
        global $default_fonts;

        $font_primary   = get_theme_mod('font_primary',   $default_fonts['primary']);
        $font_secondary = get_theme_mod('font_secondary', $default_fonts['secondary']);

        // Look up the full font-family (with generic fallback) if it exists in the library
        $all_fonts      = theme_get_all_fonts();

        $primary_family   = _theme_resolve_font_family($font_primary,   $all_fonts, 'sans-serif');
        $secondary_family = _theme_resolve_font_family($font_secondary, $all_fonts, 'serif');

        // Bundled fonts are only declared while they are selected
        $css  = theme_get_default_font_faces_css();
        $css .= ":root {\n";
        $css .= "    --font-primary: {$primary_family};\n";
        $css .= "    --font-secondary: {$secondary_family};\n";
        $css .= "}";

        return $css;
    }
}

if (! function_exists('_theme_resolve_font_family')) {
    /**
     * Given a font name (stored in theme_mod), returns the full CSS value
     * (with fallback) if it is in the library, or builds a simple one with
     * the given generic fallback.
     *
     * @internal
     */
    function _theme_resolve_font_family(string $font_name, array $all_fonts, string $generic_fallback): string
    {
        // Search by name among all available fonts
        foreach ($all_fonts as $data) {
            if (strtolower($data['name']) === strtolower($font_name)) {
                return $data['font_family']; // already includes the generic fallback from theme.json / Font Library
            }
        }
        // Not found: build a safe value
        return "\"{$font_name}\", {$generic_fallback}";
    }
}

// ─── HOOKS ───────────────────────────────────────────────────────────────────

add_action('customize_register', 'theme_customize_register_fonts');

// The Site Editor's font manager saves a copy of the theme's font families (with every bundled font face) in the
// global styles, which made WordPress print all bundled fonts whatever the Customizer selection. Drop that copy so
// only the selected fonts are loaded; fonts installed through the Font Library ('custom') are kept.
add_filter('wp_theme_json_data_user', function ($theme_json) {
    $data = $theme_json->get_data();
    if (!isset($data['settings']['typography']['fontFamilies']['theme'])) return $theme_json;

    unset($data['settings']['typography']['fontFamilies']['theme']);
    return new WP_Theme_JSON_Data($data, 'custom');
});
