---
title: `vc_widget_sidebar` — Sidebar with Widgets
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_widget_sidebar.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_widget_sidebar.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_widget_sidebar
  Direct edits here will be lost on the next regeneration.
-->

# `vc_widget_sidebar` — Sidebar with Widgets

**When to use**: render a WordPress **Sidebar** (a registered widget area, configured at WP Admin → Appearance → Widgets) inside the page flow — typically a column in a blog template, or a "filters / recent posts / tag cloud" rail next to the main content.

**Avoid when**:
- the content is a single, page-specific block — use the relevant shortcode directly (`us_post_list`, `us_search`, …) instead of going through a widget area;
- you need post archives — a `us_post_list` is faster to configure and previews live in the builder;
- you want a free-form HTML block — `us_text` or `us_html` are simpler.

**Note**: this is the only `vc_*` element in the Other category that ships in this manifest. The `sidebar_id` dropdown is populated from registered widget areas at edit-time only — the list will be empty when generating shortcode markup outside the builder.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `sidebar_id` | Slug of the widget area to render. Default `default_sidebar`. Other values depend on what the active theme / plugins register. |
| `title` | Optional heading rendered above the widget area. |

**Minimal example**

```text
[vc_widget_sidebar sidebar_id="default_sidebar"]
```

**Common combinations**

Two-column blog page: post list left, sidebar right.

```text
[vc_row][vc_column width="2/3"]
  [us_post_list]
[/vc_column][vc_column width="1/3"]
  [vc_widget_sidebar sidebar_id="blog_sidebar" title="Recent posts"]
[/vc_column][/vc_row]
```

**Anti-patterns**

- Referencing a `sidebar_id` that isn't registered on the site — the shortcode silently renders nothing.
- Putting heavy interactive widgets (forms, carousels) inside a sidebar widget — the surrounding column may be narrow; test the rendered widget's behaviour at the column's actual width.
