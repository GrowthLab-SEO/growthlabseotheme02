# Child themes and site building

Every client site runs a child theme of this theme, and every push to `main` reaches the live sites through WP Pusher.
This guide is the contract between the parent theme and the child themes: what the parent loads for you, how to build
a child theme, what you can rely on, and how to write code and content that stay fast and don't break on updates.

The same guide ships with the three GrowthLab themes (`growthlabseotheme01`, `02`, `03`). Where they differ, it says so.

---

## 1. What the parent theme does for a child theme

| Child theme file | What the parent does |
|---|---|
| `style.css` | Theme header. Its CSS is printed **inline** in the `<head>`, after everything else of the theme. |
| `styles/main.scss` → `styles/main-min.css` | Printed inline after all the parent styles (main, hero, cards, carousels and the blocks of the page) and before `style.css`, so a child rule wins over a parent rule of the same specificity. |
| `js/main.js` → `js/main-min.js` | Enqueued deferred in the footer, after the parent scripts (only if the file exists). |
| `blocks/<block>/block.json` | Registered like a parent block. A block with the same `name` as a parent block replaces it. Its `block-min.css` is inlined only on pages that use the block. |
| `acf-json/` | ACF loads the child field groups with the parent ones and saves the groups edited on the site here. |
| `template-parts/*.php`, page templates, `sidebar.php`… | Replace the parent file with the same path (standard WordPress behavior). |
| `functions.php` | Loads **before** the parent `functions.php`: a function declared here replaces a parent function wrapped in `function_exists()`. |

Inline CSS can't use relative `url()`s (they would resolve against the page URL). Use the placeholders, which the theme
replaces when it prints the CSS:

```scss
background-image: url("{child-theme-path}/assets/img/texture.webp"); // child theme folder
mask-image: url("{theme-path}/assets/icons/icon-star.svg");          // parent theme folder
```

## 2. Creating and building a child theme

The parent theme's gulp builds child themes too, so a child theme doesn't need its own `package.json` or
`node_modules`. Run every command from the **parent** theme folder.

```bash
npm install                                   # 1. once per parent theme folder (installs gulp and Sass)
npm run create-child -- --name="Client Name"  # 2. creates ../<parent>-client-name from child-theme-starter/ and builds it
                                              # 3. activate the child theme under Appearance > Themes
npm run build:child -- --child=../<parent>-client-name   # 4. after every change to its styles, scripts or blocks
npm run watch:child -- --child=../<parent>-client-name   #    or rebuild on save while you work
```

Without step 1 the commands fail ("gulp" is not recognized). The parent loads only the child's compiled files
(`styles/main-min.css`, `js/main-min.js`), so a change to a source file shows on the site only after a build.

To avoid typing the path, create `child-themes.json` in the parent folder (git-ignored, paths are local):

```json
["../growthlabseotheme03-client-name"]
```

The new child theme mirrors the parent structure:

```
<parent>-client-name/
├── style.css          theme header + quick fixes (inline)
├── functions.php      hooks and function overrides
├── styles/main.scss   child styles (partials: styles/_name.scss)
├── js/                create main.js when the site needs JavaScript
├── blocks/            child blocks or replaced parent blocks
├── template-parts/    template part overrides
└── acf-json/          child field groups
```

Every `.scss` file that doesn't start with `_` becomes `<name>-min.css`, and every `.js` becomes `<name>-min.js`, next
to the source, with source maps. Only `styles/main-min.css` and `js/main-min.js` are loaded automatically: enqueue
other files in `functions.php` when a page needs them.

The child stylesheets can use the parent partials, because the build adds the parent `styles/` folder to the Sass load
path:

```scss
@use "globals/variables" as v; // breakpoints
@use "globals/mixins" as m;    // title(), flex-center, visually-hidden...
```

**Why the parent builds the child themes** (instead of a gulp setup in each child theme): one Node install and one
version of Sass and Autoprefixer for every site, child stylesheets can use the parent partials without fragile relative
paths into the parent folder, a copied parent block keeps compiling in the child theme, and child themes stay plain
WordPress themes without build tooling in their repositories.

Commit the compiled files (`*-min.css`, `*-min.js` and their maps) in the child theme: the server doesn't build
anything.

## 3. What you can rely on (and what you can't)

