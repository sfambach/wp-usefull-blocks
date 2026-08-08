# Plan data — START HERE (handoff for other agents)

This folder is **saved plan data** for the WP Usefull Blocks plugin. Per project workflow, a new
session should **check here for saved plans before starting**.

## Current repository state

Implementation + plan data live together on branch **`cursor/local-continue-579b`**
(plugin code from the UB Link/File work, plus all plan docs).

### Implemented (code present)

| Item | Notes |
|------|-------|
| Dev scaffold / `@wordpress/scripts` | Plugin bootstrap, lint/build/test |
| **`ub-gallery`** | Focus image + thumbnail strip; Interactivity API |
| **`ub-link`** | Core-style link + traffic-light status |
| **`ub-file`** | File URL + manual “Download now” mirror |
| Site-wide content links | Normal `<a>` links get status UI via `the_content` |
| Admin menu **Useful** | Broken Links work page; Settings last |
| Settings | Traffic-light select (before / after / off), strike-through, auto-check |
| Broken Links UX (0.4.0) | Ampel, AJAX save/recheck, new-URL field, last-checked time |
| **`ub-timeline`** | Title/description table + SSR preview; Interactivity toggle (`timeline-block.md`) |
| **`ub-callout`** | Info/tip/warning/success callout (`daily-helpers.md`) |
| **`ub-toc`** | Auto table of contents from post headings (`daily-helpers.md`) |
| Heading ± toolbar | − / + on `core/heading` in the editor |
| **`ub-faq`** | Q&A accordion; Interactivity toggle (`daily-helpers.md`) |
| **`ub-reading-time`** | Estimated minutes from post word count (`daily-helpers.md`) |
| License decision | GPL “AS IS”; **no** activation disclaimer gate (`legal-disclaimer.md`) |

### Still planned (not implemented)

| Item | Plan doc |
|------|----------|
| More daily helpers (back-to-top, breadcrumbs, …) | `daily-helpers.md` |
| Link data layer → `ub-link` CPT / `ub-link-list` | `link-blocks.md` |
| Full Link Checker admin tool (beyond Broken Links) | `link-checker.md` |
| Heading tools + Headings Converter | `admin-settings-and-headings.md` |

## Run the dev environment

See `Readme.md` / `AGENTS.md`. In short: `npm install` → `npm run build` →
`npx @wp-now/wp-now start` (local WordPress at `http://localhost:8881`, admin `admin`/`password`).
Build is required before blocks appear (the `build/` dir is git-ignored).

## Binding rules (read before implementing)

- `conventions.md` — `ub-` block naming, license/liability, design rules link, definition of done,
  default architecture.
- `ux-design-guidelines.md` — UX/UI design rules (Nielsen heuristics, progressive disclosure,
  **WCAG 2.2 AA**) + per-block usability review.
- `legal-disclaimer.md` — GPL “AS IS”; no activation confirmation gate.
- Global WordPress rules (WPCS, PHP 8 OOP, security, i18n, `block.json` single source of truth,
  `@wordpress/scripts`) apply throughout.

## Default architecture (recap)

Dynamic block (PHP `render.php`) + **Interactivity API** (front end) + **`ServerSideRender`**
(editor preview = same code as front end) + **transient caching** keyed on attributes +
conditional asset loading. Details in `conventions.md` / `timeline-block.md`.

## Recommended next work

1. More daily helpers from `daily-helpers.md` (back-to-top, breadcrumbs, related, …)
2. Link data layer / `ub-link-list` — `link-blocks.md` (may evolve the current inline `ub-link`)
3. Headings Converter admin tool — `admin-settings-and-headings.md`
4. Expand Link Checker toward the full plan — `link-checker.md`

## Consolidated open questions (confirm before/at implementation)

- **Naming:** full block name `wp-usefull-blocks/ub-<name>` (default) vs short namespace
  `ub/<name>`?  (`conventions.md`)
- **Timeline:** independent toggles vs accordion; plain-text vs limited-HTML descriptions;
  horizontal-on-mobile behaviour.  (`timeline-block.md`)
- **Links:** data layer = CPT + taxonomy; `ub-link` reference vs inline+upsert; category flat vs
  hierarchical; list initial settings = category + max.  (`link-blocks.md`)
- **Headings (terminology!):** "transcription" = heading (Überschrift), "highest" = H1; inherit
  fallback = H2; ± buttons always on; converter scope/capability/safety.  (`admin-settings-and-headings.md`)
- **Link Checker:** scope, broken definition (≥400; 403/429 = check-manually), daily schedule,
  custom table.  (`link-checker.md`)

## Testing & release

- Every block/feature: build + lint + unit tests + design/a11y pass.
- Versioning: each feature → next `*.*.0`; bundled i18n pass (POT/PO/MO/JSON) before release;
  release ZIP via `git archive` with the `wp-usefull-blocks/` prefix.

## Environment caveats

- External network (Link Checker verification, File block downloads) may be restricted in the
  cloud dev VM; use local/mocked endpoints for tests, verify externally on the live site.
