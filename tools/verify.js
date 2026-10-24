/*
 * Local check before pushing: npm run verify
 *
 * 1. PHP syntax of every theme file (php -l).
 * 2. Builds the theme and the child themes of child-themes.json (see gulpfile.js).
 * 3. Loads the main pages of the local site (tools/verify-urls.php) with the parent theme and, when it exists, with
 *    the local "compat-test" child theme (mu-plugin gl-compat-child.php), and reports:
 *    - pages that fail, PHP errors and warnings printed in the page;
 *    - differences between the parent and the child render (forms, sections, blocks, headings, images);
 *    - elements that disappeared since the last run (baseline saved in .verify-baseline.json, git-ignored).
 *
 * Options: --skip-build, --update (save the current counts as the new baseline even when there are problems).
 * Exit code 1 when there are problems.
 */
const { execFileSync, execSync } = require("child_process");
const fs = require("fs");
const path = require("path");

// Local sites use self-signed certificates (and Node warns about it on every run)
process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
const emitWarning = process.emitWarning;
process.emitWarning = (warning, ...rest) => {
  if (String(warning).includes("NODE_TLS_REJECT_UNAUTHORIZED")) return;
  emitWarning.call(process, warning, ...rest);
};

const root = path.resolve(__dirname, "..");
const args = process.argv.slice(2);
const baselineFile = path.join(root, ".verify-baseline.json");
const problems = [];
const notes = [];

function step(title) {
  console.log(`\n== ${title}`);
}

// 1. PHP syntax
function lintPhp() {
  step("PHP syntax");
  const files = [];
  const walk = (dir) => {
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
      if (["node_modules", ".git"].includes(entry.name)) continue;
      const full = path.join(dir, entry.name);
      if (entry.isDirectory()) walk(full);
      else if (entry.name.endsWith(".php")) files.push(full);
    }
  };
  walk(root);
  for (const file of files) {
    try {
      execFileSync("php", ["-l", file], { stdio: "pipe" });
    } catch (error) {
      problems.push(`PHP syntax: ${path.relative(root, file)}\n    ${String(error.stdout || error.message).trim()}`);
    }
  }
  console.log(`${files.length} files`);
}

// 2. Build
function build() {
  if (args.includes("--skip-build")) return;
  step("Build");
  for (const script of ["build", "build:child"]) {
    try {
      const out = execSync(`npm run ${script}`, { cwd: root, stdio: "pipe" }).toString();
      if (/Error in plugin|Error:/i.test(out)) problems.push(`npm run ${script}:\n${out.split("\n").filter((l) => /error/i.test(l)).join("\n")}`);
      else console.log(`npm run ${script}: ok`);
    } catch (error) {
      problems.push(`npm run ${script} failed:\n${String(error.stdout || error.message).slice(-1500)}`);
    }
  }
}

// 3. Pages
function count(html, regex) {
  return (html.match(regex) || []).length;
}

function pageSignals(html) {
  return {
    sections: count(html, /<section[\s>]/g),
    blocks: count(html, /<section[^>]*class="[^"]*\bblock\b/g),
    forms: count(html, /class="gform_wrapper/g),
    iframes: count(html, /<iframe[\s>]/g),
    h1: count(html, /<h1[\s>]/g),
    images: count(html, /<img[\s>]/g),
    footer: count(html, /<footer[\s>]/g),
    main_css: count(html, /<style id="main-css">/g),
  };
}

function phpErrors(html) {
  const found = [];
  const patterns = [
    /There has been a critical error/i,
    /<b>(Fatal error|Parse error|Warning|Notice|Deprecated)<\/b>:[^<]*<b>[^<]*<\/b>/g,
    /\b(Fatal error|Uncaught \w+Error):[^<\n]{0,160}/g,
  ];
  for (const pattern of patterns) {
    const matches = html.match(pattern);
    if (matches) found.push(...matches.map((m) => m.replace(/<[^>]+>/g, "").slice(0, 200)));
  }
  return [...new Set(found)];
}

