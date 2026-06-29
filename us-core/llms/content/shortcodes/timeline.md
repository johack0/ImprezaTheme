---
title: `us_timeline` — Timeline
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/timeline.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/timeline.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=timeline
  Direct edits here will be lost on the next regeneration.
-->

# `us_timeline` — Timeline

**When to use**: a vertical sequence of content blocks strung along a connecting line with markers — company history, product roadmap, a step-by-step "how it works", changelog, hiring process. Each entry is a `us_timeline_section` child.

**Avoid when**:
- the steps are short and purely sequential with no rich content per step — a numbered `[us_iconbox]` row or a styled `[us_text]` list is lighter;
- you need switchable panels rather than an always-visible vertical list — use `[vc_tta_tabs]` / `[vc_tta_tour]`;
- you want a horizontal, rotating set of cards — use `[us_content_carousel]`.

**Children**: **only** `us_timeline_section` — the container rejects every other element as a direct child. Put the actual content (text, images, buttons, icon boxes…) *inside* each section. A timeline with no sections renders nothing at all.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `line_pos` | Which side the connecting line sits on. `left` = line on the left (default), `right` = line on the right, `center` = line down the middle with sections alternating left/right. |
| `line_style` | Line stroke — `solid` (default), `dashed`, `dotted`, `double`. |
| `line_color` | Line color (palette token / HEX / RGBA). Default `_content_border`. |
| `line_thickness` | Line width in px, `0`–`10`. Default `1px`. |
| `line_offset` | Gap between the marker and where the section content starts. Default `1.5rem`. |
| `section_gap` | Vertical gap between consecutive sections. Default `2.5rem`. |
| `hide_line_endings` | `1` trims the short line stubs above the first / below the last marker. Default `0`. |

**Marker parameters** — these set the default marker for the whole timeline; an individual section can override its color and icon (see `us_timeline_section`).

| Param | What it does |
|-------|--------------|
| `marker_style` | Marker shape — `number` (default, auto-increments 1, 2, 3…), `icon`, `circle`, `square`, `diamond`, `dash`. |
| `marker_icon` | Icon used when `marker_style="icon"` (`set|name`, default `fas|star`). Also the fallback icon for any section that opts into an icon marker without setting its own. |
| `marker_size` | Marker glyph size — any CSS length, e.g. `1.2rem` (default), `10px`, `clamp(16px, 3vw, 30px)`. |
| `marker_circle_scale` | Size of the round badge behind a `number` / `icon` marker, `1`–`4` (default `2.5`). Ignored by the plain shape styles. |
| `marker_valign` | Vertical position of the marker against its content — `top` (default), `middle`, `bottom`. |
| `marker_background_color` | Marker fill. Default `_content_bg_alt`. |
| `marker_text_color` | Number / icon color (only `number` & `icon` styles). Default `_content_faded`. |
| `marker_border_color` | Marker outline color (only `number` & `icon` styles). Default `_content_border`. |
| `marker_border_width` | Marker outline width in px, `0`–`10` (only `number` & `icon` styles). Default `1px`. |
| `sticky_markers` | `1` pins the marker to the viewport while its section scrolls past. Default `0`. |
| `marker_hide_screen_width` | Below this screen width the markers **and** the line are hidden and content reflows to a single left-aligned column. Default `600px`; clear it to keep the timeline on the smallest screens. |

For positioning, spacing, background and the anchor `el_id`, use the common **Design** group (`css=`) — same as every other element.

**Minimal example**

```text
[us_timeline]
  [us_timeline_section][us_text text="2019 — Founded in a garage."][/us_timeline_section]
  [us_timeline_section][us_text text="2022 — Shipped v1 to 10k users."][/us_timeline_section]
  [us_timeline_section][us_text text="2025 — Closed Series A."][/us_timeline_section]
[/us_timeline]
```

**Common combinations**

Centered, alternating timeline with icon markers:

```text
[us_timeline line_pos="center" marker_style="icon" marker_icon="fas|check-circle"]
  [us_timeline_section][us_text text="Discovery"][/us_timeline_section]
  [us_timeline_section][us_text text="Design"][/us_timeline_section]
  [us_timeline_section][us_text text="Launch"][/us_timeline_section]
[/us_timeline]
```

Numbered process with a dashed line and wider spacing:

```text
[us_timeline marker_style="number" line_style="dashed" section_gap="3.5rem"]
  [us_timeline_section][us_iconbox icon="fas|search" title="Research" content="..."][/us_timeline_section]
  [us_timeline_section][us_iconbox icon="fas|pencil-ruler" title="Prototype" content="..."][/us_timeline_section]
  [us_timeline_section][us_iconbox icon="fas|rocket" title="Ship" content="..."][/us_timeline_section]
[/us_timeline]
```

**Anti-patterns**

- Putting content directly inside `[us_timeline]` — only `[us_timeline_section]` is accepted as a child; everything else is dropped. Wrap each entry in a section.
- Leaving the timeline empty — with no sections it outputs nothing (not even the line).
- Reading `line_pos` as the visual side — `line_pos="right"` puts the line on the **left**. Check the mapping above.
- Using a timeline for two or three short labels — the line and markers are visual overhead a plain list reads better than.
