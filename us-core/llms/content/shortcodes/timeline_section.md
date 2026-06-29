---
title: `us_timeline_section` — Timeline Section
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/timeline_section.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/timeline_section.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=timeline_section
  Direct edits here will be lost on the next regeneration.
-->

# `us_timeline_section` — Timeline Section

**When to use**: one entry inside `[us_timeline]`. It holds the content for a single point on the timeline and, optionally, overrides that point's marker. Add as many as the timeline needs, in top-to-bottom order — markers auto-number in that order.

**Avoid when**:
- outside `[us_timeline]` — it only renders as a direct child of the timeline and has no standalone meaning;
- you just need a generic content box elsewhere — use `[us_iconbox]`, `[us_hwrapper]` / `[us_vwrapper]`, or a styled `[us_text]`.

**Children**: almost any leaf / content element — `[us_text]`, `[us_image]`, `[us_btn]`, `[us_iconbox]`, `[us_separator]`, `[us_hwrapper]` / `[us_vwrapper]`, etc. **Not allowed**: `vc_row`, `vc_row_inner`, `vc_column`, the `vc_tta_*` tabs / accordion / tour set, `[us_content_carousel]`, and nested `[us_timeline]` / `[us_timeline_section]`. For multi-column layout inside a section use `[us_hwrapper]`.

**Key parameters** — all are per-section *overrides*; leave them empty to inherit the look set on the parent `[us_timeline]`.

| Param | What it does |
|-------|--------------|
| `marker_icon` | Icon for **this** section's marker (`set|name`). Setting it forces an icon marker for this section even when the timeline's `marker_style` is `number` or a shape — handy for flagging one milestone. Empty = inherit the timeline's `marker_icon`. |
| `marker_background_color` | This section's marker fill. Empty = inherit. |
| `marker_text_color` | This section's number / icon color. Empty = inherit. |
| `marker_border_color` | This section's marker outline color. Empty = inherit. |

There is **no** `title` parameter — the marker shows the auto number (or the icon / shape from the parent), and everything visible comes from the child elements you place inside. Need an anchor target? Use the common `el_id` from the Design group.

**Minimal example**

```text
[us_timeline_section]
  [us_text text="2024 — Reached 100k customers."]
[/us_timeline_section]
```

**Common combinations**

Highlight a single milestone with a custom icon and accent fill while the rest of the timeline stays numbered:

```text
[us_timeline marker_style="number"]
  [us_timeline_section][us_text text="Kickoff"][/us_timeline_section]
  [us_timeline_section marker_icon="fas|star" marker_background_color="_content_primary"]
    [us_text text="Major release"]
  [/us_timeline_section]
  [us_timeline_section][us_text text="Next phase"][/us_timeline_section]
[/us_timeline]
```

A section with richer content (heading + image + button):

```text
[us_timeline_section]
  [us_text tag="h4" text="Series A"]
  [us_image image="123" size="medium"]
  [us_btn label="Read the announcement" link="..."]
[/us_timeline_section]
```

**Anti-patterns**

- Using `[us_timeline_section]` on its own or inside anything but `[us_timeline]` — it won't render.
- Dropping a `vc_row` or `vc_column` inside — the container blocks them; use `[us_hwrapper]` / `[us_vwrapper]` for inner layout.
- Setting the same marker colors on every section — set them once on the parent `[us_timeline]` and override only the exceptions.
- Expecting a `title=` attribute — there isn't one; put a heading element inside the section instead.
