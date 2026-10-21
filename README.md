# GrowthLab Theme 02

Base WordPress theme used by GrowthLab SEO for client sites. It is a classic PHP theme (template files plus `theme.json`) whose page content is built with ACF PRO blocks, with site-wide settings in an ACF options page and colors/fonts in the Customizer.

- Text domain / slug: `growthlabseotheme02`
- Version: 1.0.0 (see `style.css`)
- Repository: https://github.com/GrowthLab-SEO/growthlabseotheme02
- License: GPL-3.0-or-later

## Contents

1. [Overview](#overview)
2. [Requirements](#requirements)
3. [Installation and child themes](#installation-and-child-themes)
4. [Development workflow](#development-workflow)
5. [Project structure](#project-structure)
6. [Blocks](#blocks)
7. [Theme helpers reference](#theme-helpers-reference)
8. [Customizer: colors and fonts](#customizer-colors-and-fonts)
9. [Multilingual](#multilingual)
10. [Media and performance](#media-and-performance)
11. [Security notes](#security-notes)
12. [Troubleshooting](#troubleshooting)
13. [License](#license)

---

## Overview

- **Classic theme + `theme.json`.** Templates are PHP (`header.php`, `footer.php`, `page.php`, `single.php`, `home.php`, `archive.php`, `search.php`, `single-team.php`, `404.php`, `index.php`). `theme.json` sets the layout sizes (content 780px, wide 1240px) and typography options; the color palette and font families are injected at runtime from the Customizer through the `wp_theme_json_data_theme` filter in `functions.php`. The theme declares `appearance-tools` (border, color, spacing and typography controls on core blocks).
- **ACF PRO blocks.** Every folder in `blocks/` with a `block.json` is registered automatically (see [Blocks](#blocks)). Blocks appear in the "GrowthLab Theme 02 Blocks" inserter category.
- **ACF data.** Field groups, the custom post types (`case-result`, `team`, `testimonial`) and the "Site Options" options page fields are stored as JSON in `acf-json/`. The Site Options page is registered on `acf/init`.
- **Page-level fields come from the queried page.** `header.php`, `footer.php` and the templates read the "Page - Options" fields (custom header, hero, footer toggles, Hide Sidebar) with `get_page_field()`: the queried post on singular views and the posts page on the blog index. Archives, search results and 404 pages have no page options, so the first post of the loop never leaks its settings into the page.
- **Heroes.** `header.php` renders the hero chosen in the page's `hero_style` field (`default`, `home`, `home_2` ... `home_6`, `nohero`), using the templates in `template-parts/hero-*.php`. The blog index, archives and search results always use the default hero, and 404 pages have none. A custom `hero_title` is a WYSIWYG field printed as written (through `wp_kses_post()`): editors choose its tag there, so it isn't always the page `h1` (SEO or styling reasons). Without a custom title the page, term, archive, author, date or search title is printed as the `h1` (`get_page_hero_title()`). Taglines/subtitles use their own tag fields and never fall back to `h1`.
- **Listings.** `home.php` (blog, with the posts filters), `archive.php` and `search.php` (archive listing plus a "No results found" message) share the same cards and pagination; `index.php` (last fallback) renders the archive listing. Pages and single posts hide their sidebar with their own **Hide Sidebar** field; the blog index, archives and search results follow the posts page's setting (with the sidebar shown, cards use the horizontal layout).
- **Accessibility basics.** `header.php` prints a translatable "Skip to content" link as the first focusable element (visible on focus), and every template has a single `<main id="main-content">` (404 included). The mobile menu toggles are real `<button>`s (`aria-expanded`/`aria-controls`, Escape closes the menu). FAQ questions and location card cities are exposed as buttons (`role="button"`, `aria-expanded`, `aria-controls`) and respond to Enter and Space; the footer office selector uses buttons with `aria-pressed`.

### Theme 02 identity

| | Default | Source |
| --- | --- | --- |
| Primary font | Mona Sans | `theme-functions/fonts-selector.php` (`$default_fonts`) |
| Secondary font | Roboto Serif | `theme-functions/fonts-selector.php` |
| Primary (default / dark / light) | `#15253f` / `#08182f` / `#2C3D5B` | `theme-functions/color-scheme.php` (`$default_colors`) |
| Secondary | `#F4F3EE` / `#E7E5DF` / `#FFFFFF` | `theme-functions/color-scheme.php` |
| Tertiary | `#BC9061` / `#9D7A55` / `#DCAB77` | `theme-functions/color-scheme.php` |
| Text | `#15253f` | `theme-functions/color-scheme.php` |

Responsive image breakpoints for this theme are set in `functions.php` (`$GLOBALS['breakpoints']`): mobile 0, tablet 768px, ldpi 1024px, mdpi 1280px, hdpi 1920px.

---

## Requirements

From the `style.css` header:

- **PHP 8.3+**
- **WordPress 6.9+** (tested up to 7.1)

Plugins:

| Plugin | Status | Why |
| --- | --- | --- |
| Advanced Custom Fields PRO | Required | Blocks (`acf` key in `block.json`), options page (`acf_add_options_page`), repeaters/clone fields, CPT definitions in `acf-json/`. |
| Gravity Forms | Required for forms | Contact Form block and the footer form section call `render_gravity_form()`. Without the plugin the form is silently skipped. The theme disables Gravity Forms CSS and loads its init scripts in the footer. |
| ACF field for selecting Gravity Forms forms | Required for forms | The `contact_form` fields in `acf-json/` use the field type `forms`, which is not part of core ACF. Use the add-on the team installs for this on client sites. |
| LuckyWP ACF Menu Field | Required | Field type `menu` (Content Box Grid "menu" mode, footer links menu, the page's custom header menu and the Page - Sidebar menus printed by `sidebar.php` / `sidebar-blog.php`). |
| An image optimizer that creates `.webp` siblings (for example EWWW Image Optimizer) | Recommended | The picture helpers add a WebP `<source>` only when a file named `image.jpg.webp` exists next to the original. |

Building assets needs **Node.js and npm** (`package.json` declares no `engines` field; use a current LTS).

---

## Installation and child themes

1. Put the theme in `wp-content/themes/growthlabseotheme02/` (clone the repository or deploy it with WP Pusher).
2. Install and activate the plugins listed above.
3. Activate **GrowthLab Theme 02** (or a child theme of it) under Appearance > Themes.
4. Go to ACF > Field Groups and sync any group marked "Sync available" (see [ACF JSON](#acf-json-load-and-save-points)), then fill in **Site Options** (admin menu, administrators only).

Compiled CSS/JS is committed, so a fresh install works without running the build.

### ACF JSON load and save points

Defined in `theme-functions/acf-functions.php`:

- **Load:** field groups are read from the parent theme's `acf-json/` and, when a child theme is active, also from the child theme's `acf-json/`.
- **Save:** edits made in the ACF admin are written to the **active** theme's `acf-json/` (`get_stylesheet_directory()`), so with a child theme active they go to the child. The folder is created automatically on the first save.

### WP Pusher deploy sync

`theme-functions/acf-functions.php` hooks a sync routine to `wppusher_theme_was_updated` and `wppusher_theme_was_installed`. After WP Pusher deploys the theme it imports the parent theme's JSON into the database, so changes made in the repository go live without a manual "Sync":

- It only runs when this theme is the active theme or the parent of the active theme, and only if ACF is loaded.
- It hashes `acf-json/group_*.json`, `post_type_*.json` and `taxonomy_*.json` and does nothing when the hash matches the one stored in the `acf_json_parent_sync_hash` option.
- It imports ACF post types and taxonomies first, then field groups, updating existing posts by key instead of creating duplicates. Groups marked inactive in JSON are set to `acf-disabled`.
- **Skipped:** field groups that have a JSON file with the same key in the child theme's `acf-json/` (the child version wins and is left to ACF's normal sync). Unreadable or invalid JSON files are counted as warnings.
- JSON saving is disabled while it runs, so no files are rewritten. It stops after 30 seconds. The new hash is stored before importing, so if a run is cut short the remaining groups are not retried until the JSON changes again. Finish those with ACF > Field Groups > Sync.
- Each run writes a summary line to the PHP error log: `[ACF sync:growthlabseotheme02] OK — synced: N, skipped: N, warnings: N | ...`.
- Multisite is not supported.

### Child theme notes

- Child themes can add or override blocks: `blocks/*/block.json` in the child is registered after the parent, and a block with the same `name` replaces the parent one.
- The child's `style.css` is appended to the inlined critical CSS.
- When a child theme is active, `<body>` gets `theme-child-{slug}` and `theme-parent-{slug}` classes.

---

## Development workflow

```bash
npm install
npm run build   # build everything once (gulp build)
npm run watch   # build once, then keep watching (gulp default task, same as npx gulp)
```

`gulpfile.js` defines two tasks: `build` runs every build once, in parallel, and exits; `default` runs `build` and then keeps watching, so it does not exit on its own. There are no separate `sass`, `images` or `clean` tasks.

| Source | Output | Notes |
| --- | --- | --- |
| `styles/**/*.scss` | `styles/**/*-min.css` + `.map` | Compressed Sass + Autoprefixer. Partials (`_*.scss`) are not output on their own. Includes `styles/main.scss`, `styles/page-templates/*`, `styles/vendor/*`. |
| `blocks/**/*.scss` | `blocks/**/block-min.css` + `.map` | Also rebuilt when anything in `styles/` changes. |
| `js/**/*.js` | `js/**/*-min.js` + `.map` | Terser. Existing `-min.js` / `.min.js` files are ignored. |
| `blocks/**/*.js` | `blocks/**/block-min.js` + `.map` | Terser. |

Source maps point to the source files, with paths relative to the stylesheet/script folders. The theme only loads the `-min` files (`block.json` points to `file:./block-min.css` and `file:./block-min.js`). **Compiled files are committed**: always commit the source and its compiled output together.

### Conventions

- **Commit messages:** commitlint-style types, `type: Description`, in English (for example `feat: Add items title tag option to posts grid block`, `fix: ...`, `perf: ...`, `chore: ...`, `docs: ...`). One commit per logical change.
- **Code comments:** English only.
- **Escaping:** see [Security notes](#security-notes).
- **Translatable strings:** use the literal `growthlabseotheme02` text domain (not `get_text_domain()`, which translation tools can't read) and `sprintf()` placeholders for dynamic parts. Texts used by the JS files come from PHP: `siteData.i18n` (`main.js`), `styledSelectData` (`styled-select-fields.js`) and `postsFiltersData.i18n` (`posts-filters.js`).
- **Breakpoints:** `js/main.js` defines the JS breakpoints (mobile 480, tablet 768, ldpi 1024, mdpi 1200, hdpi 1440) and shares them as `window.themeBreakpoints`; the block scripts read them from there and fall back to the same numbers.
- **New block:** create `blocks/block-{name}/` with `block.json` (`"name": "acf/{name}"`, `"textdomain": "growthlabseotheme02"`, `"category": "growthlabseotheme02-blocks"`, `"acf": {"renderTemplate": "block.php"}`, `"style": ["file:./block-min.css"]`, plus `"viewScript": ["file:./block-min.js"]` if it has a script, so it only runs on the front end), `block.php` and `block.scss` (plus `block.js` if needed), create its field group assigned to the block, and run `npm run build`. No PHP registration is needed. If the block uses Splide, add it to the `growthlabseotheme02_splide_blocks` filter (see [Media and performance](#media-and-performance)).
- **New theme-functions file:** add it to the `$includes` array in `functions.php`.

---

## Project structure

```text
growthlabseotheme02/
├── acf-json/            ACF field groups (group_*.json) and post types (post_type_*.json)
├── assets/
│   ├── icons/           SVG icons
│   └── img/             Logos, transparent background images
├── blocks/              ACF blocks, one folder each (block.json, block.php, block.scss, compiled files)
├── fonts/               Bundled woff2 fonts (mona-sans-v4-latin, roboto-serif-v17-latin)
├── js/
│   ├── main.js          Menu, accordions, block extraction, lazy maps/backgrounds, video player
│   ├── picture-optimization.js  Lazy loading for <picture>/<img data-src>
│   ├── posts-filters.js Blog filters (REST API) on the blog index
│   ├── styled-select-fields.js  Custom styling for <select> fields
│   └── vendor/          Splide, TinyMCE line-height plugin
├── languages/           Translation files (.mo/.po) for the text domain
├── page-templates/      template-full-width.php ("Template Full Width")
├── styles/
│   ├── main.scss        Global styles (inlined as critical CSS)
│   ├── globals/         Variables, mixins, normalize, forms, picture styles
│   ├── template-parts/  Partials for header, footer, cards, hero, etc.
│   ├── page-templates/  template-default, template-blog, template-team-member (enqueued as files)
│   └── vendor/          Splide core CSS, TinyMCE editor styles
├── template-parts/      Heroes, cards, pagination, posts filters, social networks, Splide navigation, maps
├── theme-functions/
│   ├── acf-functions.php        Block registration and cache, block CSS inlining, options page,
│   │                            ACF JSON paths, WP Pusher sync, unsafe HTML filters, color picker palette
│   ├── color-scheme.php         Default colors, Customizer color settings, CSS variables
│   ├── fonts-selector.php       Default fonts, Customizer font settings, bundled font faces
│   ├── helpers.php              General helpers (see reference below)
│   ├── picture-optimization.php Responsive <picture> generation
│   ├── security-functions.php   Generator tag and XML-RPC
│   ├── svg-support.php          SVG uploads, sanitization and inlining
│   ├── theme-optimization.php   Head cleanup, emojis, dashicons, delayed form scripts, lazy Google Maps
│   └── tiny-mce.php             TinyMCE/ACF WYSIWYG fonts, sizes, colors and toolbar
├── functions.php        Includes, theme setup, theme.json filter, critical CSS, scripts, sidebars
├── theme.json
├── style.css            Theme header (appended to critical CSS)
├── gulpfile.js / package.json
└── *.php                Template files
```

---

## Blocks

All blocks are ACF blocks with `renderPreview: false`, `align: full`, custom class name and spacing (margin, padding, block gap) support. Every block has a **toggle_block** switch: if it is off the block renders nothing.

| Block (`block.json` title / name) | Folder | What it does | Notable options |
| --- | --- | --- | --- |
| Contact Form / `acf/contact-form` | `block-contact-form` | Title, content and a Gravity Forms form, with an optional side image. | Form picker, `background_style`, reverse columns. |
| Content Box Grid / `acf/content-box-grid` | `block-content-box-grid` | Grid of boxes with title and text. | Repeater items (optional title link) **or** "is a menu" mode that builds the boxes from a WordPress menu (menu field). |
| Content Intro / `acf/content-intro` | `block-content-intro` | Intro title, a column with image + pretitle/title/content, and an inner CTA box. | `background_type` (gradient/light/image), up to two CTA links. |
| CTA Box / `acf/cta-box` | `block-cta-box` | Call-to-action section with buttons. | `box_style` (`simple` / `full`; full adds side image, pretitle and text), `background_type` (light/dark/image), repeater of buttons. |
| FAQ / `acf/faq` | `block-faq` | Accordion grouped in sections. | FAQ sections repeater with heading tags, question tags, side images, background image, CTA link. |
| Featured Video / `acf/featured-video` | `block-featured-video` | Cover image with a play button that loads a YouTube or uploaded video. | YouTube URL or media file, inline or lightbox mode. Without a cover image it uses the cached YouTube thumbnail. Editors get a notice if the YouTube URL is not a single video. |
| Office Locations / `acf/office-locations` | `block-locations` | Location cards (`template-parts/location-card.php`). | "Show all locations" (from the current language's site options) or a manual list, disable accordion, CTA shown when there is exactly one location. |
| Logos Carousel / `acf/logos-carousel` | `block-logos-carousel` | Splide carousel of logos. | Optional link per logo, side image. |
| Posts Carousel / `acf/posts-carousel` | `block-posts-carousel` | Splide carousel of selected posts or custom cards. | `carousel_type`: custom, case results, team, blog posts, testimonials. Custom mode can select any posts or create items. Items title tag. Editors get a notice when nothing is selected. |
| Posts Grid / `acf/posts-grid` | `block-posts-grid` | Grid of posts with pagination. | `grid_type` (same types as the carousel), "show all" or a manual selection, items title tag. Each paginated grid on a page has its own page parameter (`grid-N-page` from the second grid on). Case results are sorted by `numerical_amount`. Editors get a notice when nothing is selected. |
| Selling Points / `acf/selling-points` | `block-selling-points` | Icon cards in a Splide carousel. | `background_type`, items with optional link and title tag, CTA link. |
| Two Columns / `acf/two-columns` | `block-two-columns` | Text column + image column. | `background_type` (light/dark/img), reverse order, top separator, CTA link. |

Only the three carousel blocks (Logos Carousel, Posts Carousel, Selling Points) ship a `block.js`, declared as `viewScript` so it only runs on the front end. Accordions, video playback and block extraction are handled by `js/main.js`.

### Shared block conventions

Every `block.php` follows the same pattern:

```php
if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
    $background_type = get_valid_field_choice('background_type', $background_type ?? '');
?>
    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block my-block<?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>"
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>
        <?php print_title($title, $title_tag, 'my-block__title'); ?>
        ...
    </section>
<?php endif; ?>
```

- **`get_block_fields($block)` + `$$key`.** Returns the block's ACF values with every field of its field groups present. Fields never saved (for example added after the block was inserted) get their ACF default, so extracting them into variables does not raise undefined-variable warnings. Empty defaults are `null`; repeaters, galleries, relationships and checkboxes default to `[]`; groups default to an array of their sub field defaults.
- **Repeater rows and groups are arrays.** Read sub fields as `$item['title'] ?? ''` instead of `get_sub_field()`.
- **`get_block_id_attribute($block_id)`** prints ` id="..."` (escaped) only when the block ID field has a value.
- **`get_block_extract_attribute($enabled, $place)`** prints `data-extract="before|after"`. On pages using the default template, `main.js` moves those blocks out of the content column to before or after it (full width, outside the sidebar layout).
- **`get_valid_field_choice($field_name, $value)`** returns the value only if it is one of the field's choices, otherwise the field default. Use it for every select/radio value printed into a class or attribute. Use `group.sub_field` for nested fields.
- **`print_title($title, $tag, $classes)`** for headings. The tag is limited to h1-h6/p/span/div and the title goes through `wp_kses_post()`.
- **`block_style_attribute($block)`** turns the editor's spacing settings into a `style` attribute; spacing presets (`var:preset|spacing|40`) become `var(--wp--preset--spacing--40)` through `css_preset_value()`.
- **Escaping.** `esc_url()` for hrefs/srcs, `esc_attr()` for attributes (targets, classes, aria-labels), `esc_html()` for link text and plain values, `wp_kses_post()` for WYSIWYG/text content fields (they keep their HTML, see [ACF unsafe HTML](#acf-unsafe-html)). Print a link `target` only when it is set, and add `rel="noopener noreferrer"` to `_blank` links.
- **Icons:** print theme SVGs with `get_theme_svg('assets/icons/{file}.svg')` instead of including the file.
- **`print_block_notice($message)`** shows a dashed notice to users who can `edit_posts` and prints nothing for visitors. Use it instead of an empty block (Posts Carousel/Grid with no selection, invalid YouTube URL).
- **Images** go through `img_print_picture_tag()` (see [Media and performance](#media-and-performance)).

---

## Theme helpers reference

### `theme-functions/helpers.php`

| Function | Description |
| --- | --- |
| `get_text_domain()` | Returns the theme text domain (`$GLOBALS['theme_text_domain']`). Translation functions use the literal domain instead. |
| `get_theme_svg($relative_path)` | Markup of a theme SVG file (path relative to the theme root, child theme first), or `''` if it doesn't exist. Cached per request. |
| `get_field_options($field_name, $format_value = true)` | `get_field($name, 'option')` with a per-request cache. |
| `block_style_attribute($block = [])` | Builds the escaped ` style="..."` attribute from the block's spacing/style supports. Uses `normalize_spacing_property()`, `normalize_block_spacing()`, `format_spacing_item()`. |
| `css_preset_value($value)` | Turns an editor preset value (`var:preset\|spacing\|40`) into its CSS variable (`var(--wp--preset--spacing--40)`); other values are returned unchanged. |
| `filterContentByLanguage($lang = 'es')` | True when the request path is `/{lang}` or starts with `/{lang}/`. |
| `get_languages_map()` | `[url_language_slug => language]` from the `options_by_language` repeater. |
| `get_current_language()` | `['slug' => ..., 'language' => ...]` for the current URL; `['slug' => '', 'language' => 'English']` by default. |
| `get_current_language_suffix()` | `_{slug}` for a secondary language, `''` for the default one. |
| `get_current_language_options()` | The site options group for the current language (falls back to the default `options`). |
| `get_flat_number($phone)` | Digits only. |
| `get_phone_href($phone)` | Escaped `tel:` URL. US numbers (10 digits, or 11 starting with 1) get the `+1` country code once; numbers written with another `+` prefix keep it; anything else is dialed as written. `''` when there are no digits. |
| `get_wrapped_title($title, $tag = 'p', $classes = '', $is_hero = false)` / `print_title(...)` | Safe heading wrapper (returns / echoes). |
| `get_block_id_attribute($block_id)` | Escaped ` id` attribute or `''`. |
| `print_block_notice($message)` | Editor-only notice. |
| `get_valid_field_choice($field_name, $value)` | Validates a select/radio/button group value against the field's choices. |
| `get_block_extract_attribute($enabled, $place)` | `data-extract` attribute or `''`. |
| `get_block_fields($block)` | Block fields with ACF defaults filled in. |
| `get_acf_field_default($field)` | Default value of one ACF field as templates expect it. |
| `fill_acf_group_defaults($value, $selector, $post_id = false)` | Merges a group value (site `options`, `hero_properties`) over its sub field defaults. Used in `header.php`, `footer.php` and the hero templates. |
| `get_post_fields_with_defaults($post_id = false)` | Post fields with defaults filled in (used by `single-team.php`). |
| `get_current_post_blocks()` | Flattened blocks (inner blocks follow their parent) of the current singular post, cached per post. Synced patterns (`core/block`) are expanded once each, so their blocks count as used on the page; falls back to the queried post when a secondary loop changed the global `$post`. `[]` on non-singular requests. |
| `get_page_options_id()` | ID of the page whose "Page - Options" fields apply: the queried post on singular views, the posts page on the blog index, `0` on archives, search results and 404. |
| `get_page_field($selector)` | `get_field()` on `get_page_options_id()`, or `null` when the request has no such page. |
| `is_listing_page()` | True on the blog index, archives and search results (default hero, blog assets). |
| `get_page_hero_title()` | Fallback hero title: posts page title (or "Blog"), "Search results for: ...", the singular title, term, post type archive or archive title. |
| `get_posts_grid_page_arg()` | Pagination query arg for the next posts grid (`''` for the first, then `grid-N-page`). |
| `render_gravity_form(...$args)` | Calls `gravity_form()` only if Gravity Forms is active. |
| `format_number_abbreviated($number)` | 1500 to `1.5K`, also M and B. |
| `get_yt_code($url)` | 11-character ID of a single YouTube video, or `false` for channels, playlists, etc. |
| `get_youtube_thumbnail($video_id, $size = 'hqdefault')` | Best existing thumbnail URL from `$size` down, cached in a transient (a month; the `hqdefault` fallback a day). |
| `transform_inline_font_sizes($html, $font_size_var = '--fs')` | Rewrites each inline `font-size` into `--fs: {size}; font-size: calc(var(--fs) * var(--font-scale, 1))`. The fragment is parsed inside a UTF-8 container, so multi-root markup (`<p>...</p><h1>...</h1>`) keeps its structure. Used for the `home_4` hero title. |
| `[phonenumber]` shortcode | Main phone number of the current language as a `tel:` link built with `get_phone_href()`. Attributes: `target` (`_blank` adds `rel="noopener noreferrer"`), `link_id`, `link_class`, `wrapper`, `text`. |

### Other files

| Function | File | Description |
| --- | --- | --- |
| `img_print_picture_tag(...)` / `img_generate_picture_tag(...)` | `picture-optimization.php` | Responsive `<picture>` (echo / return). Named args: `img`, `mobile_img`, `tablet_img`, `max_size`, `min_size`, `classes`, `id`, `alt_text`, `is_cover`, `img_attr` (preferably an array, its values are escaped), `is_priority`, `fetch_priority` (`false` for priority images that aren't the LCP element, e.g. the logo). |
| `image_to_svg($image, $classes = '')` | `svg-support.php` | Inlines a local SVG file inside a `<div>`, or returns an `<img>` for other images. Accepts an ACF image array or a URL; the inlined SVG keeps the image's alt text (`role="img"` + `aria-label`, or `aria-hidden` when decorative). |
| `theme_get_customizer_css()` | `color-scheme.php` | `:root` block with the color variables. |
| `hex_to_rgb($hex, $fallback = '#000000')` | `color-scheme.php` | `#15253f` to `21, 37, 63`. 3-digit colors are expanded; empty or invalid values use `$fallback` (the theme default for the Customizer colors). |
| `theme_get_all_fonts()` | `fonts-selector.php` | Bundled fonts plus Font Library fonts. |
| `theme_get_fonts_css()` | `fonts-selector.php` | `@font-face` rules for selected bundled fonts + `--font-primary` / `--font-secondary`. |
| `theme_get_default_font_faces()` | `fonts-selector.php` | Bundled font files per family. |
| `get_theme_color_palette_for_acf()` | `acf-functions.php` | Customizer colors for the ACF color picker. |
| `import_theme_images_to_folder()` | `functions.php` | Copies `assets/icons/*.svg` into `uploads/theme-icons/` as media items. Not hooked; call it once manually if needed. |

---

## Customizer: colors and fonts

### Colors (`theme-functions/color-scheme.php`)

- Appearance > Customize > Colors has a setting for each color in `$default_colors`: `primary_color`, `primary_color_dark`, `primary_color_light`, the same for `secondary` and `tertiary`, and `text_color`.
- The values are printed as RGB triplets in the inlined critical CSS: `--primary`, `--primary-dark`, `--primary-light`, `--secondary*`, `--tertiary*`, `--text`. Use them as `rgb(var(--primary))` or `rgba(var(--primary), .5)`. Short (`#fff`) values are expanded and empty or invalid values fall back to the theme default, so the variables are always valid.
- The same values feed the block editor palette (slugs `primary-color`, `primary-color-dark`, ... `text-color`; core default palettes, gradients, font sizes and spacing sizes are disabled) and the ACF color picker palette, and TinyMCE text colors read `$default_colors`. Change the defaults in `$default_colors` only.

### Fonts (`theme-functions/fonts-selector.php`)

- Appearance > Customize > Fonts has **Primary Font** and **Secondary Font** selects. The choices are the two bundled families (Mona Sans, Roboto Serif) plus any font installed through the WordPress Font Library.
- The selection is output as `--font-primary` / `--font-secondary` and as the `primary` / `secondary` font families in `theme.json`.
- **Bundled fonts are only declared while selected.** `theme_get_default_font_faces_css()` prints `@font-face` rules only for selected bundled families. When Font Library fonts are selected, no bundled file is loaded. The `fontFace` entries in `theme.json` on disk are not used at runtime (the theme.json filter replaces `fontFamilies`), and a filter on `wp_theme_json_data_user` drops the copy of the theme fonts the Site Editor can save in global styles.
- **Only the selected Font Library fonts are loaded on the front end.** `theme_print_selected_font_faces()` replaces WordPress' `wp_print_font_faces()` and prints `@font-face` rules only for the selected primary/secondary families (faces without a `font-display` get `swap`). The block editor still loads every installed font, so all of them can be previewed there.
- **Font preloads (header and hero only).** `theme_get_above_fold_font_variants()` in `theme-functions/fonts-selector.php` lists the role (primary/secondary), weight and style of the text the header and the hero render above the fold, measured on the theme's templates on desktop and mobile; variants can be limited to some hero styles (`hero_styles`) or viewports (`media`). `theme_print_font_preloads()` (priority 1 in `wp_head`) maps each variant to the file of the font selected in the Customizer, bundled or from the Font Library, picking the face the browser would use (nearest weight per the CSS rules, one file for variable fonts), with the same URL as its `@font-face` rule, and preloads at most 6 files in the list order (hero title first). After the header and hero variants come those of the first block rendered after the hero (`get_first_content_block_name()`, which skips blocks switched off with `toggle_block`), taken from the per-block table in `theme_get_first_block_font_variants()` (measured with each block placed first; core blocks share one entry and unlisted blocks, e.g. from plugins, use a default). Adjust them with the `theme_above_fold_font_variants` filter, which also receives the hero style and the first block name (an empty array disables them), and the limit with `theme_font_preload_limit`.

**Adding a bundled font face:** put the `.woff2` file in `fonts/{family-folder}/` and add an entry to `theme_get_default_font_faces()`:

```php
'Mona Sans' => [
    ['weight' => '800', 'style' => 'normal', 'file' => 'mona-sans-v4-latin/mona-sans-v4-latin-800.woff2', 'display' => 'swap'],
    // ...
],
```

A new family also needs an entry in `theme_get_all_fonts()` to appear in the Customizer.

---

## Multilingual

The theme has its own lightweight language handling, based on the URL prefix (no WPML/Polylang integration):

- Languages are defined on the **Site Options** page, in the "Site Options by Language" field group (`options_by_language` repeater). Each row has a `language` name, a `url_language_slug` and an `options` clone of the site options.
- `get_current_language()` matches the request path against each slug (`/es` or `/es/...`). No match means the default language (English, slug `''`), which uses the main `options` group.
- Per-language pieces: `get_current_language_options()` (header, footer, locations, social networks, archive cards, the blog filters' default image, `[phonenumber]`); a `main_{slug}` menu location ("Main Menu {Language}"), chosen in `header.php` with `get_current_language_suffix()` (the menu is only printed when that location has a menu; it never falls back to a list of pages); and `sidebar-default-{slug}` / `sidebar-blog-{slug}` widget areas used by `sidebar.php` and `sidebar-blog.php`.
- **Logo link:** the current language's `logo_link` site option, or `home_url('/{slug}')` when it is empty (`?:`, since per-language options store an empty value when never set). A page with the custom header on can override it with its own `logo_link`.
- **Language switcher:** when `options_by_language` has rows, the header callout lists the default language (code taken from the site language, linking to `home_url('/')`) plus every configured language, marks the current one with `aria-current` and adds `hreflang`/`lang` when the slug is a language code. It is printed whether or not a phone number is set.
- **`lang` attribute:** `header.php` prints `<html <?php language_attributes(); ?>>`. A `language_attributes` filter in `helpers.php` replaces the `lang` value with the current slug when the slug looks like a language code (`es`, `pt-br`...), since language slugs switch content without changing the WordPress locale.

**Translations:** `growthlabseotheme02_setup()` calls `load_theme_textdomain('growthlabseotheme02', get_template_directory() . '/languages')`. Templates, blocks and admin strings use the `growthlabseotheme02` text domain, and the JS texts (video lightbox labels, styled select placeholder, blog filter messages and pagination) are passed from PHP in `siteData.i18n`, `styledSelectData` and `postsFiltersData.i18n`, so they are translatable too. The blog filter month options keep their English value (`posts-filters.js` parses it) and only translate the visible text; dates in filtered cards are formatted with the page's `lang`. Put `growthlabseotheme02-{locale}.mo` / `.po` files in `languages/` (it currently holds only `.gitkeep`).

---

## Media and performance

### SVG (`theme-functions/svg-support.php`)

- SVG uploads are allowed for files ending in `.svg`, with dimensions read from the root `<svg>` tag (width/height or viewBox) for the media library.
- Every upload/sideload detected as SVG (by extension or content) is sanitized with a DOM allowlist: drawing, gradient, filter and animation elements are kept. Scripts, `on*` handlers, external `href`s (only `#id` and embedded raster `data:image` are allowed), unsafe CSS (`@import`, `expression(`, external `url()`), comments, processing instructions and the doctype are removed. Files with `<!ENTITY` declarations, compressed SVGZ or invalid XML are rejected with an error.
- `img_print_picture_tag()` and `image_to_svg()` inline local SVGs, and `the_content` replaces up to 20 local `<img src="*.svg">` with inline SVG.

### Pictures (`theme-functions/picture-optimization.php`)

- `img_print_picture_tag()` builds a `<picture>` with one `<source>` per breakpoint (`$GLOBALS['breakpoints']` in `functions.php`: tablet 768px, ldpi 1024px, mdpi 1280px, hdpi 1920px). Each breakpoint gets the widest generated size that fits it, never smaller than `min_size` (what phones get) nor larger than `max_size`; phones never get a larger file than desktops. Breakpoints that would repeat the file of the next smaller one are dropped.
- Image sizes are read from the attachment metadata, which has the real width of each file (sizes without hard crop keep each image's proportions, so they can be narrower than the registered width).
- `is_cover: true` (hero backgrounds) uses `full`, `cover-desktop`, `cover-tablet` (1024-1279px screens), `cover-tablet-portrait` and `cover-mobile-portrait`. A `tablet_img` is only used for 768-1023px screens; larger screens always get the main image. Phones get the `mobile_img`, else the `tablet_img`, else the main image, in a portrait crop; without one, the smallest file at least 1000px tall (or the tallest file), so a very large original isn't sent to phones when a generated size would do.
- A URL of a generated size (`photo-800x533.jpg`) is resolved to its attachment; URLs outside the media library are printed as a plain lazy image.
- WebP `<source>` elements are added when the image is WebP or a `.webp` copy exists (`image.jpg.webp`).
- Non-priority images use `data-src` / `data-srcset` and the `lazy-image` class; `js/picture-optimization.js` swaps them in with an IntersectionObserver (100px margin) only when they approach the viewport, and picks up images added later (e.g. Splide clones); browsers without IntersectionObserver load every image at once. A `<noscript>` copy is printed for browsers without JavaScript.
- Lazy images get `--ar-{breakpoint}-w/h` custom properties with the real size of the file each breakpoint serves; `styles/globals/_picture-optimization.scss` turns them into an `aspect-ratio`, so their space is reserved before they load. Its breakpoint list must match `$GLOBALS['breakpoints']`.
- `is_priority: true` loads the image eagerly with `fetchpriority="high"` (the hero background and side portrait, which can be the LCP element on phones); the header logo passes `fetch_priority: false`, so it loads eagerly without competing with them.
- Images with `fetchpriority="high"` are also preloaded at the start of the `<head>`: one `<link rel="preload" as="image">` per file of the picture, limited to the screens that file serves (e.g. `(min-width: 768px) and (max-width: 1023px)`) and in WebP when a WebP copy exists, exactly like the `<source>` elements, so each file is downloaded once. The `<head>` is printed before the images, so `wp_head` prints a placeholder (priority 0) that a front-end output buffer (`template_redirect`) replaces with the collected links.
- Registered sizes (all uncropped except the portrait covers): `cover-desktop` 1920x1080, `cover-tablet` 1280x600, `cover-mobile` 768x432, `cover-tablet-portrait` 960x1280 (crop), `cover-mobile-portrait` 768x1100 (crop), `content` 800x600, `featured-small` 480x360. `medium_large`, `1536x1536` and `2048x2048` are not generated (`intermediate_image_sizes` filter), and hard cropping of the core thumbnail size is disabled.

### CSS and JS loading

- **Critical CSS:** `inline_main_critical_css()` (in `wp_head`) inlines, minified, in `<style id="main-css">`: font faces and font variables, color variables, `styles/main-min.css`, the active theme's `style.css`, the Splide core CSS (only when needed) and the `block-min.css` of every block used on the current singular page. `{theme-path}` in CSS is replaced with the theme URL. Comments and whitespace are stripped (spaces around `:` and `,` are kept, since they can be significant). The result is cached in a `growthlabseotheme02_ccss_*` transient (one week) keyed by the mtime of `functions.php`, the theme URL, the mtimes of the files read, the block CSS files, whether Splide is included and a hash of the fonts and colors CSS, so editing any of them invalidates it. Customizer previews skip the cache. There is no separate main stylesheet request. Template styles (`template-default`, `template-blog`, `template-team-member`) are enqueued as files on the views that need them; the blog index, archives and search results load `template-default` and `template-blog`.
- **Block assets:** styles of blocks in use (blocks inside synced patterns included, see `get_current_post_blocks()`) are inlined and their `<link>` tags removed. The styles and scripts of every unused non-core block, plugin blocks included, are dequeued. Block scripts (`viewScript`, front end only) are moved to the footer with `defer`.
- **Splide:** only the three carousel blocks use it (filterable with `growthlabseotheme02_splide_blocks`). Its CSS is inlined only when the current post (including reusable blocks) or an active block widget contains one of them. The JS (`js/vendor/splide/splide-min.js`) is loaded on demand by the carousel block scripts through `window.loadSplide()`. The carousels read their breakpoints from `window.themeBreakpoints`.
- **Scripts:** `main-min.js`, `styled-select-fields-min.js` and `posts-filters-min.js` (blog index, archives and search results) are deferred, `picture-optimization-min.js` is async. jQuery is moved to the footer, `jquery-migrate` is removed from the `jquery` dependencies (deregistering it would make WordPress skip jQuery and every Gravity Forms script) and the Gravity Forms maps script is dequeued. `wp-i18n` is not touched: WordPress only prints it when a script needs it (e.g. `wp-a11y` for Gravity Forms). No script or image is preloaded. Theme asset URLs (parent and active child theme) get `?ver={filemtime}`.
- **Delayed form scripts:** jQuery, Gravity Forms and their dependencies (e.g. `wp-a11y`, `wp-i18n`) are printed as inert `type="text/plain"` tags (inline parts and translations included, `script_loader_tag` in `theme-functions/theme-optimization.php`). A small inline loader runs them in their original order when a Gravity Forms form gets within 600px of the viewport or on the first click, key press, touch or focus, so pages don't download or run them up front. Any script that depends on a delayed one is delayed too. Gravity Forms initialises through `gform.initializeOnLoaded()`, which waits for its own scripts, and the forms submit without JavaScript as well.
- **Blog filters:** `posts-filters.js` queries the posts REST API with the server listing's page size (Settings > Reading `posts_per_page`, passed as `postsFiltersData.perPage`). A month without a year is filtered on the server through the theme's `pf_month` REST parameter (that month in every year). A new request cancels the previous one, and cards built by the script keep the classes of the server-rendered cards.
- **Fonts:** only selected bundled fonts are declared, and only the faces the header and hero use are preloaded (see [Fonts](#fonts-theme-functionsfonts-selectorphp)).
- **YouTube:** thumbnail lookups are cached in transients (`get_youtube_thumbnail()`). Videos are only embedded after the play button is clicked.
- **Other cleanups** (`theme-optimization.php`): emojis disabled; a direct Dashicons enqueue is dequeued on the front end unless the admin bar is showing (it is not deregistered, so plugin styles that depend on it keep it); RSD, wlwmanifest, shortlink, REST, oEmbed and feed links removed from `<head>`; the WooCommerce blocks style dequeued; common Google Maps JS handles dequeued; Google Maps iframes in content replaced with `.gmap-lazy` placeholders that `main.js` loads when they come into view.

---

## Security notes

- **`theme-functions/security-functions.php`:** removes the WordPress version `<meta name="generator">` and disables XML-RPC.
- **Admin bar "Clear block cache":** visible to users with `manage_options`. The link carries a nonce (`clear_block_cache`), and the cache is only cleared when the capability and nonce both check out. This is the only admin bar tool in this theme.
- **Escaping conventions:** escape on output by context (`esc_url`, `esc_attr`, `esc_html`, `wp_kses_post` for ACF rich text), print headings through `print_title()` (`wp_kses_post`, tags allowlisted), validate select values with `get_valid_field_choice()`, and build ids with `get_block_id_attribute()`. Link targets are only printed when set, and `_blank` links get `rel="noopener noreferrer"`. The `[phonenumber]` shortcode escapes all attributes and allowlists `wrapper` and `target`, because contributors can use it.
- **Site Options** is registered on `acf/init` with the `manage_options` capability.
- **Google Maps embed codes** (`google_maps_embed_code` fields) are base64-encoded in the browser before saving to avoid ModSecurity false positives. Decode values starting with `base64:` before output.

### ACF unsafe HTML

`acf-functions.php` returns `true` for `acf/the_field/allow_unsafe_html`, `acf/the_sub_field/allow_unsafe_html` and `acf/shortcode/allow_unsafe_html`, and hides ACF's "escaped HTML" admin notice. This is intentional: text, textarea and WYSIWYG fields are used for inline markup such as `<span>`, `<a>` and `<strong>` in titles and content.

Block templates and template parts print content fields through `wp_kses_post()` and titles through `print_title()`, so scripts are stripped there. Values printed with `the_field()`, `the_sub_field()` (the sidebar menus) or the `[acf]` shortcode are not escaped by ACF. The custom hero title (`hero_title`) also goes through `wp_kses_post()`. Only give block/options editing rights to trusted users, and don't use these fields for input from untrusted users.

---

## Troubleshooting

**A new or renamed block does not appear in the inserter / changes to `block.json` are ignored.**
The list of block folders is cached in a transient (24 hours, per active theme). It is rebuilt automatically when a `block.json` is added or modified (hash of paths and modification times), on theme switch (for both the new and the previous theme), and on every admin request while `WP_DEBUG` is on. To force it, use **Clear block cache** in the admin bar.

**ACF > Field Groups shows "Sync available".**
The JSON in `acf-json/` (parent or child) is newer than the database copy, for example after a `git pull` or a deploy where the WP Pusher sync did not run or was cut short. Review and click **Sync**. Edits made in the admin are saved back to the active theme's `acf-json/`, so commit them.

**A block field added later shows its default instead of nothing.**
Expected: `get_block_fields()` fills unsaved fields with their ACF default until the block is saved again.

**A Posts Carousel/Grid shows a dashed notice in the editor or front end.**
Nothing is selected or created for that block. Visitors don't see the notice and the block is not rendered for them.

**Forms are missing.**
Check that Gravity Forms is active (`render_gravity_form()` outputs nothing otherwise) and that the form is selected in the block or in the footer form section.

---

## License

GPL-3.0-or-later. See [`LICENSE`](LICENSE).
