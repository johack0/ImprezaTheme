---
title: `us_hwrapper` — Horizontal Wrapper
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/hwrapper.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/hwrapper.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=hwrapper
  Direct edits here will be lost on the next regeneration.
-->

# `us_hwrapper` — Horizontal Wrapper

**When to use**: place a small group of inline elements side-by-side with a gap — two buttons, an icon next to text, a price with a "from" label. Lives inside a `vc_column`.

**Avoid when**:
- you need a full grid (multiple rows of side-by-side blocks) — use `vc_row_inner` + `vc_column_inner`;
- you have a single element — wrappers are pointless.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `alignment` | Horizontal alignment of items inside the wrapper — `none` (default), `left`, `center`, `right`, `justify`. Responsive — accepts a per-breakpoint URL-encoded JSON value (composition-rules §3.4). |
| `valign` | Vertical alignment of items when their heights differ — `top` (default), `middle`, `bottom`, `baseline`, `stretch`. |
| `inner_items_gap` | Spacing between items. Default `1.2rem`. Accepts `px`, `rem`, `em`. |
| `wrap` | `1` allows children to wrap to the next line on narrow screens; default `0` keeps them in one row. |
| `stack_on_mobiles` | `1` stacks the items into a single column on mobile breakpoints (useful for two CTA buttons that should be stacked on small screens). |
| `link` | Wraps the whole row in a clickable link. URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank","rel":"nofollow"}`. **Important**: all inner clickable elements (buttons, links) become non-clickable when this is set. |

**Minimal example**

```text
[us_hwrapper inner_items_gap="1rem" valign="middle"]
  [us_btn label="Primary"   link="%7B%22url%22%3A%22%23a%22%7D" style="1"]
  [us_btn label="Secondary" link="%7B%22url%22%3A%22%23b%22%7D" style="2"]
[/us_hwrapper]
```

**Common combinations**

Two CTAs that stack on mobile:

```text
[us_hwrapper inner_items_gap="1rem" valign="middle" stack_on_mobiles="1"]
  [us_btn label="Get started" link="%7B%22url%22%3A%22%23signup%22%7D"   style="1"]
  [us_btn label="Learn more"  link="%7B%22url%22%3A%22%23features%22%7D" style="2"]
[/us_hwrapper]
```

Icon next to a heading, vertically aligned to the baseline:

```text
[us_hwrapper inner_items_gap="0.5rem" valign="baseline"]
  [us_text text="Why us" tag="h2"]
  [us_text text="(updated)" tag="span"]
[/us_hwrapper]
```

**Anti-patterns**

- Using the old/invalid `gap` parameter — the correct name is `inner_items_gap`.
- Stuffing 5+ elements into one wrapper without `wrap="1"` or `stack_on_mobiles="1"` — they overflow on narrow screens.
- Setting `link` on a wrapper that contains its own buttons — the buttons become non-clickable.
