---
title: `vc_tta_accordion` — Accordion
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_tta_accordion.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_tta_accordion.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_tta_accordion
  Direct edits here will be lost on the next regeneration.
-->

# `vc_tta_accordion` — Accordion

**When to use**: FAQ sections, long support docs, "click to reveal" content — a stacked list of titles where each opens to show its body. Each row is a `vc_tta_section` inside.

**Avoid when**:
- you have only 2 short panels — use tabs or two columns;
- the content must always be visible (don't hide it behind a click for SEO-critical text);
- you need horizontal tab switching — that's `vc_tta_tabs`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `toggle` | `1` allows several sections to be opened at the same time; default `0` (only one open). |
| `scrolling` | When `toggle="0"`, `1` (default) scrolls the page to the opened section. Set `0` to disable. |
| `remove_indents` | `1` strips the left/right indents inside section panels. |
| `faq_markup` | `1` outputs FAQ structured-data markup for SEO. Available when the schema-markup theme option is on. |
| `c_align` | Title alignment — `none` (default, left) or `center`. |
| `c_icon` | Control indicator icon — `''` (none) / `chevron` / `plus` / `triangle`. Default `chevron`. |
| `c_position` | Position of the control icon — `left` (before title) or `right` (after title, default). |
| `title_tag` | HTML tag for section titles — `div` (default), `h2`–`h6`. Use `h3`/`h4` on FAQ pages for SEO. |
| `title_size` | Custom title font-size (CSS units). |
| `toggle_style` | Visual style of the section headers — `style_1` (default, classic accordion), `style_2`, `style_3`, or any configured button-style id (run `upsolution-list-button-styles` for the install's ids). |
| `sections_gap` | Gap between sections in CSS units (default `0.5rem`). Only takes effect when `toggle_style` is not `style_1`. |

**Minimal example**

```text
[vc_tta_accordion]
  [vc_tta_section title="How do I sign up?"][us_text]Click "Sign up" above.[/us_text][/vc_tta_section]
  [vc_tta_section title="Do you offer refunds?"][us_text]Yes, within 30 days.[/us_text][/vc_tta_section]
[/vc_tta_accordion]
```

**Common combinations**

FAQ accordion with schema markup, H3 titles, plus icon on the left:

```text
[vc_tta_accordion faq_markup="1" title_tag="h3" c_icon="plus" c_position="left"]
  [vc_tta_section title="Question 1"][us_text]Answer 1[/us_text][/vc_tta_section]
  [vc_tta_section title="Question 2"][us_text]Answer 2[/us_text][/vc_tta_section]
[/vc_tta_accordion]
```

**Anti-patterns**

- Hiding the primary value proposition inside the first collapsed accordion item — users may not click.
- Setting `toggle="1"` for an FAQ — defeats the "one focused question" UX.
- Using `c_icon=""` (no indicator) — users don't realise the rows are expandable.
