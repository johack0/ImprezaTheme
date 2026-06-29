---
title: `us_scroller` — Page Scroller
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/scroller.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/scroller.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=scroller
  Direct edits here will be lost on the next regeneration.
-->

# `us_scroller` — Page Scroller

**When to use**: turns a long single-page layout into a snap-scroll experience — one `vc_row` per "screen", scroll wheel and arrow keys advance to the next row, optional dot navigation jumps to any section. Typical use: one-page sites, landing pages, presentation-style scrolls.

**Avoid when**:
- the page has rows of mixed heights or text-heavy content — snap scroll skips past the bottom of tall rows;
- you only want a back-to-top button — there are simpler solutions;
- the layout is a normal multi-section page with a real footer flow — snap scroll trains users to wheel-jump and they miss content.

**Placement**: drop one `[us_scroller]` per page (typically near the top), with no children. It hooks the page's rows automatically.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `disable_width` | Screen width below which snap scrolling is disabled (default `768px`). On mobile the page falls back to normal scrolling. |
| `speed` | Animation duration between sections, in ms (default `1000ms`). Range 0–2000ms. |
| `dots` | `1` shows a vertical dot-navigation overlay. |
| `dots_style` | Dot visual style — `1` / `2` / `3` / `4` (default `1`). |
| `dots_pos` | Side of the screen for the dots — `left` or `right` (default `right`). |
| `dots_size` | Dot diameter (default `10px`). Accepts `px` or `rem`. |
| `dots_color` | Dot color. Empty inherits the theme accent. |
| `include_footer` | `1` adds a dot for the footer so visitors can jump straight to it. Only meaningful with `dots="1"`. |

**Minimal example**

```text
[us_scroller dots="1" dots_pos="right" dots_style="2"]
```

**Common combinations**

Slow, presentation-style scroll with footer in the dot strip:

```text
[us_scroller speed="1500ms" dots="1" dots_style="3" dots_size="14px" include_footer="1"]
```

**Anti-patterns**

- Two `us_scroller` instances on one page — the second one's hooks fight the first; keep it singular.
- Used on a page where rows are taller than the viewport — visitors lose the bottom half of every row.
- `disable_width="0"` (forces snap scroll on phones) — finger gestures expect inertia, not section snap.
