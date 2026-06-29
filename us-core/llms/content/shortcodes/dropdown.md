---
title: `us_dropdown` — Dropdown
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/dropdown.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/dropdown.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=dropdown
  Direct edits here will be lost on the next regeneration.
-->

# `us_dropdown` — Dropdown

**When to use**: a click/hover-activated dropdown panel anchored to a label or icon. Good for "More" links, language switchers, mini info panels with rich content.

**Avoid when**:
- you need a full navigation menu — `[us_additional_menu]`;
- you need a small inline reveal with heavy content — `[us_popup]` is better;
- you need an accordion list — `[vc_tta_accordion]`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `source` | Where the dropdown content comes from — `own` (default; custom links via `links`), `sidebar` (a WP sidebar/widget area), `wpml` (language switcher), `polylang` (language switcher). |
| `links` (group) | Items shown in the panel when `source="own"`. Each item has `label`, `url`, `icon`. |
| `sidebar_id` | WP sidebar slug used when `source="sidebar"`. |
| `wpml_switcher` | Subset of the WPML widget to show — `flag`, `native_lang`, `display_lang`. Used when `source="wpml"`. |
| `polylang_switcher` | Same idea for Polylang — `flag`, `full_name`. Used when `source="polylang"`. |
| `link_title` | Trigger label (visible when `source="own"` or `source="sidebar"`). |
| `link_icon` | Trigger icon (`set|name`). |
| `dropdown_open` | Activation — `click` (default) or `hover`. |
| `dropdown_dir` | Anchor side — `left` / `right` (default). |
| `dropdown_effect` | Open animation — `height` (default) or other transition. |

**Minimal example**

```text
[us_dropdown link_title="More" link_icon="fas|chevron-down" source="own"]
```

The `links` items are a group attribute — URL-encoded JSON array in double quotes (composition-rules §3.5). Verbose to author by hand; copy the `links=` payload of an existing dropdown as a starting point when possible.

**Anti-patterns**

- Critical content (prices, terms) hidden inside a dropdown — search engines and screen readers may miss it.
- Nested dropdowns — UX nightmare, especially on touch.
- `dropdown_open="hover"` on touch devices — hover doesn't exist.
