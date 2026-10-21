<?php

/**
 * Picture optimization
 *
 * Prints responsive <picture> elements for the theme. Public API:
 *
 *   img_print_picture_tag(img: $image, max_size: 'large', min_size: 'featured-small', classes: 'card__pic');
 *   $html = img_generate_picture_tag(...same arguments...);
 *
 * $image can be an ACF image array or an image URL. For every breakpoint the function picks one of the image
 * sizes WordPress generated (see add_image_size() in functions.php) and prints a <source> for it, plus a WebP
 * <source> when a ".webp" copy of the file exists (e.g. created by EWWW: photo.jpg -> photo.jpg.webp).
 *
 * Two kinds of pictures:
 * - Standard (default): images inside the content. Sizes are picked between min_size (phones) and max_size
 *   (the largest file any screen gets).
 * - Cover (is_cover: true): full-screen backgrounds such as hero images. They use the cover-* sizes and can
 *   take a dedicated tablet image (768-1023px screens) and mobile image (screens under 768px).
 *
 * Lazy loading: pictures are lazy by default (data-src/data-srcset, filled in by js/picture-optimization.js
 * when they get near the viewport, with a <noscript> copy for browsers without JavaScript). is_priority: true
 * loads the picture right away with fetchpriority="high", for the image that is the LCP element; those images are
 * also preloaded in the <head> (see "Preloads of high priority images").
 *
 * Layout shifts: lazy images get --ar-{breakpoint}-w/h custom properties with the real size of the file each
 * breakpoint serves; styles/globals/_picture-optimization.scss turns them into an aspect-ratio, so the space
 * is reserved before the file loads.
 */

/* -------------------------------------------------------------------------------------------------------------
 * Breakpoints and image sizes
 * ---------------------------------------------------------------------------------------------------------- */

// Minimum viewport width of each breakpoint. functions.php defines the theme's values before this file is loaded;
// these defaults are the same, for a theme that doesn't. styles/globals/_picture-optimization.scss uses them too.
if (!isset($GLOBALS['breakpoints'])) {
    $GLOBALS['breakpoints'] = [
        'mobile' => '0px',
        'tablet' => '768px',
        'ldpi'   => '1024px',
        'mdpi'   => '1280px',
        'hdpi'   => '1920px',
    ];
}

if (!function_exists('po_get_breakpoint_ranges')) {
    /**
     * Breakpoint minimum widths as integers, smallest first: ['mobile' => 0, 'tablet' => 768, ...].
     */
    function po_get_breakpoint_ranges(): array
    {
        static $ranges = null;
        if ($ranges === null) {
            $ranges = array_map('intval', $GLOBALS['breakpoints']);
            asort($ranges);
        }
        return $ranges;
    }
}

if (!function_exists('po_get_breakpoint_order')) {
    /**
     * Breakpoint names from the largest screen to the smallest. <source> elements are printed in this order,
     * because the browser uses the first one whose media query matches.
     */
    function po_get_breakpoint_order(): array
    {
        return array_reverse(array_keys(po_get_breakpoint_ranges()));
    }
}

if (!function_exists('po_get_next_breakpoint_min')) {
    /**
     * Minimum width of the next larger breakpoint, which is where this breakpoint ends (its upper limit).
     * Null for the largest breakpoint, which has no upper limit.
     */
    function po_get_next_breakpoint_min(string $breakpoint): ?int
    {
        $ranges = po_get_breakpoint_ranges();
        $names  = array_keys($ranges);
        $index  = array_search($breakpoint, $names, true);

        if ($index === false || !isset($names[$index + 1])) return null;
        return $ranges[$names[$index + 1]];
    }
}

if (!function_exists('po_get_smallest_media_breakpoint')) {
    /**
     * The smallest breakpoint that has a media query ("tablet"). Mobile is the default <source>, without one.
     */
    function po_get_smallest_media_breakpoint(): ?string
    {
        return array_keys(po_get_breakpoint_ranges())[1] ?? null;
    }
}

if (!function_exists('po_get_media_query')) {
    /**
     * Media query of a breakpoint, e.g. "(min-width: 768px)". Null for mobile, the default.
     */
    function po_get_media_query(string $breakpoint): ?string
    {
        $min = po_get_breakpoint_ranges()[$breakpoint] ?? 0;
        return $min > 0 ? "(min-width: {$min}px)" : null;
    }
}

if (!function_exists('po_init_sizes')) {
    /**
     * Stores the registered image size names (WordPress and theme sizes, plus "full") in $GLOBALS['sizes'].
     * Runs late on after_setup_theme, after functions.php registered the theme sizes.
     */
    function po_init_sizes(): void
    {
        $sizes = function_exists('get_intermediate_image_sizes') ? (array) get_intermediate_image_sizes() : [];

        global $_wp_additional_image_sizes;
        foreach (array_keys((array) $_wp_additional_image_sizes) as $size) {
            if (!in_array($size, $sizes, true)) $sizes[] = $size;
        }

        if (!in_array('full', $sizes, true)) $sizes[] = 'full';

        $GLOBALS['sizes'] = array_values($sizes);
    }
}
add_action('after_setup_theme', 'po_init_sizes', 999);

if (!function_exists('po_get_registered_width')) {
    /**
     * Width a size was registered with (e.g. 800 for "content"), 0 for unknown sizes and "full".
     * Sizes without hard crop keep each image's proportions, so the files can be narrower than this:
     * the real width of each image's files is in its metadata (po_get_available_sizes()).
     */
    function po_get_registered_width(string $size): int
    {
        global $_wp_additional_image_sizes;
        if (!empty($_wp_additional_image_sizes[$size]['width'])) {
            return (int) $_wp_additional_image_sizes[$size]['width'];
        }

        if (in_array($size, ['thumbnail', 'medium', 'large'], true)) {
            return (int) get_option("{$size}_size_w");
        }

        return 0;
    }
}

