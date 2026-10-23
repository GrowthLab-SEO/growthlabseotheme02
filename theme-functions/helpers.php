<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('get_text_domain')) {
    function get_text_domain()
    {
        return $GLOBALS['theme_text_domain'];
    }
}


if (!function_exists('get_theme_svg')) {
    /**
     * Returns the markup of a static SVG file of the theme (path relative to the theme root, child theme first),
     * or '' if it does not exist. Contents are cached per request, so icons repeated in loops are read only once.
     */
    function get_theme_svg($relative_path)
    {
        static $cache = [];
        if (array_key_exists($relative_path, $cache)) {
            return $cache[$relative_path];
        }

        $file = get_theme_file_path($relative_path);
        $svg  = is_readable($file) ? (string) file_get_contents($file) : '';

        $cache[$relative_path] = $svg;
        return $svg;
    }
}

if (!function_exists('get_field_options')) {
    function get_field_options($field_name, $format_value = true)
    {
        if (empty($field_name) || !is_string($field_name)) return null;

        static $cache = [];
        $key = $field_name . '|' . ($format_value ? '1' : '0');
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $value       = get_field($field_name, 'option', $format_value);
        $cache[$key] = $value;
        return $value;
    }
}

if (!function_exists('block_style_attribute')) {
    function block_style_attribute($block = [])
    {
        if (empty($block['style'])) {
            return '';
        }

        $style_attr = '';

        if (is_string($block['style'])) {
            $style_attr = $block['style'];
        } elseif (is_array($block['style'])) {
            if (!empty($block['style']['spacing'])) {
                $s = $block['style']['spacing'];
                if (is_string($s)) {
                    $style_attr .= $s . ' ';
                } elseif (is_array($s)) {
                    if (!empty($s['padding'])) {
                        $style_attr .= normalize_spacing_property('padding', $s['padding']) . ' ';
                    }
                    if (!empty($s['margin'])) {
                        $style_attr .= normalize_spacing_property('margin', $s['margin']) . ' ';
                    }
                    if (!empty($s['blockGap'])) {
                        $style_attr .= 'gap: ' . normalize_block_spacing($s['blockGap']) . '; ';
                    }
                }
            }

            foreach ($block['style'] as $k => $v) {
                if ($k === 'spacing') {
                    continue;
                }
                if (is_string($v) || is_numeric($v)) {
                    $style_attr .= "$k: " . normalize_block_spacing($v) . '; ';
                }
            }
        }

        $style_attr = trim($style_attr);
        if (!$style_attr) {
            return '';
        }

        return ' style="' . esc_attr($style_attr) . '"';
    }
}

if (!function_exists('normalize_spacing_property')) {
    function normalize_spacing_property($property = 'padding', $value = null)
    {
        if (!$value) {
            return '';
        }

        if (is_string($value)) {
            return "$property: " . normalize_block_spacing($value) . ';';
        }

        if (is_numeric($value)) {
            return "$property: " . normalize_block_spacing($value) . ';';
        }

        if (!is_array($value)) {
            return '';
        }

        $unit = $value['unit'] ?? $value['unitType'] ?? '';
        $t = $value['top'] ?? null;
        $r = $value['right'] ?? null;
        $b = $value['bottom'] ?? null;
        $l = $value['left'] ?? null;

        $t_formatted = $t !== null ? format_spacing_item($t, $unit) : '';
        $r_formatted = $r !== null ? format_spacing_item($r, $unit) : '';
        $b_formatted = $b !== null ? format_spacing_item($b, $unit) : '';
        $l_formatted = $l !== null ? format_spacing_item($l, $unit) : '';

        if (!$t_formatted && !$r_formatted && !$b_formatted && !$l_formatted) {
            return '';
        }

        if ($t_formatted && $r_formatted && $b_formatted && $l_formatted) {
            if ($t_formatted === $r_formatted && $t_formatted === $b_formatted && $t_formatted === $l_formatted) {
                return "$property: $t_formatted;";
            }
            if ($t_formatted === $b_formatted && $r_formatted === $l_formatted) {
                return "$property: $t_formatted $r_formatted;";
            }
            if ($r_formatted === $l_formatted) {
                return "$property: $t_formatted $r_formatted $b_formatted;";
            }
            return "$property: $t_formatted $r_formatted $b_formatted $l_formatted;";
        }

        $output = '';
        if ($t_formatted) {
            $output .= "$property-top: $t_formatted; ";
        }
        if ($r_formatted) {
            $output .= "$property-right: $r_formatted; ";
        }
        if ($b_formatted) {
            $output .= "$property-bottom: $b_formatted; ";
        }
        if ($l_formatted) {
            $output .= "$property-left: $l_formatted; ";
        }

        return rtrim($output, ' ');
    }
}

