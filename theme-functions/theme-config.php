<?php
/**
 * Design data of this theme. The theme functions shared by the GrowthLab themes (growthlab-theme-core) read it, so
 * this file is the one place where the themes differ in these values. It is not synced between themes.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default colors of the color scheme (Customizer > Colors), read by color-scheme.php, the editor palettes and TinyMCE
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

// Default fonts (Customizer > Fonts), read by fonts-selector.php
$default_fonts = [
    'primary'   => 'Mona Sans',
    'secondary' => 'Roboto Serif',
];

if (!function_exists('theme_get_config')) {
    /**
     * Design values of this theme, by key:
     * - font_faces: font files bundled in /fonts, by family (see theme_get_default_font_faces())
     * - above_fold_font_variants: font variants the header and the hero render, measured on this theme's templates;
     *   optional keys 'hero_styles', 'media' and 'when' (see theme_get_above_fold_font_variants())
     * - first_block_font_variants: font variants of the first block after the hero, by block name ("core" for core
     *   blocks, "default" for the rest). Codes: "s600" = secondary font weight 600, "p400" = primary font weight 400
     * - excerpt_length: words of the excerpts, 'default' and optionally by post type
     * - splide_blocks: blocks that render a Splide carousel (its scripts and CSS load only when one is on the page)
     */
    function theme_get_config(string $key)
    {
        $homepage_heroes = ['home', 'home_2', 'home_3', 'home_4', 'home_5', 'home_6'];

        $config = [
            'font_faces' => [
                'Roboto Serif' => [
                    ['weight' => '100', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-100.woff2', 'display' => 'swap'],
                    ['weight' => '100', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-100italic.woff2', 'display' => 'swap'],
                    ['weight' => '300', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-300.woff2', 'display' => 'swap'],
                    ['weight' => '300', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-300italic.woff2', 'display' => 'swap'],
                    ['weight' => '400', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-regular.woff2', 'display' => 'swap'],
                    ['weight' => '400', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-italic.woff2', 'display' => 'swap'],
                    ['weight' => '500', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-500.woff2', 'display' => 'swap'],
                    ['weight' => '500', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-500italic.woff2', 'display' => 'swap'],
                    ['weight' => '600', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-600.woff2', 'display' => 'swap'],
                    ['weight' => '600', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-600italic.woff2', 'display' => 'swap'],
                    ['weight' => '700', 'style' => 'normal', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-700.woff2', 'display' => 'swap'],
                    ['weight' => '700', 'style' => 'italic', 'file' => 'roboto-serif-v17-latin/roboto-serif-v17-latin-700italic.woff2', 'display' => 'swap'],
                ],
                'Mona Sans' => [
                    ['weight' => '400', 'style' => 'normal', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-regular.woff2', 'display' => 'swap'],
                    ['weight' => '400', 'style' => 'italic', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-italic.woff2', 'display' => 'swap'],
                    ['weight' => '600', 'style' => 'normal', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-600.woff2', 'display' => 'swap'],
                    ['weight' => '600', 'style' => 'italic', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-600italic.woff2', 'display' => 'swap'],
                    ['weight' => '700', 'style' => 'normal', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-700.woff2', 'display' => 'swap'],
                    ['weight' => '700', 'style' => 'italic', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-700italic.woff2', 'display' => 'swap'],
                ],
            ],
            'above_fold_font_variants' => [
                // Homepage hero titles use the secondary font: 400, and 700 for the <strong>/<b> parts
                ['role' => 'secondary', 'weight' => '700', 'style' => 'normal', 'hero_styles' => $homepage_heroes, 'when' => 'hero_title_bold'],
                ['role' => 'secondary', 'weight' => '400', 'style' => 'normal', 'hero_styles' => $homepage_heroes],
                // Default hero title, header menu, callout and hero text
                ['role' => 'primary', 'weight' => '700', 'style' => 'normal'],
                ['role' => 'primary', 'weight' => '400', 'style' => 'normal'],
            ],
            'first_block_font_variants' => [
                'acf/contact-form'      => ['s400', 'p400', 'p700'],
                'acf/content-box-grid'  => ['s500', 's400', 'p400'],
                'acf/content-intro'     => ['s500', 's200', 'p400'],
                'acf/cta-box'           => ['s700', 's400'],
                'acf/faq'               => ['s700', 's400', 'p400'],
                'acf/featured-video'    => ['s700', 's400', 'p400'],
                'acf/logos-carousel'    => ['s400'],
                'acf/office-locations'  => ['s700', 's500', 's400', 'p400', 'p700', 'p600'],
                'acf/posts-carousel'    => ['s700', 's500', 'p400', 'p700'],
                'acf/posts-grid'        => ['s400', 'p400', 'p700'],
                'acf/selling-points'    => ['s700', 's400', 'p400'],
                'acf/two-columns'       => ['s400', 'p400', 'p700'],
                'core'                  => ['p400', 'p700'],
                'default'               => ['s700', 's400', 'p400'],
            ],
            'excerpt_length' => ['default' => 15, 'team' => 50],
            'splide_blocks' => ['acf/logos-carousel', 'acf/posts-carousel', 'acf/selling-points'],
        ];

        return $config[$key] ?? null;
    }
}
