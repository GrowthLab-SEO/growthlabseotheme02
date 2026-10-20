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

        $custom = theme_get_font_library_families();

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

// ─── FONT LIBRARY CACHE ──────────────────────────────────────────────────────

if (! function_exists('theme_get_font_library_cache_key')) {
    /**
     * Transient name of the cached Font Library families, scoped to the active template and stylesheet so a theme
     * switch (or a child theme) never reads another theme's list.
     */
    function theme_get_font_library_cache_key(?string $template = null, ?string $stylesheet = null): string
    {
        $template   = $template ?? get_template();
        $stylesheet = $stylesheet ?? get_stylesheet();

        return 'theme_font_library_' . md5($template . '|' . $stylesheet);
    }
}

if (! function_exists('theme_get_font_library_families')) {
    /**
     * Font families installed through the Font Library ('custom' sub-key of the merged theme.json data).
     *
     * WP_Theme_JSON_Resolver::get_merged_data() is expensive, so the list is cached in a transient (with an
     * expiration, so it is not autoloaded) plus a static per-request cache. The transient is deleted whenever fonts,
     * global styles or the theme change (see theme_flush_font_library_cache()). The Customizer always reads fresh data.
     */
    function theme_get_font_library_families(): array
    {
        static $families = null;

        $bypass = is_customize_preview() || doing_action('customize_register');
        if (! $bypass && null !== $families) {
            return $families;
        }

        $key = theme_get_font_library_cache_key();
        $cached = $bypass ? false : get_transient($key);

        if (is_array($cached)) {
            $result = $cached;
        } else {
            $settings = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
            $result   = $settings['typography']['fontFamilies']['custom'] ?? [];

            if (! $bypass) {
                set_transient($key, $result, WEEK_IN_SECONDS);
            }
        }

        if (! $bypass) {
            $families = $result;
        }

        return $result;
    }
}

if (! function_exists('theme_flush_font_library_cache')) {
    /**
     * Deletes the cached Font Library families of the active theme.
     */
    function theme_flush_font_library_cache(): void
    {
        delete_transient(theme_get_font_library_cache_key());
    }
}

if (! function_exists('theme_flush_font_library_cache_on_delete')) {
    /**
     * Flushes the cache when a font family, font face or global styles post is deleted.
     */
    function theme_flush_font_library_cache_on_delete($post_id, $post = null): void
    {
        $post = $post ?: get_post($post_id);
        if ($post && in_array($post->post_type, ['wp_font_family', 'wp_font_face', 'wp_global_styles'], true)) {
            theme_flush_font_library_cache();
        }
    }
}

if (! function_exists('theme_flush_font_library_cache_on_switch')) {
    /**
     * Flushes the cache of both the previous and the new theme on a theme switch.
     */
    function theme_flush_font_library_cache_on_switch($new_name = '', $new_theme = null, $old_theme = null): void
    {
        theme_flush_font_library_cache();
        foreach ([$new_theme, $old_theme] as $theme) {
            if ($theme instanceof WP_Theme) {
                delete_transient(theme_get_font_library_cache_key($theme->get_template(), $theme->get_stylesheet()));
            }
        }
    }
}

// ─── CUSTOMIZER ──────────────────────────────────────────────────────────────

