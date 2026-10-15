<?php
// Enable SVG uploads
function growthlab_allow_svg_uploads($file_types)
{
    $file_types['svg'] = 'image/svg+xml';
    return $file_types;
}
add_filter('upload_mimes', 'growthlab_allow_svg_uploads');

// Only files named *.svg are accepted as SVG, so SVG content can't be stored under another extension (e.g. .html)
function growthlab_allow_svg_filetype($data, $file, $filename, $mimes, $real_mime)
{
    if (preg_match('/\.svg$/i', $filename)) {
        $override = array(
            'ext' => 'svg',
            'type' => 'image/svg+xml',
            'proper_filename' => $filename,
            0 => 'svg',
            1 => 'image/svg+xml',
            2 => false,
            3 => 'svg',
        );

        $data = array_merge((array) $data, $override);
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'growthlab_allow_svg_filetype', 10, 5);

function growthlab_allow_svg_image_mime($mime, $file)
{
    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'svg') {
        return 'image/svg+xml';
    }

    return $mime;
}
add_filter('wp_get_image_mime', 'growthlab_allow_svg_image_mime', 10, 2);

function growthlab_allow_svg_as_displayable_image($result, $path)
{
    if ($result === false && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
        return true;
    }

    return $result;
}
add_filter('file_is_displayable_image', 'growthlab_allow_svg_as_displayable_image', 10, 2);

function growthlab_get_svg_dimensions($file)
{
    if (empty($file) || !file_exists($file) || !is_readable($file)) {
        return false;
    }

    $svg_content = file_get_contents($file);
    if ($svg_content === false) {
        return false;
    }

    // Read attributes from the root <svg> tag only, so child attributes like stroke-width are ignored
    if (!preg_match('/<svg\b[^>]*>/i', $svg_content, $svg_tag)) {
        return false;
    }
    $svg_tag = $svg_tag[0];

    $width = 0;
    $height = 0;

    if (preg_match('/[\s"\']width\s*=\s*["\']\s*([0-9.]+)(px)?\s*["\']/i', $svg_tag, $match)) {
        $width = floatval($match[1]);
    }
    if (preg_match('/[\s"\']height\s*=\s*["\']\s*([0-9.]+)(px)?\s*["\']/i', $svg_tag, $match)) {
        $height = floatval($match[1]);
    }

    if (($width <= 0 || $height <= 0) && preg_match('/viewBox\s*=\s*["\']([^"\']+)["\']/i', $svg_tag, $match)) {
        $parts = preg_split('/[\s,]+/', trim($match[1]));
        if (count($parts) === 4) {
            $width = floatval($parts[2]);
            $height = floatval($parts[3]);
        }
    }

    if ($width <= 0 || $height <= 0) {
        return false;
    }

    return array($width, $height);
}

function growthlab_prepare_svg_attachment_for_js($response, $attachment, $meta)
{
    if (isset($response['mime']) && $response['mime'] === 'image/svg+xml') {
        $response['type'] = 'image';
        $response['subtype'] = 'svg+xml';
        if (empty($response['url']) && !empty($response['id'])) {
            $response['url'] = wp_get_attachment_url($response['id']);
        }

        $dimensions = false;
        if (!empty($response['id'])) {
            $file = get_attached_file($response['id']);
            $dimensions = growthlab_get_svg_dimensions($file);
        }

        if ($dimensions) {
            $response['width'] = $dimensions[0];
            $response['height'] = $dimensions[1];
            $response['sizes'] = [
                'full' => [
                    'url' => $response['url'],
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                    'orientation' => $dimensions[0] >= $dimensions[1] ? 'landscape' : 'portrait',
                ],
            ];
        } else {
            $response['sizes'] = [];
        }
    }

    return $response;
}
add_filter('wp_prepare_attachment_for_js', 'growthlab_prepare_svg_attachment_for_js', 10, 3);

function growthlab_svg_image_downsize($out, $id, $size)
{
    $mime = get_post_mime_type($id);
    if ($mime !== 'image/svg+xml') {
        return $out;
    }

    $url = wp_get_attachment_url($id);
    if (!$url) {
        return false;
    }

    // SVGs without readable dimensions are still returned (0x0), otherwise WordPress drops the image entirely
    $dimensions = growthlab_get_svg_dimensions(get_attached_file($id)) ?: array(0, 0);

    return array($url, $dimensions[0], $dimensions[1], false);
}
add_filter('image_downsize', 'growthlab_svg_image_downsize', 10, 3);

