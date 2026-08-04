# Plan: Timeline Block (`wp-usefull-blocks/ub-timeline`)

> Status: **Planning** (no implementation yet). This document is saved plan data so a
> later session can resume it. It builds on the plugin scaffold from PR #1.
>
> **Naming:** per the `ub-` convention (`docs/plans/conventions.md`) this block is
> `wp-usefull-blocks/ub-timeline`, folder `src/ub-timeline/`. (Earlier references to
> `timeline` / `src/timeline/` below should be read with the `ub-` prefix.)

## 1. Goal

A Gutenberg block that renders a **timeline** of entries. Each entry has a **title** and a
**description**. On the front end, clicking an entry's title toggles the visibility of its
description. The block author picks the layout **orientation** (horizontal or vertical).

Editing happens through a **two-column table** (Title, Description) inside the editor. Below
the table, a **live preview** shows exactly what the front end will look like, using the
**same rendering code** as the front end. Output must be fast (caching + conditional asset
loading + cache-friendly interactivity).

## 2. Requirements (restated)

- Timeline block with multiple entries; each entry = `{ title, description }`.
- Orientation selectable per block: `horizontal` | `vertical`.
- Front end: click title → show/hide its description (progressive enhancement).
- Editing UI: a table with two data columns (Title, Description) + add/remove/reorder rows.
- A rendered preview under the table that is visually identical to the front end and reuses
  the front-end rendering code (single source of truth).
- Performance: caching and conditional asset loading so pages render as fast as possible.
- "As nice as possible": polished, accessible, responsive styling.

## 3. Architecture decision

**Chosen approach: a dynamic block rendered in PHP (`render.php`), made interactive with the
WordPress Interactivity API, with the editor preview driven by `ServerSideRender`.**

Why this satisfies "preview uses the same code as the front end":

- The front end is rendered by a single PHP render (`render.php`).
- The editor preview uses `@wordpress/server-side-render`, which calls that exact PHP via the
  REST `block-renderer` endpoint. The preview markup is therefore produced by the same code —
  guaranteed identical, no divergence risk.

Why the Interactivity API for the click-to-toggle:

- It is declarative (directives in the server-rendered HTML), needs **no per-request server
  state**, and toggling happens entirely client-side. This is fully compatible with full-page
  / CDN caching, which is key to the performance requirement.

### Alternatives considered (and why not)

- **Static block (`save.js`) with a shared React component for edit + save.** Works, but the
  front-end interaction still needs a view script, and keeping `save.js` markup byte-identical
  to an editor preview is fragile (block validation / deprecations). PHP + SSR is a stronger
  "same code" guarantee and more cache-friendly.
- **Client-side rendered block (empty save, render in JS on the front end).** Bad for SEO,
  performance, and full-page caching. Rejected.

### Known trade-off

`ServerSideRender` shows identical **markup/styling**, but the Interactivity API directives are
not hydrated inside the editor preview, so click-to-toggle is not live in the preview. The
preview will look exactly like the front end; interaction is demonstrated on the front end. If
a live-interactive preview is required, we can additionally render all descriptions expanded in
the preview (a `context.isEditor` flag) — see Open Questions.

## 4. Data model (block attributes)

Defined in `block.json` (single source of truth):

- `orientation`: `string`, enum `"vertical" | "horizontal"`, default `"vertical"`.
- `items`: `array` of objects, default `[]`. Each item:
  - `title`: `string`
  - `description`: `string`
  - `id`: `string` (stable key for React lists / directives; generated on add)
- `initiallyOpen`: `boolean`, default `false` (whether descriptions start expanded). Optional.

No block-level heading is included (spec is a table of title/description rows). Adding an
optional overall title is an open question.

## 5. Editor UX (`edit.js`)

- **Table editor** built with `@wordpress/components`:
  - Columns: **Title** (`TextControl`) and **Description** (`TextareaControl`).
  - A small actions column: remove row, move up/down (reordering).
  - "Add entry" button below the table.
- **Orientation control** in `InspectorControls` (settings sidebar), using
  `ToggleGroupControl` with `horizontal` / `vertical` options. (Per project rules, settings
  belong in the sidebar; a block-toolbar shortcut may be added later.)
- **Preview**: below the table, `<ServerSideRender block="wp-usefull-blocks/timeline"
  attributes={attributes} />` renders the real front-end output.
- All UI strings via `@wordpress/i18n` `__()` with text domain `wp-usefull-blocks`.

## 6. Front-end rendering (`render.php`) & interactivity

- `render.php` builds the timeline HTML from `$attributes`:
  - Root wrapper via `get_block_wrapper_attributes()` with orientation modifier class
    (`is-orientation-horizontal` / `is-orientation-vertical`) and Interactivity API attributes
    (`data-wp-interactive`, per-item `data-wp-context`).
  - Each entry: a `<button>` title (keyboard-accessible) with `data-wp-on--click` to toggle,
    `data-wp-bind--aria-expanded`, and a description region with `data-wp-bind--hidden` /
    `data-wp-class--is-open`, `role="region"`, `aria-labelledby`.
- **Security (WPCS):** escape all output — `esc_html()` for text, `esc_attr()` for attributes;
  descriptions are treated as plain text (or `wp_kses_post()` if limited HTML is allowed — see
  Open Questions). No raw echo of user input.
- `view.js` (registered as `viewScriptModule`) registers the Interactivity API store with a
  `toggle` action flipping `context.isOpen`. Loaded as an ES module only when the block is on
  the page.
