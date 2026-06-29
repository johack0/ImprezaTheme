---
title: `us_additional_menu` — Additional Menu
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/additional_menu.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/additional_menu.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=additional_menu
  Direct edits here will be lost on the next regeneration.
-->

# `us_additional_menu` — Additional Menu

**When to use**: render a WP Nav Menu (configured under **Appearance → Menus**) as a secondary navigation inside content — footer columns, sidebars, sub-page lists, mega-menu fragments.

**Avoid when**:
- you need the site's main header navigation — that lives in the Header Builder, not in content;
- you only have 2-3 hardcoded links — `us_text` with `<a>` tags is enough.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `source` | The WP Nav Menu to render. **Per-site value** — depends on what menus exist under Appearance → Menus on the install. |
| `layout` | `ver` (vertical) / `hor` (horizontal). |
| `responsive_width` | Below this CSS width, the menu collapses to vertical. Leave empty to keep horizontal with overflow scroll. |
| `show_as_accordion` | (Only when `layout="ver"`) `1` displays sub-items as expandable accordion sections. |
| `main_style` | Item style — `links` / `blocks`. |
| `main_gap` | Gap between top-level items. |
| `sub_items` | `1` to render second-level (sub-menu) items. |

**Minimal example**

```text
[us_additional_menu source="footer-menu" layout="ver"]
```

(`footer-menu` is whatever slug the editor created in Appearance → Menus.)

**Anti-patterns**

- Hardcoding the menu structure inside `us_html` instead of `us_additional_menu` — admins can no longer edit it via Appearance → Menus.
- Using `layout="hor"` for a long list — items wrap or scroll horizontally, which feels broken.
