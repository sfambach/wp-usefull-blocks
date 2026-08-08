# Plan: Daily helpers (own ideas + backlog)

> Status: **Active**. Product direction for small WordPress helpers that ease everyday
> authoring and site maintenance. Complements the named feature plans (`link-blocks.md`,
> `admin-settings-and-headings.md`, …).

## Principles

- Prefer **small, focused** tools over dashboards.
- Reuse the default block architecture (dynamic PHP + SSR preview + Interactivity only when
  needed).
- Admin work pages only when there is real list/tool work; Settings stay last under **Useful**.
- Ship one helper at a time as the next `*.*.0` when the feature is usable.

## Shipped

| Helper | Release | Why it helps daily |
|--------|---------|--------------------|
| **`ub-callout`** | 0.6.0 | Info / tip / warning / success boxes without custom HTML |
| **`ub-toc`** | 0.6.0 | Auto table of contents from post headings with stable anchors |
| **Heading ± toolbar** | 0.6.0 | Faster outline edits on `core/heading` (− / + level) |
| **`ub-faq`** | 0.7.0 | Q&A accordion (timeline toggle pattern; question/answer rows) |
| **`ub-reading-time`** | 0.7.0 | Estimated reading time from post word count |

## Idea backlog (implement later, pick by usefulness)

### Blocks

| Idea | Notes |
|------|-------|
| **`ub-back-to-top`** | Accessible “Back to top” control (Interactivity smooth scroll + reduced motion) |
| **`ub-breadcrumb`** | Simple post/page breadcrumb trail |
| **`ub-related`** | Related posts by category/tag (cached query) |
| **`ub-button`** | Opinionated CTA button (style presets, optional icon) — only if core/button gaps hurt |
| **`ub-snippet`** | Code/pre block with one-click copy |
| **`ub-mailto`** | Obfuscated mailto / contact link to reduce casual scrapers |
| **`ub-progress`** | Simple “reading progress” bar for long posts |

### Admin / site tools

| Idea | Notes |
|------|-------|
| **Headings Converter** | Already planned (`admin-settings-and-headings.md`) |
| **Duplicate post/page** | One-click draft copy from list table row actions |
| **Orphan media finder** | List attachments unused in content (expensive — cache + batch) |
| **Slug conflict helper** | Warn when permalinks collide / suggest fixes |
| **Empty states kit** | Notice when a post has no featured image / no excerpt before publish |
| **Scheduled content digest** | Dashboard widget: what publishes in the next 7 days |
| **Redirect from broken link** | From Broken Links row → optional redirect map (careful with performance) |

### Editor quality-of-life

| Idea | Notes |
|------|-------|
| Inherit previous heading level | Already planned (settings toggle) |
| Default gallery → UB Gallery | Optional replace/transform |
| Paste URL → UB File/Link | Block transforms from bare URL |

## Decision log

- Callout content: **plain text** (title + body) for v1; RichText/InnerBlocks later if needed.
- TOC: built from `core/heading` in the **same post**; min/max level attributes; inject missing
  heading `id`s via `render_block` so links work.
- Heading ±: editor-only asset (`assets/editor-heading-toolbar.js`), no experimental WP APIs.
- FAQ: **independent** toggles (not exclusive accordion); plain-text question/answer; same
  Interactivity store pattern as timeline.
- Reading time: default **200 WPM** (clamped 50–600); optional prefix + word-count; uses
  `postId` context like TOC.