if (!function_exists('po_get_available_sizes')) {
    /**
     * Sizes generated for an image, with the real width of each file, from the attachment metadata:
     * ['full' => 2560, 'cover-desktop' => 1620, 'content' => 800, ...]. Sizes the image is too small for
     * are not generated by WordPress, so they are missing here.
     */
    function po_get_available_sizes(array $meta): array
    {
        $available = [];

        foreach ($GLOBALS['sizes'] as $size) {
            $width = $size === 'full' ? (int) ($meta['width'] ?? 0) : (int) ($meta['sizes'][$size]['width'] ?? 0);
            if ($width > 0) $available[$size] = $width;
        }

        return $available;
    }
}

if (!function_exists('po_get_size_dimensions')) {
    /**
     * Real width and height of one size of an image: ['w' => 800, 'h' => 533].
     */
    function po_get_size_dimensions(array $fields, string $size): array
    {
        if ($size !== 'full' && isset($fields['meta']['sizes'][$size])) {
            return [
                'w' => (int) $fields['meta']['sizes'][$size]['width'],
                'h' => (int) $fields['meta']['sizes'][$size]['height'],
            ];
        }

        return ['w' => (int) $fields['width'], 'h' => (int) $fields['height']];
    }
}

if (!function_exists('po_get_size_url')) {
    /**
     * URL of one size of an image, or the full image URL when that size doesn't exist.
     */
    function po_get_size_url(array $fields, string $size): string
    {
        return $fields['urls'][$size] ?? $fields['url'];
    }
}

/* -------------------------------------------------------------------------------------------------------------
 * Image data
 * ---------------------------------------------------------------------------------------------------------- */

if (!function_exists('img_get_empty_fields')) {
    /**
     * Image data used when an image can't be read.
     */
    function img_get_empty_fields(): array
    {
        return [
            'id'        => 0,            // Attachment ID (0: not a media library image)
            'url'       => '',           // Full image URL
            'width'     => 0,            // Full image width
            'height'    => 0,            // Full image height
            'alt'       => '',
            'mime_type' => 'image/jpeg',
            'urls'      => [],           // URL of every generated size, by size name
            'meta'      => [],           // Attachment metadata (sizes, widths, heights)
        ];
    }
}

