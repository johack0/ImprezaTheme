---
title: `vc_tta_tabs` — Tabs
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_tta_tabs.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_tta_tabs.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_tta_tabs
  Direct edits here will be lost on the next regeneration.
-->

# `vc_tta_tabs` — Tabs

**When to use**: switching between a small number of grouped content panels with horizontal tab triggers — feature comparisons, plan tiers, FAQ topics. Each tab is a `vc_tta_section` inside.

**Avoid when**:
- you have only 2 short blocks and they fit side-by-side — use `vc_row.columns="2"`;
- you have many sequential expandable rows — use `vc_tta_accordion` instead;
- you need a vertical tab strip — use `vc_tta_tour`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `layout` | Visual style of the tab strip — `default`, `simple`, `simple2`, `simple3`, `radio`, `radio2`, `radio3`, `modern`, `trendy`, `timeline`, `timeline2`. |
| `tabs_alignment` | Tab-strip alignment — `none` (default), `center`, `justify`. |
| `switch_sections` | What triggers a tab switch — `click` (default) or `hover`. |
| `accordion_at_width` | CSS width below which the tabs collapse into an accordion (e.g. `768px`). Leave empty for automatic. |
| `title_tag` | HTML tag for tab/section titles — `div` (default), `h2`–`h6`. |
| `title_size` | Custom font-size of the tab labels. |
| `title_font` | Tab-label font family (Theme Options fonts). |
| `title_weight` | Font weight of the tab labels — any CSS value (`400`, `600`, `700`, `bold`). |
| `title_lineheight` | Line height of the tab labels — any CSS value (`1.2`, `1.5`, `24px`). |
| `title_transform` | Text case of the tab labels — `''` (default) / `none` / `uppercase` / `lowercase` / `capitalize`. |

**Accordion-view parameters**

These take effect once the tab strip collapses into an accordion — automatically on narrow screens, or at the width set by `accordion_at_width`.

| Param | What it does |
|-------|--------------|
| `scrolling` | `1` (default) scrolls the page to the section when it opens; `0` disables. |
| `remove_indents` | `1` strips the left/right indents inside the section panels. Default `0`. |
| `c_align` | Section-title alignment — `none` (default, left) or `center`. |
| `c_icon` | Control indicator icon — `''` (none) / `chevron` (default) / `plus` / `triangle`. |
| `c_position` | Position of the control icon — `left` (before title) or `right` (after title, default). Applies only when `c_icon` is set. |

> `toggle` and `faq_markup` are accordion-only and are **not** available on `vc_tta_tabs`.

**Minimal example**

```text
[vc_tta_tabs]
  [vc_tta_section title="Starter"][us_text]Starter content[/us_text][/vc_tta_section]
  [vc_tta_section title="Pro"][us_text]Pro content[/us_text][/vc_tta_section]
[/vc_tta_tabs]
```

**Common combinations**

Centered tab strip that becomes an accordion under 768px:

```text
[vc_tta_tabs layout="modern" tabs_alignment="center" accordion_at_width="768px"]
  [vc_tta_section title="Overview"][us_text]…[/us_text][/vc_tta_section]
  [vc_tta_section title="Features"][us_text]…[/us_text][/vc_tta_section]
  [vc_tta_section title="Pricing"][us_text]…[/us_text][/vc_tta_section]
[/vc_tta_tabs]
```

**Anti-patterns**

- Putting more than ~5 tabs on a single strip — labels overflow or wrap on narrow viewports. Use `accordion_at_width` to mitigate.
- Using `vc_tta_tabs` for very long content per tab — accordions paginate that better on mobile.
- Using `switch_sections="hover"` on touch devices — hover doesn't exist there, users get stuck on the first tab.
