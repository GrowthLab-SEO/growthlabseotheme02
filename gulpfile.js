const { parallel, series, src, dest, watch } = require("gulp");
const sass = require("gulp-sass")(require("sass"));
const terser = require("gulp-terser");
const sourcemaps = require("gulp-sourcemaps");
const rename = require("gulp-rename");
const postcss = require("gulp-postcss");
const autoprefixer = require("autoprefixer");
const path = require("path");
const fs = require("fs");

// Sass reports imported partials with absolute paths, which differ between machines and builds.
// Write every source relative to the stream base, like the entry files.
function relativeSource(sourcePath, file) {
  if (!path.isAbsolute(sourcePath)) return sourcePath;
  return path.relative(file.base, sourcePath).split(path.sep).join("/");
}

// Source globs of a theme folder: this theme or a child theme with the same structure
function themePaths(root) {
  return {
    scss_globals: [`${root}/styles/**/*.scss`],
    scss_blocks: [`${root}/blocks/**/*.scss`],
    js: [`${root}/js/**/*.js`, `!${root}/js/**/*-min.js`, `!${root}/js/**/*.min.js`],
    js_blocks: [`${root}/blocks/**/*.js`, `!${root}/blocks/**/*-min.js`, `!${root}/blocks/**/*.min.js`],
  };
}

// The partials of this theme ("globals/variables", "globals/mixins", "template-parts/...") can be used from any
// stylesheet, including the ones of child themes
const sassOptions = { style: "compressed", loadPaths: [path.join(__dirname, "styles")] };

function addMinSuffix(file) {
  file.basename += "-min";
}

function stylesTask(globs, outDir, name) {
  const task = () =>
    src(globs, { allowEmpty: true })
      .pipe(sourcemaps.init())
      .pipe(sass(sassOptions).on("error", sass.logError))
      .pipe(postcss([autoprefixer()]))
      .pipe(rename(addMinSuffix))
      .pipe(sourcemaps.mapSources(relativeSource))
      .pipe(sourcemaps.write("./"))
      .pipe(dest(outDir));
  task.displayName = name;
  return task;
}

function scriptsTask(globs, outDir, name) {
  const task = () =>
    src(globs, { allowEmpty: true })
      .pipe(sourcemaps.init())
      .pipe(terser())
      .pipe(rename(addMinSuffix))
      .pipe(sourcemaps.write("./"))
      .pipe(dest(outDir));
  task.displayName = name;
  return task;
}

// Build and watch tasks of a theme folder
function themeTasks(root, label) {
  const paths = themePaths(root);
  const tasks = {
    styles: stylesTask(paths.scss_globals, `${root}/styles/`, `${label}:styles`),
    stylesBlocks: stylesTask(paths.scss_blocks, `${root}/blocks/`, `${label}:styles-blocks`),
    scripts: scriptsTask(paths.js, `${root}/js/`, `${label}:scripts`),
    scriptsBlocks: scriptsTask(paths.js_blocks, `${root}/blocks/`, `${label}:scripts-blocks`),
  };
  tasks.build = parallel(tasks.styles, tasks.stylesBlocks, tasks.scripts, tasks.scriptsBlocks);
  tasks.watch = (done) => {
    watch(paths.scss_globals, tasks.styles);
    watch(paths.scss_globals, tasks.stylesBlocks);
    watch(paths.scss_blocks, tasks.stylesBlocks);
    watch(paths.js, tasks.scripts);
    watch(paths.js_blocks, tasks.scriptsBlocks);
    done();
  };
  return tasks;
}

const theme = themeTasks(".", "theme");

// One-shot build (npm run build)
exports.build = theme.build;
// Build once, then rebuild on changes (npm run watch / npx gulp)
exports.default = series(theme.build, theme.watch);

