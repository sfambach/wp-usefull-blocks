# Plan: Link Checker (admin tool)

> Status: **Planning** (no implementation yet). Saved plan data. A general (site-wide) tool,
> surfaced as a work page under the "Usefull Blocks" top-level menu.

## 1. Goal

Collect all links (URLs) from posts and pages, check whether they still work, list the broken
ones, and let the author fix them (edit the URL) directly.

## 2. Components

### 2.1 Link extraction
- Scan content of the configured post types (default **post** + **page**; optionally the
  `ub_link` CPT) for hyperlinks — `<a href>` — using `DOMDocument` (robust for block + classic
  content). Also cover URLs stored in known block/link attributes where relevant.
- Consider **http/https** links only; skip `mailto:`, `tel:`, and pure `#` anchors. Internal and
  external links are both checked (Open Questions).
- Record, per URL, the **source posts** where it occurs (for editing and reporting).

### 2.2 Storage (custom table)
- A custom table `{$wpdb->prefix}ub_link_check` (created via `dbDelta`) for scalability:
  `id`, `url` (indexed), `http_status`, `is_broken`, `redirect_url`, `last_checked`,
  `occurrences` (JSON of post IDs), timestamps.
- All queries use `$wpdb->prepare()`; URLs sanitised with `esc_url_raw()` on store, `esc_url()`
  on output (per project rules).

### 2.3 Checking mechanism
- Verify with `wp_remote_head()` (fallback to a ranged `wp_remote_get()` when HEAD is not
  allowed), with a timeout and redirect following.
- **Broken** = connection error/timeout or HTTP status **>= 400**. Ambiguous responses
  (e.g. 403/429 — bot-blocking) are flagged as **"check manually"** rather than hard-broken.
- Runs in the **background via WP-Cron** in small **batches** (avoids PHP timeouts and rate
  issues); a "Scan now" button triggers an immediate batch. Stale results are re-checked on a
  configurable interval (default daily).

### 2.4 Admin UI (work page "Link Checker")
- A `WP_List_Table` listing broken/suspect links: URL, HTTP status, source post(s) (with edit
  links), last checked.
- **Filters:** status (broken / check-manually / all), post type.
- **Row actions:**
  - **Edit URL** — replace the URL across **all** occurrences in the source posts' content
    (parse content, swap `href`, save; WP revisions provide undo). Reuses a shared
    URL-replacement helper.
  - **Recheck** — verify a single link immediately.
  - **Ignore/dismiss** — mark as intentionally ignored (excluded from the broken list).
- **Bulk actions:** recheck / ignore for the current selection.

## 3. Settings (start with a few)
- Recheck interval (e.g. daily/weekly), request timeout, batch size, post types to scan,
  broken-status codes, and an **exclude list** (URL/domain patterns). Lives in the plugin
  Settings page (its own section or under General).

## 4. Performance & caching
- Background cron batches; only stale links re-checked; results cached in the custom table.
- Indexed `url` column; `$wpdb->prepare()`d queries; admin assets enqueued only on the tool page.

## 5. Capabilities & security
- Capability `manage_options` (site-wide tool). Nonces on all actions; capability checks;
  sanitised inputs; escaped output.

## 6. File structure (additions)
```
includes/
  LinkChecker/
    class-scanner.php     # extract links from content -> table
    class-verifier.php    # cron batch HTTP checks
    class-list-table.php  # WP_List_Table UI
    class-url-replacer.php# shared: replace a URL across a post's content (reusable)
    class-schema.php      # dbDelta table create/upgrade
```
- Logic separated from admin view; the URL-replacer is shared/DRY (also usable elsewhere).

## 7. Testing plan (implementation phase)
- **PHP unit:** extraction from sample block/classic content; broken detection given mocked HTTP
  responses; URL replacement across occurrences; schema create/upgrade.
- **Lint + build.**
- **E2E in `wp-now`:** seed posts with a good and a deliberately broken URL, run "Scan now",
  confirm the broken one is listed, edit it, and verify content updated. Capture a video.
  - *Note:* checking real external URLs requires outbound network access; the cloud dev VM may
    restrict egress. Tests will use local/mocked endpoints where possible.

## 8. i18n / release / menu
- i18n from the start; bundled translation pass before release (per rules).
- New feature ⇒ next `*.*.0`.
- Added as a **work page** under the Usefull Blocks top-level menu (before Settings) — see the
  consolidated menu in `docs/plans/admin-settings-and-headings.md`.

## 9. Open questions / assumptions
Defaults I'll use unless you say otherwise:
1. **Scope:** scan **post** + **page** (+ optionally `ub_link` CPT); http/https `<a href>` only
   (skip mailto/tel/anchors); check internal + external. — *Assumed.*
2. **Broken definition:** connection error/timeout or HTTP >= 400; 403/429 = "check manually".
   — *Assumed.*
3. **Fixing:** edit the URL and replace across all occurrences (+ link to source posts); rely on
   WP revisions for undo. — *Assumed.*
4. **Scheduling:** WP-Cron, default **daily** recheck + manual "Scan now". — *Assumed.*
5. **Storage:** custom DB table. — *Assumed.*
