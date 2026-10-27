# Rules for AI-assisted site building

Read this file before writing any code or content for a site built on this theme: a child theme, its styles, scripts
and blocks, or the pages and options of the site. It applies to every AI tool and to people too.

The same file ships with the three GrowthLab themes (`growthlabseotheme01`, `02`, `03`). Two other files of this theme
complete it, and these rules don't repeat them:

- **`CATALOG.md`**: what this theme can build. Its blocks with every field and option, the heroes, page templates, post
  types, Site Options, per-page options and default colors and fonts. Generated from the theme files, so it is always
  current. A block, field or option that isn't in it doesn't exist.
- **`CHILD-THEMES.md`**: how child themes work. Folder structure, build commands, what the parent loads, stable template
  parts and helpers, SCSS tokens and classes, custom properties and the CSS standards (its section 7).

---

## 1. Where this fits

| Step | Who | Produces |
|---|---|---|
| Brief | Website builder | What the client has: content, logo, photos, colors, fonts, phones, offices. |
| Mockup | Claude Design | The look of the pages, made of the blocks of `CATALOG.md`. A picture, not code. |
| Child theme | Claude Code, with the builder | The client's child theme: styles, scripts, blocks and overrides. |
| Content | Claude Code, with the builder | The pages, their blocks and fields, Site Options, menus, colors and fonts, entered in WordPress. |
| Review and launch | Website builder | Checked in WordPress, edited where needed, published. |

**The mockup is a reference, never a source.** Don't copy its HTML, CSS, inline styles or fixed colors: they were made
for a design tool. Read what each section is (which block, which content) and how it looks, then build it with this
theme.

**WordPress stays editable.** The build only speeds up the first version of the site. Editors must be able to change
every text, image, link, phone number, color and font afterwards from WordPress, without code, without breaking the
design.

## 2. The decision ladder

For each section of the design, take the first step that works and stop:

1. **A block of `CATALOG.md` with its own options.** Pick the block whose fields hold the section's content, then its
   options: background type, box style, image position, reverse order, card type.
2. **Tokens.** Colors and fonts in the Customizer, so they become the custom properties (`--primary`, `--font-secondary`
   …) that every block already uses. Most of a new look comes from here.
3. **Child theme CSS.** Restyle the block from its root class or an Additional CSS class, following section 5.
4. **A copy of the block in the child theme**, when the markup has to change: different order of elements, extra
   wrappers for a layout, a shape CSS alone would need many hacks for. Copy `blocks/<block>/` to the child theme with the
   same `name` in `block.json` (it replaces the parent block), keep its field group, and change its `block.php` and
   `block.scss`. The editor keeps the same fields. Rule of thumb: if the CSS for a section needs more than one
   pseudo-element trick, negative margins to move elements around, or rules that hide parts of the block to rebuild them,
   copy the block instead.
5. **A new block in the child theme**, only for content or behavior no block has (a field the client needs that no
   block stores, a new interaction). It gets its own `block.json`, field group in the child's `acf-json/`, `block.php` and
   `block.scss`, following the structure of the parent blocks.

**Report every copied or new block.** Before writing it, tell the builder which block and why, and register the request
as described in section 8. When three sites ask for the same thing, it goes to the parent theme.

Never edit the parent theme from a site build. Parent changes are made in the parent repository by its developer.

## 3. Content

**Never invent content.** No stats, awards, case results, reviews, years, credentials, attorney names, quotes, logos or
phone numbers that the brief or the client didn't provide.

When the design has a section whose content doesn't exist yet:

1. **Ask the builder** whether the content exists and where.
2. If nobody can answer, **leave the section out**. Don't add placeholder text, empty cards or sample logos.
3. If the builder wants the section ready for later, add the block with `toggle_block` off. It doesn't print, and the
   editor turns it on once the content is there.

Rules that follow from it:

- **`[CONFIRM]`, `[X]`, "Photo · …" and other mockup placeholders never reach WordPress.** They are notes for the
  builder.
- **Don't build features for content the client may not want.** Examples: a "contact this attorney" box on every bio
  page, an awards carousel without awards, a team slider with highlighted achievements nobody has written. Build them
  only when the brief asks for them and the content exists.
