/**
 * Builds CATALOG.md: what a site built on this theme can contain, read from the theme files, so it never goes out of
 * date. AI-RULES.md points to it: the blocks with their fields and options, the heroes, the page templates, the post
 * types, Site Options, the per-page options and the default colors and fonts.
 *
 * Usage: node tools/catalog.js          writes CATALOG.md
 *        node tools/catalog.js --check  exits with 1 when CATALOG.md is out of date (e.g. before a commit)
 *
 * Sources: blocks/<block>/block.json (name, title, description) and block.php (root class), acf-json (fields),
 * page-templates/*.php (Template Name), theme-functions/theme-config.php (default colors and fonts, read with PHP).
 */
const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");

const root = path.join(__dirname, "..");
const outFile = path.join(root, "CATALOG.md");
const read = (file) => fs.readFileSync(path.join(root, file), "utf8");
const exists = (file) => fs.existsSync(path.join(root, file));

// Fields every block has, documented once instead of in every block
const COMMON_BLOCK_FIELDS = ["toggle_block", "block_id", "extract_block_from_content", "place"];
// Field types that only organize the edit screen
const LAYOUT_TYPES = ["message", "accordion", "tab"];

const groups = fs
  .readdirSync(path.join(root, "acf-json"))
  .filter((f) => f.startsWith("group_"))
  .map((f) => JSON.parse(read(`acf-json/${f}`)))
  .filter((g) => g.active !== false);

const postTypes = fs
  .readdirSync(path.join(root, "acf-json"))
  .filter((f) => f.startsWith("post_type_"))
  .map((f) => JSON.parse(read(`acf-json/${f}`)))
  .filter((p) => p.active !== false);

const rules = (group) => (group.location || []).flat();
const groupsWhere = (param, value) =>
  groups.filter((g) => rules(g).some((r) => r.param === param && r.operator === "==" && (value === undefined || r.value === value)));

// Every field key → field, to describe conditional logic by field name instead of key
const fieldsByKey = {};
const indexFields = (fields) =>
  (fields || []).forEach((f) => {
    fieldsByKey[f.key] = f;
    indexFields(f.sub_fields);
    Object.values(f.layouts || {}).forEach((l) => indexFields(l.sub_fields));
  });
groups.forEach((g) => indexFields(g.fields));

const clean = (text) => String(text ?? "").replace(/\s+/g, " ").replace(/\|/g, "\\|").trim();

// Conditions are described against the fields of the same group: ACF only evaluates those, so a condition on a field
// of another group (left over from copying a group) is reported as broken
function describeCondition(logic, groupKeys) {
  if (!Array.isArray(logic) || !logic.length) return "";
  const choiceLabel = (field, value) => (field?.choices && field.choices[value] ? `"${field.choices[value]}"` : `"${value}"`);
  const isToggle = (r) => fieldsByKey[r.field]?.name === "toggle_block";
  const rule = (r) => {
    if (groupKeys && !groupKeys.has(r.field)) return `⚠ broken condition (field ${r.field} is not in this group)`;
    const field = fieldsByKey[r.field];
    const name = field?.name || field?.label || r.field;
    if (field?.type === "true_false") return `${name} ${r.value == "1" === (r.operator === "==") ? "on" : "off"}`;
    if (r.operator === "!=empty") return `${name} set`;
    if (r.operator === "==empty") return `${name} empty`;
    return `${name} ${r.operator === "==" ? "is" : "is not"} ${choiceLabel(field, r.value)}`;
  };
  // Every block field shows only with toggle_block on, so that part of a condition says nothing
  const ors = logic.map((and) => and.filter((r) => !isToggle(r)));
  if (ors.some((and) => !and.length)) return "";
  return ors.map((and) => and.map(rule).join(" and ")).join(" or ");
}

function describeType(f) {
  const extras = [];
  if (f.type === "post_object" || f.type === "relationship") extras.push(`of ${[].concat(f.post_type || "any").join(", ")}`);
  if (f.type === "repeater" && (f.min || f.max)) extras.push(`${f.min || 0}-${f.max || "∞"} rows`);
  if (f.type === "clone") {
    // A clone of a whole group's fields is named by its group; else by the fields it copies
    const keys = [].concat(f.clone || []);
    const group = groups.find((g) => g.fields.some((x) => keys.includes(x.key) || keys.includes(x.name)));
    const named = keys.map((k) => fieldsByKey[k] || Object.values(fieldsByKey).find((x) => x.name === k)).filter((x) => x && !LAYOUT_TYPES.includes(x.type));
    extras.push(group && named.length > 5 ? `of the ${clean(group.title)} fields` : `of ${named.map((x) => x.name).join(", ")}`);
  }
  return extras.length ? `${f.type} (${extras.join(", ")})` : f.type;
}

