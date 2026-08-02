# Plan: Admin area, Settings & Heading-level tools

> Status: **Planning** (no implementation yet). Saved plan data; builds on the plugin scaffold
> (PR #1) and complements the Timeline block plan (`docs/plans/timeline-block.md`).
>
> **Terminology note:** the request was dictated; "transcription" is read as **heading**
> (German "Überschrift"). "Transcription converter" = **Headings Converter**. "Highest/largest
> heading" = the **most prominent** heading, i.e. the **lowest number** (H1 > H2 > … > H6).

## 1. Scope

Three connected pieces:

1. **Own admin menu** for "Usefull Blocks" (because there are now several backend pages).
2. **Settings page** with (a) a per-block section for each plugin block and (b) a general
   section for WordPress-wide behaviors.
3. **Heading-level features**: an editor behavior + toolbar buttons, and a bulk **Headings
   Converter** admin tool.

## 2. Admin menu structure (per project rules)

Rule: multiple work pages ⇒ own top-level menu; work pages first; **Settings always last**.

This is the **consolidated** menu across all plan docs (work pages appear before Settings; the
exact order of the work pages is flexible):

```
Usefull Blocks (top-level menu)
├── Headings Converter   (work page)          # admin-settings-and-headings.md
├── Links                (work page, ub_link CPT)   # link-blocks.md
├── Link Categories      (work page, taxonomy)      # link-blocks.md
├── Link Checker         (work page)          # link-checker.md
└── Settings             (last submenu)
```

- Top-level slug: `wp-usefull-blocks`. Landing page = the first work page (Headings Converter).
- Registered with `add_menu_page()` + `add_submenu_page()`; capability per page (see §7).
- This supersedes the earlier "no admin menu / core Settings" note in the Timeline plan.

## 3. Settings page

- Built on the **Settings API** (`register_setting`, `add_settings_section`,
  `add_settings_field`, `do_settings_sections`) rendered on our own settings subpage, stored in
  a single option array `wp_usefull_blocks_settings`.
- **Per-block sections:** one section per plugin block (`wp-usefull-blocks/*`), generated
  dynamically from the registered block list. If a block has no specific settings yet, the
  section shows **just its headline** (placeholder), ready to be filled later.
- **General section:** WordPress-wide behaviors. First setting:
  - **"Inherit previous heading level for new headings"** (toggle). Enables the editor behavior
    in §4A.
- Security/WPCS: nonces (Settings API handles this), sanitization callbacks per field, escaped
  output, text domain `wp-usefull-blocks`, Yoda conditions, `strict_types`.
- The general settings that affect the editor are exposed to editor JS (via a small localized
  data object or the settings REST endpoint) so the block-editor scripts can react to them.

## 4. Heading-level editor features

Applied to the **core Heading block** (`core/heading`) via editor filters — no core files
touched.

### 4A. Inherit previous heading level (general setting, §3)

- When enabled, a newly inserted heading takes the **same level as the nearest preceding
  heading** in the post (instead of the default). Manual changes still work (e.g. jump back to
  H2 whenever you want).
- First heading in a post (no previous heading) falls back to a default level (proposed **H2**).
- Implementation approach: subscribe to the block-editor store and, when a new `core/heading`
  is inserted with the default level, set its `level` to the previous heading's level via
  `updateBlockAttributes`. This is the **trickiest part** (insertion detection has edge cases);
  fallback options in Open Questions.

### 4B. Plus / minus toolbar buttons

- Add two buttons to the Heading block toolbar (`BlockControls` via the `editor.BlockEdit`
  filter): **−** and **+**.
- **−** decreases the level number by 1 (e.g. H3 → H2, H2 → H1). **+** increases it by 1
  (e.g. H3 → H4). Clamped to H1–H6; buttons disable at the boundaries.

## 5. Headings Converter (admin work page)

Purpose: review and bulk-fix heading levels across posts/pages.

- **Listing:** a table of posts/pages showing, per item, the **top heading level** present in
  its content (the most prominent = lowest number, e.g. H1). Columns: Title, Post type, Top
  heading level, Actions.
- **Filter:** a dropdown to filter the list by top heading level (e.g. "show all pages whose top
  heading is H1"), so problem content is easy to find and correct.
- **Per-row actions:** **−** and **+** buttons that shift **all** headings in that post by ±1,
  preserving relative structure (e.g. +1 turns H1/H2/H3 into H2/H3/H4). Clamped to H1–H6; a
  button is disabled if shifting would exceed the range (e.g. **−** disabled when any heading is
  already H1, **+** disabled when any is H6).
- **How the shift works:** server-side, parse the post with `parse_blocks()`, find
  `core/heading` blocks, adjust the `level` attribute and the inner `<hN>` tag, then
  `serialize_blocks()` and `wp_update_post()`. WordPress **revisions** provide undo. Actions are
  protected by nonces + capability checks and a confirmation step.
- **Scope (proposed):** post types `post` and `page`; Gutenberg block headings. Raw `<hN>` in
  classic content = best-effort/optional (Open Questions).
- **Optional bulk action:** apply ±1 to all currently filtered posts at once.

## 6. Performance & caching

- **Converter listing** scans content for headings, which is potentially expensive. Cache each
  post's computed top heading level in a transient keyed by post ID + `post_modified` (auto
  -invalidates on edit); recompute lazily.
- Enqueue admin scripts/styles only on our admin pages (hook suffix check).
- Editor filters are lightweight and only affect the heading block.

## 7. Capabilities & security

- **Settings page:** `manage_options`.
- **Headings Converter:** it edits content (including others' posts), so proposed capability
  `edit_others_posts` (Open Questions). All mutations via `admin-post.php`/REST with nonces,
  capability checks, sanitized input, escaped output, `$wpdb->prepare()` if any custom query is
  needed (none expected — uses `WP_Query`).

## 8. Internationalisation

- All strings via i18n APIs with text domain `wp-usefull-blocks` from the start. Bundled
  translation pass (POT/PO/MO + editor JSON) only before release, per project rules.

## 9. File structure (additions)

```
wp-usefull-blocks.php            # bootstrap: wires up admin menu + editor filters (or includes)
includes/
  Admin/
    class-menu.php               # registers the top-level menu + submenus
    class-settings-page.php      # Settings API: general + per-block sections
    class-headings-converter.php # listing, filter, ±1 shift handlers (nonces, caps)
  Headings/
    class-heading-shifter.php    # reusable block-parsing + level-shift logic (shared)
src/editor/
  heading-tools/                 # editor JS: inherit-level behavior + ± toolbar buttons
    index.js
```

- The heading-shift logic is encapsulated in one class reused by both the Converter and any
  future callers (DRY, per project rules). Model/logic separated from admin view rendering.
- Editor JS is built by the existing `@wordpress/scripts` pipeline; the PHP admin classes are
  OOP with `declare(strict_types=1)` and WPCS naming.

## 10. Testing plan (implementation phase)

- **Unit (PHP):** `Heading_Shifter` — shifting a mixed set of headings ±1, clamping, no-op at
  boundaries; top-level detection.
- **Unit (JS):** ± toolbar level math and clamping; inherit-level selection of previous level.
- **Lint + build.**
- **E2E in `wp-now`:** (1) editor: insert headings, verify inherit + ± buttons; (2) settings:
  toggle general setting and see per-block placeholder sections; (3) converter: create a post
  with H1/H2, filter by H1, apply +1, confirm content updated to H2/H3. Capture a video.

## 11. Release & versioning (per rules)

- These are new features → next `*.*.0`. If shipped after the Timeline block (0.2.0), this would
  be **0.3.0**; if bundled together, coordinate a single `*.*.0`. Pre-release: bundled i18n pass,
  update `Readme.md`/`readme.txt` changelog + FAQ, wiki, project page; release ZIP via
  `git archive` with the `wp-usefull-blocks/` prefix.
- **FAQ:** document that the "inherit heading level" behavior is controlled by a general setting
  (per the rule to record required settings in the FAQ).

## 12. Open questions / assumptions

Defaults I'll use unless you say otherwise:

1. **"Transcription" = heading (Überschrift).** — *Assumed yes* (whole plan depends on it).
2. **"Highest heading" = most prominent = H1 (lowest number).** — *Assumed yes.*
3. **Per-block settings source:** the plugin's own blocks (`wp-usefull-blocks/*`), not all
   registered blocks. — *Assumed plugin's own blocks.*
4. **Inherit-level fallback** when no previous heading exists: default **H2**. — *Assumed H2.*
5. **± toolbar buttons:** always available on heading blocks (not gated by the general setting).
   — *Assumed always on.*
6. **Converter scope:** post types `post` + `page`; block (Gutenberg) headings; raw classic
   `<hN>` best-effort only. — *Assumed as stated.*
7. **Converter capability:** `edit_others_posts`. — *Assumed.*
8. **Converter safety:** confirmation prompt + rely on WP revisions for undo (no separate
   backup). — *Assumed.*
9. **Menu landing page:** the top-level menu opens the Headings Converter (first work page).
   — *Assumed* (alternative: a small Overview page).
