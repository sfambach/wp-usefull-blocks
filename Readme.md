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
| **UB Link** | `wp-usefull-blocks/ub-link` | URL + traffic light; broken links struck through; auto-check on render + cron |
| **UB File** | `wp-usefull-blocks/ub-file` | File URL + manual “Download now” mirror; same status / strike-through behaviour |
| WP Usefull Blocks | `wp-usefull-blocks/wp-usefull-blocks` | Scaffold sample; will be removed later |

Each block lives in its own folder under `src/` with a `block.json`. `npm run build`
compiles every block into `build/` and generates `build/blocks-manifest.php`, which the
plugin uses to register all blocks efficiently.

## Development

Install dependencies:

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