// Sanitize SVG markup: keeps drawing elements, classes, gradients and filters, but strips
// scripts, event handlers, external references and unsafe CSS.
// Returns the cleaned markup, or false if the file is not a valid SVG or cannot be made safe.
function growthlab_sanitize_svg_markup(string $markup): string|false
{
    // Reject empty and compressed (svgz) files
    if (trim($markup) === '' || str_starts_with($markup, "\x1f\x8b")) {
        return false;
    }

    // Entity declarations enable XXE and "billion laughs" attacks
    if (stripos($markup, '<!ENTITY') !== false) {
        return false;
    }

    $allowed_tags = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc', 'style', 'switch', 'view',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'image',
        'text', 'tspan', 'textpath',
        'lineargradient', 'radialgradient', 'stop', 'pattern', 'clippath', 'mask', 'marker',
        'filter', 'feblend', 'fecolormatrix', 'fecomponenttransfer', 'fecomposite', 'feconvolvematrix',
        'fediffuselighting', 'fedisplacementmap', 'fedistantlight', 'fedropshadow', 'feflood',
        'fefunca', 'fefuncb', 'fefuncg', 'fefuncr', 'fegaussianblur', 'femerge', 'femergenode',
        'femorphology', 'feoffset', 'fepointlight', 'fespecularlighting', 'fespotlight', 'fetile',
        'feturbulence',
        'animate', 'animatetransform', 'animatemotion', 'mpath',
    ];

    $doc = new DOMDocument();
    $use_errors = libxml_use_internal_errors(true);
    // LIBXML_NONET blocks network access; entities are never substituted (no LIBXML_NOENT)
    $loaded = $doc->loadXML($markup, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($use_errors);

    if (!$loaded || !$doc->documentElement || strtolower($doc->documentElement->localName) !== 'svg') {
        return false;
    }

    if ($doc->doctype) {
        $doc->removeChild($doc->doctype);
    }

    $xpath = new DOMXPath($doc);

    // Remove comments and processing instructions (e.g. xml-stylesheet)
    foreach (iterator_to_array($xpath->query('//comment() | //processing-instruction()')) as $node) {
        $node->parentNode->removeChild($node);
    }

    foreach (iterator_to_array($xpath->query('//*')) as $element) {
        // Skip nodes already detached together with a removed ancestor
        if (!$element->parentNode) {
            continue;
        }

        $tag = strtolower($element->localName);

        if (!in_array($tag, $allowed_tags, true)) {
            $element->parentNode->removeChild($element);
            continue;
        }

        // Animations must not rewrite links or event handlers
        if (str_starts_with($tag, 'animate')) {
            $target = strtolower(trim($element->getAttribute('attributeName')));
            if (str_ends_with($target, 'href') || str_starts_with($target, 'on')) {
                $element->parentNode->removeChild($element);
                continue;
            }
        }

        if ($tag === 'style' && !growthlab_is_safe_svg_css($element->textContent)) {
            $element->parentNode->removeChild($element);
            continue;
        }

        foreach (iterator_to_array($element->attributes) as $attr) {
            $name = strtolower($attr->localName);
            $value = strtolower(preg_replace('/[\s\x00-\x1f]+/', '', html_entity_decode($attr->value, ENT_QUOTES | ENT_HTML5)));

            $unsafe = str_starts_with($name, 'on')
                || ($name === 'href' && !growthlab_is_safe_svg_href($tag, $value))
                || ($name === 'style' && !growthlab_is_safe_svg_css($attr->value))
                || preg_match('/(java|vb)script:|data:text\/html|expression\(/', $value);

            if ($unsafe) {
                $element->removeAttributeNode($attr);
            }
        }
    }

    return $doc->saveXML($doc->documentElement);
}

// Only internal references (#id) are allowed, plus embedded raster images inside <image>
function growthlab_is_safe_svg_href(string $tag, string $href): bool
{
    if (str_starts_with($href, '#')) {
        return true;
    }

    return in_array($tag, ['image', 'feimage'], true)
        && (bool) preg_match('/^data:image\/(png|jpe?g|gif|webp);/', $href);
}

// Blocks CSS that can load external resources or execute code
function growthlab_is_safe_svg_css(string $css): bool
{
    $normalized = strtolower(preg_replace('/[\s\x00-\x1f]+/', '', $css));

    if (preg_match('/@import|expression\(|javascript:|vbscript:|behavior:|-moz-binding/', $normalized)) {
        return false;
    }

    preg_match_all('/url\(([\'"]?)(.*?)\)/', $normalized, $urls);
    foreach ($urls[2] as $url) {
        if (!str_starts_with($url, '#') && !preg_match('/^data:image\/(png|jpe?g|gif|webp);/', $url)) {
            return false;
        }
    }

    return true;
}

