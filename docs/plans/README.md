# Plan data — START HERE (handoff for other agents)

This folder is **saved plan data** for the WP Usefull Blocks plugin. Per project workflow, a new
session should **check here for saved plans before starting**. Everything is in **planning**
status — no feature code has been implemented yet.

## Current repository state

- Greenfield WordPress Gutenberg block plugin. The dev environment + scaffold is in
  **PR #1** (branch `cursor/setup-dev-environment-02e5`): `@wordpress/scripts` plugin, one
  `@wordpress/create-block` **sample block only** (placeholder, to be replaced by `ub-*` blocks).
- This plan data is in **PR #2** (branch `cursor/timeline-block-plan-02e5`).
- Merge both PRs to persist the environment + plans for future sessions.

## Run the dev environment (from PR #1)

See `Readme.md` / `AGENTS.md`. In short: `npm install` → `npm run build` →
`npx @wp-now/wp-now start` (local WordPress at `http://localhost:8881`, admin `admin`/`password`).
Build is required before blocks appear (the `build/` dir is git-ignored).

## Binding rules (read before implementing)

- `conventions.md` — `ub-` block naming, default block architecture, design rules link, and the
  per-block **definition of done**.
- `ux-design-guidelines.md` — UX/UI design rules (Nielsen heuristics, progressive disclosure,
  **WCAG 2.2 AA**) + per-block usability review.
- Global WordPress rules (WPCS, PHP 8 OOP, security, i18n, `block.json` single source of truth,
  `@wordpress/scripts`) apply throughout.

## Default architecture (recap)

Dynamic block (PHP `render.php`) + **Interactivity API** (front end) + **`ServerSideRender`**
(editor preview = same code as front end) + **transient caching** keyed on attributes +
conditional asset loading. Details in `conventions.md` / `timeline-block.md`.

## Backlog & recommended build order

| # | Item | Plan doc | Notes |
|---|------|----------|-------|
| 1 | **`ub-timeline`** block | `timeline-block.md` | Start here; self-contained. Also stand up the **Block Showcase + pre-test pipeline** with it. |
| 2 | **Block Showcase + pre-tests** | `block-showcase-and-pretests.md` | Auto-updating test page; standing pre-handover test routine. |
| 3 | **`ub-gallery`** block | `ub-gallery-block.md` | eBay-style single-focus gallery. |
| 4 | **Link data layer** → `ub-link`, `ub-link-list` | `link-blocks.md` | CPT `ub_link` + taxonomy `ub_link_category`, then the two blocks. |
| 5 | **Link Checker** | `link-checker.md` | Provides the shared URL **verifier** + status table. |
| 6 | **`ub-file`** block | `ub-file-block.md` | Reuses the Link Checker verifier for the traffic light. |
| 7 | **Admin menu + Settings** | `admin-settings-and-headings.md` | Own top-level menu (work pages first, Settings last); per-block sections + general section. |
| 8 | **Heading tools + Headings Converter** | `admin-settings-and-headings.md` | Inherit-level setting, ± toolbar buttons, bulk converter. |

Notes: the admin menu (item 7) is a dependency for per-block settings sections and general
settings; work pages (Headings Converter, Links, Link Categories, Link Checker) can be added to
it incrementally, with **Settings always last**.

## Consolidated open questions (confirm before/at implementation)

- **Naming:** full block name `wp-usefull-blocks/ub-<name>` (default) vs short namespace
  `ub/<name>`?  (`conventions.md`)
- **Timeline:** independent toggles vs accordion; plain-text vs limited-HTML descriptions;
  horizontal-on-mobile behaviour.  (`timeline-block.md`)
- **Gallery:** focus-image click default = Lightbox vs Link to media file.  (`ub-gallery-block.md`)
- **Links:** data layer = CPT + taxonomy; `ub-link` reference vs inline+upsert; category flat vs
  hierarchical; list initial settings = category + max.  (`link-blocks.md`)
- **Headings (terminology!):** "transcription" = heading (Überschrift), "highest" = H1; inherit
  fallback = H2; ± buttons always on; converter scope/capability/safety.  (`admin-settings-and-headings.md`)
- **Link Checker:** scope, broken definition (≥400; 403/429 = check-manually), daily schedule,
  custom table.  (`link-checker.md`)
- **File block:** mirror timing, link/fallback behaviour, traffic-light states, MIME/size limits,
  default link text.  (`ub-file-block.md`)
- **Showcase:** Page + private + on-demand.  (`block-showcase-and-pretests.md`)

## Testing & release

- Every block/feature: build + lint + unit tests + `wp-now` E2E + design/a11y pass, and refresh
  the Block Showcase (see `block-showcase-and-pretests.md`).
- Versioning: each feature → next `*.*.0`; bundled i18n pass (POT/PO/MO/JSON) before release;
  release ZIP via `git archive` with the `wp-usefull-blocks/` prefix.

## Environment caveats

- External network (Link Checker verification, File block downloads) may be restricted in the
  cloud dev VM; use local/mocked endpoints for tests, verify externally on the live site.
