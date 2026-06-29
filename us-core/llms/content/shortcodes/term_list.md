---
title: `us_term_list` — Term List
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/term_list.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/term_list.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=term_list
  Direct edits here will be lost on the next regeneration.
-->

# `us_term_list` — Term List

**When to use**: a grid of taxonomy terms rendered through a Grid Layout — "Browse by category" tiles on a shop / blog landing, portfolio category cards, an A–Z directory of CPT terms. One card per term, with the term's name, count, description, image (if assigned in the term meta), and a link to the term archive.

**Avoid when**:
- you want a hierarchical sidebar with parent/child indentation — use `us_category_nav` (folds branches, auto-expands the current term);
- you want a faceted filter that re-queries a `us_post_list` — use `us_list_filter` instead;
- you want a horizontal slider — use `us_term_carousel`.

**Key parameters**

**Source**

| Param | What it does |
|-------|--------------|
| `source` | `all` (default), `include` (selected term IDs), `exclude` (all except selected), `current_term` (child terms of the term currently being viewed), `current_post` (terms attached to the post being viewed). |
| `taxonomy` | Taxonomy slug — `category`, `post_tag`, `product_cat`, `product_tag`, `us_portfolio_category`, or any custom taxonomy. Default `category`. Hidden when `source="current_term"` (taxonomy is implied by context). |
| `term_ids` | Comma-separated term IDs for `source="include"` / `"exclude"`. |
| `include_children` | `1` recurses into child terms. Ignored when `source="include"` / `"current_post"`. |
| `hide_empty` | `1` hides terms with no posts. |
| `exclude_current` | `1` excludes the term being viewed (on a term archive). |

**Order & quantity**

| Param | What it does |
|-------|--------------|
| `orderby` | `name` (default), `count` (post count), `include` (preserves `term_ids` order), `menu_order` (WooCommerce drag-order), `rand`, `custom` (custom field). |
| `orderby_custom_field` / `orderby_custom_type` | Custom-field name + `1` to sort numerically. |
| `order_invert` | `1` flips direction. |
| `limit_number` | `1` enables the `number` cap. |
| `number` | Max number of terms when `limit_number="1"`. 1–30, default `12`. |

**Custom-field filter**: `meta_query_relation` (`none` / `AND` / `OR`) + `meta_query` JSON, same shape as `us_post_list`.

**No-results**: `no_items_action` (`message` / `page_block` / `hide_grid`) + `no_items_message` / `no_items_page_block`.

**Appearance**

| Param | What it does |
|-------|--------------|
| `items_layout` | Grid Layout id — restricted to `blog` / `tile` / `text` / `side` / `portfolio` types. Default `blog_1`. |
| `type` | `grid` (default), `masonry`, `metro`. |
| `items_valign` | `1` centers items vertically. |
| `columns` | 1–10, default `3`. |
| `items_gap` | Gap. Default `10px`. |
| `load_animation` | Same enum as `us_post_list`. |
| `img_size` | `default` or any registered image size. |
| `img_aspect_ratio` | Aspect ratio of the **term image inside each card** — a CSS `aspect-ratio` value (`1`, `16/9`, `2/3`, any `W/H`). Empty (default) = as the Grid Layout dictates. Drives `--img-aspect-ratio`. |
| `title_size` | CSS length override for the term-name font size. |
| `item_aspect_ratio` | Aspect ratio of the **whole card box** — a CSS `aspect-ratio` value. Empty (default) = inherit the Grid Layout's fixed item ratio if it sets one. Drives `--item-aspect-ratio`. |
| `overriding_link` | Wraps the card in a link. Dynamic value options: the term archive page (`post`), `popup_post` (open the archive in a popup), `custom_field|us_tile_link`. |
| `popup_*` | Popup behaviour when `overriding_link` opens a popup. |

**Minimal example**

```text
[us_term_list taxonomy="category" hide_empty="1" columns="3"]
```

**Common combinations**

"Shop by category" tiles on a homepage, 4 columns, hide empties, fixed order via selected IDs:

```text
[us_term_list taxonomy="product_cat" source="include"
              term_ids="15,18,21,23" orderby="include"
              columns="4" items_layout="tile_1"
              item_aspect_ratio="1"]
```

Child terms of the current product category, on a category archive page:

```text
[us_term_list source="current_term" hide_empty="1"
              columns="3" items_layout="blog_1"]
```

**Anti-patterns**

- `source="current_term"` on a non-term page — there is no current term, so the list returns empty silently.
- `taxonomy="post_tag"` with `include_children="1"` — tags are flat; the param has no effect.
- Using this as a hierarchical sidebar — use `us_category_nav` instead (Term List always renders as a flat grid).
