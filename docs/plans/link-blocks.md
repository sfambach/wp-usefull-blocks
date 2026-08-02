# Plan: Link blocks — `ub-link` and `ub-link-list`

> Status: **Planning** (no implementation yet). Saved plan data. Follows the `ub-` naming
> convention (`docs/plans/conventions.md`) and the default block architecture.

## 1. Goal

Two related blocks:

1. **`ub-link`** — define/show a single link that is assigned to a **link category**.
2. **`ub-link-list`** — list the links of a chosen category, with a **maximum count**.

For a listing block to aggregate "links of category X" across the site, the links must live in
a **queryable store** — not only inline in a single post's content. So the design introduces a
small data layer.

## 2. Data layer (the important decision)

**Recommended:** a custom taxonomy + custom post type.

- **Taxonomy `ub_link_category`** — the "link categories" shared by both blocks and admin.
  Proposed **flat** (like tags); hierarchical is possible (Open Questions).
- **Custom post type `ub_link`** — one entry per link. Fields:
  - post title = link **label**
  - **URL** (post meta `ub_link_url`)
  - **category** (`ub_link_category` term)
  - optional later: description, "open in new tab", `rel`/nofollow, icon.
- Both registered with `show_in_rest = true` so blocks can read them via the REST API and the
  editor selectors.

Why a CPT: it makes links centrally manageable and queryable by category with proper caching,
capabilities, and REST support — which is exactly what `ub-link-list` needs.

### How `ub-link` relates to storage (Open Question)

- **Option A (recommended):** `ub-link` **references** an existing link entry (pick one, or
  create one inline via REST). Editing the URL/label is centralized in the CPT; the block just
  displays it. No duplication/sync issues.
- **Option B:** `ub-link` stores URL/label/category **inline** and **upserts** a `ub_link`
  entry so the listing can find it. More authoring-friendly but adds sync/lifecycle complexity
  (edits, deletes, orphans).

## 3. Admin (links management)

- Since the plugin now has an **own top-level "Usefull Blocks" menu** (see
  `docs/plans/admin-settings-and-headings.md`), surface link management there:
  `Links` (CPT list/add) and `Link Categories` (taxonomy) as work pages, with **Settings still
  last**. (Registered via `show_in_menu => 'wp-usefull-blocks'`.)
- Capabilities mapped to standard post capabilities; nonces + sanitisation for the URL meta
  (`esc_url_raw()` on save, `esc_url()` on output).

## 4. `ub-link` block

- **Attributes:** `linkId` (referenced `ub_link`), or (Option B) `url` + `label` +
  `categoryId`. `showCategory` (bool) optional.
- **Editor:** a selector to choose an existing link (Option A) or fields to enter URL + label +
  pick a category (Option B), via `@wordpress/components` + REST-backed selects.
- **Render:** dynamic `render.php` outputs an escaped `<a>` (with optional category label).
  Editor preview via `ServerSideRender` (same code as front end).

## 5. `ub-link-list` block

- **Attributes (start small, per request):**
  - `categoryId` — the `ub_link_category` term to list. Empty = all.
  - `max` — maximum number of links to display (integer; sensible default e.g. 5).
  - (later) `orderBy` (date/title/menu_order), `order`, show category/description.
- **Editor:** category select + a number control for `max`, in `InspectorControls`.
  `ServerSideRender` preview below.
- **Render (`render.php`):** a `WP_Query` for `ub_link` filtered by the term, limited to `max`,
  with `no_found_rows => true` (no pagination needed — per project rules) and only required
  fields. Outputs an escaped `<ul>` of links.

## 6. Performance & caching

- List render cached in a transient keyed by `categoryId` + `max` (+ order params later);
  **invalidated** when any `ub_link` is saved/deleted or terms change (hook
  `save_post_ub_link`, `deleted_post`, `edited_ub_link_category`). Filterable TTL.
- `WP_Query` uses `no_found_rows`, `fields` trimmed, and post-meta handled efficiently.
- Conditional asset loading via `block.json`; minimal/no front-end JS (these blocks are mostly
  static output; Interactivity only if a feature needs it).

## 7. File structure

```
includes/
  Content/
    class-link-cpt.php        # registers ub_link CPT + ub_link_category taxonomy + meta
src/ub-link/                  # single-link block (block.json, index/edit, render.php, styles)
src/ub-link-list/             # listing block (block.json, index/edit, render.php, styles)
```

- CPT/taxonomy/meta registration encapsulated in one class (DRY), separate from block render.

## 8. i18n / release / settings

- i18n from the start; bundled translation pass before release (per rules).
- New feature ⇒ next `*.*.0`. Both blocks get **placeholder Settings sections** until they have
  specific settings (per the admin plan).

## 9. Testing plan (implementation phase)

- **PHP unit:** CPT/taxonomy registration; list query returns correct links filtered by term and
  respects `max`; cache invalidation on save.
- **JS unit:** attribute handling for both blocks.
- **Lint + build.**
- **E2E in `wp-now`:** create a couple of link categories + links; add `ub-link` referencing one;
  add `ub-link-list` for a category with `max = N`; verify front-end output and that changing a
  link updates the list. Capture a video.

## 10. Open questions / assumptions

Defaults I'll use unless you say otherwise:

1. **Data layer:** CPT `ub_link` + taxonomy `ub_link_category`. — *Assumed (recommended).*
2. **`ub-link` storage relationship:** Option A (reference an existing link entry, create inline
   allowed). — *Assumed A.*
3. **Link category type:** flat (non-hierarchical), like tags. — *Assumed flat.*
4. **Link fields (initial):** label + URL + category (+ optional "open in new tab"). — *Assumed
   this minimal set.*
5. **`ub-link-list` initial settings:** just **category** + **max count**; ordering/extra
   display options later. — *Assumed (matches "start with a few settings").*
6. **Links managed** under the Usefull Blocks top-level menu (Links + Link Categories), Settings
   last. — *Assumed.*
