---
title: `us_list_search` — List Search
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/list_search.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/list_search.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=list_search
  Direct edits here will be lost on the next regeneration.
-->

# `us_list_search` — List Search

**When to use**: a free-text search box that filters a sibling `us_post_list` / `us_product_list` by the standard WordPress `s` URL param — "Search the blog", "Find a product". Live-search (filter as you type) is on by default; the target list must have `apply_url_params="1"`.

**Avoid when**:
- you want a site-wide search box that goes to the WP search results page — use `us_search` (header search element);
- you want faceted filtering by value (category / price / stock) — use `us_list_filter`;
- the target listing is a user / term list — these queries don't consume `s`.

**Wiring**: same as `us_list_filter` — `list_to_search="first"` (default) auto-targets the first list on the page; `list_to_search="selector"` with `list_selector_to_search=".my-list"` lets you target a specific list element.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `list_to_search` | `first` (default) or `selector`. |
| `list_selector_to_search` | CSS class or ID of the target list. Required when `list_to_search="selector"`. |
| `live_search` | `1` (default) filters while the visitor types; `0` requires Enter / submit. |
| `change_url_params` | `1` (default) writes the query into the URL for shareable / bookmarkable state. |
| `text` | Placeholder text. Default "Search". |
| `icon` | Search icon. Default `fas|search`. Format: `<style>|<name>` (`fas`, `far`, `fal`, `fab`). |
| `icon_pos` | `right` (default) or `left`. |
| `icon_size` | Icon size. Default `18px`. |
| `us_field_style` | Field style key from Theme Options → Form Field Styles. |

**Minimal example**

```text
[us_list_search text="Search posts…"]
```

**Common combinations**

Shop toolbar with a search field that filters the products grid in place:

```text
[us_list_search text="Search products"
                icon="fas|search" icon_pos="left"
                live_search="1" change_url_params="1"
                us_field_style="1"]
```

Page with two lists — search drives the second one explicitly:

```text
[us_list_search list_to_search="selector"
                list_selector_to_search=".docs-grid"
                text="Search docs"]
```

**Anti-patterns**

- Confusing this with `us_search` — `us_search` is the global header search; `us_list_search` filters one list on the page and never navigates away.
- Live search across very large lists (thousands of items) without pagination — each keystroke triggers an ajax request; pair with `pagination="numbered_ajax"` and a reasonable `quantity`.
- `change_url_params="0"` on a page where visitors should be able to bookmark the filtered state — the URL stays clean but the share-back behaviour is lost.
