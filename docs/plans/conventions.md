# Conventions (WP Usefull Blocks)

> Saved plan data. Project-specific conventions that apply to all blocks. These complement the
> global WordPress coding rules (WPCS, security, i18n, etc.).

## Block naming: `ub-` prefix

All blocks in this plugin use the **`ub-`** prefix ("UB" = Usefull Blocks) on the block name.

- **Block name (slug):** `ub-<name>` — e.g. `ub-gallery`, `ub-timeline`.
- **Full WordPress block name** (WordPress requires `namespace/name`): vendor namespace
  `wp-usefull-blocks` + the `ub-` slug ⇒ `wp-usefull-blocks/ub-<name>`
  (e.g. `wp-usefull-blocks/ub-gallery`). The part the author refers to as "the block name" is
  the `ub-<name>` slug; the namespace is the vendor prefix.
  - *Open question:* alternatively use a short namespace `ub` ⇒ `ub/gallery`. Default is
    `wp-usefull-blocks/ub-<name>` unless you prefer the shorter namespace.
- **Source folder:** `src/ub-<name>/`.
- **Generated CSS class:** WordPress derives `wp-block-wp-usefull-blocks-ub-<name>` automatically.

### Applies to existing plans

- The planned **Timeline** block is renamed to **`ub-timeline`**
  (`wp-usefull-blocks/ub-timeline`, folder `src/ub-timeline/`).
- The scaffold sample block `wp-usefull-blocks/wp-usefull-blocks` (from `@wordpress/create-block`)
  is a placeholder and will be removed/replaced by the real `ub-*` blocks.

## Design & UX rules (binding)

All blocks and admin screens must follow the **UX & UI Design Guidelines**
(`docs/plans/ux-design-guidelines.md`): native `@wordpress/components`, recognition over recall,
progressive disclosure, sensible defaults, clear feedback/empty/loading states, and **WCAG 2.2
AA** accessibility (keyboard, focus, ARIA, never colour-alone, reduced motion).

## Definition of done for every block (standing rule)

Whenever a block is added or changed, before handing it over for testing:

- Follow the **Design & UX rules** above (incl. a keyboard/screen-reader pass).
- Add/refresh it on the **Block Showcase** test page (auto-synced) and run the **pre-handover
  tests** — see `docs/plans/block-showcase-and-pretests.md`.

## Default block architecture (recap)

Unless a block clearly warrants otherwise, blocks follow the architecture chosen for the
Timeline block (see `docs/plans/timeline-block.md`):

- **Dynamic block** rendered in PHP (`render.php`) as the single rendering source.
- **Editor preview** via `@wordpress/server-side-render` (same code as the front end).
- **Front-end interactivity** via the **WordPress Interactivity API** (cache-friendly, no
  per-request server state).
- **Caching**: transient keyed by a hash of block attributes (self-invalidating on edit) +
  conditional asset loading via `block.json`.
