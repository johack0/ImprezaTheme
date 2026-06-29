---
title: `us_separator` — Separator
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/separator.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/separator.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=separator
  Direct edits here will be lost on the next regeneration.
-->

# `us_separator` — Separator

**When to use**: introduce vertical space or a visible divider between content blocks. Two roles:

- **Spacer** — invisible vertical gap (no line);
- **Divider** — horizontal line with optional thickness/color.

**Avoid when**:
- you just need spacing inside a horizontal/vertical wrapper — use the `gap` param on `us_hwrapper`/`us_vwrapper`;
- you want a decorative shape between sections — use `vc_row.us_shape_*` (shape dividers).

**Key parameters**

| Param | What it does |
|-------|--------------|
| `size` | Vertical space size — preset (`small`/`medium`/`large`/`huge`) or `custom`. |
| `height` | Custom CSS height when `size="custom"` (e.g. `60px`, `4rem`). |
| `show_line` | `1` to draw a visible line, `0` for invisible spacer. |
| `line_width` | Width of the line (`100%`, `50%`, `200px`). |
| `thick` | Line thickness in px. |
| `style` | Line style — `solid` / `dashed` / `dotted` / `double`. |
| `color` | Line color (HEX, RGBA, or theme palette var). |

**Minimal example**

```text
[us_separator size="medium" show_line="1"]
```

**Anti-patterns**

- Using multiple consecutive `us_separator` blocks to "tune" spacing — set one `size="custom" height="..."` instead.
- Heavy decorative lines between every section — kills visual hierarchy. Use sparingly.