if (!function_exists('normalize_block_spacing')) {
    function normalize_block_spacing($value)
    {
        if (is_string($value) && $value !== '' && preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . 'px';
        }

        if (is_numeric($value)) {
            return $value . 'px';
        }

        if (is_string($value)) {
            return css_preset_value($value);
        }

        if (is_array($value)) {
            $unit = $value['unit'] ?? $value['unitType'] ?? '';

            if (!empty($value['size'])) {
                return format_spacing_item($value['size'], $unit);
            }

            $items = array_filter(array_map(function ($item) use ($unit) {
                return format_spacing_item($item, $unit);
            }, $value));

            return implode(' ', $items);
        }

        return '';
    }
}

// The editor stores preset spacing as "var:preset|spacing|40": turn it into the CSS variable WordPress prints
// (var(--wp--preset--spacing--40)). Other values are returned as they are.
if (!function_exists('css_preset_value')) {
    function css_preset_value($value)
    {
        if (is_string($value) && str_starts_with($value, 'var:')) {
            return 'var(--wp--' . str_replace('|', '--', substr($value, 4)) . ')';
        }

        return $value;
    }
}

if (!function_exists('format_spacing_item')) {
    function format_spacing_item($value, $unit = '')
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_string($value) && preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . ($unit ?: 'px');
        }

        if (is_numeric($value)) {
            return $value . ($unit ?: 'px');
        }

        return css_preset_value((string) $value);
    }
}

if (!function_exists('filterContentByLanguage')) {
    function filterContentByLanguage($lang = 'es')
    {
        if (empty($lang) || !is_string($lang)) return false;

        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $path   = parse_url($uri, PHP_URL_PATH) ?: '/';
        $prefix = '/' . ltrim($lang, '/');

        if ($path === $prefix) return true;
        if (strpos($path, $prefix . '/') === 0) return true;

        return false;
    }
}

if (!function_exists('get_languages_map')) {
    function get_languages_map()
    {
        static $map = null;
        if ($map !== null) return $map;

        $lang_options = get_field_options('options_by_language') ?: [];
        if (!is_array($lang_options)) {
            $map = [];
            return $map;
        }

        $map = array_column($lang_options, 'language', 'url_language_slug');
        return $map;
    }
}

if (!function_exists('get_current_language')) {
    function get_current_language()
    {
        static $cached = null;
        if ($cached !== null) return $cached;

        $languages = get_languages_map();

        foreach ($languages as $slug => $language) {
            if (filterContentByLanguage($slug)) {
                $cached = [
                    'slug'     => $slug,
                    'language' => $language
                ];
                return $cached;
            }
        }

        $cached = [
            'slug'     => '',
            'language' => __('English', 'growthlabseotheme02')
        ];

        return $cached;
    }
}

// Language URL slugs (options_by_language) switch content without changing the WordPress locale, so
// language_attributes() would still print the site language there. Use the slug as the lang code when it is one.
add_filter('language_attributes', function ($output) {
    $slug = get_current_language()['slug'] ?? '';
    if ($slug === '' || !preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})*$/i', $slug)) return $output;
    return preg_replace('/lang="[^"]*"/', 'lang="' . esc_attr($slug) . '"', $output);
});

if (!function_exists('get_current_language_suffix')) {
    function get_current_language_suffix()
    {
        if (get_current_language()['slug'] !== '') return '_' . get_current_language()['slug'];
        return '';
    }
}

