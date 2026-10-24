# Child theme blocks

Each block lives in its own folder, like the parent theme blocks:

```
blocks/block-example/
├── block.json      "name": "acf/example", "acf": { "renderTemplate": "block.php" }, "style": "file:./block-min.css"
├── block.php       markup (ACF fields with get_field())
├── block.scss      built to block-min.css, inlined in the <head> only on pages that use the block
└── block.js        optional, built to block-min.js ("viewScript": "file:./block-min.js")
```

- The parent theme registers every `blocks/*/block.json` of the child theme.
- A block with the same `name` as a parent block (e.g. `acf/logos-carousel`) replaces it, so copy the parent block
  folder and change what you need.
- Save the block field group from the ACF admin: it goes to this theme's `acf-json/` folder.
- `block.scss` can `@use "globals/variables" as v;` and `@use "globals/mixins" as m;` from the parent theme.