function describeOptions(f, groupKeys) {
  const parts = [];
  if (f.choices && Object.keys(f.choices).length) {
    const list = Object.entries(f.choices).map(([value, label]) => (value === label ? `\`${value}\`` : `\`${value}\` ${label}`));
    parts.push(list.join(", "));
  }
  const def = f.default_value;
  if (def !== undefined && def !== "" && def !== null && def !== false && !(Array.isArray(def) && !def.length)) {
    parts.push(`default: ${f.type === "true_false" ? (def == 1 ? "on" : "off") : `\`${clean(JSON.stringify(def)).replace(/^"|"$/g, "")}\``}`);
  }
  if (f.instructions) parts.push(clean(f.instructions));
  const when = describeCondition(f.conditional_logic, groupKeys);
  if (when) parts.push(`shown when ${when}`);
  return parts.map((p) => p.replace(/[.\s]+$/, "")).join(". ");
}

// Markdown table of a field list; repeaters and groups list their sub fields indented under them
function fieldTable(fields, skip = []) {
  const rows = [];
  const groupKeys = new Set();
  const collect = (list) =>
    (list || []).forEach((f) => {
      groupKeys.add(f.key);
      collect(f.sub_fields);
      Object.values(f.layouts || {}).forEach((l) => collect(l.sub_fields));
    });
  collect(fields);
  const walk = (list, depth) =>
    (list || []).forEach((f) => {
      if (LAYOUT_TYPES.includes(f.type) || skip.includes(f.name)) return;
      const name = `${"↳ ".repeat(depth)}\`${f.name}\``;
      rows.push(`| ${name} | ${clean(f.label)} | ${describeType(f)} | ${describeOptions(f, groupKeys)} |`);
      walk(f.sub_fields, depth + 1);
      Object.values(f.layouts || {}).forEach((l) => {
        rows.push(`| ${"↳ ".repeat(depth + 1)}layout \`${l.name}\` | ${clean(l.label)} | layout | |`);
        walk(l.sub_fields, depth + 2);
      });
    });
  walk(fields, 0);
  if (!rows.length) return "_No fields._\n";
  return ["| Field | Label | Type | Options and notes |", "|---|---|---|---|", ...rows].join("\n") + "\n";
}

// Root class of a block: the classes of its first <section> (or first element with class), without PHP
function blockRootClass(dir) {
  const file = `blocks/${dir}/block.php`;
  if (!exists(file)) return "";
  // PHP inside the opening tag can hold ">" and quotes, so drop it first and take the first class after <section
  const php = read(file);
  const start = php.search(/<section\b/);
  if (start < 0) return "";
  const m = php.slice(start).replace(/<\?(?:php|=)?[\s\S]*?\?>/g, " ").match(/class="([^"]*)"/s);
  if (!m) return "";
  return m[1].split(/\s+/).filter((c) => /^[a-z][\w-]*$/.test(c)).join(" ");
}

const printsLogoSymbol = (file) => exists(file) && read(file).includes("logo_symbol");

// Theme PHP files (functions.php and theme-functions/), where shortcodes are registered
function themePhpFiles() {
  const dir = path.join(root, "theme-functions");
  return ["functions.php", ...(fs.existsSync(dir) ? fs.readdirSync(dir).filter((f) => f.endsWith(".php")).map((f) => `theme-functions/${f}`) : [])];
}