if (!function_exists('get_current_language_options')) {
    function get_current_language_options()
    {
        $current = get_current_language();
        $slug    = $current['slug'] ?? '';

        if ($slug === '') {
            return get_field_options('options');
        }

        $lang_opts = get_field_options('options_by_language') ?: [];
        if (!is_array($lang_opts)) return get_field_options('options');

        foreach ($lang_opts as $lang_opt) {
            if (!isset($lang_opt['url_language_slug'])) continue;
            if ($lang_opt['url_language_slug'] === $slug && isset($lang_opt['options'])) {
                return $lang_opt['options'];
            }
        }

        return get_field_options('options');
    }
}

if (!function_exists('get_flat_number')) {
    function get_flat_number($phone)
    {
        if (!$phone) return;
        return preg_replace("/[^0-9]/", '', $phone);
    }
}

// tel: URL for a phone number. US numbers get the +1 country code once, whether or not the number already includes it;
// numbers written with another international prefix (+52...) keep it. Returns '' when there are no digits.
if (!function_exists('get_phone_href')) {
    function get_phone_href($phone)
    {
        $digits = get_flat_number($phone);
        if (!$digits) return '';

        if (strlen($digits) === 10) {
            $digits = '1' . $digits;
        } elseif (!(strlen($digits) === 11 && $digits[0] === '1') && !str_starts_with(ltrim((string) $phone), '+')) {
            // Not a US number and no international prefix: dial it as written
            return esc_url('tel:' . $digits, array('tel'));
        }

        return esc_url('tel:+' . $digits, array('tel'));
    }
}

// Wraps a title in a heading/paragraph tag. The tag is limited to a safe list (falling back to h1 for
// hero titles and p otherwise) and the title keeps only post-safe HTML, so ACF values can't inject scripts.
if (!function_exists('get_wrapped_title')) {
    function get_wrapped_title($title, $tag = 'p', $classes = '', $is_hero = false)
    {
        if (!$title) return;

        $allowed_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div');
        $tag = strtolower(trim((string) $tag));
        if (!in_array($tag, $allowed_tags, true)) {
            $tag = $is_hero ? 'h1' : 'p';
        }

        $class_attr = $classes ? ' class="' . esc_attr($classes) . '"' : '';

        return "<$tag$class_attr>" . wp_kses_post($title) . "</$tag>";
    }
}

if (!function_exists('print_title')) {
    function print_title($title, $tag = 'p', $classes = '', $is_hero = false)
    {
        if (!$title) return;
        echo get_wrapped_title($title, $tag, $classes, $is_hero);
    }
}

// Returns the escaped id attribute for a block wrapper (with a leading space), or an empty string when no id is set
if (!function_exists('get_block_id_attribute')) {
    function get_block_id_attribute($block_id)
    {
        $block_id = trim((string) $block_id);
        return $block_id !== '' ? ' id="' . esc_attr($block_id) . '"' : '';
    }
}

// Prints a notice in place of a block's content (e.g. when nothing is selected).
// Only users who can edit posts see it; visitors get no output.
if (!function_exists('print_block_notice')) {
    function print_block_notice($message)
    {
        if (!current_user_can('edit_posts')) return;
        echo '<div class="block-notice container" style="margin-block:2rem;padding:2rem;border:2px dashed currentColor;text-align:center;"><p>' . esc_html($message) . '</p></div>';
    }
}

// Returns $value when it is one of the choices of an ACF select/radio/button group field, otherwise the field's
// default value ('' when it has none). Choices are read from the field definition in the current block or post,
// so adding an option in ACF needs no code change. Use "group.sub_field" for fields inside a group.
if (!function_exists('get_valid_field_choice')) {
    function get_valid_field_choice($field_name, $value)
    {
        $path = explode('.', $field_name);
        $field = get_field_object(array_shift($path), false, false, false);

        foreach ($path as $sub_name) {
            $sub_fields = is_array($field) ? ($field['sub_fields'] ?? []) : [];
            $field = current(array_filter($sub_fields, fn($sub) => ($sub['name'] ?? '') === $sub_name)) ?: null;
        }

        $choices = is_array($field) && is_array($field['choices'] ?? null) ? $field['choices'] : [];
        if (is_scalar($value) && $value !== '' && array_key_exists((string) $value, $choices)) {
            return (string) $value;
        }

        $default = $field['default_value'] ?? '';
        return is_scalar($default) && array_key_exists((string) $default, $choices) ? (string) $default : '';
    }
}