- **Images come from the Media Library** (the client's photos and the licensed stock the builder uploaded). Never
  hotlink, never use AI-generated or AI-altered photos of people or places, never use images found online.
- **Headings follow the brief's heading structure.** One `h1` per page: the hero title.
- **Body copy written for the site** (where the brief allows it) is marked for the builder's review, never presented as
  the client's.

## 4. Editability

Everything a visitor reads or clicks comes from WordPress data, never from code:

| Content | Comes from |
|---|---|
| Phone numbers, emails, offices, addresses, social links | Site Options (`CATALOG.md`, Site Options) |
| Logos, logo symbol, default images | Site Options, Media Library |
| Section texts, titles, buttons, images | The block's fields |
| Hero title, texts, images and buttons | The page's Page - Options |
| Menus (header, footer, sidebar) | Appearance › Menus |
| Colors and fonts | Appearance › Customize (Colors, Fonts); fonts installed with the WordPress Font Library |
| Forms | Gravity Forms, chosen in the block or in Site Options (Form Type: Gravity Form, Custom code or None) |

So:

- **Phone numbers in content use the `[phonenumber]` shortcode** (`CATALOG.md`, Shortcodes), which prints the Main
  Phone Number of Site Options as a link, e.g. `[phonenumber link_class="btn btn--primary"]` for a call button inside a
  WYSIWYG field. Shortcodes don't work in Link fields, so a call button in a block's Link field (CTA Link, CTA Buttons)
  holds a fixed number: use it only when the design needs that button there, and list it for the builder as a place
  to update if the number changes.
- **The logo symbol is the brand mark for decoration.** Blocks and template parts that print it are marked in
  `CATALOG.md` (Logo symbol): restyle those spots (size, position, opacity, color for an SVG) instead of adding the mark
  again. A new spot for the mark goes in a copied block or template part that prints `logo_symbol` from Site Options
  with `img_print_picture_tag()`, so it follows when the editor replaces the logo. Never the URL of the uploaded logo
  in CSS. Other brand motifs that are not the logo (patterns, dividers, textures) are static files of the child theme,
  in `assets/`, referenced with `{child-theme-path}`.
- **No text in CSS.** `content: "(916) 363-2889"`, `content: "FIRM"` or a firm name in `::before` can't be edited
  and goes out of date. `content` is only for decorative shapes (`content: ""`) and icons.
- **No CSS that depends on the content.** Selectors like `li:nth-child(9)` (the ninth menu item),
  `:has(a[href*="contact"])` or `img[alt*="name"]` break as soon as an editor reorders a menu, changes a link or an
  alt text. Use a class the editor sets (Additional CSS class on a block, CSS Classes on a menu item) or a block option.
- **No patches over logos or images.** Masks that cut a part of the logo and draw another on top break when the logo
  changes. Ask for the right logo file and upload it.
- **No hidden content rebuilt elsewhere.** Hiding a theme element and recreating it with pseudo-elements, or hiding a
  Gravity Form to show another, leaves editors changing fields that no longer show anything.
- **Fonts through the Font Library** (Latin subset WOFF2, every weight in the same family), then chosen in
  Customizer › Fonts. Not `@font-face` rules in the child theme for fonts the editors should be able to change.

## 5. Code standards

The CSS standards are those of `CHILD-THEMES.md`, section 7, and apply in full: SCSS in `styles/`, BEM with a client
prefix, rules scoped to a block or Additional CSS class, no IDs, mobile first with the theme breakpoints, theme tokens,
no layout changes to carousel internals. In addition, for AI-written code:

**CSS**

- **`!important` is not a tool to win.** Allowed only against inline styles you can't change (third-party embeds,
  plugin output) and the reason is written in a comment next to it. If a rule doesn't apply, find the rule it loses to
  and match its scope, or move up the decision ladder.
- **No fixed colors.** Colors are `rgb(var(--primary))`, `rgba(var(--secondary-dark), 0.6)`, etc. A brand color the
  Customizer palette can't hold becomes one custom property of the child theme, defined once in `styles/main.scss`
  (`--client-accent: 201, 178, 124;`) and used everywhere, so it can be changed in one place.
- **No fixed sizes in px for text**: `rem`, and `clamp()` or the `m.title()` mixin for headings.
- **One rule per selector.** Don't redefine the same selector further down the file to fix the one above: edit it.
  No commented-out old versions; git keeps history.
