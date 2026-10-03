# WP Usefull Blocks

Some useful blocks for the WordPress Gutenberg (block) editor, packaged as a standard
WordPress plugin built with [`@wordpress/scripts`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
and the modern Block API (v3, `block.json` as the single source of truth).

## Requirements

- Node.js 20+ and npm (used for the block build tooling)
- A WordPress 6.8+ site with PHP 8.1+ to run the plugin

## Project structure

```
wp-usefull-blocks.php          # Plugin bootstrap (registers blocks from build/)
includes/                      # Shared PHP helpers (gallery, URL status, file mirror, REST)
src/ub-gallery/                # UB Gallery block (dynamic + Interactivity API)
src/ub-timeline/               # UB Timeline block (table editor + Interactivity toggle)
src/ub-faq/                    # UB FAQ block (Q&A accordion)
src/ub-reading-time/           # UB Reading Time (from post word count)
src/ub-callout/                # UB Callout block (info/tip/warning/success)
src/ub-toc/                    # UB Table of Contents (from post headings)
src/ub-link/                   # UB Link block (URL + live status / strike-through)
src/ub-file/                   # UB File block (URL + optional local media mirror)
src/shared/                    # Shared front-end styles (status / broken links)
src/wp-usefull-blocks/         # Scaffold sample block (placeholder)
build/                         # Compiled assets (generated, git-ignored)
readme.txt                     # WordPress.org plugin readme
```

### Blocks

| Block | Name | Notes |
| --- | --- | --- |
| **UB Gallery** | `wp-usefull-blocks/ub-gallery` | Focus image + thumbnail strip; click = lightbox / media link / none |
| **UB Timeline** | `wp-usefull-blocks/ub-timeline` | Title/description entries; click title to expand; vertical or horizontal |
| **UB FAQ** | `wp-usefull-blocks/ub-faq` | Question/answer accordion; independent toggles |
| **UB Reading Time** | `wp-usefull-blocks/ub-reading-time` | Estimated minutes from this post’s word count |
| **UB Callout** | `wp-usefull-blocks/ub-callout` | Info / tip / warning / success note for readers |
| **UB Table of Contents** | `wp-usefull-blocks/ub-toc` | Auto list of this post’s headings with in-page links |
| **UB Link** | `wp-usefull-blocks/ub-link` | Normal WP-style link + traffic light; strike-through when broken (global settings) |
| **UB File** | `wp-usefull-blocks/ub-file` | File URL + manual “Download now” mirror; same status behaviour via global settings |
| WP Usefull Blocks | `wp-usefull-blocks/wp-usefull-blocks` | Scaffold sample; will be removed later |

Each block lives in its own folder under `src/` with a `block.json`. `npm run build`
compiles every block into `build/` and generates `build/blocks-manifest.php`, which the
plugin uses to register all blocks efficiently.

## Admin: Useful

Top-level menu **Useful**:

1. **Broken Links** — scan the site, list broken URLs, change/replace them in posts/pages  
2. **Settings** (last) — traffic-light select (before / after / off), strike-through, auto-check  

These settings apply to **all normal WordPress links** in post/page content site-wide.

## Localization

Source strings are English (WordPress convention). A German translation (`de_DE`) ships in
`languages/` (`wp-usefull-blocks-de_DE.po` / `.mo` plus block-editor JSON). Set the site language
to **Deutsch** under Settings → General to use it.

```bash
npm install
```

Common commands:

| Task | Command |
| --- | --- |
| Start dev build (watch) | `npm start` |
| Production build | `npm run build` |
| Lint JS | `npm run lint:js` |
| Lint styles | `npm run lint:css` |
| Lint (JS + styles) | `npm run lint` |
| Unit tests | `npm test` |
| Format | `npm run format` |
| Build distributable zip | `npm run plugin-zip` |

## Running WordPress locally

The block assets must be built (`npm run build`) before the block appears in WordPress.
Then run a local WordPress instance with this plugin activated using
[`@wp-now/wp-now`](https://www.npmjs.com/package/@wp-now/wp-now) (no Docker required):

```bash
npm run build
npx @wp-now/wp-now start
```

`wp-now` boots a local WordPress with this plugin auto-activated and prints the URL
(default `http://localhost:8881`, admin login `admin` / `password`). Open a new post in
the block editor and insert the **UB Gallery** block (Media category).

Alternatively, mount the plugin folder into any WordPress install under
`wp-content/plugins/wp-usefull-blocks/` and activate it from **Plugins**.