/*
 * Child themes
 *
 * A child theme is built with this gulpfile, so it doesn't need its own Node setup. It mirrors this theme:
 *   styles/main.scss   -> styles/main-min.css   printed inline after the parent styles (other files: styles/*-min.css)
 *   js/main.js         -> js/main-min.js        enqueued after the parent scripts
 *   blocks/<block>/    -> *-min.css / *-min.js  registered by the parent from block.json
 * Its stylesheets can @use this theme's partials, e.g. @use "globals/variables" as v;
 *
 * Which child themes to build, by priority:
 *   npm run build:child -- --child=../growthlabseotheme03-child   (the option can be repeated)
 *   CHILD_THEMES="../a-child;../b-child" npm run build:child
 *   child-themes.json in this folder: ["../growthlabseotheme03-child"]  (git-ignored: the paths are local)
 */
function childThemeRoots() {
  const fromArgs = process.argv.filter((arg) => arg.startsWith("--child=")).map((arg) => arg.slice(8));
  const fromEnv = (process.env.CHILD_THEMES || "").split(";").filter(Boolean);
  const configFile = path.join(__dirname, "child-themes.json");
  const fromFile = fs.existsSync(configFile) ? JSON.parse(fs.readFileSync(configFile, "utf8")) : [];
  const roots = fromArgs.length ? fromArgs : fromEnv.length ? fromEnv : fromFile;

  return roots.map((root) => {
    const absolute = path.resolve(__dirname, root);
    if (!fs.existsSync(path.join(absolute, "style.css"))) {
      throw new Error(`Not a child theme folder (no style.css): ${absolute}`);
    }
    // gulp globs use forward slashes
    return absolute.split(path.sep).join("/");
  });
}

function childTasks(kind) {
  return (done) => {
    const roots = childThemeRoots();
    if (!roots.length) {
      console.log("No child theme to build: pass --child=<path>, set CHILD_THEMES or create child-themes.json");
      return done();
    }
    const tasks = roots.map((root) => themeTasks(root, path.basename(root))[kind]);
    return parallel(...tasks)(done);
  };
}

exports.buildChild = childTasks("build");
exports.watchChild = series(childTasks("build"), childTasks("watch"));

/*
 * New child theme from child-theme-starter/:
 *   npm run create-child -- --name="Client Name" [--slug=client-name] [--dir=../]
 * Creates ../<this theme>-<slug> (default slug: "child") with the Template header set to this theme.
 */
function createChild(done) {
  const arg = (name) => (process.argv.find((a) => a.startsWith(`--${name}=`)) || "").split("=").slice(1).join("=");
  const parentSlug = path.basename(__dirname);
  const name = arg("name") || "Child";
  const slug = arg("slug") || (arg("name") ? name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "") : "child");
  const target = path.resolve(__dirname, arg("dir") || "..", `${parentSlug}-${slug}`);

  if (fs.existsSync(target)) return done(new Error(`Folder already exists: ${target}`));

  const starter = path.join(__dirname, "child-theme-starter");
  const replace = (text) =>
    text
      .replace(/\{\{parent\}\}/g, parentSlug)
      .replace(/\{\{name\}\}/g, name)
      .replace(/\{\{slug\}\}/g, slug)
      .replace(/\{\{function_prefix\}\}/g, `${parentSlug}_${slug}`.replace(/[^a-z0-9_]/gi, "_"));

  const copy = (from, to) => {
    fs.mkdirSync(to, { recursive: true });
    for (const entry of fs.readdirSync(from, { withFileTypes: true })) {
      const source = path.join(from, entry.name);
      const destination = path.join(to, entry.name);
      if (entry.isDirectory()) copy(source, destination);
      else fs.writeFileSync(destination, replace(fs.readFileSync(source, "utf8")));
    }
  };
  copy(starter, target);

  console.log(`Created ${target}`);
  console.log(`Build it with: npm run build:child -- --child=${path.relative(__dirname, target).split(path.sep).join("/")}`);
  done();
}

exports.createChild = createChild;
