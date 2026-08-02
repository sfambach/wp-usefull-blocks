# WP Usefull Blocks

Some useful blocks for the WordPress Gutenberg (block) editor, packaged as a standard
WordPress plugin built with [`@wordpress/scripts`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
and the modern Block API (v3, `block.json` as the single source of truth).

## Requirements

- Node.js 20+ and npm (used for the block build tooling)
- A WordPress 6.8+ site with PHP 7.4+ to run the plugin

## Project structure

```
wp-usefull-blocks.php          # Plugin bootstrap (registers blocks from build/)
src/wp-usefull-blocks/         # Block source (block.json, edit.js, save.js, styles)
build/                         # Compiled assets (generated, git-ignored)
readme.txt                     # WordPress.org plugin readme
```

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
the block editor and insert the **WP Usefull Blocks** block.

Alternatively, mount the plugin folder into any WordPress install under
`wp-content/plugins/wp-usefull-blocks/` and activate it from **Plugins**.
