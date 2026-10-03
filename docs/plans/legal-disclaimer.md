# Plan: License & liability disclaimer

> Status: **Decision recorded** (no activation gate). Saved plan data.
>
> Context: Users of Gutenberg blocks may hit bugs or misconfiguration. Question was
> whether open-source use is already “at own risk”, or whether the plugin must show
> a liability disclaimer that the user confirms on install/activation.

## Decision

**No confirmation dialog on install or activation.**

WordPress plugins are distributed under a **GPL-compatible license**. The GPL already
includes an **“AS IS” / no-warranty** clause and a **limitation of liability** (to the
extent permitted by law). That is sufficient for this free plugin; we will not add a
click-to-accept gate.

## What we will do instead

1. **Ship a `LICENSE` file** in the plugin root (default for this project:
   **GPL-2.0-or-later**, matching typical WordPress practice). Also declare the license
   in the plugin header and `readme.txt`.
2. **Short disclaimer in documentation** (plugin `readme.txt` / project README / FAQ),
   in clear language, for example:
   - Software is provided as is, without warranty.
   - Use at your own risk; keep backups before major updates or bulk tools.
   - The plugin assists with site features; site owners remain responsible for their
     content, accessibility, and legal compliance.
3. **Feature-specific notes** where risk is higher (e.g. Link Checker, Headings
   Converter, file mirroring): mention caveats in that feature’s UI help text and FAQ —
   not as a global activation blocker.
4. **Optional (later):** a one-time, **non-blocking** admin notice after first activation
   with a link to the docs/FAQ. Must **not** require a checkbox or prevent using the
   plugin.

## What we will not do

- No modal, checkbox, or “I agree” step on install or activate.
- No blocking of plugin features until a legal text is accepted.
- No custom Terms of Service that contradict or replace the GPL grant.

## Rationale (short)

| Approach | Verdict |
|---|---|
| Rely on GPL + docs | **Chosen** — standard for free WordPress plugins |
| Forced accept on activate | Rejected — poor UX, uncommon/problematic for WordPress.org-style plugins, little extra protection beyond the license |

## Implementation notes (when scaffolding / releasing)

- Add `LICENSE` when the plugin scaffold lands (see PR #1 / setup branch).
- Add a FAQ entry under Settings/docs before the first public release (bundled with the
  usual pre-release i18n/docs pass).
- Do **not** implement activation hooks solely for legal acceptance.

## Legal note

This document records a product decision for the project. It is not legal advice.
If commercial distribution or paid support is added later, revisit whether separate
terms are needed for that offering (still without blocking core GPL plugin use).