- **Decorative SVGs are files**, in the child theme's `assets/` (or uploaded once to the Media Library), referenced with
  `url("{child-theme-path}/assets/...")`. No data-URI SVGs repeated in several rules. Never an upload URL with a fixed
  year/month path in CSS for something the editor may replace.
- **Small output.** The child CSS is inlined in every page. Prefer a token or a block option over a rule, and a copied
  block over hundreds of override lines.
- **Visual effects keep accessibility.** Text keeps 4.5:1 contrast over photos and overlays, focus outlines stay
  visible, interactive elements are at least 44px, animations respect reduced motion (CHILD-THEMES.md, section 7).

**HTML (block copies, new blocks, template part overrides)**

- Same structure as the parent blocks: the `toggle_block` check, `get_block_fields()`, `get_block_id_attribute()`,
  the block's `className`, `block_style_attribute()`, `print_title()` for titles with their tag field,
  `img_print_picture_tag()` for images, `esc_html()` / `esc_attr()` / `esc_url()` on every printed value that isn't
  formatted content.
- Real elements: `<button>` for actions, `<a href>` for links, headings in order, `alt` on meaningful images and
  `alt=""` with `aria-hidden="true"` on decorative ones.
- Keep the parent's classes in a copied block where the markup is the same, so the theme scripts (accordions, lazy
  images, carousels) keep working.

**JavaScript**

- Only when the design needs behavior no block has. First check `CATALOG.md`: accordions, carousels, the mobile menu,
  lazy images and video lightboxes already exist.
- In the child's `js/` folder, built by the parent's gulp (`CHILD-THEMES.md`, section 2). No inline `<script>` in
  template parts, no scripts in Code Snippets or in the block content.
- Plain modern JavaScript, no libraries for what the platform does, no jQuery. Small: one purpose per function, event
  listeners scoped to the block that needs them, nothing running on pages without that block.
- Never take over the theme's components (Splide carousels, the menu, accordions): extend them with options or classes.

**PHP**

- In the child's `functions.php` or its template parts and blocks, never in Code Snippets: the child theme is versioned
  and deployed with WP Pusher, snippets aren't.
- Image sizes and loading of a theme image are changed by its context (`CATALOG.md`, Images) with
  `theme_set_picture_sizes()` or the `theme_picture_args` filter, never by overriding the template that prints it.
- Hooks and filters of `CHILD-THEMES.md` (section 4) before overriding a file. Override as few template parts as
  possible: an overridden file doesn't get the parent's later fixes.
- No direct database queries, no `eval`, no writing files, no remote requests on page load.

**Comments** in English, explaining why a rule or function exists when it isn't obvious.

## 6. Not allowed

- Editing the parent theme from a site build.
- Inventing content, or publishing mockup placeholders.
- Text, phone numbers, names or links inside CSS.
- `!important` to win specificity; IDs as selectors; selectors that depend on content order or text.
- Fixed hex colors instead of tokens; repeated data-URI SVGs; upload URLs in CSS for replaceable images.
- Changing the layout or behavior of carousel internals and theme scripts.
- Hiding theme elements to rebuild them with pseudo-elements.
- Code in Code Snippets, Customizer › Additional CSS (beyond a quick fix the builder will move), or block HTML.
- Hotlinked, found or AI-generated images.
- Credentials, API keys or tokens in any file of the child theme: its repository is public.

## 7. Before handing over

Check each item and tell the builder the result:

1. Every section of the design maps to a block of `CATALOG.md`, a copied block or a new block (reported).
2. No placeholder, invented stat, sample logo or lorem ipsum anywhere.
3. An editor can change the phone number in Site Options, a block's title and image, a menu's order and the
   Customizer colors, and the design follows without breaking.
4. The child CSS has no `!important` without a comment, no IDs, no fixed hex colors, no text in `content`.
5. The child theme builds (`npm run build:child`) and its compiled files are committed.
6. The pages look right at 390px, 768px and 1440px, with the keyboard focus visible.
7. Nothing was changed in the parent theme.

## 8. Requests for the parent theme

Copied and new blocks are signs of what the parent theme is missing. Each one is registered in the parent theme
requests tracker named in the builder guide, one entry per need (for example "Diamond grid for selling points"), with
a note per site that asked for it: the site, the theme, what the design needed and whether it was solved with a copied
or a new block. When a need reaches three sites, the parent theme developer is told and decides whether it goes into
the themes.