The parent keeps these stable, and when one has to change it keeps the old way working for a while and says so in
`CHANGELOG.md` under "Check after updating":

- **Template parts and their arguments** (table below). An override receives the same `$args`. New arguments can be
  added; existing ones keep their meaning. Example: `footer-form.php` (theme-03) receives `form_html` (the printed form)
  and still receives `form` (the Gravity Form ID) for overrides written before `form_html` existed.
- **Function names and signatures** of the helpers (section 4) and of every function wrapped in `function_exists()`.
- **Filters** listed in section 4.
- **The custom properties** (section 6) and the **utility classes** (section 5).

What is **not** stable, so don't build on it:

- The internal markup and classes of blocks (`.two-columns__wrapper`, `.selling-points__grid`…) can change to fix
  layout or accessibility. Style them from the block's own class or an Additional CSS class, and check them after
  updates.
- Never change the layout of carousel internals (`.splide__list`, `.splide__track`, the `*__grid` element of carousel
  blocks): Splide measures them. Example: `justify-content: center` on `.selling-points__grid` breaks the Selling Points
  carousel on mobile, because on mobile that element is the carousel track.
- An overridden template part doesn't get the parent's later fixes. Override as few files as possible, and compare
  your copy with the parent file when the changelog mentions it.

### Template parts

Arguments each template part reads from `$args` (`get_template_part('template-parts/<name>', null, $args)`):

| Template part | Themes | Arguments |
|---|---|---|
| `default-card` | all | `classes`, `content`, `link_target`, `link_url`, `picture`, `tag`, `title`, `title_tag` |
| `post-card` | all | `cat`, `classes`, `excerpt`, `link_target`, `link_url`, `meta`, `picture`, `tag`, `title`, `title_tag` |
| `result-card` | all | `case_description`, `case_title`, `classes`, `numerical_amount`, `title_tag` |
| `testimonial-card` | all | `author`, `classes`, `condition`, `content`, `link_url`, `picture`, `role`, `source` |
| `team-card` | 02, 03 | `classes`, `content`, `first_name`, `link_target`, `link_url`, `picture`, `role`, `title`, `title_tag` |
| `team-card-grid` | 02, 03 | `classes`, `link_target`, `link_url`, `name`, `picture`, `role`, `title_tag` |
| `location-card` | all | `accordion`, `classes`, `location` |
| `google-maps` | all | `classes`, `iframe_src`, `name` |
| `posts-pagination` | all | `classes`, `end_size`, `mid_size`, `page_arg`, `paged`, `query`, `query_arg` |
| `splide-navigation` | all | `classes`, `nav_link` |
| `social-networks` | all | `social_networks` |
| `posts-filters` | all | `classes` |
| `footer-form` | 03 | `title`, `tag`, `link`, `form_html`, `form` |
| `footer-locations` | 03 | `offices` |
| `hero-default`, `hero-homepage`, `hero-homepage-v2` … `v6` | all | `hero_title`, `hero_cta_button_default`, `hero_image_desktop_default`, `hero_image_tablet_default`, `hero_image_mobile_default`; theme-01 also `hero_title_tag` |

Every hero is a `<section id="hero" class="hero …">` with a modifier per style, to scope hero rules: `hero--default`
(inner pages), `hero--homepage` (styles 1, 4 and 6), `hero--v2` … `hero--v6`. Target one style by its own modifier:
`.hero--homepage` doesn't cover styles 2, 3 and 5, so `:not(.hero--homepage)` is not "inner pages"; use
`.hero--default`.

The blog filters build the cards of their results from the theme's own post card: the blog, archive and search
templates print it once inside a `<template>` with placeholder values (`theme_print_posts_filters_card_template()`).
An override of `post-card.php` is used for the filtered results too, as long as it prints the values it receives.

The card template parts load their own styles: when a page loads `template-parts/post-card` (from the parent or from
an override), the theme prints the post card styles. Keep the file names if you override them.

## 4. PHP helpers and filters

All of them are wrapped in `function_exists()`, so a child theme can replace them by declaring a function with the same
name in its `functions.php`.

**Images** (`theme-functions/picture-optimization.php`):