// Returns the data-extract attribute that main.js uses to move a block before/after the content, or '' when disabled
if (!function_exists('get_block_extract_attribute')) {
    function get_block_extract_attribute($enabled, $place)
    {
        if (!$enabled) return '';
        $place = get_valid_field_choice('place', $place);
        return $place !== '' ? ' data-extract="' . esc_attr($place) . '"' : '';
    }
}

// Returns the current block's ACF values with every field of its field groups present. Fields that were never saved
// (e.g. added after the block was created) get their ACF default value, so templates can keep extracting them into
// variables without undefined variable warnings. Empty defaults are null, so isset() checks keep working as before.
if (!function_exists('get_block_fields')) {
    function get_block_fields($block)
    {
        $values = get_fields() ?: [];
        $block_name = is_array($block) ? ($block['name'] ?? '') : (string) $block;
        if ($block_name === '' || !function_exists('acf_get_field_groups')) return $values;

        static $defaults_by_block = [];
        if (!isset($defaults_by_block[$block_name])) {
            $defaults = [];
            foreach (acf_get_field_groups(['block' => $block_name]) as $group) {
                foreach (acf_get_fields($group) ?: [] as $field) {
                    // Tabs, messages and accordions have no name and hold no value
                    if (!empty($field['name'])) $defaults[$field['name']] = get_acf_field_default($field);
                }
            }
            $defaults_by_block[$block_name] = $defaults;
        }

        return array_merge($defaults_by_block[$block_name], $values);
    }
}

// Default value of an ACF field as the template expects it: [] for multi-value fields, the sub field defaults for groups,
// a boolean for true/false fields, and null when the field has no default
if (!function_exists('get_acf_field_default')) {
    function get_acf_field_default($field)
    {
        $type = $field['type'] ?? '';

        if (in_array($type, ['repeater', 'flexible_content', 'gallery', 'relationship', 'checkbox'], true)) return [];

        if ($type === 'group') {
            $group = [];
            foreach ($field['sub_fields'] ?? [] as $sub_field) {
                if (!empty($sub_field['name'])) $group[$sub_field['name']] = get_acf_field_default($sub_field);
            }
            return $group;
        }

        $default = $field['default_value'] ?? null;
        if ($type === 'true_false') return (bool) $default;

        return is_scalar($default) && $default !== '' ? $default : null;
    }
}

// Merges an ACF group value (e.g. the site options or hero_properties) over the defaults of its sub fields, so templates
// can extract it into variables without undefined variable warnings. $selector and $post_id locate the field definition.
if (!function_exists('fill_acf_group_defaults')) {
    function fill_acf_group_defaults($value, $selector, $post_id = false)
    {
        static $defaults_by_selector = [];
        if (!isset($defaults_by_selector[$selector]) && function_exists('get_field_object')) {
            // Posts that never saved the field have no reference to it, so fall back to looking it up by name
            $field = get_field_object($selector, $post_id, false, false) ?: (function_exists('acf_get_field') ? acf_get_field($selector) : false);
            $defaults = is_array($field) ? get_acf_field_default($field) : null;
            if (is_array($defaults) && $defaults) $defaults_by_selector[$selector] = $defaults;
        }

        return array_merge($defaults_by_selector[$selector] ?? [], is_array($value) ? $value : []);
    }
}

// Returns a post's ACF values merged over the defaults of every field group assigned to it (see get_block_fields())
if (!function_exists('get_post_fields_with_defaults')) {
    function get_post_fields_with_defaults($post_id = false)
    {
        $post_id = $post_id ?: get_the_ID();
        $values = get_fields($post_id) ?: [];
        if (!function_exists('acf_get_field_groups')) return $values;

        $defaults = [];
        foreach (acf_get_field_groups(['post_id' => $post_id]) as $group) {
            foreach (acf_get_fields($group) ?: [] as $field) {
                if (!empty($field['name'])) $defaults[$field['name']] = get_acf_field_default($field);
            }
        }

        return array_merge($defaults, $values);
    }
}