// Shortcodes: name, attributes (from shortcode_atts) and the comment above the function
function shortcodesSection() {
  const found = [];
  for (const file of themePhpFiles()) {
    const php = read(file);
    for (const m of php.matchAll(/add_shortcode\(\s*['"]([\w-]+)['"]\s*,\s*['"]([\w]+)['"]/g)) {
      const [, tag, fn] = m;
      const at = php.search(new RegExp(`function\\s+${fn}\\s*\\(`));
      if (at < 0) { found.push({ tag, atts: [], doc: "" }); continue; }
      const body = php.slice(at, at + 3000);
      const atts = [...(body.match(/shortcode_atts\(\s*array\(([\s\S]*?)\)\s*,/)?.[1] || "").matchAll(/['"]([\w-]+)['"]\s*=>\s*([^,\n]+)/g)]
        .map(([, name, value]) => `\`${name}\` (default ${value.trim()})`);
      const doc = php.slice(0, at).split("\n").reverse().slice(1)
        .reduce((acc, line) => (acc.done || !/^\s*\/\//.test(line) ? { ...acc, done: true } : { lines: [line.replace(/^\s*\/\/\s?/, ""), ...acc.lines] }), { lines: [] })
        .lines.join(" ");
      found.push({ tag, atts, doc: clean(doc.replace(/^\[[\w-]+\]\s*shortcode:\s*/i, "")) });
    }
  }
  const out = ["## Shortcodes", ""];
  if (!found.length) return out.concat("_None._", "").join("\n");
  out.push(
    "They work in WYSIWYG fields and in the post content, not in Link, Text or URL fields (a button of a Link field " +
      "can't follow Site Options: put the link in a WYSIWYG field with the shortcode instead, styled with its class " +
      "attribute, e.g. `link_class=\"btn btn--primary\"`).",
    ""
  );
  for (const s of found) {
    out.push(`- \`[${s.tag}]\`: ${s.doc || "_No comment above its function._"}${s.atts.length ? ` Attributes: ${s.atts.join(", ")}.` : ""}`);
  }
  out.push("");
  return out.join("\n");
}

// Template parts that print the Site Options logo symbol (the blocks say it in their own entry)
function logoSymbolSection() {
  const parts = fs.readdirSync(path.join(root, "template-parts")).filter((f) => f.endsWith(".php") && printsLogoSymbol(`template-parts/${f}`));
  return [
    "## Logo symbol",
    "",
    "The logo symbol (Site Options, `logo_symbol`) is the brand mark the theme prints as decoration. Editors replace it in " +
      "Site Options and every place follows. Blocks that print it say so in their entry; template parts that print it: " +
      (parts.length ? parts.map((p) => `\`template-parts/${p}\``).join(", ") : "none") + ".",
    "",
  ].join("\n");
}

function blocksSection() {
  const out = ["## Blocks", ""];
  out.push(
    "Every block has these properties, so they are not repeated below: `toggle_block` (the block prints only when it is on; " +
      "a block can be kept in a page turned off until its content exists), `block_id` (the section's HTML id, used as an " +
      "anchor and to target the section in CSS), `extract_block_from_content` and `place` (move the section out of the " +
      "content column, before or after it, on pages with a sidebar). Blocks also take the editor's Additional CSS class.",
    ""
  );
  const dirs = fs.readdirSync(path.join(root, "blocks")).filter((d) => exists(`blocks/${d}/block.json`)).sort();
  for (const dir of dirs) {
    const meta = JSON.parse(read(`blocks/${dir}/block.json`));
    const fieldGroups = groupsWhere("block", meta.name);
    out.push(`### ${meta.title} (\`${meta.name}\`)`, "");
    out.push(meta.description ? clean(meta.description) : "_No description in block.json._", "");
    const rootClass = blockRootClass(dir);
    if (rootClass) out.push(`Root classes: \`${rootClass}\`. Files: \`blocks/${dir}/\`.`, "");
    if (printsLogoSymbol(`blocks/${dir}/block.php`)) out.push("Prints the logo symbol of Site Options (`logo_symbol`).", "");
    for (const g of fieldGroups) out.push(fieldTable(g.fields, COMMON_BLOCK_FIELDS));
    if (!fieldGroups.length) out.push("_No field group._", "");
  }
  return out.join("\n");
}

function heroesSection() {
  const out = ["## Heroes", ""];
  const heroStyle = Object.values(fieldsByKey).find((f) => f.name === "hero_style" && f.choices);
  const parts = fs.readdirSync(path.join(root, "template-parts")).filter((f) => f.startsWith("hero-")).sort();
  out.push(
    "Every page has a hero, chosen in its Page - Options (`hero_style`). Its title, texts, images and buttons are fields " +
      "of that group (see Per-page options).",
    ""
  );
  if (heroStyle) {
    out.push("| `hero_style` value | Name |", "|---|---|");
    for (const [value, label] of Object.entries(heroStyle.choices)) out.push(`| \`${value}\` | ${clean(label)} |`);
    out.push("");
  }
  out.push(`Template parts: ${parts.map((p) => `\`template-parts/${p}\``).join(", ")}.`, "");
  return out.join("\n");
}

function templatesSection() {
  const out = ["## Page templates", "", "| File | Template Name |", "|---|---|"];
  const dir = path.join(root, "page-templates");
  const files = fs.existsSync(dir) ? fs.readdirSync(dir).filter((f) => f.endsWith(".php")).sort() : [];
  for (const f of files) {
    const name = read(`page-templates/${f}`).match(/Template Name:\s*(.+)/)?.[1]?.replace(/\*\/.*$/, "").trim() || "";
    out.push(`| \`page-templates/${f}\` | ${clean(name)} |`);
  }
  out.push("| (none) | Default template: content with the sidebar |", "");
  out.push(
    "Other templates WordPress picks by itself: `front-page.php`/`page.php` (pages), `single.php` (posts), " +
      "`home.php`, `archive.php`, `search.php` (post listings), `single-<post type>.php` and `404.php` when present: " +
      fs.readdirSync(root).filter((f) => /^(single|archive|taxonomy)-.+\.php$/.test(f)).map((f) => `\`${f}\``).join(", ") + ".",
    ""
  );
  return out.join("\n");
}

function postTypesSection() {
  const out = ["## Post types", ""];
  for (const p of postTypes) {
    const labels = p.labels || {};
    out.push(`### ${clean(p.title || labels.name)} (\`${p.post_type}\`)`, "");
    const supports = [].concat(p.supports || []).join(", ");
    if (supports) out.push(`Supports: ${supports}. Public: ${p.public !== false ? "yes" : "no"}. Archive: ${p.has_archive ? "yes" : "no"}.`, "");
    for (const g of groupsWhere("post_type", p.post_type)) {
      if (rules(g).some((r) => r.param === "post_type" && r.value === "page")) continue; // shared page options
      out.push(`Fields (${clean(g.title)}):`, "", fieldTable(g.fields));
    }
  }
  out.push("Posts (`post`) and pages (`page`) are the WordPress ones.", "");
  return out.join("\n");
}

function optionsSection(title, intro, list) {
  const out = [`## ${title}`, "", intro, ""];
  for (const g of list) out.push(`### ${clean(g.title)}`, "", fieldTable(g.fields));
  return out.join("\n");
}

function designDefaultsSection() {
  const out = ["## Customizer defaults", ""];
  let data = null;
  try {
    const code =
      "define('ABSPATH', 1); require 'theme-functions/theme-config.php';" +
      "echo json_encode(['colors' => $default_colors, 'fonts' => $default_fonts]);";
    data = JSON.parse(execFileSync("php", ["-r", code], { cwd: root, encoding: "utf8" }));
  } catch {
    out.push("_Could not read theme-functions/theme-config.php (PHP not available)._", "");
    return out.join("\n");
  }
  out.push(
    "Colors and fonts are chosen in Appearance › Customize (Colors, Fonts) and printed as custom properties " +
      "(`--primary`, `--primary-dark`, …, `--font-primary`, `--font-secondary`; see CHILD-THEMES.md). The theme defaults:",
    "",
    "| Color | Default | Dark | Light |",
    "|---|---|---|---|"
  );
  for (const [name, value] of Object.entries(data.colors)) {
    if (typeof value === "string") out.push(`| \`${name}\` | ${value} | | |`);
    else out.push(`| \`${name}\` | ${value.default} | ${value.dark} | ${value.light} |`);
  }
  out.push("", `Fonts: primary (body text) ${data.fonts.primary}, secondary (headings) ${data.fonts.secondary}.`, "");
  return out.join("\n");
}

const theme = path.basename(root);
const pageGroups = groups.filter((g) => rules(g).some((r) => r.param === "post_type" && r.value === "page"));
const optionGroups = groupsWhere("options_page");

const md = [
  `# Catalog: ${theme}`,
  "",
  "Generated by `tools/catalog.js` from the theme files (`npm run build` runs it). Don't edit it by hand: change the " +
    "blocks, the field groups or the block descriptions (`description` in each block.json) and build again.",
  "",
  "What a site built on this theme can contain. A design that needs something not listed here follows the decision " +
    "ladder in AI-RULES.md.",
  "",
  blocksSection(),
  heroesSection(),
  templatesSection(),
  postTypesSection(),
  optionsSection(
    "Per-page options",
    "Fields of every page (and of the post types listed in each group): its hero, sidebar and footer options.",
    pageGroups
  ),
  optionsSection(
    "Site Options",
    "Site-wide content, in the admin menu Site Options: logos, phone numbers, offices, social networks, the footer and " +
      "its form, default images. Anything a page shows from these fields is edited there once for the whole site.",
    optionGroups
  ),
  shortcodesSection(),
  logoSymbolSection(),
  designDefaultsSection(),
]
  .join("\n")
  .replace(/\n{3,}/g, "\n\n");

if (process.argv.includes("--check")) {
  const current = fs.existsSync(outFile) ? fs.readFileSync(outFile, "utf8").replace(/\r\n/g, "\n") : "";
  if (current !== md) {
    console.error("CATALOG.md is out of date: run node tools/catalog.js");
    process.exit(1);
  }
  console.log("CATALOG.md is up to date");
} else {
  fs.writeFileSync(outFile, md);
  console.log(`CATALOG.md written (${md.split("\n").length} lines)`);
}
