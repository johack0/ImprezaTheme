---
title: `us_socials` — Social Links
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/socials.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/socials.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=socials
  Direct edits here will be lost on the next regeneration.
-->

# `us_socials` — Social Links

**When to use**: a row of social-platform icons linking to your profiles — header strip, footer column, author bio.

**Avoid when**:
- you need a single share-button — use a sharing plugin or `us_btn` with a sharer URL;
- you want follower-count widgets — that's a different shortcode/plugin altogether.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `items` (group) | Ordered list of social entries — a group attribute (URL-encoded JSON array in double quotes; see composition-rules §3.5). Each item is `{ "type": "<platform>", "url": <link> }`. `type` is a platform slug (`facebook`, `twitter`, `instagram`, `linkedin`, `youtube`, `github`, … or `custom`) and the icon is derived from it automatically. A per-item `icon`, `title` and `color` exist **only** when `type` is `custom`. Default item set: Facebook + Twitter. |
| `shape` | Icon container shape — `none` / `square` / `rounded` / `circle`. |
| `style` | `default` / `colored` / `outlined` / `solid`. |
| `icons_color` | `brand` (use platform default colors) / `text` / `link`. |
| `hover` | Hover effect — `fade` / `slide` / `none`. |
| `gap` | Gap between icons. |
| `hide_tooltip` | `1` to disable on-hover tooltip. |

**Minimal example**

Default Facebook + Twitter set (no params needed):

```text
[us_socials]
```

**Authoring `items` — copy, don't hand-write.** The platform goes in `type`; built-in platforms have **no** `icon` key (the icon is derived as `fab|<type>`, with a few special-cased ones). Each item's `url` is a `link` value that gets URL-encoded *inside* the already-URL-encoded `items` attribute (i.e. double-encoded), so assembling the string by hand is error-prone. Start from a section template that already ships a `[us_socials]` (the footer and contact categories do — see [sections.md](sections.md)) and swap each item's `type` and target URL, keeping the key names exactly as the template has them.

**Anti-patterns**

- Linking to a social account that hasn't posted in 2 years — better to omit.
- 10+ platform icons in a row — pick 3-5 most active channels.