async function fetchPage(url, child) {
  const headers = child ? { "X-GL-Child": "compat-test" } : {};
  const response = await fetch(url, { headers, redirect: "follow" });
  return { status: response.status, html: await response.text() };
}

async function checkPages() {
  step("Pages");
  let site;
  try {
    site = JSON.parse(execFileSync("php", [path.join(root, "tools", "verify-urls.php")], { stdio: ["ignore", "pipe", "ignore"] }).toString().trim().split("\n").pop());
  } catch (error) {
    problems.push(`Could not read the site URLs (tools/verify-urls.php): ${error.message}`);
    return {};
  }

  const childDir = path.join(root, "..", `${site.template}-compat-test`);
  const muPlugin = path.join(root, "..", "..", "mu-plugins", "gl-compat-child.php");
  const withChild = fs.existsSync(childDir) && fs.existsSync(muPlugin);
  if (!withChild) notes.push("No compat-test child theme: pages are only checked with the parent theme.");

  const results = {};
  for (const [name, url] of Object.entries(site.urls)) {
    const expected = name === "404" ? 404 : 200;
    const parent = await fetchPage(url, false);
    const entry = { url, parent: pageSignals(parent.html) };

    if (parent.status !== expected) problems.push(`${name}: HTTP ${parent.status} (expected ${expected}) ${url}`);
    for (const error of phpErrors(parent.html)) problems.push(`${name}: PHP error with the parent theme: ${error}`);

    if (withChild) {
      const child = await fetchPage(url, true);
      entry.child = pageSignals(child.html);
      if (child.status !== expected) problems.push(`${name}: HTTP ${child.status} with the child theme ${url}`);
      for (const error of phpErrors(child.html)) problems.push(`${name}: PHP error with the child theme: ${error}`);
      for (const key of ["forms", "blocks", "footer", "main_css", "h1"]) {
        if (entry.child[key] !== entry.parent[key]) {
          problems.push(`${name}: ${key} differ between parent (${entry.parent[key]}) and child (${entry.child[key]}) ${url}`);
        }
      }
      if (!child.html.includes("--gl-compat-child-styles")) problems.push(`${name}: the child theme compiled styles are not printed`);
    }
    results[name] = entry;
    process.stdout.write(".");
  }
  console.log(` ${Object.keys(results).length} pages`);
  return results;
}

// Elements that disappeared since the last run
function compareBaseline(results) {
  if (!fs.existsSync(baselineFile)) {
    notes.push("No baseline yet: this run's counts are saved as the baseline.");
    return;
  }
  const baseline = JSON.parse(fs.readFileSync(baselineFile, "utf8"));
  for (const [name, entry] of Object.entries(results)) {
    const before = baseline[name];
    if (!before) continue;
    for (const theme of ["parent", "child"]) {
      if (!entry[theme] || !before[theme]) continue;
      for (const [key, value] of Object.entries(entry[theme])) {
        if (value < (before[theme][key] ?? 0)) {
          problems.push(`${name} (${theme}): ${key} went from ${before[theme][key]} to ${value} since the last run`);
        }
      }
    }
  }
}

(async () => {
  lintPhp();
  build();
  const results = await checkPages();
  compareBaseline(results);

  step("Result");
  notes.forEach((note) => console.log(`note: ${note}`));
  if (problems.length) {
    problems.forEach((problem) => console.log(`✗ ${problem}`));
    console.log(`\n${problems.length} problem(s).`);
  } else {
    console.log("✓ No problems.");
  }

  // Save the baseline when everything passed (or when asked), so the next run compares with a good state
  if (Object.keys(results).length && (!problems.length || args.includes("--update"))) {
    fs.writeFileSync(baselineFile, JSON.stringify(results, null, 2));
  }
  process.exit(problems.length ? 1 : 0);
})();