// Blocks of the current singular post, parsed once per post and flattened (inner blocks follow their parent).
// Synced patterns (core/block) are expanded, so the blocks they contain count as used on the page. Falls back to the
// queried post when a secondary loop changed the global $post. Returns [] for non-singular requests.
if (!function_exists('get_current_post_blocks')) {
    function get_current_post_blocks()
    {
        static $cache = [];

        if (!is_singular()) return [];

        global $post;
        $current = $post;
        if (!($current instanceof WP_Post) || strpos((string) $current->post_content, '<!-- wp:') === false) {
            $queried = get_queried_object();
            if ($queried instanceof WP_Post) $current = $queried;
        }
        if (!($current instanceof WP_Post) || strpos((string) $current->post_content, '<!-- wp:') === false) return [];

        if (isset($cache[$current->ID])) return $cache[$current->ID];

        $blocks = [];
        $expanded_refs = [];
        $walk = function ($items) use (&$walk, &$blocks, &$expanded_refs) {
            foreach ($items as $block) {
                $blocks[] = $block;
                if (!empty($block['innerBlocks'])) $walk($block['innerBlocks']);

                // Each synced pattern is expanded once, which also stops patterns that include themselves
                $ref = ($block['blockName'] ?? '') === 'core/block' ? (int) ($block['attrs']['ref'] ?? 0) : 0;
                if ($ref && !isset($expanded_refs[$ref])) {
                    $expanded_refs[$ref] = true;
                    $walk(parse_blocks((string) get_post_field('post_content', $ref)));
                }
            }
        };
        $walk(parse_blocks($current->post_content));

        return $cache[$current->ID] = $blocks;
    }
}

// Returns the pagination query arg for the next paginated posts grid of the request: '' for the first grid (it keeps
// using paged/page, so existing URLs keep working) and 'grid-N-page' for the following ones
if (!function_exists('get_posts_grid_page_arg')) {
    function get_posts_grid_page_arg()
    {
        static $grid_count = 0;
        $grid_count++;
        return $grid_count === 1 ? '' : "grid-{$grid_count}-page";
    }
}

// ID of the page whose "Page - Options" fields apply to the current request: the queried post on singular views
// and the posts page on the blog index. Archives, search results and 404 pages have no such page and return 0.
if (!function_exists('get_page_options_id')) {
    function get_page_options_id()
    {
        if (is_singular()) return (int) get_queried_object_id();
        if (is_home()) return (int) get_option('page_for_posts');
        return 0;
    }
}

// Reads a page-level ACF field for the current request (see get_page_options_id()). Returns null when the request
// has no page, instead of letting ACF fall back to the global $post (the first post of the loop on listings).
if (!function_exists('get_page_field')) {
    function get_page_field($selector)
    {
        $page_id = get_page_options_id();
        return $page_id ? get_field($selector, $page_id) : null;
    }
}

// Blog index, archives and search results always use the default hero
if (!function_exists('is_listing_page')) {
    function is_listing_page()
    {
        return is_home() || is_archive() || is_search();
    }
}

// Hero style of the current request: listings always use the default hero and 404 pages have none
if (!function_exists('get_page_hero_style')) {
    function get_page_hero_style()
    {
        if (is_404()) return 'nohero';
        if (is_listing_page()) return 'default';
        return get_page_field('hero_style') ?: 'default';
    }
}

// Name of the first block rendered in the current post's content (the one right after the hero), skipping
// synced pattern wrappers and ACF blocks switched off with their toggle_block field. '' when there is none.
if (!function_exists('get_first_content_block_name')) {
    function get_first_content_block_name()
    {
        foreach (get_current_post_blocks() as $block) {
            $name = $block['blockName'] ?? '';
            if ($name === '' || $name === 'core/block') continue;
            if (isset($block['attrs']['data']['toggle_block']) && !$block['attrs']['data']['toggle_block']) continue;
            return $name;
        }
        return '';
    }
}

// Fallback hero title for the current request, used when the page has no custom hero title
if (!function_exists('get_page_hero_title')) {
    function get_page_hero_title()
    {
        if (is_home()) {
            $page_id = (int) get_option('page_for_posts');
            return $page_id ? get_the_title($page_id) : __('Blog', 'growthlabseotheme02');
        }
        if (is_search()) {
            /* translators: %s: search query */
            return sprintf(__('Search results for: %s', 'growthlabseotheme02'), get_search_query());
        }
        if (is_singular()) return get_the_title(get_queried_object_id());
        if (is_category() || is_tag() || is_tax()) return single_term_title('', false);
        if (is_post_type_archive()) return post_type_archive_title('', false);
        if (is_archive()) return get_the_archive_title();
        return '';
    }
}

