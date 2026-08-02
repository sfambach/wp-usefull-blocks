# Plan: UB File block (`wp-usefull-blocks/ub-file`)

> Status: **Planning** (no implementation yet). Saved plan data. Follows the `ub-` naming
> convention and the default block architecture. Integrates with the Link Checker
> (`docs/plans/link-checker.md`) for URL status.

## 1. Goal

A block where you enter a **file URL**. The block:

- links to the **original file**,
- **downloads a copy into the WordPress media library** (a local mirror/backup),
- shows a **configurable link text** (with a site-wide default "motto"/text),
- **falls back to the local copy** if the original link is no longer available, and
- on the front end shows a **traffic-light status** (green = link OK, red = broken) for the
  original URL.

## 2. Data model (attributes)

- `sourceUrl` — the original file URL.
- `label` — link text; when empty, use the site-wide default (see §6).
- `attachmentId` — the downloaded local copy (media library attachment).
- `linkBehavior` — enum `"original-fallback-local" | "original-only" | "local-only"`,
  default `original-fallback-local`.
- `showStatus` — boolean, default `true` (traffic light on the front end).

## 3. Mirroring (download to the media library)

- When the block is saved with a new `sourceUrl`, the file is downloaded **server-side** via
  `download_url()` + `media_handle_sideload()` (loading `wp-admin/includes/{file,media,image}.php`),
  creating a media attachment; `attachmentId` is stored on the block.
- **Security/limits:** capability check (`upload_files`), nonce, **allowed MIME types**
  (site's `get_allowed_mime_types()` + a configurable allowlist) and a **max file size** setting;
  reject disallowed/oversized files. De-duplicate by source URL (don't re-download unchanged).
- **Refresh:** a "Download/refresh copy" action in the editor; optional re-mirror when the
  original is later detected broken (Open Questions).
- *Legal note:* mirroring third-party files can have copyright implications — surfaced as a hint
  in the editor.

## 4. Front-end rendering (dynamic block) & status

- Because the status changes over time, `ub-file` is a **dynamic block** (`render.php`): it reads
  the **current URL status** from the shared status store (the Link Checker's
  `ub_link_check` table / verifier) and renders:
  - the link (per `linkBehavior`; if the original is broken, link to the local copy),
  - an accessible **traffic-light** indicator: **green** OK, **red** broken, **yellow** =
    unknown/not-yet-checked/ambiguous (e.g. 403/429). Not color-only — includes an icon +
    `aria-label`/visually-hidden text (e.g. "Link status: OK").
- Editor preview via `ServerSideRender` (same code as the front end).
- Status is produced by the **shared verifier** (same one the Link Checker uses) and refreshed by
  the same WP-Cron batch — single source of truth, no duplicate checking logic (DRY).

## 5. Performance & caching
- Status is cached in the shared table; the block render is cached in a transient keyed by
  `sourceUrl` + `attachmentId` + status + `linkBehavior` (invalidated when status changes).
- Conditional asset loading via `block.json`; minimal front-end code.

## 6. Settings (general)
- **Default link text** ("standard motto") used when a block's `label` is empty.
- **Default link behavior** (fallback to local when broken — on/off).
- **Allowed MIME types** + **max file size** for mirroring.
- Recheck interval is shared with the Link Checker settings.

## 7. Integration / shared code
- Reuses the Link Checker's **URL status verifier** and results store. If not yet built, this
  block and the Link Checker share a single `Verifier` + status table (extract it once, use in
  both) — encapsulated and DRY.

## 8. File structure (additions)
```
src/ub-file/            # block.json, index/edit, render.php, view (status), styles
includes/
  Files/
    class-mirror.php     # download + sideload into media library (limits, security)
```
(Shared verifier/status store lives under `includes/LinkChecker/` per that plan.)

## 9. Testing plan (implementation phase)
- **PHP unit:** mirror download (mocked HTTP), MIME/size enforcement, de-dup; render output for
  each status + `linkBehavior`; fallback link selection.
- **Lint + build.**
- **E2E in `wp-now`:** add a `ub-file` block with a (local/mocked) file URL, confirm the copy
  appears in the media library, the front end shows the link + green light; simulate a broken
  source and confirm red light + fallback to the local copy. Capture a video.
  - *Note:* real downloads/checks need outbound network; the cloud dev VM may restrict egress —
    tests use local/mocked endpoints.

## 10. i18n / release / showcase
- i18n from the start; bundled translation pass before release (per rules).
- New feature ⇒ next `*.*.0`. Added to the Block Showcase page and pre-handover tests.

## 11. Open questions / assumptions
Defaults I'll use unless you say otherwise:
1. **Mirror timing:** auto-download on save + manual "refresh copy". — *Assumed.*
2. **Link behavior:** link to original, auto-fallback to the local copy when broken. — *Assumed.*
3. **Traffic light states:** green / red / **yellow** (unknown/ambiguous), accessible (icon +
   text, not color-only). — *Assumed.*
4. **Limits:** configurable allowed MIME types + max size (security). — *Assumed.*
5. **Auto re-mirror when the original goes broken:** off by default (manual refresh). — *Assumed.*
6. **Default link text** stored as a general setting. — *Assumed.*
