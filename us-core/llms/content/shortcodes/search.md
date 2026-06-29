---
title: `us_search` — Search Form
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/search.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/search.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=search
  Direct edits here will be lost on the next regeneration.
-->

# `us_search` — Search Form

**When to use**: a search input with submit icon. Standard places: site header (via Header Builder), inside a 404 page, on an archive sidebar.

**Avoid when**:
- you need a faceted product search — that's not what `us_search` does — see `shortcodes/product_list` + `shortcodes/list_filter`;
- you only need a static "Search" link — `us_text` with an `<a>` tag.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `text` | Placeholder text shown inside the input (e.g. `Search docs…`). |
| `search_post_type` | Comma-separated list of post-type slugs to restrict search to (e.g. `post,product`). Empty = all. |
| `us_field_style` | Per-site field style (Theme Options → Field Styles). |
| `icon` | Submit icon (`fas|search` by default). |
| `icon_pos` | Position of the search icon — `left` or `right`. |
| `field_bg_color` | Background color of the input. |
| `field_text_color` | Text color of the input. |
| `icon_size` | Icon size (CSS units, e.g. `18px`). |

Note: `us_search` is dual-purpose — it also appears inside the Header Builder where it gets extra context-only params (`layout`, `field_width`, responsive icon sizes). Those are header-only and not editable via `[us_search ...]` in `post_content`.

**Minimal example**

```text
[us_search text="Search docs…" icon="fas|search" icon_pos="right"]
```

**Anti-patterns**

- Two search forms on the same page — pick one location.
- Long placeholder text — gets truncated on mobile.
