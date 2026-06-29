---
title: `vc_row_inner` — Inner Row
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_row_inner.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_row_inner.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_row_inner
  Direct edits here will be lost on the next regeneration.
-->

# `vc_row_inner` — Inner Row

**When to use**: a nested grid inside a `vc_column`. Use when one column of the outer row needs its own multi-column layout (e.g. a sidebar block with two stacked cards, or a feature row inside a tab section).

**Avoid when**:
- you only need a horizontal arrangement of two-three elements with a gap — `[us_hwrapper]` is lighter;
- you are at the top level — that's `vc_row`'s job, not `vc_row_inner`.

**Key parameters**

`vc_row_inner` only has **column-layout** parameters — none of the section-level row settings (no background image, no color scheme, no full-height, no sticky). Its children must be `vc_column_inner`.

The desktop column layout follows the same rule as `vc_row`: it is **derived from the `width` attribute on each child `vc_column_inner`**, not from any attribute on the inner row itself. See `vc_column` for the full list of allowed `width` fractions and their layouts. The responsive overrides below work as attributes on the inner row.

| Param | What it does |
|-------|--------------|
| `columns` | _Computed automatically from child `vc_column_inner` widths._ Do not set — overwritten at render time. |
| `columns_layout` | Auto-computed CSS `grid-template-columns` value when children reduce to `custom`. Do not set. |
| `columns_gap_source` | Same as `vc_row.columns_gap_source` — `default` (default: site-wide gap from Theme Options) or `custom` (use this inner row's `columns_gap`). Emit the pair `columns_gap_source="custom" columns_gap="…"` to set a custom gap. |
| `columns_gap` | Gap between columns, applied with `columns_gap_source="custom"`. Same shape as `vc_row.columns_gap` — either scalar (`"2rem"`) or per-breakpoint **URL-encoded JSON in DOUBLE quotes** (`columns_gap="%7B%22default%22%3A%224rem%22%2C%22mobiles%22%3A%221rem%22%7D"` decodes to `{"default":"4rem","mobiles":"1rem"}`; single-quoted raw JSON is mangled by `wptexturize` — never use it). No separate `tablets_columns_gap` / `mobiles_columns_gap` attribute. See composition-rules §3.4. |
| `columns_reverse` | `1` reverses column stacking on mobile (last column goes on top). |
| `equal_columns_height` | `1` makes columns the same height. |
| `content_placement` | Vertical alignment of column content — `top` / `middle` / `bottom`. |
| `columns_type` | `1` adds extra padding around the column content (useful when columns have a background). |
| `gap` | Additional gap (CSS units). |
| `laptops_columns` / `tablets_columns` / `mobiles_columns` | Responsive column overrides on the inner row. Same enum as the computed `columns` on `vc_row`. |
| `ignore_columns_stacking` | `1` ignores the global "Columns Stacking Width" theme option. |

**Minimal example**

```text
[vc_row][vc_column]
  [vc_row_inner]
    [vc_column_inner width="1/2"][us_text text="Left"][/vc_column_inner]
    [vc_column_inner width="1/2"][us_text text="Right"][/vc_column_inner]
  [/vc_row_inner]
[/vc_column][/vc_row]
```

**Anti-patterns**

- Wrapping `vc_row_inner` directly under `post_content` — it must live inside `vc_row → vc_column`.
- Mixing `vc_column` (instead of `vc_column_inner`) inside `vc_row_inner` — wrong tag, layout breaks.
- Trying to set a background image / `color_scheme` / `full_height` on `vc_row_inner` — those are section-level and only exist on `vc_row`. Wrap the inner row in an outer row if you need a section background.
- **Nesting a second `vc_row_inner` inside a `vc_column_inner`** (`vc_row_inner > vc_column_inner > vc_row_inner`). There is exactly **one** level of inner grid: WordPress's shortcode parser is not recursive per tag, so a `vc_row_inner` inside a `vc_row_inner` mis-pairs its closing tag and the block breaks on the front end. For a sub-arrangement inside an inner column use `us_hwrapper` / `us_vwrapper`, or flatten the layout. See composition-rules §2.1.
- **Omitting `width="…"` on `vc_column_inner` children.** Same trap as on `vc_row`: bare inner columns default to `width="1/1"` and the row collapses to one column.