if (! function_exists('theme_customize_register_fonts')) {
    function theme_customize_register_fonts($wp_customize)
    {
        global $default_fonts;

        $wp_customize->add_section('fonts_section', [
            'title'       => esc_html__('Fonts', 'growthlabseotheme02'),
            'priority'    => 30,
            'description' => esc_html__('Select the fonts for your theme', 'growthlabseotheme02'),
        ]);

        register_font_setting($wp_customize, 'font_primary',   $default_fonts['primary'],   __('Primary Font', 'growthlabseotheme02'));
        register_font_setting($wp_customize, 'font_secondary', $default_fonts['secondary'], __('Secondary Font', 'growthlabseotheme02'));
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
            'label'   => esc_html($label),
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

// Keep the cached Font Library families in sync with fonts, global styles, Customizer saves and theme switches
add_action('save_post_wp_font_family', 'theme_flush_font_library_cache');
add_action('save_post_wp_font_face', 'theme_flush_font_library_cache');
add_action('save_post_wp_global_styles', 'theme_flush_font_library_cache');
add_action('deleted_post', 'theme_flush_font_library_cache_on_delete', 10, 2);
add_action('customize_save_after', 'theme_flush_font_library_cache');
add_action('switch_theme', 'theme_flush_font_library_cache_on_switch', 10, 3);
add_action('after_switch_theme', 'theme_flush_font_library_cache');

// The Site Editor's font manager saves a copy of the theme's font families (with every bundled font face) in the
// global styles, which made WordPress print all bundled fonts whatever the Customizer selection. Drop that copy so
// only the selected fonts are loaded; fonts installed through the Font Library ('custom') are kept.
add_filter('wp_theme_json_data_user', function ($theme_json) {
    $data = $theme_json->get_data();
    if (!isset($data['settings']['typography']['fontFamilies']['theme'])) return $theme_json;

    unset($data['settings']['typography']['fontFamilies']['theme']);
    return new WP_Theme_JSON_Data($data, 'custom');
});

// ─── FONT LIBRARY @FONT-FACE OUTPUT ──────────────────────────────────────────

if (! function_exists('theme_normalize_font_family_name')) {
    /**
     * Normalizes a font family name for comparisons (lowercase, without quotes or surrounding spaces).
     */
    function theme_normalize_font_family_name(string $name): string
    {
        return strtolower(trim($name, " \t\n\r\0\x0B'\""));
    }
}

if (! function_exists('theme_get_selected_font_family_names')) {
    /**
     * Normalized family names of the primary and secondary fonts selected in the Customizer: the saved name and
     * the first family of its CSS font-family value (they can differ for Font Library fonts).
     */
    function theme_get_selected_font_family_names(): array
    {
        global $default_fonts;

        $all_fonts = theme_get_all_fonts();
        $names     = [];

        foreach (['font_primary' => 'primary', 'font_secondary' => 'secondary'] as $mod => $slot) {
            $name    = (string) get_theme_mod($mod, $default_fonts[$slot]);
            $family  = _theme_resolve_font_family($name, $all_fonts, '');
            $names[] = theme_normalize_font_family_name($name);
            $names[] = theme_normalize_font_family_name(explode(',', $family)[0]);
        }

        return array_values(array_unique(array_filter($names)));
    }
}

if (! function_exists('theme_print_selected_font_faces')) {
    /**
     * Replaces WordPress' front-end wp_print_font_faces(), which prints @font-face rules for every font installed
     * through the Font Library even when they aren't used. Only the families selected in the Customizer are printed,
     * and faces without an explicit font-display use swap (WordPress defaults to fallback), like the bundled fonts.
     * The block editor still loads every installed font, so all of them can be previewed there.
     */
    function theme_print_selected_font_faces()
    {
        if (! class_exists('WP_Font_Face_Resolver') || ! function_exists('wp_print_font_faces')) return;

        $selected = theme_get_selected_font_family_names();
        $fonts    = [];

        // The resolver returns one group of faces per font family; the family name is in each face's font-family
        foreach (WP_Font_Face_Resolver::get_fonts_from_theme_json() as $faces) {
            $family = (string) ($faces[0]['font-family'] ?? '');
            if (! in_array(theme_normalize_font_family_name($family), $selected, true)) continue;

            foreach ($faces as $i => $face) {
                if (empty($face['font-display'])) {
                    $faces[$i]['font-display'] = 'swap';
                }
            }
            $fonts[] = $faces;
        }

        if ($fonts) {
            wp_print_font_faces($fonts);
        }
    }
}

remove_action('wp_head', 'wp_print_font_faces', 50);
add_action('wp_head', 'theme_print_selected_font_faces', 50);
