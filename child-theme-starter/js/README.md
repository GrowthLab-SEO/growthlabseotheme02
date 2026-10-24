# Child theme scripts

Create `main.js` here when the child theme needs JavaScript. The parent theme builds it to `main-min.js` and enqueues
it deferred in the footer, after the parent scripts (`npm run build:child` from the parent theme folder).

There is no `main.js` by default, so sites without custom scripts don't load an extra file.

Other files in this folder are built too (`name.js` to `name-min.js`), but only `main-min.js` is enqueued: enqueue the
others in `functions.php` when a page needs them.