// Render a Gravity Forms form only if the plugin is active
if (!function_exists('render_gravity_form')) {
    function render_gravity_form(...$args)
    {
        if (!function_exists('gravity_form')) return;
        return gravity_form(...$args);
    }
}

// HTML of the form chosen in a set of form fields:
// - "{prefix}_type": gravity_form, custom or none. Fields saved before the type existed have none and keep their Gravity Form.
// - "{prefix}": the Gravity Form ID.
// - "{prefix}_code": the embed code or script of a third-party form, printed as is.
// Returns an empty string when there is no form to print.
if (!function_exists('get_selected_form_html')) {
    function get_selected_form_html($fields, $prefix = 'contact_form')
    {
        $fields = is_array($fields) ? $fields : [];
        $type   = ($fields[$prefix . '_type'] ?? '') ?: 'gravity_form';

        if ($type === 'custom') return trim((string) ($fields[$prefix . '_code'] ?? ''));
        if ($type !== 'gravity_form' || empty($fields[$prefix])) return '';

        ob_start();
        render_gravity_form($fields[$prefix], display_title: false, display_description: false);
        return trim((string) ob_get_clean());
    }
}

if (!function_exists('format_number_abbreviated')) {
    function format_number_abbreviated($number)
    {
        if ($number >= 1000000000) {
            return round($number / 1000000000, 1) . 'B';
        } elseif ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return $number;
    }
}

// Returns the 11-character ID of a single YouTube video, or false for any URL that is not a video
// (channels, playlists, search results, the homepage, etc.). Mirrors extractYouTubeID() in main.js
if (!function_exists('get_yt_code')) {
    function get_yt_code($url = false)
    {
        if (!$url || !is_string($url)) return false;

        $url = trim($url);
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) $url = 'https://' . ltrim($url, '/');

        $parts = wp_parse_url($url);
        if (empty($parts['host'])) return false;

        $host = strtolower(preg_replace('/^(www\.|m\.|music\.)/i', '', $parts['host']));
        $segments = array_values(array_filter(explode('/', $parts['path'] ?? '')));
        $id = null;

        if ($host === 'youtu.be') {
            $id = $segments[0] ?? null;                                  // youtu.be/ID
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if (($segments[0] ?? '') === 'watch') {
                parse_str($parts['query'] ?? '', $query);                // youtube.com/watch?v=ID
                $id = $query['v'] ?? null;
            } elseif (in_array($segments[0] ?? '', ['embed', 'shorts', 'live', 'v', 'e'], true)) {
                $id = $segments[1] ?? null;                              // /embed/ID, /shorts/ID, /live/ID
            }
        }

        // "videoseries" is the 11-character placeholder used by playlist embeds, not a video
        return is_string($id) && $id !== 'videoseries' && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : false;
    }
}

