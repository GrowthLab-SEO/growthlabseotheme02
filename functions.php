<?php

/**
 * Functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage growthlabseotheme02
 * 
 */

// Theme text domain global
$GLOBALS['theme_text_domain'] = 'growthlabseotheme02';

// Define custom breakpoints for this theme
$GLOBALS['breakpoints'] = [
    'mobile' => '0px',
    'tablet' => '768px',   // differs from the default
    'ldpi'   => '1024px',
    'mdpi'   => '1280px',  // differs from the default
    'hdpi'   => '1920px',  // differs from the default
];


// Include Theme Functions
$includes = [
    'theme-functions/theme-optimization.php',
    'theme-functions/color-scheme.php',
    'theme-functions/fonts-selector.php',
    'theme-functions/acf-functions.php',
    'theme-functions/helpers.php',
    'theme-functions/svg-support.php',
    'theme-functions/picture-optimization.php',
    'theme-functions/tiny-mce.php',
    'theme-functions/security-functions.php',
];

foreach ($includes as $file) {
    if (file_exists(get_template_directory() . '/' . $file)) {
        require_once get_template_directory() . '/' . $file;
    }
}


if (!function_exists('growthlabseotheme02_setup')) {
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     *
     * Note that this function is hooked into the after_setup_theme hook, which
     * runs before the init hook. The init hook is too late for some features, such
     * as indicating support for post thumbnails.
     *
     *
     * @return void
     */

    function growthlabseotheme02_setup()
    {
        // Make the theme available for translation
        load_theme_textdomain('growthlabseotheme02', get_template_directory() . '/languages');

        /*
		* Let WordPress manage the document title.
		* This theme does not use a hard-coded <title> tag in the document head,
		* WordPress will provide it for us.
		*/
        add_theme_support('title-tag');

        /*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
        add_theme_support('post-thumbnails');

        // Custom Logo Support
        $defaults = array(
            'height'               => 200,
            'width'                => 360,
            'flex-height'          => true,
            'flex-width'           => true,
            'unlink-homepage-logo' => true,
        );

        add_theme_support('custom-logo', $defaults);

        // Disable hard cropping for all image sizes (write only when the stored value differs)
        if ((int) get_option('thumbnail_crop') !== 0) {
            update_option('thumbnail_crop', 0);
        }


        // Don't generate these WordPress sizes (the size list WordPress uses to generate files comes from this filter)
        add_filter('intermediate_image_sizes', function ($sizes) {
            return array_diff($sizes, [
                'medium_large',
                '1536x1536',
                '2048x2048'
            ]);
        });

        //Add custom sized images
        // Covers
        add_image_size('cover-desktop', 1920, 1080, false);
        add_image_size('cover-tablet', 1280, 600, false);
        add_image_size('cover-mobile', 768, 432, false);

        // Covers portrait
        add_image_size('cover-tablet-portrait', 960, 1280, true);
        add_image_size('cover-mobile-portrait', 768, 1100, true);

        // Content
        add_image_size('content', 800, 600, false);

        // Cards
        add_image_size('featured-small', 480, 360, false);

        // Add custom image sizes to the media selector
        add_filter('image_size_names_choose', function ($sizes) {
            return array_merge([
                'thumbnail'    => __('Thumbnail'),
                'medium'       => __('Medium'),
                'large'        => __('Large'),
                'full'         => __('Full Size'),
                'cover-desktop'  => __('Cover Desktop (1920×1080)', 'growthlabseotheme02'),
                'cover-tablet'   => __('Cover Tablet (1280×600)', 'growthlabseotheme02'),
                'cover-mobile'   => __('Cover Mobile (768×432)', 'growthlabseotheme02'),
                'cover-tablet-portrait'  => __('Cover Tablet Portrait (960×1280)', 'growthlabseotheme02'),
                'cover-mobile-portrait'  => __('Cover Mobile Portrait (768×1100)', 'growthlabseotheme02'),
                'content'   => __('Content (800×600)', 'growthlabseotheme02'),
                'featured-small' => __('Featured Small (480×360)', 'growthlabseotheme02'),
            ], $sizes);
        });

        // Tipography and Color Support
        add_theme_support('appearance-tools');

        // Register Navigation Menus
        register_nav_menus(
            array(
                'main' => esc_html__('Main Menu', 'growthlabseotheme02')
            )
        );

        if (function_exists('get_languages_map')) {
            foreach (get_languages_map() as $slug => $language) {
                register_nav_menus(
                    array(
                        'main_' . $slug => sprintf(esc_html__('Main Menu %s', 'growthlabseotheme02'), $language)
                    )
                );
            }
        }
    }
}
add_action('after_setup_theme', 'growthlabseotheme02_setup');

add_filter('wp_theme_json_data_theme', function ($theme_json) {
    global $default_colors, $default_fonts;

    $data = $theme_json->get_data();

    // ─── DISABLE UNWANTED GLOBAL CSS ────────────────────────────────────────
    $data['settings']['color']['defaultPalette']            = false;
    $data['settings']['color']['defaultGradients']          = false;
    $data['settings']['typography']['defaultFontSizes']     = false;
    $data['settings']['spacing']['defaultSpacingSizes']     = false;

    // ─── COLOR PALETTE ───────────────────────────────────────────────────────
    $data['settings']['color']['palette'] = [
        ['name' => __('Primary Color',        'growthlabseotheme02'), 'slug' => 'primary-color',        'color' => get_theme_mod('primary_color',        $default_colors['primary']['default'])],
        ['name' => __('Primary Color Dark',   'growthlabseotheme02'), 'slug' => 'primary-color-dark',   'color' => get_theme_mod('primary_color_dark',   $default_colors['primary']['dark'])],
        ['name' => __('Primary Color Light',  'growthlabseotheme02'), 'slug' => 'primary-color-light',  'color' => get_theme_mod('primary_color_light',  $default_colors['primary']['light'])],
        ['name' => __('Secondary Color',      'growthlabseotheme02'), 'slug' => 'secondary-color',      'color' => get_theme_mod('secondary_color',      $default_colors['secondary']['default'])],
        ['name' => __('Secondary Color Dark', 'growthlabseotheme02'), 'slug' => 'secondary-color-dark', 'color' => get_theme_mod('secondary_color_dark', $default_colors['secondary']['dark'])],
        ['name' => __('Secondary Color Light', 'growthlabseotheme02'), 'slug' => 'secondary-color-light', 'color' => get_theme_mod('secondary_color_light', $default_colors['secondary']['light'])],
        ['name' => __('Tertiary Color',       'growthlabseotheme02'), 'slug' => 'tertiary-color',       'color' => get_theme_mod('tertiary_color',       $default_colors['tertiary']['default'])],
        ['name' => __('Tertiary Color Dark',  'growthlabseotheme02'), 'slug' => 'tertiary-color-dark',  'color' => get_theme_mod('tertiary_color_dark',  $default_colors['tertiary']['dark'])],
        ['name' => __('Tertiary Color Light', 'growthlabseotheme02'), 'slug' => 'tertiary-color-light', 'color' => get_theme_mod('tertiary_color_light', $default_colors['tertiary']['light'])],
        ['name' => __('Text Color',           'growthlabseotheme02'), 'slug' => 'text-color',           'color' => get_theme_mod('text_color',           $default_colors['text'])],
    ];

    // ─── FONTS ───────────────────────────────────────────────────────────────
    // Families come from the Customizer, without theme.json fontFace entries, so bundled fonts are only
    // declared by theme_get_default_font_faces_css() while they are selected (same as themes 01 and 03)
    $font_primary   = get_theme_mod('font_primary',   $default_fonts['primary']);
    $font_secondary = get_theme_mod('font_secondary', $default_fonts['secondary']);

    $data['settings']['typography']['fontFamilies'] = [
        ['name' => $font_primary,   'slug' => 'primary',   'fontFamily' => "\"{$font_primary}\", sans-serif"],
        ['name' => $font_secondary, 'slug' => 'secondary', 'fontFamily' => "\"{$font_secondary}\", serif"],
    ];

    $theme_json->update_with($data);
    return $theme_json;
});

/**
 * Remove link from custom logo
 */
function growthlabseotheme02_remove_custom_logo_link($html)
{
    // Extract just the <img> tag from the logo HTML
    preg_match('/<img[^>]+>/', $html, $matches);

    if (!empty($matches[0])) {
        return $matches[0];
    }

    return $html;
}
add_filter('get_custom_logo', 'growthlabseotheme02_remove_custom_logo_link');

/*Custom Excerpt Size*/
function growthlabseotheme02_custom_excerpt_length($length)
{
    if (get_post_type() === 'team') {
        return 50;
    }
    return 15;
}
add_filter('excerpt_length', 'growthlabseotheme02_custom_excerpt_length', 999);

/*custom excerpt more*/
function wpdocs_excerpt_more($more)
{
    return '...';
}
add_filter('excerpt_more', 'wpdocs_excerpt_more');

/**
 * Whether the current request renders a Splide carousel: a carousel block in the current post
 * (or in a reusable block it references) or in a block widget of an active sidebar.
 *
 * @return bool
 */
function growthlabseotheme02_request_uses_splide()
{
    static $uses_splide = null;
    if ($uses_splide !== null) return $uses_splide;

    // Blocks whose markup uses Splide
    $splide_blocks = apply_filters('growthlabseotheme02_splide_blocks', array(
        'acf/logos-carousel',
        'acf/posts-carousel',
        'acf/selling-points',
    ));

    $content_has_splide = function ($content) use ($splide_blocks) {
        foreach ($splide_blocks as $block_name) {
            if (has_block($block_name, $content)) return true;
        }
        return false;
    };

    $uses_splide = false;

    // Current post blocks (parsed once per request), including reusable blocks
    foreach (get_current_post_blocks() as $block) {
        if (in_array($block['blockName'], $splide_blocks, true)) {
            $uses_splide = true;
            break;
        }
        if ($block['blockName'] === 'core/block' && !empty($block['attrs']['ref'])) {
            $reusable = get_post((int) $block['attrs']['ref']);
            if ($reusable && $content_has_splide($reusable->post_content)) {
                $uses_splide = true;
                break;
            }
        }
    }

    // Block widgets in active sidebars
    if (!$uses_splide) {
        $sidebars      = wp_get_sidebars_widgets();
        $block_widgets = get_option('widget_block', array());
        unset($sidebars['wp_inactive_widgets']);

        foreach ($sidebars as $widget_ids) {
            foreach ((array) $widget_ids as $widget_id) {
                if (!preg_match('/^block-(\d+)$/', (string) $widget_id, $matches)) continue;
                $content = $block_widgets[$matches[1]]['content'] ?? '';
                if ($content !== '' && $content_has_splide($content)) {
                    $uses_splide = true;
                    break 2;
                }
            }
        }
    }

    return $uses_splide;
}

/**
 * Add scripts and styles.
 *
 *
 * @return void
 */

/**
 * Hero stylesheets of the current page. The build moves the rules of the homepage hero variants out of
 * main-min.css into styles/heroes/ (see gulpfile.js), so each page only prints the ones its hero uses.
 * Filter: theme_hero_css_files (array of file paths, and the hero style).
 */
if (!function_exists('theme_get_hero_css_files')) {
    function theme_get_hero_css_files()
    {
        $files_by_style = array(
            'home'   => array('hero-homepage'),
            'home_2' => array('hero-v2'),
            'home_3' => array('hero-v3'),
            'home_4' => array('hero-homepage', 'hero-v4'),
            'home_5' => array('hero-v5'),
            'home_6' => array('hero-homepage', 'hero-v6'),
        );
        $hero_style = function_exists('get_page_hero_style') ? get_page_hero_style() : 'default';

        $files = array();
        foreach ($files_by_style[$hero_style] ?? array() as $name) {
            $file = get_template_directory() . "/styles/heroes/$name-min.css";
            if (file_exists($file)) $files[] = $file;
        }

        return (array) apply_filters('theme_hero_css_files', $files, $hero_style);
    }
}

function inline_main_critical_css()
{
    // Block CSS files collected per block type by the wp_enqueue_scripts callback in acf-functions.php
    global $block_critical_css_files;
    $block_css_files = !empty($block_critical_css_files) ? $block_critical_css_files : array();

    // Dynamic Fonts
    $fonts_css = theme_get_fonts_css();

    // Dynamic Color Scheme
    $color_scheme = theme_get_customizer_css();

    $theme_uri       = get_template_directory_uri();
    $main_css_file   = get_template_directory() . "/styles/main-min.css";
    $style_css_file  = get_stylesheet_directory() . "/style.css";
    // Compiled styles of the child theme (its styles/main.scss, built with this theme's gulpfile), printed between
    // the parent styles and the child's style.css
    $child_css_file = is_child_theme() ? get_stylesheet_directory() . '/styles/main-min.css' : '';
    if ($child_css_file && !file_exists($child_css_file)) $child_css_file = '';
    $splide_css_file = get_template_directory() . '/styles/vendor/splide/splide-core.min.css';

    // Add Splide critical CSS only when carousels are present
    $include_splide = growthlabseotheme02_request_uses_splide() && file_exists($splide_css_file);

    // Cache the final CSS, keyed by everything that affects it
    $hero_css_files = theme_get_hero_css_files();
    $source_files = array_filter(array_merge(array($main_css_file), $hero_css_files, array($child_css_file, $style_css_file)));
    if ($include_splide) $source_files[] = $splide_css_file;
    foreach ($block_css_files as $type_files) {
        $source_files = array_merge($source_files, $type_files);
    }

    $source_mtimes = array();
    foreach ($source_files as $file) {
        $source_mtimes[$file] = (int) @filemtime($file);
    }

    $cache_key = 'growthlabseotheme02_ccss_' . md5(serialize(array(
        // This file builds the CSS, so changing it invalidates the cache too
        @filemtime(__FILE__),
        $theme_uri,
        get_stylesheet_directory_uri(),
        $source_mtimes,
        $block_css_files,
        $include_splide,
        md5($fonts_css . $color_scheme),
    )));

    // Customizer previews change fonts/colors on every refresh, so don't fill the cache from them
    $use_cache = !is_customize_preview();
    $critical_css = $use_cache ? get_transient($cache_key) : false;
    if (is_string($critical_css)) {
        echo '<style id="main-css">' . $critical_css . '</style>';
        return;
    }

    $critical_css = file_get_contents($main_css_file);
    foreach ($hero_css_files as $hero_css_file) $critical_css .= file_get_contents($hero_css_file);
    if ($child_css_file) $critical_css .= file_get_contents($child_css_file);
    $critical_css .= file_get_contents($style_css_file);
    $critical_css = $fonts_css . $color_scheme . $critical_css;

    if ($include_splide) {
        $critical_css .= "\n/* Splide Critical CSS */\n" . file_get_contents($splide_css_file);
    }

    // Combine the block CSS per type, wrapped in a comment for debugging
    $block_critical_css = '';
    foreach ($block_css_files as $type => $type_files) {
        $type_css = '';
        foreach ($type_files as $css_file) {
            $type_css .= file_get_contents($css_file) . "\n";
        }
        if (!empty($type_css)) {
            $block_critical_css .= "/* Block type: {$type} */\n" . $type_css;
        }
    }

    // Add block critical CSS if any
    if (!empty($block_critical_css)) {
        $critical_css .= "\n/* Block Critical CSS */\n" . $block_critical_css;
    }

    // The CSS is inline, so relative url()s would resolve against the page: theme assets use these placeholders
    $critical_css = str_replace('{child-theme-path}', get_stylesheet_directory_uri(), $critical_css);
    $critical_css = preg_replace('/\{theme-path\}/', $theme_uri, $critical_css);
    // Minify CSS: remove comments, extra spaces, and newlines
    $critical_css = preg_replace('/\/\*.*?\*\//s', '', $critical_css); // Remove CSS comments
    $critical_css = preg_replace('/\s+/', ' ', $critical_css); // Collapse whitespace runs into one space
    // Spaces around braces and semicolons are never significant; the ones around ":" and "," can be (".a :hover", strings)
    $critical_css = preg_replace('/\s*([{};])\s*/', '$1', $critical_css);
    $critical_css = trim($critical_css); // Trim leading/trailing whitespace

    // An expiration keeps the transient out of the autoloaded options
    if ($use_cache) set_transient($cache_key, $critical_css, WEEK_IN_SECONDS);

    echo '<style id="main-css">' . $critical_css . '</style>';
}
add_action('wp_head', 'inline_main_critical_css', 10);

function growthlabseotheme02_scripts()
{
    if (is_admin()) return;

    // Move jQuery to footer (safe for GF)
    wp_scripts()->add_data('jquery', 'group', 1);
    wp_scripts()->add_data('jquery-core', 'group', 1);

    // Drop jQuery Migrate from the jquery dependencies (not needed for modern GF).
    // Deregistering it instead would make WordPress skip jquery and every script that depends on it.
    $jquery = wp_scripts()->query('jquery', 'registered');
    if ($jquery) {
        $jquery->deps = array_values(array_diff($jquery->deps, ['jquery-migrate']));
    }

    // Gravity Forms - remove maps
    wp_dequeue_script('gform_gravityforms_maps');

    // Main JS scripts.
    wp_enqueue_script(
        'growthlabseotheme02-main-scripts',
        get_template_directory_uri() . '/js/main-min.js',
        array(),
        filemtime(get_template_directory() . '/js/main-min.js'),
        ["strategy" => "defer", "in_footer" => true]
    );
    wp_localize_script('growthlabseotheme02-main-scripts', 'siteData', [
        'homeURL' => home_url(),
        // Printed inside HTML attributes by main.js
        'i18n' => [
            'closeVideo'  => esc_attr__('Close video', 'growthlabseotheme02'),
            'videoDialog' => esc_attr__('Video player', 'growthlabseotheme02'),
        ],
    ]);

    // Select Styles
    wp_enqueue_script(
        'growthlabseotheme02-styled-select-fields',
        get_template_directory_uri() . '/js/styled-select-fields-min.js',
        array(),
        filemtime(get_template_directory() . '/js/styled-select-fields-min.js'),
        ["strategy" => "defer", "in_footer" => true]
    );
    wp_localize_script('growthlabseotheme02-styled-select-fields', 'styledSelectData', [
        'placeholder' => __('Select an option', 'growthlabseotheme02'),
    ]);

    // Picture Optimization scripts.
    wp_enqueue_script(
        'growthlabseotheme02-picture-optimization',
        get_template_directory_uri() . '/js/picture-optimization-min.js',
        array(),
        filemtime(get_template_directory() . '/js/picture-optimization-min.js'),
        ["strategy" => "async", "in_footer" => true]
    );

    // Child theme scripts (its js/main.js, built with this theme's gulpfile)
    $child_js_file = get_stylesheet_directory() . '/js/main-min.js';
    if (is_child_theme() && file_exists($child_js_file)) {
        wp_enqueue_script(
            'growthlabseotheme02-child-scripts',
            get_stylesheet_directory_uri() . '/js/main-min.js',
            array('growthlabseotheme02-main-scripts'),
            filemtime($child_js_file),
            true
        );
        wp_script_add_data('growthlabseotheme02-child-scripts', 'strategy', 'defer');
    }

    // Third party JS scripts.
    wp_localize_script('growthlabseotheme02-main-scripts', 'splideData', [
        'url' => get_template_directory_uri() . '/js/vendor/splide/splide-min.js',
    ]);

    // Load specific template stylesheet
    if (is_page() || is_single()) {
        if (!is_page_template('page-templates/template-full-width.php')) {
            wp_enqueue_style('growthlabseotheme02-template-default', get_template_directory_uri() . '/styles/page-templates/template-default-min.css', array(),  filemtime(get_template_directory() . '/styles/page-templates/template-default-min.css'));
        }
        if (is_singular('team')) {
            wp_enqueue_style('growthlabseotheme02-template-team-member', get_template_directory_uri() . '/styles/page-templates/template-team-member-min.css', array(),  filemtime(get_template_directory() . '/styles/page-templates/template-team-member-min.css'));
        }
    }
    if (is_listing_page()) {
        wp_enqueue_style('growthlabseotheme02-template-default', get_template_directory_uri() . '/styles/page-templates/template-default-min.css', array(),  filemtime(get_template_directory() . '/styles/page-templates/template-default-min.css'));
        wp_enqueue_style('growthlabseotheme02-blog', get_template_directory_uri() . '/styles/page-templates/template-blog-min.css', array(),  filemtime(get_template_directory() . '/styles/page-templates/template-blog-min.css'));
        wp_enqueue_script(
            'growthlabseotheme02-posts-filters',
            get_template_directory_uri() . '/js/posts-filters-min.js',
            array(),
            filemtime(get_template_directory() . '/js/posts-filters-min.js'),
            ["strategy" => "defer", "in_footer" => true]
        );
        wp_localize_script(
            'growthlabseotheme02-posts-filters',
            'postsFiltersData',
            array(
                'restUrl' => get_rest_url(null, 'wp/v2'),
                // Same page size as the server-rendered listing, so pagination doesn't change after filtering
                'perPage' => (int) get_option('posts_per_page'),
                // Default image of the current language, falling back to the base options
                'defaultImage' => function_exists('get_current_language_options')
                    ? ((get_current_language_options()['posts_default_image']['url'] ?? '') ?: (get_field_options('options')['posts_default_image']['url'] ?? ''))
                    : '',
                'i18n' => array(
                    'noPosts'       => __('No posts found for the selected filters.', 'growthlabseotheme02'),
                    'loadError'     => __('Could not load posts. Please try again.', 'growthlabseotheme02'),
                    'readMore'      => __('Read More', 'growthlabseotheme02'),
                    /* translators: %s: post title */
                    'readMoreAbout' => __('Read more about %s', 'growthlabseotheme02'),
                    /* translators: %s: active filter (search text, category, month or year) */
                    'removeFilter'  => __('Remove %s', 'growthlabseotheme02'),
                    'prev'          => __('Prev', 'growthlabseotheme02'),
                    'next'          => __('Next', 'growthlabseotheme02'),
                ),
            )
        );
    }
}

add_action('wp_enqueue_scripts', 'growthlabseotheme02_scripts');

// Blog filters: "pf_month" (1-12) filters the posts REST endpoint by month across every year. The date range
// parameters of the endpoint can only express a month within a given year.
add_filter('rest_post_query', function ($args, $request) {
    $month = (int) $request->get_param('pf_month');
    if ($month >= 1 && $month <= 12) {
        $args['date_query'][] = array('month' => $month);
    }
    return $args;
}, 10, 2);

// Add theme and parent/child theme classes to body
add_filter('body_class', function ($classes) {
    if (is_child_theme()) {
        $theme = wp_get_theme();
        $classes[] = 'theme-child-' . sanitize_html_class($theme->get_stylesheet());
        $classes[] = 'theme-parent-' . sanitize_html_class($theme->get_template());
    }
    return $classes;
});

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 *
 * @return void
 */
function growthlabseotheme02_widgets_init()
{

    register_sidebar(
        array(
            'name'          => esc_html__('Default Sidebar', 'growthlabseotheme02'),
            'id'            => 'sidebar-default',
            'description'   => esc_html__('Add widgets here to appear in the page sidebar.', 'growthlabseotheme02'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<p class="widget-title">',
            'after_title'   => '</p>',
        )
    );

    register_sidebar(
        array(
            'name'          => esc_html__('Blog Sidebar', 'growthlabseotheme02'),
            'id'            => 'sidebar-blog',
            'description'   => esc_html__('Add widgets here to appear in the Blog sidebar.', 'growthlabseotheme02'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<p class="widget-title">',
            'after_title'   => '</p>',
        )
    );

    if (function_exists('get_languages_map')) {
        foreach (get_languages_map() as $slug => $language) {
            register_sidebar(
                array(
                    'name'          => sprintf(esc_html__('%s Sidebar', 'growthlabseotheme02'), $language),
                    'id'            => "sidebar-default-{$slug}",
                    'description'   => esc_html__('Add widgets here to appear in the page sidebar.', 'growthlabseotheme02'),
                    'before_widget' => '<div id="%1$s" class="widget %2$s">',
                    'after_widget'  => '</div>',
                    'before_title'  => '<p class="widget-title">',
                    'after_title'   => '</p>',
                )
            );

            register_sidebar(
                array(
                    'name'          => sprintf(esc_html__('%s Blog Sidebar', 'growthlabseotheme02'), $language),
                    'id'            => "sidebar-blog-{$slug}",
                    'description'   => esc_html__('Add widgets here to appear in the Blog sidebar.', 'growthlabseotheme02'),
                    'before_widget' => '<div id="%1$s" class="widget %2$s">',
                    'after_widget'  => '</div>',
                    'before_title'  => '<p class="widget-title">',
                    'after_title'   => '</p>',
                )
            );
        }
    }
}

add_action('widgets_init', 'growthlabseotheme02_widgets_init');


// Gravity Forms 
add_filter('gform_disable_css', '__return_true');
add_filter('gform_disable_theme_editor_styles', '__return_true');
add_filter('gform_init_scripts_footer', '__return_true');


//Import All Theme Icons to the Media Library
//Run only once after theme installation
function import_theme_images_to_folder()
{
    // Path to your theme's images folder
    $image_folder = get_template_directory() . '/assets/icons/';

    // Create custom folder in uploads
    $upload_dir = wp_upload_dir();
    $custom_folder = $upload_dir['basedir'] . '/theme-icons/';
    $custom_url = $upload_dir['baseurl'] . '/theme-icons/';

    // Create folder if it doesn't exist
    if (!file_exists($custom_folder)) {
        wp_mkdir_p($custom_folder);
    }

    // Get all image files
    $images = glob($image_folder . '*.{svg}', GLOB_BRACE);

    foreach ($images as $image_path) {
        $filename = basename($image_path);

        // Check if file already exists
        $existing = get_posts([
            'post_type' => 'attachment',
            'meta_query' => [[
                'key' => '_wp_attached_file',
                'value' => 'theme-icons/' . $filename,
                'compare' => '='
            ]]
        ]);

        if (!empty($existing)) continue;

        // Copy file to custom folder
        $new_file = $custom_folder . $filename;
        copy($image_path, $new_file);

        // Create attachment
        $attachment = [
            'post_mime_type' => mime_content_type($new_file),
            'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $new_file);

        // Update attachment metadata with correct path
        update_attached_file($attach_id, 'theme-icons/' . $filename);

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $new_file);
        wp_update_attachment_metadata($attach_id, $attach_data);
    }
}

// Force the widget sections to stay available
add_action('customize_register', function ($wp_customize) {
    // Check and force the widgets panel
    $widgets_panel = $wp_customize->get_panel('widgets');
    if ($widgets_panel) {
        $widgets_panel->active_callback = '__return_true';
    }

    // Force the specific sections to always be active
    $sidebar_default = $wp_customize->get_section('sidebar-widgets-sidebar-default');
    if ($sidebar_default) {
        $sidebar_default->active_callback = '__return_true';
    }

    $sidebar_blog = $wp_customize->get_section('sidebar-widgets-sidebar-blog');
    if ($sidebar_blog) {
        $sidebar_blog->active_callback = '__return_true';
    }
}, 999);

// Prevent the Customizer from dynamically hiding widget sections
add_action('customize_controls_print_footer_scripts', function () {
?>
    <script>
        (function($) {
            wp.customize.bind('ready', function() {
                // Force the widget panels to stay visible
                var widgetsPanel = wp.customize.panel('widgets');
                if (widgetsPanel) {
                    widgetsPanel.active.set(true);

                    // Prevent it from being hidden
                    widgetsPanel.active.validate = function() {
                        return true;
                    };
                }

                // Force specific sections
                ['sidebar-widgets-sidebar-default', 'sidebar-widgets-sidebar-blog'].forEach(function(sectionId) {
                    var section = wp.customize.section(sectionId);
                    if (section) {
                        section.active.set(true);
                        section.active.validate = function() {
                            return true;
                        };
                    }
                });
            });
        })(jQuery);
    </script>
<?php
}, 999);

// Add dynamic versioning to theme scripts and styles
add_filter('script_loader_src', 'growthlabseotheme02_add_dynamic_asset_version', 10, 2);
add_filter('style_loader_src', 'growthlabseotheme02_add_dynamic_asset_version', 10, 2);

/**
 * Set the ?ver= of assets from the parent or the active child theme to the file's mtime.
 *
 * @param string $src    Asset URL.
 * @param string $handle Asset handle.
 * @return string
 */
function growthlabseotheme02_add_dynamic_asset_version($src, $handle)
{
    // Child theme first: its directory name may start with the parent's
    $theme_dirs = array();
    if (is_child_theme()) {
        $theme_dirs[get_stylesheet_directory_uri()] = get_stylesheet_directory();
    }
    $theme_dirs[get_template_directory_uri()] = get_template_directory();

    // Remove scheme for comparison
    $src_normalized = preg_replace('(^https?:)', '', $src);

    foreach ($theme_dirs as $theme_dir_uri => $theme_dir) {
        $theme_dir_normalized = preg_replace('(^https?:)', '', $theme_dir_uri);
        if (strpos($src_normalized, $theme_dir_normalized . '/') !== 0) continue;

        // Extract path without query string
        $path = (string) parse_url($src, PHP_URL_PATH);

        // Remove the theme directory from path to get relative path
        $relative_path = str_replace(parse_url($theme_dir_uri, PHP_URL_PATH), '', $path);
        $file_path = $theme_dir . $relative_path;

        if (file_exists($file_path)) {
            $version = filemtime($file_path);
            // Remove existing ver parameter and add new one
            $src = remove_query_arg('ver', $src);
            $src = add_query_arg('ver', $version, $src);
        }
        break;
    }
    return $src;
}