if (!function_exists('img_find_attachment_id')) {
    /**
     * Attachment ID of an image URL. Also works with the URL of a generated size ("photo-800x533.jpg"), which
     * attachment_url_to_postid() doesn't recognise: the "-WIDTHxHEIGHT" suffix is removed and the lookup retried.
     */
    function img_find_attachment_id(string $url): int
    {
        $url = preg_replace('/[?#].*$/', '', $url);
        $id  = attachment_url_to_postid($url);

        if (!$id && preg_match('/-\d+x\d+(\.[a-z0-9]+)$/i', $url)) {
            $id = attachment_url_to_postid(preg_replace('/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $url));
        }

        return (int) $id;
    }
}

if (!function_exists('img_parse_fields')) {
    /**
     * Normalizes an image (ACF image array or URL) into the fields above.
     * A URL that isn't a media library image (external or theme files) keeps only its URL; it is printed as
     * a plain image without sizes.
     */
    function img_parse_fields(array|string $img): array
    {
        // ACF image array: it already has the URLs of every size
        if (is_array($img)) {
            $id        = (int) ($img['ID'] ?? $img['id'] ?? 0);
            $acf_sizes = is_array($img['sizes'] ?? null) ? $img['sizes'] : [];
            $full_url  = $img['url'] ?? '';

            $urls = ['full' => $full_url];
            foreach ($GLOBALS['sizes'] as $size) {
                if ($size !== 'full') $urls[$size] = $acf_sizes[$size] ?? $full_url;
            }

            return [
                'id'        => $id,
                'url'       => $full_url,
                'width'     => (int) ($img['width'] ?? 0),
                'height'    => (int) ($img['height'] ?? 0),
                'alt'       => $img['alt'] ?? '',
                'mime_type' => $img['mime_type'] ?? 'image/jpeg',
                'urls'      => $urls,
                'meta'      => ($id ? wp_get_attachment_metadata($id) : []) ?: [],
            ];
        }

        // URL: find its attachment to read the sizes
        $url  = (string) $img;
        $id   = img_find_attachment_id($url);
        $meta = $id ? wp_get_attachment_metadata($id) : false;

        if (!$meta) {
            $type = wp_check_filetype(strtok($url, '?#'))['type'] ?: 'image/jpeg';
            return array_merge(img_get_empty_fields(), ['url' => $url, 'mime_type' => $type]);
        }

        $full_url = wp_get_attachment_url($id);
        $urls     = ['full' => $full_url];
        foreach ($GLOBALS['sizes'] as $size) {
            if ($size !== 'full') $urls[$size] = wp_get_attachment_image_url($id, $size) ?: $full_url;
        }

        return [
            'id'        => $id,
            'url'       => $full_url,
            'width'     => (int) ($meta['width'] ?? 0),
            'height'    => (int) ($meta['height'] ?? 0),
            'alt'       => get_post_meta($id, '_wp_attachment_image_alt', true) ?: '',
            'mime_type' => get_post_mime_type($id) ?: 'image/jpeg',
            'urls'      => $urls,
            'meta'      => $meta,
        ];
    }
}

if (!function_exists('img_get_fields')) {
    /**
     * img_parse_fields() with a per-request cache: the same image is often printed several times per page.
     */
    function img_get_fields(array|string $img): array
    {
        static $cache = [];

        $key = md5(is_array($img) ? serialize($img) : $img);
        return $cache[$key] ??= img_parse_fields($img);
    }
}

/* -------------------------------------------------------------------------------------------------------------
 * WebP copies
 * ---------------------------------------------------------------------------------------------------------- */

if (!function_exists('img_url_to_path')) {
    /**
     * File path of a site URL (uploads, theme files...). http/https differences are ignored. Returns '' for URLs
     * of other sites.
     */
    function img_url_to_path(string $url): string
    {
        $url      = strtok($url, '?#');
        $strip    = fn($u) => preg_replace('#^https?:#i', '', untrailingslashit($u));
        $upload   = wp_get_upload_dir();
        $relative = $strip($url);

        if (str_starts_with($relative, $strip($upload['baseurl']))) {
            return untrailingslashit($upload['basedir']) . substr($relative, strlen($strip($upload['baseurl'])));
        }

        if (str_starts_with($relative, $strip(home_url()))) {
            return untrailingslashit(ABSPATH) . substr($relative, strlen($strip(home_url())));
        }

        return '';
    }
}

if (!function_exists('img_is_webp')) {
    function img_is_webp(string $url, string $mime_type = ''): bool
    {
        return $mime_type === 'image/webp' || str_ends_with(strtolower((string) strtok($url, '?#')), '.webp');
    }
}

if (!function_exists('img_has_webp')) {
    /**
     * Whether a WebP copy of the file exists next to it ("photo.jpg.webp"). Cached per request.
     */
    function img_has_webp(string $url, string $mime_type = ''): bool
    {
        static $cache = [];

        if (img_is_webp($url, $mime_type)) return true;
        if (!isset($cache[$url])) {
            $path        = img_url_to_path($url);
            $cache[$url] = $path !== '' && file_exists($path . '.webp');
        }

        return $cache[$url];
    }
}

if (!function_exists('img_get_webp_url')) {
    function img_get_webp_url(string $url, string $mime_type = ''): string
    {
        return img_is_webp($url, $mime_type) ? $url : $url . '.webp';
    }
}

/* -------------------------------------------------------------------------------------------------------------
 * Choosing the size of each breakpoint: standard pictures
 * ---------------------------------------------------------------------------------------------------------- */

if (!function_exists('po_select_candidate')) {
    /**
     * Size of a standard picture for one breakpoint (not mobile, see img_get_standard_mobile_size()), or null when
     * no size fits it: the breakpoint then uses the file of the next smaller breakpoint.
     *
     * A size fits a breakpoint when its width (the registered width; the real width for sizes without one) is
     * between the breakpoint's minimum width and the next breakpoint's minimum width. Among the sizes that fit,
     * the widest wins. Every size is used once per picture ($already_used), so each breakpoint gets its own file.
     * The tablet breakpoint (the smallest with a media query) also takes sizes narrower than its minimum width,
     * so it isn't left without an image.
     *
     * @param string $breakpoint   Breakpoint name ("hdpi" ... "tablet").
     * @param array  $available    Generated sizes of the image and their real widths (po_get_available_sizes()).
     * @param string $max_size     Largest size any breakpoint may get ("full": no limit).
     * @param string $min_size     Size phones get; no breakpoint gets a smaller file ('': no limit).
     * @param array  $already_used Sizes taken by larger breakpoints.
     */
    function po_select_candidate(
        string $breakpoint,
        array  $available,
        string $max_size,
        string $min_size,
        array  $already_used = []
    ): ?string {
        // With the same minimum and maximum, every screen gets that one size (the mobile file)
        if ($max_size === $min_size) return null;

        $bp_min          = po_get_breakpoint_ranges()[$breakpoint] ?? 0;
        $bp_max          = po_get_next_breakpoint_min($breakpoint);
        $is_tablet       = $breakpoint === po_get_smallest_media_breakpoint();
        $max_registered  = $max_size !== 'full' ? po_get_registered_width($max_size) : 0;
        $min_registered  = $min_size !== '' ? po_get_registered_width($min_size) : 0;
        // Real width of the minimum size for this image (null when it wasn't generated)
        $min_width       = $min_registered > 0 ? ($available[$min_size] ?? null) : null;

        // Sizes smaller than the minimum are never used
        if ($min_width !== null) {
            $available = array_filter($available, fn($width) => $width >= $min_width);
        }
        asort($available);

        // A size is skipped when it is the minimum or smaller, was used by a larger breakpoint, or is "full" after
        // another size was used (the original file only goes to the largest screens)
        $is_excluded = function (string $size, int $width) use ($min_width, $min_registered, $already_used): bool {
            if ($min_width !== null && $width <= $min_width) return true;
            if (in_array($size, $already_used, true)) return true;
            if ($min_registered > 0 && $size !== 'full' && po_get_registered_width($size) < $min_registered) return true;
            if ($size === 'full' && !empty($already_used)) return true;
            return false;
        };

        // 1. max_size is a registered size (e.g. "large")
        if ($max_registered > 0) {
            // The maximum itself goes to the largest breakpoint it is wide enough for
            if (isset($available[$max_size]) && !in_array($max_size, $already_used, true)) {
                if (!$is_tablet && $available[$max_size] < $bp_min) return null;

                // When the only size between the minimum and the maximum is the minimum, phones get the minimum
                // and every larger screen the maximum: the switch happens at the tablet breakpoint
                $sizes      = array_keys($available);
                $next_lower = $sizes[array_search($max_size, $sizes, true) - 1] ?? null;
                if (!$is_tablet && $min_width !== null && $next_lower !== null && $available[$next_lower] === $min_width) {
                    return null;
                }

                return $max_size;
            }

            // Otherwise the widest size that fits the breakpoint and isn't wider than the maximum
            $candidates = [];
            foreach ($available as $size => $width) {
                if ($is_excluded($size, $width)) continue;

                $compare_width = po_get_registered_width($size) ?: $width;

                // An original smaller than a registered size counts as that size (the largest one it fits in)
                if ($size === 'full') {
                    foreach ($available as $other => $other_width) {
                        $other_registered = po_get_registered_width($other);
                        if ($other !== 'full' && $other_registered >= $width && $width >= $other_width) {
                            $compare_width = $other_registered;
                        }
                    }
                }

                if (!$is_tablet && $compare_width < $bp_min) continue;
                if ($bp_max !== null && $compare_width > $bp_max) continue;
                if ($compare_width <= $max_registered) $candidates[$size] = $width;
            }

            if (!$candidates) return null;
            arsort($candidates);
            return array_key_first($candidates);
        }

        // 2. No maximum ("full"): the widest size that fits the breakpoint
        $candidates = [];
        foreach ($available as $size => $width) {
            if ($is_excluded($size, $width)) continue;

            $compare_width = po_get_registered_width($size) ?: $width;

            // At the tablet breakpoint, an original that fits in a registered size counts as slightly wider than
            // the largest such size (under the breakpoint's upper limit), so that size is preferred over it
            if ($size === 'full' && $is_tablet) {
                $containing = [];
                foreach ($available as $other => $other_width) {
                    $other_registered = po_get_registered_width($other);
                    if ($other !== 'full' && $other_registered >= $width && $width >= $other_width && $other_registered < $bp_max) {
                        $containing[] = $other_registered;
                    }
                }
                $compare_width = ($containing ? max($containing) : 0) + 1;
            }

            if ($compare_width < $bp_min || (!$is_tablet && $width < $bp_min)) continue;
            if ($bp_max !== null && $compare_width > $bp_max) continue;
            $candidates[$size] = $compare_width;
        }

        if (!$candidates) return null;
        arsort($candidates);
        return array_key_first($candidates);
    }
}

if (!function_exists('img_get_standard_mobile_size')) {
    /**
     * Size phones get in a standard picture: min_size when it was generated, otherwise the first generated size of
     * a list from larger to smaller that isn't wider than max_size (a phone never gets a larger file than desktop).
     */
    function img_get_standard_mobile_size(array $available, string $max_size, string $min_size): string
    {
        if ($min_size !== '' && isset($available[$min_size])) return $min_size;

        $max_width = $max_size === 'full' ? PHP_INT_MAX : ($available[$max_size] ?? po_get_registered_width($max_size));

        foreach (['cover-mobile', 'content', 'featured-small', 'medium', 'thumbnail'] as $size) {
            if (isset($available[$size]) && $available[$size] <= $max_width) return $size;
        }

        return isset($available[$max_size]) ? $max_size : 'full';
    }
}

/* -------------------------------------------------------------------------------------------------------------
 * Choosing the size of each breakpoint: cover pictures
 * ---------------------------------------------------------------------------------------------------------- */

// Sizes cover pictures use: landscape for desktops and laptops, portrait crops for tablets and phones
if (!defined('PO_COVER_SIZES')) {
    define('PO_COVER_SIZES', ['full', 'cover-desktop', 'cover-tablet', 'cover-tablet-portrait', 'cover-mobile-portrait']);
}

if (!function_exists('po_pick_cover_size')) {
    /**
     * Cover size for one breakpoint: the widest file that is wider than the breakpoint's minimum width and not
     * wider than its upper limit. Null when none fits.
     */
    function po_pick_cover_size(string $breakpoint, array $available, array $already_used = []): ?string
    {
        $bp_min = po_get_breakpoint_ranges()[$breakpoint] ?? 0;
        $bp_max = po_get_next_breakpoint_min($breakpoint);

        $candidates = [];
        foreach (PO_COVER_SIZES as $size) {
            if (!isset($available[$size]) || in_array($size, $already_used, true)) continue;
            if ($available[$size] <= $bp_min) continue;
            if ($bp_max !== null && $available[$size] > $bp_max) continue;
            $candidates[$size] = $available[$size];
        }

        if (!$candidates) return null;
        arsort($candidates);
        return array_key_first($candidates);
    }
}

if (!function_exists('po_get_smallest_size_at_least')) {
    /**
     * Smallest generated size at least $min_width wide, or the widest generated size when none is. "full" is the
     * last option, because it can be a very large original.
     */
    function po_get_smallest_size_at_least(array $available, int $min_width): string
    {
        $resized = array_diff_key($available, ['full' => true]);
        if (!$resized) return 'full';

        asort($resized);
        foreach ($resized as $size => $width) {
            if ($width >= $min_width) return $size;
        }

        return array_key_last($resized);
    }
}

if (!function_exists('po_get_cover_mobile_size')) {
    /**
     * Cover size for phones (portrait screens): the portrait crops when they exist. Otherwise the smallest file at
     * least 1000px tall, so it still covers a phone screen, or the tallest file when none is. The original is only
     * chosen when it is the smallest file that tall or under 1000px tall itself, so phones never get a huge original
     * when a smaller generated size would do.
     */
    function po_get_cover_mobile_size(array $fields): string
    {
        $available = po_get_available_sizes($fields['meta']);

        foreach (['cover-mobile-portrait', 'cover-tablet-portrait'] as $size) {
            if (isset($available[$size])) return $size;
        }

        // Generated sizes first, so they win over the original when both have the same height
        $heights = [];
        foreach (array_keys($available) as $size) {
            if ($size !== 'full') $heights[$size] = po_get_size_dimensions($fields, $size)['h'];
        }
        $heights['full'] = (int) $fields['height'];

        asort($heights);
        foreach ($heights as $size => $height) {
            if ($height >= 1000) return $size;
        }

        return array_key_last($heights);
    }
}

/* -------------------------------------------------------------------------------------------------------------
 * Markup
 * ---------------------------------------------------------------------------------------------------------- */

if (!function_exists('img_create_source')) {
    /**
     * One <source>. Lazy pictures use data-srcset; img_wrap_picture() turns it into srcset when needed.
     */
    function img_create_source(string $url, string $mime_type, ?string $media = null): string
    {
        $media_attr = $media ? " media='" . esc_attr($media) . "'" : '';
        return "<source data-srcset='" . esc_url($url) . "' type='" . esc_attr($mime_type) . "'{$media_attr}>";
    }
}

if (!function_exists('img_source_entry')) {
    /**
     * One file of a picture: the breakpoint it starts at and its URL and type.
     */
    function img_source_entry(string $breakpoint, string $url, string $mime): array
    {
        return [
            'url'   => $url,
            'mime'  => $mime,
            'min'   => po_get_breakpoint_ranges()[$breakpoint] ?? 0,
            'media' => po_get_media_query($breakpoint),
        ];
    }
}

if (!function_exists('img_dedupe_entries')) {
    /**
     * Drops the breakpoints (largest screens first) whose file is the same as the next smaller breakpoint's: the
     * smaller one already covers them. Used for both the <source> elements and the preloads, so they always match.
     */
    function img_dedupe_entries(array $entries): array
    {
        $entries = array_values($entries);
        $kept    = [];

        foreach ($entries as $i => $entry) {
            $next = $entries[$i + 1] ?? null;
            if (!$next || $next['url'] !== $entry['url']) $kept[] = $entry;
        }

        return $kept;
    }
}

if (!function_exists('img_render_sources')) {
    /**
     * <source> elements of a list of img_source_entry() entries, largest screens first. Each file gets its WebP
     * copy first when it exists.
     */
    function img_render_sources(array $entries): array
    {
        $sources = [];

        foreach (img_dedupe_entries($entries) as $entry) {
            if (img_is_webp($entry['url'], $entry['mime'])) {
                $sources[] = img_create_source($entry['url'], 'image/webp', $entry['media']);
                continue;
            }
            if (img_has_webp($entry['url'], $entry['mime'])) {
                $sources[] = img_create_source(img_get_webp_url($entry['url'], $entry['mime']), 'image/webp', $entry['media']);
            }
            $sources[] = img_create_source($entry['url'], $entry['mime'], $entry['media']);
        }

        return $sources;
    }
}

if (!function_exists('img_get_attributes_string')) {
    /**
     * Extra <img> attributes. An array (['data-id' => 5]) is escaped; a string is kept for backwards compatibility.
     */
    function img_get_attributes_string(array|string $attributes): string
    {
        if (is_string($attributes)) return $attributes !== '' ? ' ' . wp_kses_post($attributes) : '';

        $html = '';
        foreach ($attributes as $name => $value) {
            $name = preg_replace('/[^a-z0-9_:.-]/i', '', (string) $name);
            if ($name === '' || $value === false || $value === null) continue;
            $html .= $value === true ? " {$name}" : " {$name}='" . esc_attr((string) $value) . "'";
        }

        return $html;
    }
}

if (!function_exists('img_create_img_tag')) {
    /**
     * The <img> of a picture.
     *
     * - Priority images load right away (src, loading="eager"), with the width/height of the mobile file, and get
     *   fetchpriority="high" unless $fetch_priority is false (e.g. a logo, which must not compete with the hero).
     * - Lazy images use data-src and class="lazy-image" (js/picture-optimization.js loads them) and, instead of
     *   width/height, the real size of the file of each breakpoint as --ar-{breakpoint}-w/h custom properties.
     *
     * @param array $bp_ratios ['mobile' => ['w' => 480, 'h' => 320], 'tablet' => [...], ...]
     */
    function img_create_img_tag(
        string       $src,
        array        $bp_ratios      = [],
        string       $alt            = '',
        bool         $is_priority    = false,
        array|string $extra          = '',
        bool         $fetch_priority = true
    ): string {
        $attributes = [];

        if ($is_priority) {
            $mobile       = $bp_ratios['mobile'] ?? [];
            $attributes[] = "src='" . esc_url($src) . "'";
            if (!empty($mobile['w'])) $attributes[] = "width='" . (int) $mobile['w'] . "'";
            if (!empty($mobile['h'])) $attributes[] = "height='" . (int) $mobile['h'] . "'";
        } else {
            $attributes[] = "data-src='" . esc_url($src) . "'";

            $ratios = [];
            foreach ($bp_ratios as $breakpoint => $size) {
                if (!empty($size['w']) && !empty($size['h'])) {
                    $ratios[] = "--ar-{$breakpoint}-w:" . (int) $size['w'];
                    $ratios[] = "--ar-{$breakpoint}-h:" . (int) $size['h'];
                }
            }
            if ($ratios) $attributes[] = "style='" . implode(';', $ratios) . "'";
        }

        $attributes[] = "alt='" . esc_attr($alt) . "'";
        $attributes[] = "loading='" . ($is_priority ? 'eager' : 'lazy') . "'" . ($is_priority && $fetch_priority ? " fetchpriority='high'" : '');
        $attributes[] = "decoding='async'";
        if (!$is_priority) $attributes[] = "class='lazy-image'";

        return '<img ' . implode(' ', $attributes) . img_get_attributes_string($extra) . '>';
    }
}

if (!function_exists('img_wrap_picture')) {
    /**
     * Wraps the <source> elements and the <img> in a <picture>. Priority pictures get real srcset attributes;
     * lazy pictures also get a <noscript> copy with them, for browsers without JavaScript.
     */
    function img_wrap_picture(array $sources, string $img_tag, string $classes = '', string $id = '', bool $is_priority = false): string
    {
        $id_attr    = $id ? " id='" . esc_attr($id) . "'" : '';
        $class_attr = $classes ? " class='" . esc_attr($classes) . "'" : '';
        $loaded     = fn(array $list) => implode('', array_map(fn($source) => str_replace('data-srcset=', 'srcset=', $source), $list));

        if ($is_priority) {
            return "<picture{$id_attr}{$class_attr}>" . $loaded($sources) . $img_tag . '</picture>';
        }

        $noscript_img = str_replace(['data-src=', " class='lazy-image'"], ['src=', ''], $img_tag);

        return "<picture{$id_attr}{$class_attr}>" . implode('', $sources) . $img_tag . '</picture>'
            . "<noscript><picture{$class_attr}>" . $loaded($sources) . $noscript_img . '</picture></noscript>';
    }
}

if (!function_exists('img_finish_picture')) {
    /**
     * Builds the <picture> of a list of img_source_entry() entries. Pictures with fetchpriority="high" also get
     * their files preloaded in the <head> (img_register_preload()).
     */
    function img_finish_picture(array $entries, string $img_tag, string $classes, string $id, bool $is_priority, bool $fetch_priority): string
    {
        if ($is_priority && $fetch_priority) img_register_preload($entries);

        return img_wrap_picture(img_render_sources($entries), $img_tag, $classes, $id, $is_priority);
    }
}

/* -------------------------------------------------------------------------------------------------------------
 * Preloads of high priority images
 *
 * The browser only finds an image when the HTML parser reaches it, after the header and the inline critical CSS
 * (100-150KB). Images with fetchpriority="high" are preloaded at the start of the <head> so their download starts
 * with the first bytes of the page. The <head> is printed before the images, so it gets a placeholder that the
 * page output buffer replaces with the collected preloads.
 *
 * Each file of the picture gets one preload, limited to the screens that use it ("(min-width: 768px) and
 * (max-width: 1023px)"), in WebP when a WebP copy exists, exactly like the <source> elements, so the browser
 * downloads each file once.
 * ---------------------------------------------------------------------------------------------------------- */

if (!defined('PO_PRELOAD_PLACEHOLDER')) {
    define('PO_PRELOAD_PLACEHOLDER', '<!-- growthlab-image-preloads -->');
}

if (!function_exists('img_register_preload')) {
    /**
     * Collects the preload links of a picture's files for img_print_preloads(). Returns the links.
     */
    function img_register_preload(array $entries): array
    {
        $entries = img_dedupe_entries($entries);
        $links   = [];

        foreach ($entries as $i => $entry) {
            // Screens this file is used on: from its breakpoint up to the next larger file's breakpoint
            $larger = $entries[$i - 1] ?? null;
            $media  = array_filter([
                $entry['min'] > 0 ? "(min-width: {$entry['min']}px)" : '',
                $larger ? '(max-width: ' . ($larger['min'] - 1) . 'px)' : '',
            ]);

            $webp = img_is_webp($entry['url'], $entry['mime']) || img_has_webp($entry['url'], $entry['mime']);
            $url  = $webp ? img_get_webp_url($entry['url'], $entry['mime']) : $entry['url'];
            $type = $webp ? 'image/webp' : $entry['mime'];

            $links[] = sprintf(
                '<link rel="preload" as="image" href="%s" type="%s"%s fetchpriority="high">',
                esc_url($url),
                esc_attr($type),
                $media ? ' media="' . esc_attr(implode(' and ', $media)) . '"' : ''
            );
        }

        $GLOBALS['po_image_preloads'] = array_values(array_unique(array_merge($GLOBALS['po_image_preloads'] ?? [], $links)));

        return $links;
    }
}

if (!function_exists('img_print_preloads_placeholder')) {
    /**
     * Placeholder for the preloads, first in the <head> (before the inline critical CSS and the font preloads).
     */
    function img_print_preloads_placeholder(): void
    {
        echo PO_PRELOAD_PLACEHOLDER . "\n";
    }
}
add_action('wp_head', 'img_print_preloads_placeholder', 0);

if (!function_exists('img_start_preloads_buffer')) {
    /**
     * Buffers the front end HTML so the placeholder can be replaced once the whole page is rendered.
     */
    function img_start_preloads_buffer(): void
    {
        if (is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_embed()) return;

        ob_start(function ($html) {
            if (!is_string($html) || !str_contains($html, PO_PRELOAD_PLACEHOLDER)) return $html;

            $links = $GLOBALS['po_image_preloads'] ?? [];
            return preg_replace('/' . preg_quote(PO_PRELOAD_PLACEHOLDER, '/') . '\R?/', $links ? implode("\n", $links) . "\n" : '', $html, 1);
        });
    }
}
add_action('template_redirect', 'img_start_preloads_buffer', PHP_INT_MAX);

/* -------------------------------------------------------------------------------------------------------------
 * Picture builders
 * ---------------------------------------------------------------------------------------------------------- */

if (!function_exists('img_generate_standard_picture')) {
    /**
     * Standard picture: one size per breakpoint between min_size and max_size (po_select_candidate()), an optional
     * dedicated tablet image (768-1023px) and an optional dedicated mobile image.
     */
    function img_generate_standard_picture(
        array        $fields,
        array|string $tablet_img     = [],
        array|string $mobile_img     = [],
        string       $max_size       = 'full',
        string       $min_size       = '',
        string       $classes        = '',
        string       $id             = '',
        string       $alt            = '',
        bool         $is_priority    = false,
        array|string $extra          = '',
        bool         $fetch_priority = true
    ): string {
        $available     = po_get_available_sizes($fields['meta']);
        $tablet_fields = !empty($tablet_img) ? img_get_fields($tablet_img) : null;
        $mobile_fields = !empty($mobile_img) ? img_get_fields($mobile_img) : null;

        $entries      = [];   // One entry per breakpoint, largest first
        $bp_ratios    = [];   // Real size of the file each breakpoint serves
        $already_used = [];

        foreach (po_get_breakpoint_order() as $breakpoint) {
            if ($breakpoint === 'mobile') continue;

            if ($breakpoint === po_get_smallest_media_breakpoint() && $tablet_fields) {
                $url  = $tablet_fields['url'];
                $mime = $tablet_fields['mime_type'];
                $bp_ratios[$breakpoint] = ['w' => $tablet_fields['width'], 'h' => $tablet_fields['height']];
            } else {
                $size = po_select_candidate($breakpoint, $available, $max_size, $min_size, $already_used);
                if ($size === null) continue; // This breakpoint uses the next smaller breakpoint's file

                $already_used[] = $size;
                $url  = po_get_size_url($fields, $size);
                $mime = $fields['mime_type'];
                $bp_ratios[$breakpoint] = po_get_size_dimensions($fields, $size);
            }

            $entries[] = img_source_entry($breakpoint, $url, $mime);
        }

        // Phones: the dedicated mobile image, or a small size of the main image
        if ($mobile_fields) {
            $mobile_url  = $mobile_fields['url'];
            $mobile_mime = $mobile_fields['mime_type'];
            $bp_ratios['mobile'] = ['w' => $mobile_fields['width'], 'h' => $mobile_fields['height']];
        } else {
            $mobile_size = img_get_standard_mobile_size($available, $max_size, $min_size);
            $mobile_url  = po_get_size_url($fields, $mobile_size);
            $mobile_mime = $fields['mime_type'];
            $bp_ratios['mobile'] = po_get_size_dimensions($fields, $mobile_size);
        }
        $entries[] = img_source_entry('mobile', $mobile_url, $mobile_mime);

        $img_tag = img_create_img_tag(
            src: $mobile_url,
            bp_ratios: $bp_ratios,
            alt: $alt ?: $fields['alt'],
            is_priority: $is_priority,
            extra: $extra,
            fetch_priority: $fetch_priority
        );

        return img_finish_picture($entries, $img_tag, $classes, $id, $is_priority, $fetch_priority);
    }
}

if (!function_exists('img_generate_cover_picture')) {
    /**
     * Cover picture (full-screen backgrounds):
     * - Tablet screens (768-1023px) get the dedicated tablet image when there is one; every larger screen always
     *   gets the main image.
     * - Each breakpoint gets the widest cover size that fits it (po_pick_cover_size()). When none fits the tablet
     *   range, it keeps the previous size of the main image, or gets the smallest file of the tablet image that
     *   covers 768px.
     * - Phones get the mobile image, else the tablet image, else the main image, in a portrait size
     *   (po_get_cover_mobile_size()).
     */
    function img_generate_cover_picture(
        array        $img_fields,
        array|string $tablet_img     = [],
        array|string $mobile_img     = [],
        string       $classes        = '',
        string       $id             = '',
        string       $alt            = '',
        bool         $is_priority    = false,
        array|string $extra          = '',
        bool         $fetch_priority = true
    ): string {
        $tablet_fields = !empty($tablet_img) ? img_get_fields($tablet_img) : null;
        $mobile_fields = !empty($mobile_img) ? img_get_fields($mobile_img) : null;
        $tablet_bp     = po_get_smallest_media_breakpoint();

        $main_available = po_get_available_sizes($img_fields['meta']);
        $main_used      = [];
        $entries        = [];
        $bp_ratios      = [];

        foreach (po_get_breakpoint_order() as $breakpoint) {
            if ($breakpoint === 'mobile') continue;

            $fields = $img_fields;
            $size   = null;

            if ($breakpoint === $tablet_bp && $tablet_fields) {
                // Dedicated tablet image, only for the tablet range
                $fields    = $tablet_fields;
                $available = po_get_available_sizes($tablet_fields['meta']);
                $size      = po_pick_cover_size($breakpoint, $available)
                    ?? po_get_smallest_size_at_least($available, po_get_breakpoint_ranges()[$breakpoint]);
            } else {
                $size = po_pick_cover_size($breakpoint, $main_available, $main_used);

                // The tablet range is never left without a file: it keeps the last size of the main image
                if ($size === null && $breakpoint === $tablet_bp && $main_used) {
                    $size = end($main_used);
                }
                if ($size === null) continue; // This breakpoint uses the next smaller breakpoint's file

                $main_used[] = $size;
            }

            $entries[] = img_source_entry($breakpoint, po_get_size_url($fields, $size), $fields['mime_type']);
            $bp_ratios[$breakpoint] = po_get_size_dimensions($fields, $size);
        }

        // Phones: mobile image, else tablet image, else the main image
        $mobile_source = $mobile_fields ?? $tablet_fields ?? $img_fields;
        $mobile_size   = po_get_cover_mobile_size($mobile_source);
        $mobile_url    = po_get_size_url($mobile_source, $mobile_size);

        $entries[]           = img_source_entry('mobile', $mobile_url, $mobile_source['mime_type']);
        $bp_ratios['mobile'] = po_get_size_dimensions($mobile_source, $mobile_size);

        $img_tag = img_create_img_tag(
            src: $mobile_url,
            bp_ratios: $bp_ratios,
            alt: $alt ?: $img_fields['alt'],
            is_priority: $is_priority,
            extra: $extra,
            fetch_priority: $fetch_priority
        );

        return img_finish_picture($entries, $img_tag, $classes, $id, $is_priority, $fetch_priority);
    }
}

if (!function_exists('img_generate_single_picture')) {
    /**
     * Picture with a single file for every screen: thumbnails (max_size "thumbnail") and images that aren't in
     * the media library (external URLs, theme files), which have no generated sizes.
     */
    function img_generate_single_picture(
        array        $fields,
        string       $size           = 'full',
        string       $classes        = '',
        string       $id             = '',
        string       $alt            = '',
        bool         $is_priority    = false,
        array|string $extra          = '',
        bool         $fetch_priority = true
    ): string {
        $url        = po_get_size_url($fields, $size);
        $dimensions = $size === 'full' ? ['w' => $fields['width'], 'h' => $fields['height']] : [
            'w' => (int) ($fields['meta']['sizes'][$size]['width'] ?? 0),
            'h' => (int) ($fields['meta']['sizes'][$size]['height'] ?? 0),
        ];

        $img_tag = img_create_img_tag(
            src: $url,
            bp_ratios: ['mobile' => $dimensions],
            alt: $alt ?: $fields['alt'],
            is_priority: $is_priority,
            extra: $extra,
            fetch_priority: $fetch_priority
        );

        $entries = [img_source_entry('mobile', $url, $fields['mime_type'])];
        return img_finish_picture($entries, $img_tag, $classes, $id, $is_priority, $fetch_priority);
    }
}

/* -------------------------------------------------------------------------------------------------------------
 * Public API
 * ---------------------------------------------------------------------------------------------------------- */

if (!function_exists('img_generate_picture_tag')) {
    /**
     * Returns a responsive <picture> for an image ('' when there is no image). SVGs are printed inline
     * (image_to_svg()).
     *
     * @param array|string $img            ACF image array or image URL.
     * @param array|string $mobile_img     Optional image for phones (under 768px).
     * @param array|string $tablet_img     Optional image for tablets (768-1023px).
     * @param string       $max_size       Largest size any screen gets ("full": no limit; "thumbnail": always the thumbnail).
     * @param string       $min_size       Size phones get, and the smallest any screen gets ('': automatic).
     * @param string       $classes        Classes of the <picture>.
     * @param string       $id             id of the <picture>.
     * @param string       $alt_text       Alt text; the media library alt text when empty.
     * @param bool         $is_cover       Full-screen background (hero): uses the cover-* sizes.
     * @param array|string $img_attr       Extra <img> attributes, preferably as an array.
     * @param bool         $is_priority    Load right away (images visible on load, e.g. the hero).
     * @param bool|null    $fetch_priority fetchpriority="high" for priority images; null follows $is_priority.
     *                                     Pass false for priority images that aren't the LCP element (logo...).
     */
    function img_generate_picture_tag(
        array|string $img,
        array|string $mobile_img     = [],
        array|string $tablet_img     = [],
        string       $max_size       = 'full',
        string       $min_size       = '',
        string       $classes        = '',
        string       $id             = '',
        string       $alt_text       = '',
        bool         $is_cover       = false,
        array|string $img_attr       = '',
        bool         $is_priority    = false,
        ?bool        $fetch_priority = null
    ): string {
        if (empty($img)) return '';
        if (empty($GLOBALS['sizes'])) po_init_sizes();

        // Unknown size names are ignored
        if ($max_size !== 'full' && !in_array($max_size, $GLOBALS['sizes'], true)) $max_size = 'full';
        if ($min_size !== '' && !in_array($min_size, $GLOBALS['sizes'], true)) $min_size = '';

        $fields = img_get_fields($img);
        if (empty($fields['url'])) return '';

        if (in_array($fields['mime_type'], ['image/svg+xml', 'image/svg'], true)) {
            return function_exists('image_to_svg') ? image_to_svg($img, $classes) : '';
        }

        $common = [
            'classes'        => $classes,
            'id'             => $id,
            'alt'            => $alt_text,
            'is_priority'    => $is_priority,
            'extra'          => $img_attr,
            'fetch_priority' => $fetch_priority ?? $is_priority,
        ];

        // Not in the media library: no generated sizes to choose from
        if (!$fields['id'] || empty($fields['meta'])) {
            return img_generate_single_picture(...['fields' => $fields] + $common);
        }

        if ($is_cover) {
            return img_generate_cover_picture(...['img_fields' => $fields, 'tablet_img' => $tablet_img, 'mobile_img' => $mobile_img] + $common);
        }

        if ($max_size === 'thumbnail') {
            return img_generate_single_picture(...['fields' => $fields, 'size' => 'thumbnail'] + $common);
        }

        return img_generate_standard_picture(...[
            'fields'     => $fields,
            'tablet_img' => $tablet_img,
            'mobile_img' => $mobile_img,
            'max_size'   => $max_size,
            'min_size'   => $min_size,
        ] + $common);
    }
}

if (!function_exists('img_print_picture_tag')) {
    /**
     * Prints img_generate_picture_tag(); same arguments.
     */
    function img_print_picture_tag(
        array|string $img,
        array|string $mobile_img     = [],
        array|string $tablet_img     = [],
        string       $max_size       = 'full',
        string       $min_size       = '',
        string       $classes        = '',
        string       $id             = '',
        string       $alt_text       = '',
        bool         $is_cover       = false,
        array|string $img_attr       = '',
        bool         $is_priority    = false,
        ?bool        $fetch_priority = null
    ): void {
        echo img_generate_picture_tag(
            img: $img,
            mobile_img: $mobile_img,
            tablet_img: $tablet_img,
            max_size: $max_size,
            min_size: $min_size,
            classes: $classes,
            id: $id,
            alt_text: $alt_text,
            is_cover: $is_cover,
            img_attr: $img_attr,
            is_priority: $is_priority,
            fetch_priority: $fetch_priority
        );
    }
}
