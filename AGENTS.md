# AGENTS.md

## Cursor Cloud specific instructions

This repository is a single product: the **WP Usefull Blocks** WordPress plugin — custom
Gutenberg (block editor) blocks built with `@wordpress/scripts`. There is no separate
backend/frontend split; the "app" is WordPress hosting this plugin.

Standard commands are defined in `package.json` and documented in `Readme.md` — use those
as the source of truth. Notes below are the non-obvious caveats.

### Building (required before the block shows up)
- The compiled `build/` directory is git-ignored and is **not** produced by the startup
  update script. WordPress will load the plugin, but the block only registers after a
  build. Run `npm run build` (one-off) or `npm start` (watch) before running WordPress or
  expecting the block in the editor.

### Running WordPress locally
- `npx @wp-now/wp-now start` (run from the repo root) boots a local WordPress in *plugin
  mode* with this plugin auto-activated at `http://localhost:8881` (admin login
  `admin` / `password`). First run downloads WordPress + SQLite + a PHP-wasm runtime over
  the network, then caches them.
- No Docker or system PHP is required for `wp-now`.
- `wp-now` is deprecated upstream; the maintained successor is
  `npx @wp-playground/cli@latest start`. Prefer it if `wp-now` breaks.
- The classic Docker-based alternative (`@wordpress/env`) is not configured here and would
  require installing Docker.

### Testing
- Unit tests run with `npm test` (`wp-scripts test-unit-js`, Jest).
- Gotcha: importing `@wordpress/block-editor` inside a test pulls in an ESM-only `uuid`
  build that Jest does not transform, causing `SyntaxError: Unexpected token 'export'`.
  Mock `@wordpress/block-editor` in unit tests instead of importing the real package (see
  `src/wp-usefull-blocks/__tests__/block.test.js` for the pattern).
