---
title: `us_counter` — Animated Counter
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/counter.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/counter.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=counter
  Direct edits here will be lost on the next regeneration.
-->

# `us_counter` — Animated Counter

**When to use**: highlight a number that animates from an initial value to a final one when it enters the viewport — "1 200+ customers", "98% uptime", "10 years". Typical for stats/social-proof sections.

**Avoid when**:
- the number is static informational text — use `[us_text]`;
- you need to count down to a date — use `[us_countdown_timer]`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `final` | Target value, **including any prefix/suffix as part of the string** — `99`, `$70`, `98%`, `1200+`, `35kg`. There is no separate prefix/suffix parameter. |
| `initial` | Starting value of the animation. Same shape as `final` (`0`, `$0`, `1%`). Visible when `animation="none"`. |
| `animation` | `none` (default — animate the number value) or `slide` (slide the digits vertically). |
| `duration` | Animation duration in seconds — `0.5s` to `4.0s` step `0.5s`. Default `2.0s`. |
| `align` | `none` / `left` / `center` / `right`. Default `center`. |
| `color` | `primary` / `secondary` / `heading` / `text` / `custom`. Picks a theme color. |
| `custom_color` | HEX/RGBA value when `color="custom"`. |
| `title` | Caption shown below the number. Supports dynamic values. |
| `title_tag` | HTML tag for the caption — `h6` (default), `h2`–`h5`, `div`, `span`. |
| `title_size` | Custom CSS font-size for the caption. |

**Minimal example**

```text
[us_counter final="1200+" title="Happy customers"]
```

**Common combinations**

Four stats in a row (note: units like `+`, `%`, `K` go inside `final`):

```text
[vc_row columns="4"]
  [vc_column][us_counter final="1200+" title="Customers" color="primary"][/vc_column]
  [vc_column][us_counter final="98%"   title="Uptime"    color="primary"][/vc_column]
  [vc_column][us_counter final="10"    title="Years"     color="primary"][/vc_column]
  [vc_column][us_counter final="50K"   title="Downloads" color="primary"][/vc_column]
[/vc_row]
```

**Anti-patterns**

- Decimal precision that nobody verifies (`1183.27`) — round to a memorable number.
- Vague suffixes (`many`, `lots of`) in `final` — defeats the purpose of a counter.
