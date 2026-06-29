---
title: `us_progbar` — Progress Bar
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/progbar.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/progbar.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=progbar
  Direct edits here will be lost on the next regeneration.
-->

# `us_progbar` — Progress Bar

**When to use**: visualise a percentage that animates when entering the viewport — skill levels, progress towards a goal, completion stats.

**Avoid when**:
- the value isn't a percentage — use `[us_counter]`;
- you need real-time progress (file upload) — that needs JS, not a static shortcode.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `count` | Current value displayed as a string — `50%`, `7/10`, `42`. Required. |
| `final_value` | Target value the bar fills to — `100%`, `10`, etc. The bar animates from 0 to this value's numeric part. |
| `hide_count` | `1` hides the current value next to the bar. Default `0`. |
| `hide_final_value` | `1` hides the target value. Default `1`. Visible only when `hide_count="0"`. |
| `title` | Label shown above the bar. |
| `title_tag` | HTML tag for the title — `h6` (default), `h2`–`h5`, `div`, `span`. |
| `title_size` | Custom title font-size (CSS units). |
| `style` | Visual style — `1`, `2`, `3`, `4`, `5`. |
| `color` | `primary` / `secondary` / `heading` / `text` / `custom`. |
| `bar_color` | Custom HEX/RGBA for the fill — used when `color="custom"`. |
| `size` | Bar thickness (e.g. `10px`, `0.5rem`). |

**Minimal example**

```text
[us_progbar count="80%" final_value="100%" title="Reliability" color="primary"]
```

**Anti-patterns**

- Five bars all at 90%+ — readers stop trusting the data.
- Mixing units across one cluster (% next to absolute counts) — confusing without context.
- Using `hide_count="1"` and `hide_final_value="1"` together — the user sees only a filled bar with no number.