- `block.json`: `"render": "file:./render.php"`, `"viewScriptModule": "file:./view.js"`,
  `"supports": { "interactivity": true, "html": false }`.

## 7. Styling (`style.scss` shared, `editor.scss` editor-only)

- **Vertical**: entries stacked; a vertical connector line with dots/markers per entry.
- **Horizontal**: entries in a flex row along a horizontal connector; horizontally scrollable
  on overflow.
- **Responsive**: horizontal collapses to vertical (or becomes scrollable) below a breakpoint.
- **Accessibility**: titles are real `<button>`s (Enter/Space work), visible focus styles,
  `aria-expanded`/`aria-controls`, smooth height/opacity transition, and
  `@media (prefers-reduced-motion: reduce)` to disable animation.
- `style.scss` applies to both editor preview and front end (loaded via `block.json` `style`);
  `editor.scss` styles only the table-editing UI.

## 8. Performance & caching

- **Conditional assets:** `block.json` registration means the style and `viewScriptModule` load
  only on pages/posts that actually contain the block.
- **Cache-friendly interactivity:** the Interactivity API toggles client-side with no server
  round-trip, so output is safe to serve from full-page/CDN caches.
- **Server render cache:** in `render.php`, cache the generated HTML in a transient keyed by a
  hash of the attributes (`md5( wp_json_encode( $attributes ) )`) plus a version constant.
  Because the key is derived from the content, edits naturally produce a new key (self
  -invalidating); a filterable TTL (default `WEEK_IN_SECONDS`) bounds staleness. Uses the
  object cache when available.
- **Lightweight CSS/JS:** no external libraries; minimal, dependency-free code.

## 9. File structure (new block)

Added under the existing plugin (blocks are auto-registered from `build/` via
`wp_register_block_types_from_metadata_collection` — see `wp-usefull-blocks.php`):

```
src/timeline/
  block.json      # metadata, attributes, render/view/style refs
  index.js        # registerBlockType (edit only; dynamic block has no save)
  edit.js         # table editor + InspectorControls + ServerSideRender preview
  render.php      # shared front-end render (escaping + caching + Interactivity markup)
  view.js         # Interactivity API store/actions (viewScriptModule)
  style.scss      # shared front-end + editor styling
  editor.scss     # editor-only (table UI) styling
```

New dependency: `@wordpress/server-side-render` (for the preview).

## 10. Internationalisation

- All user-facing strings wrapped in i18n APIs with text domain `wp-usefull-blocks` from the
  start.
- Per project rules, translations (POT/PO/MO/JSON) are **not** regenerated on each UI change;
  a single bundled translation pass is done before release.

## 11. Admin menu / settings

- The Timeline block itself needs no dedicated settings to function. However, the separate
  admin plan (`docs/plans/admin-settings-and-headings.md`) introduces an **own top-level
  "Usefull Blocks" menu** with a Settings page that lists a **per-block section** for every
  plugin block. Timeline will therefore get a placeholder settings section (headline only)
  until it has specific settings. Any required setting will be noted in the FAQ (per rules).

## 12. Testing plan (for the implementation phase)

- **JS unit tests** (`wp-scripts test-unit-js`): attribute defaults, add/remove/reorder row
  logic, orientation switching (with `@wordpress/block-editor` mocked, per AGENTS.md).
- **PHP render check**: assert escaped output, orientation class, and per-item markup.
- **Lint + build**: `npm run lint`, `npm run build`.
- **End-to-end in `wp-now`**: insert block, fill the table, switch orientation, confirm the
  preview matches, publish, then on the front end click a title to toggle its description.
  Capture a video artifact.

## 13. Release & versioning (per rules)

- Target version **0.2.0** (new feature → next `*.*.0`).
- Before release: bundled i18n pass (POT/PO/MO/JSON), update `Readme.md`, `readme.txt`
  changelog, wiki, and project page.
- Build the release ZIP via `git archive` with the correct `wp-usefull-blocks/` folder prefix.

## 14. Implementation task breakdown

1. Add `src/timeline/block.json` (attributes, render/view/style, interactivity support).
2. Implement `render.php` (markup + Interactivity directives + escaping + transient caching).
3. Implement `view.js` (Interactivity store: `toggle`).
4. Implement `edit.js` (table editor + orientation control + `ServerSideRender` preview).
5. Add `style.scss` (vertical + horizontal + responsive + a11y) and `editor.scss` (table UI).
6. Add `@wordpress/server-side-render` dependency.
7. Build, lint, unit tests.
8. E2E test in `wp-now`; capture demo.
9. Pre-release: i18n pass, docs/changelog, version bump to 0.2.0, release ZIP.

## 15. Open questions / assumptions

Defaults I will use unless you say otherwise:

1. **Toggle behavior:** independent toggles (multiple descriptions can be open at once), all
   collapsed initially. Alternative: accordion (only one open at a time). — *Assume independent.*
2. **Description content:** plain text only (safer, simpler). Alternative: allow limited HTML
   via `RichText` + `wp_kses_post()`. — *Assume plain text.*
3. **Horizontal on mobile:** collapse to vertical below a breakpoint. Alternative: keep
   horizontal with touch scroll. — *Assume collapse to vertical.*
4. **Preview interactivity:** SSR preview is visually identical but not click-interactive.
   *Assume acceptable*; optionally render descriptions expanded in preview.
5. **Optional overall block title/description** above the entries: *assume not needed* (spec is
   the entries table only).
