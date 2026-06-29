---
title: `us_vwrapper` — Vertical Wrapper
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vwrapper.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vwrapper.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vwrapper
  Direct edits here will be lost on the next regeneration.
-->

# `us_vwrapper` — Vertical Wrapper

**When to use**: stack a small group of elements vertically with a controlled gap — heading + sub-heading + button, three icon-boxes inside a card, a small product card. Lives inside a `vc_column`.

**Avoid when**:
- the default vertical flow inside a column already gives the right spacing — adding a wrapper just for one gap is overkill;
- you need horizontal arrangement — use `[us_hwrapper]`;
- you need a multi-row grid — use `vc_row_inner` + `vc_column_inner`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `alignment` | Horizontal alignment of children — `none` (default), `left`, `center`, `right`. |
| `valign` | Vertical distribution of children inside the wrapper — `top` (default), `middle`, `bottom`, `justify`. |
| `inner_items_gap` | Vertical spacing between children. Default `0.7rem`. Accepts `px`, `rem`, `em`. |
| `link` | Wraps the whole stack in a clickable link. URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank","rel":"nofollow"}`. Note: any clickable elements inside (buttons, links) become non-clickable when this is set. |

**Minimal example**

```text
[us_vwrapper alignment="center" inner_items_gap="1rem"]
  [us_text]<h2>Plan name</h2>[/us_text]
  [us_text]$19/mo[/us_text]
  [us_btn label="Choose" link="%7B%22url%22%3A%22%23signup%22%7D"]
[/us_vwrapper]
```

**Anti-patterns**

- Using `us_vwrapper` to recreate a `vc_column` layout — columns already stack vertically.
- Setting `link` on a wrapper that contains its own buttons or links — those become non-clickable.
- Mixing `gap` (the old/invalid param name) — the correct name is `inner_items_gap`.
