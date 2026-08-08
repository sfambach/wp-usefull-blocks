=== WP Usefull Blocks ===
Contributors:      The WordPress Contributors
Tags:              block
Tested up to:      6.8
Stable tag:        0.7.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Some useful blocks for the WordPress Gutenberg editor.

== Description ==

WP Usefull Blocks provides practical Gutenberg blocks. This release includes **UB Gallery**
(eBay-style focus image + thumbnails), **UB Link** (URL with live status), **UB File**
(file URL with optional local media mirror via “Download now”), **UB FAQ** (Q&A accordion),
and **UB Reading Time** (estimated minutes from post word count).

Under the top-level **Useful** menu: Broken Links (list + edit URLs) and Settings.
When enabled, every normal WordPress link in post/page content gets a traffic-light status
and optional strike-through for broken URLs — site-wide. Status is checked on page render
(cached) and refreshed in the background via WP-Cron.

The plugin is provided as is under GPL-2.0-or-later. Use at your own risk and keep backups
before major updates.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-usefull-blocks` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Insert the **UB Gallery** block from the Media category and select images.

== Frequently Asked Questions ==

= Is there a warranty or liability acceptance step? =

No. The plugin is GPL-licensed software provided as is. There is no confirmation dialog on
install or activation. Site owners remain responsible for their content and backups.

= What does clicking the large gallery image do? =

By default it opens a lightbox. In the block sidebar you can switch that to “Link to media
file” or “None”.

= How are broken links detected and shown? =

Go to Useful → Settings and choose the traffic-light position from the select list
(before / after / off; default: before), enable strike-through, and automatic URL checks.
This applies to every standard link in post and page content on the whole site.
External URLs are checked remotely; links to posts/pages on the same site are verified via
WordPress (no HTTP loopback). Results are cached (default 12 hours) and refreshed hourly
via WP-Cron. Broken links are struck through on the front end. Full-page caches may delay
the visual update until that HTML is regenerated.

= Where are the link status settings? =

Useful → Settings (top-level admin menu “Useful”; Settings is the last submenu item).

= How do I find and fix broken links? =

Open Useful → Broken Links, click Scan now, then use “Update URL” on a row to replace that
link in all listed posts/pages.

= Why does UB File not download automatically? =

Mirroring is intentional and manual. Use “Download now” in the editor to copy the file into
the media library. If you change the source URL later, press Download now again.

= How do I configure the UB Timeline block? =

Insert **UB Timeline**, fill the Title / Description table, and choose Vertical or Horizontal
in the block sidebar. On small screens a horizontal timeline stacks vertically. Clicking a
title on the front end expands or collapses its description. There are no required global
settings under Useful → Settings.

= How do UB Callout and Table of Contents work? =

**UB Callout** is a highlighted note (info / tip / warning / success) with optional title.
**UB Table of Contents** lists headings from the same post; set min/max heading levels in the
sidebar. On the front end, headings get stable `id`s so TOC links jump correctly. In the
editor, core Heading blocks also get **− / +** toolbar buttons to change the level quickly.

= How do UB FAQ and Reading Time work? =

**UB FAQ** uses a Question / Answer table in the editor. On the front end, each question
toggles its answer independently (same Interactivity pattern as UB Timeline).
**UB Reading Time** estimates minutes from this post’s word count (default 200 words/minute).
Optional prefix label and word-count display are in the block sidebar.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 0.7.0 =
* Add UB FAQ block: Question/Answer table editor with click-to-expand answers.
* Add UB Reading Time block: estimated minutes from post word count (configurable WPM).

= 0.6.0 =
* Add UB Callout block (info / tip / warning / success notes).
* Add UB Table of Contents block from post headings with stable in-page anchors.
* Add − / + heading level buttons on core Heading blocks in the editor.
* Document further daily-helper ideas in docs/plans/daily-helpers.md.

= 0.5.0 =
* Add UB Timeline block: Title/Description table editor, vertical/horizontal layout.
* Front end click-to-expand descriptions (Interactivity API); SSR preview in the editor.
* Useful → Settings: placeholder section for UB Timeline (per-block controls in the sidebar).

= 0.4.0 =
* Broken Links admin: traffic-light status, reason + last-checked time, reload icon.
* Update URL in-place (AJAX): ampel updates without page jump; Recheck uses new URL when set.
* Faster single-URL recheck (no full rescan); compact Old/New URL columns with copy arrow.

= 0.3.0 =
* Add UB Link block — WordPress-style link (RichText + link UI) with traffic-light status.
* Add UB File block with manual “Download now” media mirror (refresh when URL changes).
* Global settings under Settings → Usefull Blocks: show status, strike-through, auto-check.
* Broken links are struck through on the front end; status auto-checks on page load (cached) and via hourly WP-Cron.

= 0.2.0 =
* Add UB Gallery block (focus image + thumbnail strip, lightbox/media/none click modes).

= 0.1.0 =
* Initial scaffold release.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above. This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation." Arbitrary sections will be shown below the built-in sections outlined above.
