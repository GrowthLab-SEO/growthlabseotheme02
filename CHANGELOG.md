# Changelog

All notable changes to GrowthLab Theme 02. The version is in `style.css` and goes up with every update that is pushed:
patch (1.0.x) for fixes, minor (1.x.0) for new features or changes editors can see.

## 1.1.0 - 2026-10-23

### Added
- Every Gravity Forms field (Contact Form block, footer form in Site Options and the page footer form in Page - Options)
  has a **Form Type** select: Gravity Form, Custom code or None. **Custom code** shows a **Form Code** textarea for the
  embed code or script of a third-party form (e.g. a CRM), printed as is. Forms saved before this keep their Gravity Form.

### Fixed
- Section content of blocks and the footer columns (Main Content, Text Content, FAQ answers, etc.) is no longer passed
  through `wp_kses_post()`, which removed iframes, scripts and forms pasted by editors. Titles and short card texts keep it.
- Paragraphs with right aligned images float to the right.
- Tapping a default card on mobile reveals its content.
- The team card symbol gets its styles (hidden on mobile, positioned from tablet up).
- The menu close button shows between 1024px and 1199px, where the off-canvas navigation is still used.
- Invalid grid lines in the header (the callout keeps the same size).
- Contrast of hovered options in the styled selects.

### Changed
- Site Options: "Posts Default Title Tag" and "Posts Default Image" are now labeled "Posts Cards Default Title Tag"
  and "Posts Cards Default Image". They only apply to the post cards of the blog, archive and search listings, not to
  the page title in the hero.

### Check after updating
- Pages with embeds in block content (forms, maps, videos): they print again. Remove any workaround that restored them.
- Contact forms: they keep their Gravity Form. To use a third-party form, set Form Type to Custom code.
- Team cards: the symbol is now hidden on mobile and positioned from tablet up.
- Field groups stored in the database: ACF shows "Sync available" for Site Options, Page - Options and
  Block - Contact Form.
