---
title: `vc_tta_tour` — Vertical Tabs (Tour)
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_tta_tour.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_tta_tour.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_tta_tour
  Direct edits here will be lost on the next regeneration.
-->

# `vc_tta_tour` — Vertical Tabs (Tour)

**When to use**: tabs whose triggers sit in a vertical column (typical for "step-by-step" walkthroughs or feature-on-the-left, content-on-the-right layouts). Each step is a `vc_tta_section`.

**Avoid when**:
- you have few short panels and a wide viewport — horizontal `vc_tta_tabs` reads better;
- you need each step always visible — render them as consecutive rows instead.

**Key parameters**

Most parameters mirror `vc_tta_tabs` (`layout`, `switch_sections`, `title_font`/`title_weight`/`title_transform`/`title_size`/`title_lineheight`, `title_tag`; and the accordion-view controls `accordion_at_width`, `scrolling`, `remove_indents`, `c_align`, `c_icon`, `c_position`). See `vc_tta_tabs.md` for those.

**Tour-specific** (no equivalents in `vc_tta_tabs`):

| Param | What it does |
|-------|--------------|
| `tab_position` | Which side the vertical strip sits on — `left` (default) or `right`. |
| `controls_size` | Width of the strip relative to the section — `auto` (default, content-based), `10`, `20`, `30`, `40`, `50` (percent of the wrapper). Use a fixed % when you want a stable trigger column even if labels change length. |

**Layout differences from tabs**: `vc_tta_tour.layout` accepts the same set as tabs **except** `timeline`/`timeline2` — those are horizontal-only and not available here. Available values: `default`, `simple`, `simple2`, `simple3`, `radio`, `radio2`, `radio3`, `modern`, `trendy`.

**Minimal example**

```text
[vc_tta_tour]
  [vc_tta_section title="1. Install"][us_text text="Download…"][/vc_tta_section]
  [vc_tta_section title="2. Configure"][us_text text="Open settings…"][/vc_tta_section]
  [vc_tta_section title="3. Launch"][us_text text="Run the app…"][/vc_tta_section]
[/vc_tta_tour]
```

**Common combinations**

Right-anchored tour strip of fixed width (30%), good for a feature-list with screenshot on the left:

```text
[vc_tta_tour tab_position="right" controls_size="30"]
  [vc_tta_section title="Dashboards"][us_image image="11" size="large"][/vc_tta_section]
  [vc_tta_section title="Reports"][us_image image="12" size="large"][/vc_tta_section]
  [vc_tta_section title="Exports"][us_image image="13" size="large"][/vc_tta_section]
[/vc_tta_tour]
```

**Anti-patterns**

- Putting more than ~6 steps on a single tour — the vertical strip becomes hard to scan on mobile (it collapses to a horizontal scroller or stack on narrow screens). Pair with `accordion_at_width` to mitigate.
- Setting `layout="timeline"` or `"timeline2"` — those styles are horizontal-only and not available on tour; the value will be ignored or fall back to `default`.
