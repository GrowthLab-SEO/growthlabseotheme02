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
// Disable Dashicons on front-end. The admin bar (shown to any logged-in user) needs them, and plugin styles that
// depend on them would be dropped if the style were deregistered, so only dequeue a direct enqueue.
function wpdocs_dequeue_dashicon()
{
    if (is_admin_bar_showing()) {
        return;
    }
    wp_dequeue_style('dashicons');
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

/**
 * Delayed loading of jQuery and Gravity Forms.
 *
 * jQuery is only needed by Gravity Forms, and the footer form is on every page, so both would load on every page view.
 * Their scripts (inline parts and translations included) are printed as inert text/plain tags, and a small loader
 * runs them in their original order when a form gets close to the viewport or on the first user interaction.
 * Gravity Forms initialises itself through gform.initializeOnLoaded(), which waits for its own scripts, and the forms
 * submit without JavaScript too.
 */
if (!function_exists('growthlab_is_delayed_script')) {
    function growthlab_is_delayed_script($handle)
    {
        static $delayed = null;

        if ($delayed === null) {
            $registered = wp_scripts()->registered;
            $delayed = array();

            // jQuery, Gravity Forms and the phone mask
            foreach (array_keys($registered) as $name) {
                if (in_array($name, array('jquery', 'jquery-core', 'jquery-migrate', 'imask'), true) || str_starts_with($name, 'gform_')) {
                    $delayed[$name] = true;
                }
            }

            // Their dependencies (e.g. wp-a11y) are delayed with them, and so is every script that depends on a delayed
            // one, so nothing runs before a script it needs
            do {
                $changed = false;
                foreach ($registered as $name => $script) {
                    $deps = (array) $script->deps;
                    if (isset($delayed[$name])) {
                        foreach ($deps as $dep) {
                            if (!isset($delayed[$dep]) && isset($registered[$dep])) {
                                $delayed[$dep] = $changed = true;
                            }
                        }
                    } elseif (array_intersect($deps, array_keys($delayed))) {
                        $delayed[$name] = $changed = true;
                    }
                }
            } while ($changed);
        }

        return isset($delayed[$handle]);
    }
}

add_filter('script_loader_tag', function ($tag, $handle) {
    if (is_admin() || is_customize_preview() || !growthlab_is_delayed_script($handle)) return $tag;

    $processor = new WP_HTML_Tag_Processor($tag);
    while ($processor->next_tag('script')) {
        $type = $processor->get_attribute('type');
        // Leave data blocks (e.g. application/json) alone
        if (is_string($type) && !in_array(strtolower($type), array('text/javascript', 'module'), true)) continue;

        $src = $processor->get_attribute('src');
        if (is_string($src)) {
            $processor->set_attribute('data-delayed-src', $src);
            $processor->remove_attribute('src');
        }
        if (is_string($type)) $processor->set_attribute('data-delayed-type', $type);
        $processor->set_attribute('type', 'text/plain');
        $processor->set_attribute('data-delayed-script', true);
    }

    $GLOBALS['growthlab_has_delayed_scripts'] = true;

    return $processor->get_updated_html();
}, 999, 2);

// Loader for the delayed scripts, printed after the footer scripts
add_action('wp_footer', function () {
    if (empty($GLOBALS['growthlab_has_delayed_scripts'])) return;

    $loader = <<<'JS'
(function () {
  var started = false;
  var events = ["pointerdown", "keydown", "touchstart", "focusin"];
  var observer = null;

  function run(scripts, index) {
    if (index >= scripts.length) return;
    var placeholder = scripts[index];
    var script = document.createElement("script");

    Array.prototype.forEach.call(placeholder.attributes, function (attribute) {
      if (["type", "data-delayed-src", "data-delayed-type", "data-delayed-script"].indexOf(attribute.name) === -1) {
        script.setAttribute(attribute.name, attribute.value);
      }
    });
    if (placeholder.dataset.delayedType) script.type = placeholder.dataset.delayedType;

    if (placeholder.dataset.delayedSrc) {
      // External scripts run one after another, in the order they were printed
      script.async = false;
      script.onload = script.onerror = function () { run(scripts, index + 1); };
      script.src = placeholder.dataset.delayedSrc;
      placeholder.replaceWith(script);
    } else {
      script.text = placeholder.textContent;
      placeholder.replaceWith(script);
      run(scripts, index + 1);
    }
  }

  function start() {
    if (started) return;
    started = true;
    events.forEach(function (name) { window.removeEventListener(name, start, true); });
    if (observer) observer.disconnect();

    var scripts = Array.prototype.slice.call(document.querySelectorAll("script[data-delayed-script]"));
    // Download every file in parallel right away; they still run in order
    scripts.forEach(function (placeholder) {
      if (!placeholder.dataset.delayedSrc) return;
      var preload = document.createElement("link");
      preload.rel = "preload";
      preload.as = "script";
      preload.href = placeholder.dataset.delayedSrc;
      document.head.appendChild(preload);
    });
    run(scripts, 0);
  }

  events.forEach(function (name) { window.addEventListener(name, start, { capture: true, passive: true }); });

  var forms = document.querySelectorAll(".gform_wrapper, form[id^='gform_']");
  if (!("IntersectionObserver" in window)) {
    if (forms.length) start();
    return;
  }
  observer = new IntersectionObserver(function (entries) {
    if (entries.some(function (entry) { return entry.isIntersecting; })) start();
  }, { rootMargin: "600px 0px" });
  Array.prototype.forEach.call(forms, function (form) { observer.observe(form); });
})();
JS;

    wp_print_inline_script_tag($loader, array('id' => 'growthlab-delayed-scripts'));
}, 1000);
