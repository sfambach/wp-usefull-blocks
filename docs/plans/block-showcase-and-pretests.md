# Plan: Block Showcase test page + pre-handover tests

> Status: **Planning** (no implementation yet). Saved plan data.
>
> **Standing rule (author request):** whenever a block is added or changed, the Block Showcase
> page must be updated and the pre-handover tests must pass **before** handing the work over for
> testing. This is part of every block's definition of done.

## 1. Purpose

A single WordPress page that displays **every** plugin block with representative content, so the
author can test all blocks in one place. It stays in sync automatically as blocks are added, and
is smoke-tested before hand-over so obvious errors are caught first.

## 2. Current status / dependency

No feature blocks exist yet (only the `@wordpress/create-block` sample). The live page is
therefore created/populated **as blocks are implemented**. This doc defines the mechanism now so
it is ready and consistently applied.

## 3. Mechanism (how the page is built and kept updated)

- **Idempotent sync routine** that creates or updates one page titled **"UB Block Showcase"**
  (fixed slug `ub-block-showcase`, identified by post meta `_ub_showcase = 1` so re-runs never
  create duplicates).
- The routine **iterates over all registered `wp-usefull-blocks/ub-*` blocks** and, for each,
  emits a labeled section: a heading with the block title + one (or more) instances of the block
  using representative **fixture attributes**. New blocks are picked up automatically ⇒ the page
  "updates itself" when a block is added.
- **Single source of truth for fixtures:** reuse each block's `block.json` `example` where
  possible, plus a small per-block fixtures map for richer cases (e.g. a gallery needs sample
  images).
- **Delivery (dev):**
  - a **WP-CLI command** (e.g. `wp ub showcase:sync`), and
  - a **button on the Usefull Blocks admin page** ("Rebuild showcase page").
  - (Optional) also register a **block pattern** "UB: all blocks" for manual insertion.
- Page status kept non-public by default (**draft or private**) so it never leaks to visitors.

## 4. Pre-handover automated tests (run before giving it to the author)

Run in order; all must pass:

1. `npm run lint` (JS + styles).
2. `npm run build`.
3. `npm test` (unit).
4. **Rebuild the showcase page** via the sync routine (seeds sample images if needed).
5. **Smoke test in `wp-now`** (headless/`computerUse`):
   - Open the showcase in the **block editor**: no block-validation errors, no JS console
     errors, every block's `ServerSideRender` returns without error.
   - Open the showcase on the **front end**: HTTP 200, no JS console errors.
   - With `WP_DEBUG` on, the PHP `debug.log` shows **no notices/warnings/errors** from the
     plugin.
6. Capture a screenshot/video artifact of the showcase (editor + front end).

Only after all steps pass is the page handed over for manual testing.

## 5. File structure (additions)

```
includes/
  Dev/
    class-showcase.php     # sync routine (create/update page), fixtures map, admin button
    class-cli.php          # wp ub showcase:sync command
```

- Encapsulated, reusable; not loaded on the front end.

## 6. Open questions / assumptions

Defaults I'll use unless you say otherwise:

1. **Post vs Page:** a **Page** titled "UB Block Showcase". — *Assumed Page.*
2. **Visibility:** **private** (visible to logged-in editors/admins, not public). — *Assumed
   private* (alt: draft, or published).
3. **Auto-create on activation:** **no** — created on demand via the sync command/button (avoids
   touching content unexpectedly, especially in production). — *Assumed on-demand.*
4. **Gallery fixtures:** seed a few placeholder images into the media library for the showcase.
   — *Assumed.*
