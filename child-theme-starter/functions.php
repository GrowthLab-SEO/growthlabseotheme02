<?php
/**
 * {{name}}: child theme of {{parent}}.
 *
 * The parent theme already loads, from this folder:
 * - styles/main-min.css (built from styles/main.scss) and style.css, inline in the <head>
 * - js/main-min.js (built from js/main.js), deferred in the footer
 * - blocks/<block>/block.json, which also replaces a parent block with the same name
 * - acf-json/, where ACF saves and loads this theme's field groups
 * - template-parts/ and page templates, which replace the parent files with the same path
 *
 * Build the styles and scripts from the parent theme folder:
 *   npm run build:child -- --child=../{{parent}}-{{slug}}
 *
 * Parent functions wrapped in function_exists() can be replaced by declaring a function with the same name here.
 * See CHILD-THEMES.md in the parent theme for what is safe to override.
 */

if (!defined('ABSPATH')) {
    exit;
}
