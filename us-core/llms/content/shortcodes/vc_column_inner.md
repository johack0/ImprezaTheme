---
title: `vc_column_inner` — Inner Column
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_column_inner.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_column_inner.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_column_inner
  Direct edits here will be lost on the next regeneration.
-->

# `vc_column_inner` — Inner Column

**When to use**: the only allowed direct child of `vc_row_inner` (mirror of `vc_column` ↔ `vc_row` at the inner level).

**Key parameters**

Same shape as `vc_column`, with **one exception**: a `vc_column_inner` may **not** contain another `vc_row_inner`. The inner grid is one level deep only — WordPress's shortcode parser cannot nest a tag inside itself (see composition-rules §2.1). So `vc_column_inner` holds leaves and `us_hwrapper` / `us_vwrapper` wrappers, never a nested `vc_row_inner`. The most important param is `width="<num>/<den>"` — it determines the inner row's layout, exactly as on `vc_column`. See the `vc_column` entry for the full width-to-layout mapping.

| Param | What it does |
|-------|--------------|
| `width` | Fractional width of this inner column. Required when the inner row has more than one column. See `vc_column` for the full enum. |

**Minimal example**

See `vc_row_inner` above.

**Anti-patterns**

- Using `vc_column_inner` inside `vc_row` (instead of `vc_column`) — wrong tag at the wrong level.
- **Putting a `vc_row_inner` inside a `vc_column_inner`** — same-tag nesting (`vc_row_inner > vc_column_inner > vc_row_inner`); WordPress can't parse it and the block breaks. Use `us_hwrapper` / `us_vwrapper` for a sub-arrangement, or flatten the layout. See composition-rules §2.1.
- **Omitting `width="…"`** when the inner row has multiple columns — defaults to `1/1` and the row collapses to a single column.