```php
img_print_picture_tag(
    img: $image,              // ACF image array, attachment ID or URL
    mobile_img: [], tablet_img: [],   // optional art direction images
    max_size: 'full', min_size: '',   // image sizes allowed for the sources
    classes: '', id: '', alt_text: '',
    is_cover: false,          // fills its container (hero and section backgrounds)
    img_attr: '',             // extra <img> attributes
    is_priority: false,       // above the fold: eager loading and preload
    fetch_priority: null,     // force fetchpriority high (true) or never (false)
    context: ''               // stable name of the image ("area/name"), for theme_picture_args
);
img_generate_picture_tag(/* same arguments */): string;
```

Always print images with these functions: they choose the size per breakpoint, serve WebP when it exists, add the
aspect ratio that prevents layout shifts, lazy load below the fold and preload the priority images.

**Changing the sizes of a theme image** without overriding its template: every image the theme prints has a context,
listed in `CATALOG.md` (Images), e.g. `hero/home_4/side-portrait` or `block/two-columns/image`. In the child theme's
`functions.php`:

```php
// "*" matches one part of the context: the side portrait of every hero
theme_set_picture_sizes('hero/*/side-portrait', max_size: 'large', min_size: 'content');

// Any argument, with the filter (it receives the arguments, the context and the image)
add_filter('theme_picture_args', function ($args, $context, $img) {
    if ($context === 'block/two-columns/image') $args['max_size'] = 'content';
    return $args;
}, 10, 3);
```

Cover images (`is_cover`, e.g. hero backgrounds and the theme-03 side portraits) use the cover sizes and ignore
`max_size` and `min_size`: set `is_cover: false` too to use them. Give your own images a context with the same
`area/name` convention when other code may need to adjust them.

**Content and fields** (`theme-functions/helpers.php`):

| Function | Use |
|---|---|
| `print_title($title, $tag = 'p', $classes = '', $is_hero = false)` / `get_wrapped_title(...)` | Title wrapped in a validated tag (`h1`–`h6`, `p`, `span`, `div`). |
| `get_page_field($selector)` | Field of the page whose "Page - Options" apply to the request (also on the blog index). |
| `get_page_options_id()` | ID of that page. |
| `get_field_options($field_name, $format_value = true)` | Site Options field. |
| `fill_acf_group_defaults($value, $selector, $post_id = false)` | Fills unsaved subfields with their ACF defaults. |
| `get_block_fields($block)` | Every field of a block, with defaults (blocks use it to extract variables). |
| `get_valid_field_choice($field_name, $value)` | A choice value checked against the field choices. |
| `get_block_id_attribute($block_id)`, `block_style_attribute($block)`, `get_block_extract_attribute($enabled, $place)` | Attributes for block wrappers. |
| `get_page_hero_style()`, `get_page_hero_title()` | Hero style (`default`, `home`, `home_2`…`home_6`, `nohero`) and fallback title. |
| `get_current_post_blocks()`, `get_first_content_block_name()` | Parsed blocks of the request and the first one after the hero. |
| `is_listing_page()` | Blog index, archives and search. |
| `get_selected_form_html($fields, $prefix = 'contact_form')` | HTML of the form chosen in a set of form fields (Gravity Form, custom code or none). |
| `render_gravity_form(...$args)` | `gravity_form()` only when Gravity Forms is active. |
| `get_theme_svg($relative_path)` | Inline SVG from the theme (cached per request). |
| `get_phone_href($phone)`, `get_flat_number($phone)` | Phone links. |
| `get_yt_code($url)`, `get_youtube_thumbnail($video_id, $size)` | YouTube helpers. |
| `get_text_domain()` | The theme text domain. |
| `transform_inline_font_sizes($html)` | Makes the font sizes chosen in the WYSIWYG for parts of a title scale on small screens (see `--font-scale`). |
| `theme_get_config($key)` | Design data of the theme (`theme-functions/theme-config.php`). |

Languages: theme-01 is bilingual (`filterContentByLanguage()`, `get_bilingual_text()`, options with the `_es` suffix);
themes 02 and 03 use `get_current_language()`, `get_current_language_options()` and `get_languages_map()`.

**Filters**:

| Filter | Arguments | Use |
|---|---|---|
| `theme_above_fold_font_variants` | `$variants, $hero_style, $first_block` | Font faces preloaded for the header, hero and first block. Return `[]` to preload none. |
| `theme_font_preload_limit` | `$limit` (6) | Maximum number of preloaded font files. |
| `theme_font_preload_max_bytes` | `$bytes` (300 KB) | Font files bigger than this are never preloaded. |
| `theme_font_variant_condition` | `$result, $condition` | Custom conditions for the `when` key of the font variants. |
| `theme_hero_css_files` | `$files, $hero_style` | Hero stylesheets printed for the page. |
| `theme_picture_args` | `$args, $context, $img` | Arguments of a theme image (sizes, cover, priority, classes…), by its context (see Images above). |
| `theme_page_hero_title` | `$title` | Fallback hero title (blog, archives, search), e.g. to translate it. |
| `theme_main_script_data` | `$data` | Data of the main script (`siteData`), e.g. its texts. |
| `theme_styled_select_data` | `$data` | Data of the styled selects script (its placeholder text). |
| `<theme>_splide_blocks` | `$blocks` | Blocks that render a carousel (its script and styles load only with them). |
| `<theme>_request_uses_splide` | `$uses_splide` | Whether the page needs the carousel script, e.g. for a carousel outside the blocks. |

## 5. SCSS: variables, mixins and reusable classes

**Breakpoints** (`globals/variables`, mobile first, use them with `min-width`):

| Variable | 01 | 02, 03 |
|---|---|---|
| `v.$mobile` | 480px | 480px |
| `v.$tablet` | 768px | 768px |
| `v.$ldpi` | 1024px | 1024px |
| `v.$mdpi` | 1280px | 1200px |
| `v.$hdpi` | 1920px | 1440px |

The root font size is `62.5%`, so `1rem` = 10px: `1.6rem` is 16px.

**Mixins** (`globals/mixins`):

| Mixin | Use |
|---|---|
| `m.title($color, $font-size-min, $font-size-mid, $font-size-max, $font-weight, $line-height)` | Heading style with a fluid `clamp()` size. Theme-02 has an extra `$after` argument. |
| `m.flex-center` | `display: flex` centered on both axes. |
| `m.visually-hidden` | Hidden but available to screen readers. |
| `m.nice-corner(...)` | Theme-02 only: decorative corner borders. |

**Reusable classes** (also usable as Additional CSS classes on blocks):

| Class | Use |
|---|---|
| `.container` | Centered content with the theme max width and side margins. |
| `.formatted-text` | Rich text (WYSIWYG content): spacing, lists with bullets or numbers, links. |
| `.btn` + `.btn--primary`, `--secondary`, `--tertiary` (03 also `--primary-dark`, `--tertiary-light`) | Buttons with the theme styles. Use both classes: `class="btn btn--primary"`. |
| `.h1` … `.h6` | Heading styles on any element (keep the right heading tag for SEO). |
| `.tx-center` | Centered text. |
| `.shadow-box`, `.border-box` | Theme box decorations. |
| `.hide-on-mobile` (< 768px), `.hide-on-tablet` (< 1024px), `.hide-on-desktop` (≥ 1024px) | Responsive visibility. |
| `.screen-reader-text` | Visually hidden text for screen readers (WordPress standard). |
| `.accordion` with `__heading`, `__content`, `__inner` and `.open` | Accordion pattern of the theme scripts. |
| `.bg-gradient` (02, 03), `.bg-bicolor` (01) | Section backgrounds of the design. |
| `.gradient-overlay` | Dark overlay on background images. |

## 6. Custom properties

Printed in the `<head>` from the Customizer (colors and fonts) and from the theme styles (spacing):

| Property | Value | Use |
|---|---|---|
| `--primary`, `--primary-dark`, `--primary-light` | RGB numbers, e.g. `14, 93, 184` | `color: rgb(var(--primary));` `background: rgba(var(--primary), 0.8);` |
| `--secondary`, `--secondary-dark`, `--secondary-light` | RGB numbers | Same. |
| `--tertiary`, `--tertiary-dark`, `--tertiary-light` | RGB numbers | Same. |
| `--text` | RGB numbers | Body text color. |
| `--font-primary`, `--font-secondary` | Font family lists | `font-family: var(--font-primary);` |
| `--sp-xsm`, `--sp-sm`, `--sp-md`, `--sp-lg` | 1.6rem, 3.2rem, 4.8rem, 6.4rem (02 also `--sp-xlg`: 9rem) | Spacing. |
| `--container` (02, 03) | 1240px | Content max width. |
| `--font-scale` | 0.75 (mobile), 0.9 (tablet), 1 (from 1024px) | Scale of the font sizes chosen in WYSIWYG titles. A component can set its own. |

