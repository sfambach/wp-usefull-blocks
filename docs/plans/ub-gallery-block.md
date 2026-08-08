# Plan: UB Gallery block (`wp-usefull-blocks/ub-gallery`)

> Status: **Planning** (no implementation yet). Saved plan data. Follows the `ub-` naming
> convention (`docs/plans/conventions.md`) and the default block architecture.

## 1. Goal

An image gallery block that works like the standard WordPress image gallery for **picking and
managing images**, but displays them in an **eBay-style single-focus layout**:

- One image shown large ("in focus") at the top.
- The other images shown as a **selectable thumbnail strip below**.
- Clicking a thumbnail swaps it into the large focus position.
- Clicking the large image either **opens an enlargement (lightbox)** or **links to the media
  file** — configurable (see Open Questions; the author is undecided).

## 2. Data model (block attributes)

Modelled after the classic self-contained gallery (an images array — simpler for a custom
display than nesting `core/image` inner blocks):

- `images`: `array` of objects, default `[]`. Each: `{ id, url, alt, caption, fullUrl }`.
- `selectedIndex`: initial focus index, default `0` (front-end selection is client-side state).
- `onImageClick`: `string` enum `"lightbox" | "media" | "none"`, default `"lightbox"`
  (behavior when the large image is clicked). *Default pending confirmation.*
- `thumbnailSize` / `focusSize`: registered image sizes used for thumbs vs the focus image
  (for correct `srcset`/performance). Sensible defaults chosen; may be exposed later.

## 3. Editor UX (`edit.js`)

- **Image management like core gallery:** `MediaPlaceholder` / `MediaUpload` with
  `multiple` + `gallery`, allowing add, remove, reorder, and edit of alt/caption. Uses the media
  library the same way as the core gallery.
- **Settings** (`InspectorControls`): "On click of the focus image" →
  `ToggleGroupControl`/`SelectControl` (`Lightbox` / `Link to media file` / `None`), plus image
  size options if exposed.
- **Preview:** below the management UI, `<ServerSideRender block="wp-usefull-blocks/ub-gallery"
  attributes={attributes} />` renders the real front-end markup (same code as the front end).
- All strings via i18n with text domain `wp-usefull-blocks`.

## 4. Front-end rendering (`render.php`) & interaction

- `render.php` outputs: a **focus figure** (large image) + a **thumbnail list** of `<button>`s.
  Images rendered via `wp_get_attachment_image()` for proper `srcset`/`sizes`, lazy loading,
  and correct escaping.
- **Interactivity API** (`view.js` as `viewScriptModule`): a `context.selectedIndex` state;
  thumbnails use `data-wp-on--click` to set it; the focus figure binds its `src`/`srcset`/`alt`
  to the selected image (`data-wp-bind--…`). Client-side only ⇒ cache-friendly.
- **Focus-image click behavior** (per `onImageClick`):
  - `lightbox`: open an enlarged overlay. Prefer reusing WordPress core's built-in image
    **lightbox behavior** if it can be applied cleanly; otherwise a small custom
    Interactivity-API overlay (focus trap, `Esc` to close, backdrop click to close).
  - `media`: wrap the focus image in a link to the attachment's **full-size media file**.
  - `none`: no action.
- **Accessibility:** thumbnails are real `<button>`s with `aria-selected`/`aria-current`,
  keyboard operable (arrow keys optional), visible focus styles; focus image `alt` updates with
  selection; `prefers-reduced-motion` respected for any transitions.

## 5. Styling (`style.scss` shared, `editor.scss` editor-only)

- Focus image area (responsive, maintains aspect ratio) + a horizontal, scrollable thumbnail
  strip beneath; selected thumbnail highlighted.
- Responsive: thumbnails wrap/scroll on small screens.

## 6. Performance & caching

- Dynamic render cached in a transient keyed by a hash of `images` + settings (self
  -invalidating when the gallery changes); filterable TTL.
- Responsive images (`srcset`/`sizes`) + `loading="lazy"` on non-focus images.
- Conditional asset loading via `block.json`; Interactivity view script loaded only when the
  block is present; no external libraries.

## 7. File structure

```
src/ub-gallery/
  block.json      # name wp-usefull-blocks/ub-gallery; render/view/style; interactivity: true
  index.js        # registerBlockType (dynamic block, no save)
  edit.js         # media management + settings + ServerSideRender preview
  render.php      # focus image + thumbnails; escaping; caching; Interactivity markup
  view.js         # Interactivity store (select thumbnail, optional lightbox)
  style.scss      # shared front-end + editor styling
  editor.scss     # editor-only styling
```

## 8. i18n / release / menu

- i18n from the start; bundled translation pass before release (per rules).
- New feature ⇒ next `*.*.0`. Will also get a **placeholder section** on the Settings page
  (per `docs/plans/admin-settings-and-headings.md`) until it has specific settings.

## 9. Testing plan (implementation phase)

- **JS unit:** attribute handling (add/remove/reorder), click-behavior selection.
- **PHP:** render output escaping, correct focus/thumbnail markup, `srcset` present.
- **Lint + build.**
- **E2E in `wp-now`:** insert block, add 3 images, verify focus + thumbnails, click a thumbnail
  to swap focus, and verify the focus-image click behavior (lightbox/link). Capture a video.

## 10. Open questions / assumptions

Defaults I'll use unless you say otherwise:

1. **Focus-image click:** make it a setting with options **Lightbox / Link to media file /
   None**; default **Lightbox**. — *Assumed* (you were undecided between lightbox and media
   link).
2. **Image model:** self-contained `images` array (not nested `core/image` blocks), while the
   picking experience mirrors the core gallery. — *Assumed.*
3. **Thumbnail position:** below the focus image (as described); other positions could be a
   future option. — *Assumed below.*
4. **Lightbox implementation:** reuse WordPress core's image lightbox if feasible, else a small
   custom Interactivity overlay. — *Assumed.*