// Converts inline font-size styles into custom CSS variables
function transform_inline_font_sizes(
    string $html,
    string $font_size_var = '--fs'
): string {

    if (trim($html) === '') {
        return $html;
    }

    $previous_errors = libxml_use_internal_errors(true);

    $dom = new DOMDocument();

    // Wrap the fragment in a container so multi-root WYSIWYG markup isn't nested into its first element, and declare
    // UTF-8 instead of converting to HTML entities (mb_convert_encoding 'HTML-ENTITIES' is deprecated in PHP 8.2+)
    $dom->loadHTML(
        '<?xml encoding="UTF-8"><div id="transform-inline-font-sizes">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous_errors);

    $container = $dom->getElementById('transform-inline-font-sizes');
    if (!$container) {
        return $html;
    }

    foreach ($container->getElementsByTagName('*') as $element) {

        if (!$element->hasAttribute('style')) {
            continue;
        }

        $style = $element->getAttribute('style');

        if (
            !preg_match(
                '/font-size\s*:\s*([^;]+)/i',
                $style,
                $matches
            )
        ) {
            continue;
        }

        $font_size = trim($matches[1]);

        // Remove the original font-size
        $style = preg_replace(
            '/font-size\s*:\s*[^;]+;?/i',
            '',
            $style
        );

        $style = trim($style);

        if ($style !== '' && substr($style, -1) !== ';') {
            $style .= ';';
        }

        // Add the variable and the new rule
        $style .= sprintf(
            '%s:%s;font-size:calc(var(%s) * var(--font-scale, 1));',
            $font_size_var,
            $font_size,
            $font_size_var
        );

        $element->setAttribute('style', $style);
    }

    $output = '';
    foreach ($container->childNodes as $child) {
        $output .= $dom->saveHTML($child);
    }

    return $output;
}

// Returns the best available thumbnail URL for a YouTube video, from $size downwards.
// The result is cached in a transient, so YouTube is only queried once per video and size instead of on every page view.
if (!function_exists('get_youtube_thumbnail')) {
    function get_youtube_thumbnail(string $video_id, string $size = 'hqdefault'): string
    {
        $base = "https://img.youtube.com/vi/{$video_id}";
        $cache_key = 'gl_yt_thumb_' . md5($video_id . '|' . $size);

        $cached = get_transient($cache_key);
        if (is_string($cached) && $cached !== '') return $cached;

        $sizes = ['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'];

        // Check from the requested size downwards
        $start = array_search($size, $sizes, true);
        $sizes = array_slice($sizes, $start !== false ? $start : 2);

        foreach ($sizes as $s) {
            $url = "{$base}/{$s}.jpg";
            $response = wp_remote_head($url, array('timeout' => 3));

            // YouTube is unreachable: stop instead of waiting for every remaining size
            if (is_wp_error($response)) break;

            if (wp_remote_retrieve_response_code($response) === 200) {
                // YouTube returns a ~1.4 KB 120x90 placeholder for sizes that don't exist
                $length = (int) wp_remote_retrieve_header($response, 'content-length');
                if ($length > 2000) {
                    set_transient($cache_key, $url, MONTH_IN_SECONDS);
                    return $url;
                }
            }
        }

        // hqdefault always exists; cache this fallback for a shorter time so a temporary failure is retried later
        $fallback = "{$base}/hqdefault.jpg";
        set_transient($cache_key, $fallback, DAY_IN_SECONDS);
        return $fallback;
    }
}

// [phonenumber] shortcode: prints the main phone number from the site options as a tel: link.
// All attributes are escaped because any user who can edit posts (including contributors) can use it.
function phonenumber($atts, $content = null)
{
    $atts = shortcode_atts(array(
        'target' => '_self',
        'link_id' => '',
        'link_class' => '',
        'wrapper' => '',
        'text' => ''
    ), $atts, 'phonenumber');

    $options = get_current_language_options();
    $phone = $options['main_phone_number'] ?? '';

    // Fall back to the "text" attribute when no phone number is set in the options
    $link_text = $phone ?: $atts['text'];
    $link_number = get_flat_number($link_text);

    if (!$link_number) {
        return '';
    }

    $allowed_wrappers = array('span', 'div', 'p', 'strong', 'em', 'small', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6');
    $wrapper = strtolower(trim($atts['wrapper']));
    $wrapper = in_array($wrapper, $allowed_wrappers, true) ? $wrapper : '';

    $target = in_array($atts['target'], array('_self', '_blank', '_parent', '_top'), true) ? $atts['target'] : '_self';

    // id and class go on the wrapper when there is one, otherwise on the link
    $id_class = '';
    if ($atts['link_id'] !== '') {
        $id_class .= ' id="' . esc_attr($atts['link_id']) . '"';
    }
    if ($atts['link_class'] !== '') {
        $id_class .= ' class="' . esc_attr($atts['link_class']) . '"';
    }

    $link = sprintf(
        '<a href="%s" target="%s"%s%s>%s</a>',
        get_phone_href($link_text),
        esc_attr($target),
        $target === '_blank' ? ' rel="noopener noreferrer"' : '',
        $wrapper ? '' : $id_class,
        esc_html($link_text)
    );

    return $wrapper ? "<$wrapper$id_class>$link</$wrapper>" : $link;
}
add_shortcode('phonenumber', 'phonenumber');
