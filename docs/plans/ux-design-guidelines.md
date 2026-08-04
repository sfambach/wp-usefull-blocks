# UX & UI Design Guidelines (rules for all blocks & admin screens)

> Saved plan data. **These are binding design rules** for every block and admin screen in this
> plugin (referenced from `docs/plans/conventions.md`). Goal: blocks that are easy to operate,
> understandable, consistent, and accessible — favouring **recognition over recall**.

## 1. Core principles (Nielsen heuristics, applied)

1. **Visibility of system status** — always show what's happening: `Spinner` while loading,
   `Snackbar`/`Notice` on success/error, disabled controls that explain *why* (tooltip).
2. **Match the real world** — plain, human labels (no jargon); icons that match their meaning.
3. **User control & freedom** — easy undo; destructive actions are confirmable and rely on WP
   revisions; never lose the user's input silently.
4. **Consistency & standards** — use **native `@wordpress/components`** and follow block-editor /
   `wp-admin` patterns so controls look and behave exactly as users already expect (recognition).
5. **Error prevention** — validate inline, constrain inputs (e.g. clamp heading levels, number
   fields with min/max), confirm irreversible/bulk actions.
6. **Recognition, not recall** — surface options where they're used; show current state (active
   toolbar buttons, selected thumbnail, chosen category) rather than making users remember.
7. **Flexibility & efficiency** — sensible defaults so a block is useful immediately; keyboard
   shortcuts where natural; power options tucked away, not in the way.
8. **Aesthetic & minimalist design** — only necessary controls visible; group and collapse the
   rest (progressive disclosure).
9. **Help users recognise/recover from errors** — clear, specific, non-technical messages with a
   next step.
10. **Help & documentation** — concise `help` text on controls; link to docs/FAQ where useful.

## 2. Control placement (progressive disclosure)

- **Block toolbar (`BlockControls`)** — the few most-used, direct-manipulation actions
  (e.g. heading ± level, alignment).
- **Inspector sidebar (`InspectorControls`)** — settings grouped in `PanelBody` sections;
  primary panel open by default, secondary/advanced collapsed.
- **Inline/in-canvas** — content entry happens in place; the block preview reflects the result.
- **Admin screens** — standard page header, Settings API layout, `WP_List_Table` for lists,
  filters above the table, bulk actions, admin `Notice`s for feedback.

## 3. Defaults, empty & loading states

- Every block is usable right after insertion: provide a **placeholder/empty state**
  (`MediaPlaceholder`-style) with a clear call to action, and a `block.json` **`example`** so the
  inserter shows a meaningful preview.
- Show **loading** (`Spinner`/skeleton) for async work (media, `ServerSideRender`, checks) and a
  friendly **empty result** message (e.g. "No links in this category yet").

## 4. Discoverability in the inserter (recognition)

- Each `block.json` has a clear **`title`**, a one-line **`description`**, recognizable
  **`icon`**, a shared **block category**, and **`keywords`** (incl. German synonyms) so blocks
  are easy to find by search.

## 5. Accessibility (WCAG 2.2 AA — required)

- Full **keyboard** operation; **visible focus** styles; logical tab order.
- Semantic HTML (`<button>` for actions, headings for structure, lists for lists).
- Correct **ARIA** only where needed (`aria-expanded`, `aria-selected`, `aria-controls`,
  `aria-label`/visually-hidden text); associate `help` text via `aria-describedby`.
- **Never rely on colour alone** — pair colour with icon/text (e.g. traffic light has an icon +
  label). Meet **contrast** ratios.
- Respect **`prefers-reduced-motion`**; provide non-animated fallbacks.
- Images require **alt**; decorative images get empty alt.
- Adequate **touch-target** sizes and spacing.

## 6. Consistency of interaction language

- Same icons/labels for the same concept across blocks (e.g. category selector, "add row",
  status light) — implemented once and reused (DRY) so behaviour is identical everywhere.
- Consistent wording and sentence case per WordPress style; all strings i18n-wrapped.

## 7. Responsiveness & performance (perceived UX)

- Layouts adapt to small screens (e.g. horizontal timeline → vertical; thumbnail strips scroll).
- Keep interactions instant (Interactivity API, cached renders); avoid layout shift (reserve
  space for images/status).

---

## 8. Per-block usability review (planned blocks)

| Block / tool | Key usability & design decisions |
| --- | --- |
| **`ub-timeline`** | Table editor for fast entry; clear "Add entry" + reorder/remove; orientation via `ToggleGroupControl` (shows current choice); titles are `<button>`s with `aria-expanded`; responsive (horizontal→vertical); `example` + keywords for the inserter. |
| **`ub-gallery`** | Familiar core-gallery picking (recognition); selected thumbnail clearly highlighted (`aria-selected`); keyboard-navigable thumbnails; focus-image click behaviour is an explicit, labelled setting; lightbox has focus trap + `Esc`/backdrop close; responsive; lazy images with reserved space. |
| **`ub-link`** | Clear link picker/creator; shows chosen link + category; validates URL; sensible default label. |
| **`ub-link-list`** | Category select + number field with min/max; empty state ("No links yet"); loading spinner in preview; predictable ordering. |
| **`ub-file`** | Explicit states: mirroring progress (spinner), success/error notice; accessible traffic light (icon + text, not colour-only); clear fallback explanation; copyright hint; size/type errors shown inline. |
| **Heading tools** | ± buttons in the toolbar with tooltips; disabled at H1/H6 boundaries (explained); inherit-level is an opt-in setting with help text; non-destructive (manual override always possible). |
| **Headings Converter** | List with clear "top heading level" column; filter above the table; ± actions disabled at boundaries with reason; **confirmation** before bulk changes; revisions for undo; success/error notices. |
| **Link Checker** | Standard `WP_List_Table`; status filters; per-row edit/recheck/ignore; bulk actions with confirmation; "check manually" state for ambiguous responses; progress feedback for scans. |

## 9. Definition of done (design)

Before hand-over, each block/screen is checked against §1–§7 (a short design/a11y checklist),
including a quick keyboard-only and screen-reader-label pass.