The colors are numbers, not colors: always wrap them in `rgb()` or `rgba()`. Using the properties instead of fixed
values keeps the child theme working when the client changes the colors or fonts in the Customizer.

## 7. Writing CSS in a child theme

Follow the conventions of the parent styles:

1. **Write SCSS in `styles/`**, not in `style.css` or in Customizer > Additional CSS. Everything is inlined in the
   `<head>` of every page, so its size matters: a few hundred lines are fine; hundreds of KB (inline SVG data URIs,
   copied stylesheets) slow down every page.
2. **BEM naming and nesting**: `.client-cta`, `.client-cta__title`, `.client-cta--dark`, written with `&__` and `&--`.
   Prefix the classes you create (client or project name) so they never collide with parent classes.
3. **Scope the rules**: start from a block class or an Additional CSS class (`.is-client-highlight .content-box__title`),
   never from bare element selectors or parent classes alone, so the rule only affects what you meant.
4. **No IDs and no `!important`** to win: IDs tie the CSS to one page, and a higher specificity doesn't survive markup
   changes. Increase specificity with a scoping class instead. Use `!important` only against inline styles you can't
   change (e.g. third-party embeds).
5. **Mobile first**: base styles for mobile, then `@media (min-width: v.$tablet)` and up, with the theme breakpoints.
6. **Theme tokens**: colors with `rgb(var(--primary))`, fonts with `var(--font-primary)`, spacing with `var(--sp-sm)`,
   sizes in `rem`.
7. **Don't change the layout of parent components** you don't own (carousels, grids of blocks): add a modifier class
   or an option instead, and ask for it in the parent theme when several sites need it.
8. **Motion and focus**: animate `transform` and `opacity` (not `width`, `height`, `top`, `margin`), never remove the
   focus outline (`outline: none`) without a visible replacement, and keep the theme's reduced motion support (it stops
   transitions for visitors who ask for it).
9. **Comments in English**, explaining why a rule exists when it isn't obvious (a client request, a fix for a plugin).

## 8. Building a fast site (content and third parties)

Most of the slowness found in live sites came from content and third-party code, not from the theme:

- **Images**: upload hero images and portraits as **WebP** (quality 80–90), not PNG. EWWW converts PNGs to lossless
  WebP, which can weigh four or five times more than a lossy WebP of the same quality. Keep transparency where the design needs it
  (WebP supports it). Upload images at least twice their displayed size for high density screens, and add an
  alternative text.
- **Fonts**: install fonts with only the characters the site uses (Latin subset). A font from Google Fonts with every
  character can weigh 2 MB per file (e.g. Japanese fonts); the theme doesn't preload files over 300 KB, but they still
  load. [google-webfonts-helper](https://gwfh.mranftl.com/fonts) gives Latin subsets. Install each weight in the same
  family (a "Medium" file uploaded alone can create a separate family).
- **CSS**: no large CSS in Customizer > Additional CSS (it's inline on every page): use the child theme styles.
- **Third-party scripts**: one tag manager container (GTM or gtag), not several; add tracking and chat scripts through
  it with a delay where possible. Each extra analytics or ads script costs main thread time on every page.
- **Forms**: for a CRM form, set the form block's Form Type to "Custom code" and paste the embed code; don't hide a
  Gravity Form with CSS.
- **Title sizes**: font sizes chosen in the WYSIWYG for parts of a title scale down on small screens, never below the
  title's own size at that screen (unless the chosen size is smaller) nor below 12px. Prefer the title's own size and
  use custom sizes for emphasis.
- **Headings**: one `h1` per page (the hero title). "Posts Cards Default Title Tag" is the tag of the post cards in the
  listings: use `h2` or `p`, never `h1`.

## 9. Before and after a parent theme update

- Read `CHANGELOG.md`: the version in `style.css` goes up with every update, and "Check after updating" lists what to
  look at.
- For parent theme developers: `npm run verify` checks the PHP syntax, builds the theme and the local child themes, and
  loads the local site with the parent theme and with a test child theme that overrides template parts like client
  sites do. Run it before pushing.