// Sanitize SVG files before WordPress stores them; unsafe or invalid files are rejected
function growthlab_sanitize_svg_upload($file)
{
    if (!empty($file['error']) || empty($file['tmp_name'])) {
        return $file;
    }

    // Detect SVGs by extension and by content, so a renamed SVG is also sanitized
    $is_svg = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'svg'
        || (function_exists('mime_content_type') && mime_content_type($file['tmp_name']) === 'image/svg+xml');

    if (!$is_svg) {
        return $file;
    }

    $markup = file_get_contents($file['tmp_name']);
    $clean = $markup === false ? false : growthlab_sanitize_svg_markup($markup);

    if ($clean === false) {
        $file['error'] = __('This SVG file is invalid or unsafe and could not be uploaded.', 'growthlabseotheme02');
        return $file;
    }

    file_put_contents($file['tmp_name'], $clean);

    return $file;
}
add_filter('wp_handle_upload_prefilter', 'growthlab_sanitize_svg_upload');
add_filter('wp_handle_sideload_prefilter', 'growthlab_sanitize_svg_upload');

// Image to SVG
function image_to_svg(array|string $image, string $classes = '')
{
    if (is_array($image) && (empty($image) || !isset($image['url'], $image['mime_type']))) {
        return '';
    }

    if (is_string($image) && $image === '') {
        return '';
    }

    if (is_array($image)) {
        $img_url = $image['url'];
    } else {
        $img_url = $image;
    }

    try {
        $upload_dir = wp_get_upload_dir();

        // Remove query string/URL fragments
        $img_url = preg_replace('/\?.*$/', '', $img_url);

        // Try mapping from uploads baseurl to basedir
        $baseurl = untrailingslashit($upload_dir['baseurl']);
        $basedir = untrailingslashit($upload_dir['basedir']);

        if (strpos($img_url, $baseurl) !== false) {
            $image_path = str_replace($baseurl, $basedir, $img_url);
        } elseif (strpos($img_url, home_url('/')) !== false) {
            // Fallback: map site URLs to ABSPATH
            $image_path = str_replace(home_url('/'), ABSPATH, $img_url);
        } else {
            // If not a site URL, use the path as-is (may already be local)
            $image_path = $img_url;
        }

        // Normalize path for Windows and Unix
        $image_path = wp_normalize_path($image_path);

        // Detailed debug log
        error_log(sprintf('image_to_svg: url="%s" baseurl="%s" basedir="%s" path="%s"', $img_url, $baseurl, $basedir, $image_path));

        if (!file_exists($image_path) || !is_readable($image_path)) {
            error_log('SVG Image path is missing or not readable: ' . $image_path);
            return '';
        }

        if (mime_content_type($image_path) === "image/svg+xml") {
            $svg_content = @file_get_contents($image_path);
            if ($svg_content === false) {
                error_log('Could not read SVG file: ' . $image_path);
                return '';
            }

            return "<div class='$classes'>$svg_content</div>";
        }

        // Build img tag with escaped attributes
        return sprintf(
            '<img src="%s" width="%s" height="%s" alt="%s" title="%s" loading="lazy" decoding="async">',
            esc_url($image['url']),
            esc_attr($image['width'] ?? ''),
            esc_attr($image['height'] ?? ''),
            esc_attr($image['alt'] ?? ''),
            esc_attr($image['title'] ?? ''),
        );
    } catch (Exception $e) {
        error_log('SVG Processing Error: ' . $e->getMessage());
        return '';
    }
}

// SVG in content - OPTIMIZED
function check_content_images($content)
{
    // Quick early return for content without images
    if (strpos($content, '<img') === false) {
        return $content;
    }

    $pattern = '/<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>/i';
    $matches = [];

    // Count matches to avoid processing too many
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
        return $content;
    }

    // Limit to first 20 images to prevent excessive processing
    if (count($matches[0]) > 20) {
        return $content;
    }

    return preg_replace_callback($pattern, function ($match) {
        $src = $match[1];

        // Quick check: skip non-SVG URLs
        if (strpos($src, '.svg') === false) {
            return $match[0];
        }

        // Convert URL to local path: remove query and map uploads
        $upload_dir = wp_get_upload_dir();
        $src_trim = preg_replace('/\?.*$/', '', $src);

        if (strpos($src_trim, untrailingslashit($upload_dir['baseurl'])) !== false) {
            $src_local = str_replace(untrailingslashit($upload_dir['baseurl']), untrailingslashit($upload_dir['basedir']), $src_trim);
        } elseif (strpos($src_trim, home_url('/')) !== false) {
            $src_local = str_replace(home_url('/'), ABSPATH, $src_trim);
        } else {
            return $match[0]; // Skip external URLs
        }

        $src_local = wp_normalize_path($src_local);

        try {
            if (!file_exists($src_local) || !is_readable($src_local)) {
                return $match[0];
            }

            // Use extension check instead of mime_content_type (faster)
            if (pathinfo($src_local, PATHINFO_EXTENSION) !== 'svg') {
                return $match[0];
            }

            $svg_content = file_get_contents($src_local);
            return $svg_content !== false ? $svg_content : $match[0];
        } catch (Exception $e) {
            return $match[0];
        }
    }, $content);
}

add_filter('the_content', 'check_content_images');
