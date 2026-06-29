---
title: `vc_tta_section` — Tab / Accordion / Tour Item
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_tta_section.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_tta_section.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_tta_section
  Direct edits here will be lost on the next regeneration.
-->

# `vc_tta_section` — Tab / Accordion / Tour Item

**When to use**: a single panel inside `vc_tta_tabs`, `vc_tta_accordion`, or `vc_tta_tour`. The tag is the same in all three contexts; the wrapper decides how it renders.

**Avoid when**:
- you use it outside any of the three parent containers — it has no rendering context;
- you want a generic info card — use `[us_iconbox]` or a styled `[us_text]`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `title` | Label shown on the trigger (tab name / accordion summary). Required. Default `Tab 1`. Supports dynamic values. |
| `tab_link` | Optional link — when set, **clicking the trigger navigates to this URL** instead of switching the panel. Use this to turn a tab into a navigation item that opens an external page or anchor. URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank","rel":"nofollow"}`. Supports dynamic values. |
| `active` | `1` makes this section open by default when the page loads. Default `0`. Only one section should typically have `active="1"` per container (in accordion with `toggle="1"`, more than one is allowed). |
| `indents` | `1` stretches the section content to the **full available width**, removing the default left/right indents. Useful for full-width media inside a tab. Default `0`. |
| `icon` | Icon shown next to the trigger title (`set|name`, e.g. `fas|info-circle`). |
| `i_position` | Icon position — `left` (default, before title) or `right` (after title). |
| `bg_color` | Per-section background color (HEX/RGBA/palette var). Overrides the parent container's background for this section only. |
| `text_color` | Per-section text color. Use together with `bg_color` for sections that need a distinct theme. |

There is **no** `tab_id` parameter for anchor / deep-linking — for that purpose use the common `el_id` parameter from the Design group instead, and link to `#yourid` from elsewhere on the page.

**Minimal example**

```text
[vc_tta_section title="Starter"]
  [us_text text="Starter content"]
[/vc_tta_section]
```

**Common combinations**

A section that opens by default, with an icon prefix:

```text
[vc_tta_section title="Overview" active="1" icon="fas|home" i_position="left"]
  [us_text text="Welcome…"]
[/vc_tta_section]
```

A tab that navigates to an external page instead of switching panels:

```text
[vc_tta_section title="Docs" tab_link="%7B%22url%22%3A%22https%3A%2F%2Fexample.com%2Fdocs%22%2C%22target%22%3A%22_blank%22%7D"]
[/vc_tta_section]
```

A featured/highlighted section with custom colors and stretched content:

```text
[vc_tta_section title="Pro" bg_color="#1a73e8" text_color="#fff" indents="1"]
  [us_image image="123" size="full"]
[/vc_tta_section]
```

**Anti-patterns**

- Using `tab_id` for deep-linking — that parameter does not exist on `vc_tta_section`. Set `el_id` (from the Design group) and link to `#<id>` from outside.
- Setting `active="1"` on multiple sections inside a tabs/tour container — only one panel can be active at a time; the last wins.
- Empty `title` — the trigger becomes unclickable on touch and unreadable to screen readers.
- Using `tab_link` thinking it's a deep-link — it converts the tab into a navigation link that **leaves** the panel-switching behaviour entirely.
