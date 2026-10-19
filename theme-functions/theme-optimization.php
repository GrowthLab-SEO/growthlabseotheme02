<?php
// Disable unnecessary features
function cleanup_wordpress()
{
    // Remove RSD link
    remove_action('wp_head', 'rsd_link');

    // Remove wlwmanifest link
    remove_action('wp_head', 'wlwmanifest_link');

    // Remove shortlink
    remove_action('wp_head', 'wp_shortlink_wp_head');

    // Remove REST API links if not needed
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');

    // Remove feed links if not using them
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);

    // Disable embeds if not needed
    remove_action('wp_head', 'wp_oembed_add_host_js');
}
add_action('init', 'cleanup_wordpress');

// Remove Translation Scripts - OPTIMIZED
add_action('wp_enqueue_scripts', function () {
    global $post;

    // Only load if page actually has blocks that need i18n
    $needs_i18n = false;

    if (is_singular() && !empty($post->post_content)) {
        // Fast string check instead of full parsing
        if (strpos($post->post_content, '<!-- wp:') !== false) {
            // Parse blocks and check if any need translation
            $blocks = parse_blocks($post->post_content);

            foreach ($blocks as $block) {
                // Check if block is a dynamic/interactive block that needs i18n
                if (in_array($block['blockName'], [
                    'core/search',
                    'core/query',
                    'core/navigation',
                    // Add your custom blocks that need translation
                ])) {
                    $needs_i18n = true;
                    break;
                }
            }
        }
    }

    // Remove if not needed
    if (!$needs_i18n && !is_admin()) {
        wp_dequeue_script('wp-i18n');
        wp_deregister_script('wp-i18n');
    }
}, 100);


/**
 * Filter function used to remove the tinymce emoji plugin.
 * 
 * @param    array  $plugins  
 * @return   array  Difference betwen the two arrays
 */
function disable_emojis_tinymce($plugins)
{
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    } else {
        return array();
    }
}
// Disable Dashicons on front-end
function wpdocs_dequeue_dashicon()
{
    if (current_user_can('update_core')) {
        return;
    }
    wp_deregister_style('dashicons');
}

//Disable the emoji's
function disable_emojis()
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
}

/**
 * Remove unused core block styles (optional - more aggressive)
 * Uncomment if you want to disable all core block styles by default
 */
function dequeue_core_blocks_styles()
{
    // Remove core block library CSS
    //wp_dequeue_style('wp-block-library');
    //wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-blocks-style'); // WooCommerce blocks
    //wp_dequeue_style('global-styles'); // Global styles
}


if (!is_admin()) {
    add_action('init', 'disable_emojis');
    add_action('wp_enqueue_scripts', 'wpdocs_dequeue_dashicon');
    add_action('wp_enqueue_scripts', "dequeue_core_blocks_styles", 100);
}

/**
 * Preload the bundled font files of the fonts selected in the Customizer.
 * Nothing is preloaded when other fonts (e.g. from the Font Library) are selected.
 */
add_action('wp_head', function () {
    if (!function_exists('theme_get_selected_default_font_faces')) return;

    $base = get_template_directory_uri() . '/fonts/';

    foreach (theme_get_selected_default_font_faces() as $faces) {
        foreach ($faces as $face) {
            if (empty($face['preload'])) continue;
            echo '<link rel="preload" href="' . esc_url($base . $face['file']) . '" as="font" type="font/woff2" crossorigin>' . "\n";
        }
    }
}, 1);


/**
 * Find and remove Google Maps JS API if accidentally loaded
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;

    // Check for common Google Maps script handles
    $gmap_handles = array(
        'google-maps',
        'google-maps-api',
        'gmaps',
        'gmap',
        'maps-api',
    );

    foreach ($gmap_handles as $handle) {
        if (wp_script_is($handle, 'enqueued')) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }
}, 100);

/**
 * Google Maps IFrame Lazy Loading
 */
add_filter('the_content', function ($content) {
    static $map_counter = 0;

    $content = preg_replace_callback(
        '/<iframe([^>]*src=["\']https:\/\/(www\.)?google\.com\/maps\/embed[^"\']*["\'][^>]*)>.*?<\/iframe>/is',
        function ($matches) use (&$map_counter) {
            $iframe = $matches[0];
            $map_id = 'gmap-' . ++$map_counter;

            preg_match('/src=["\']([^"\']*)["\']/', $iframe, $src_match);
            preg_match('/width=["\']([^"\']*)["\']/', $iframe, $width_match);
            preg_match('/height=["\']([^"\']*)["\']/', $iframe, $height_match);

            $src = $src_match[1] ?? '';
            $width = $width_match[1] ?? '100%';
            $height = $height_match[1] ?? '450';

            // Append px only to unitless numbers; keep values like 100% as they are
            $css_size = function ($value) {
                $value = trim($value);
                return is_numeric($value) ? $value . 'px' : $value;
            };

            return sprintf(
                '<div id="%s" class="gmap-lazy" data-src="%s" style="width:%s;height:%s;"></div>',
                $map_id,
                esc_attr($src),
                esc_attr($css_size($width)),
                esc_attr($css_size($height))
            );
        },
        $content
    );

    return $content;
}, 20);
