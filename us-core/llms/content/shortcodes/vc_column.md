---
title: `vc_column` — Column
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_column.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_column.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_column
  Direct edits here will be lost on the next regeneration.
-->

# `vc_column` — Column

**When to use**: the only allowed direct child of `vc_row`. Every content element lives inside a column.

**Avoid when**:
- you want columns inside columns — use `vc_row_inner` + `vc_column_inner` for nested grids;
- you need a horizontal/vertical wrapper with gap controls — use `[us_hwrapper]` / `[us_vwrapper]` inside the column.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `width` | **The column's fractional width — sets the row layout.** Format `<num>/<den>`. Default `1/1` (single full-width column). See the table below for the full enum. |
| `sticky` | `1` pins the column at the top during scroll (typical for sidebars). |
| `sticky_pos_top` | When `sticky=1`, distance from the top of the viewport at which the column sticks (CSS units, e.g. `80px`, `6rem`). |
| `stretch` | `1` stretches the column to the browser-window edge, ignoring the row's content width. |
| `us_bg_overlay_color` | A color/gradient drawn as an overlay above the column background — useful when an image is set on the parent row. |
| `link` | Wraps the whole column in a clickable link. **Note**: all inner clickable elements become non-clickable in this mode. URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank","rel":"nofollow"}`. |

### How column layout works

The row's overall layout is **derived from the `width` attributes of its child columns**, not from any attribute on the row itself. At render time, us-core scans the row's children, collects their `width` values, and reduces them to a layout descriptor. A row with no widths (or `width="1/1"` on each child) is always a single-column row, no matter what other attributes you set.

Allowed `width` values and the layouts they produce:

| Children `width` (in order) | Resulting layout |
|-----------------------------|------------------|
| `1/1` | 1 column, full width |
| `1/2`, `1/2` | 2 equal columns |
| `1/3`, `1/3`, `1/3` | 3 equal columns |
| `1/4` × 4 | 4 equal columns |
| `1/5` × 5 | 5 equal columns |
| `1/6` × 6 | 6 equal columns |
| `1/3`, `2/3` | 2 columns, 1/3 + 2/3 |
| `2/3`, `1/3` | 2 columns, 2/3 + 1/3 |
| `1/4`, `3/4` | 2 columns, 1/4 + 3/4 |
| `3/4`, `1/4` | 2 columns, 3/4 + 1/4 |
| `1/5`, `4/5` | 2 columns, 1/5 + 4/5 |
| `4/5`, `1/5` | 2 columns, 4/5 + 1/5 |
| `1/6`, `5/6` | 2 columns, 1/6 + 5/6 |
| `5/6`, `1/6` | 2 columns, 5/6 + 1/6 |
| `2/5`, `3/5` | 2 columns, 2/5 + 3/5 |
| `3/5`, `2/5` | 2 columns, 3/5 + 2/5 |
| `1/4`, `1/2`, `1/4` | 3 columns, 1/4 + 1/2 + 1/4 |
| `1/5`, `3/5`, `1/5` | 3 columns, 1/5 + 3/5 + 1/5 |
| `1/6`, `2/3`, `1/6` | 3 columns, 1/6 + 2/3 + 1/6 |
| any other combination | falls back to a custom CSS-grid layout (each `width` becomes a `1fr`-scaled track) |

Responsive overrides for narrower viewports are controlled by `laptops_columns` / `tablets_columns` / `mobiles_columns` on the parent **`vc_row`** (not the columns) — see `vc_row` for details.

**Minimal example**

Single full-width column:

```text
[vc_row][vc_column][us_text text="Body"][/vc_column][/vc_row]
```

Three equal columns:

```text
[vc_row]
  [vc_column width="1/3"][us_text text="A"][/vc_column]
  [vc_column width="1/3"][us_text text="B"][/vc_column]
  [vc_column width="1/3"][us_text text="C"][/vc_column]
[/vc_row]
```

**Common combinations**

Sticky sidebar column (1/4) next to a wider content column (3/4):

```text
[vc_row]
  [vc_column width="1/4" sticky="1" sticky_pos_top="80px"]
    [us_additional_menu source="docs-nav" layout="ver"]
  [/vc_column]
  [vc_column width="3/4"]
    [us_text text="Doc title" tag="h1"]
    [vc_column_text]Body text with multiple paragraphs and rich HTML.[/vc_column_text]
  [/vc_column]
[/vc_row]
```

Three columns on desktop, two on tablet, one on mobile — desktop comes from the column widths, the others from the row attributes:

```text
[vc_row tablets_columns="2" mobiles_columns="1"]
  [vc_column width="1/3"]…[/vc_column]
  [vc_column width="1/3"]…[/vc_column]
  [vc_column width="1/3"]…[/vc_column]
[/vc_row]
```

**Anti-patterns**

- **Omitting `width` on multi-column rows.** Bare `[vc_column]` defaults to `width="1/1"`, so three bare `[vc_column]` siblings still render as a single full-width column (the layout is computed from widths, not from how many columns you wrote).
- **Setting `columns="3"` on the `vc_row` and expecting it to split the row.** That attribute is overwritten at render time from the child `width` values; without `width` on each column it has no effect.
- **Mixing fractions with different denominators in a three-or-more-column row** unless the combination is one of the special triples (`1/4+1/2+1/4`, `1/5+3/5+1/5`, `1/6+2/3+1/6`). Other mixes fall through to a custom CSS-grid layout and may not look as you expect.
- **Setting `link` on a column that contains buttons or other links** — those become non-clickable.
- **Using `stretch="1"` on every column** — the row stops feeling like a row.
