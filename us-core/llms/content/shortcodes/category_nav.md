---
title: `us_category_nav` — Category Navigation
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/category_nav.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/category_nav.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=category_nav
  Direct edits here will be lost on the next regeneration.
-->

# `us_category_nav` — Category Navigation

**When to use**: a hierarchical list of taxonomy terms (post categories, product categories, custom taxonomies) with parent/child indentation, optional post counts, and an "accordion" mode that folds branches. Typical placement: a blog/shop sidebar, an archive landing page, a docs index.

**Avoid when**:
- you only need a flat list of all terms — `us_term_list` is the loop variant and is faster to render;
- you want a faceted filter that re-queries the listing — `us_list_filter` (paired with `us_post_list`);
- you want a horizontal main-menu — that's a WP menu rendered via `us_additional_menu`, not a category nav.

**Context**: on a category/term archive page, the **current** term and its ancestors are auto-expanded and highlighted; on a regular page, the whole tree (up to `max_parent_level`) renders.

**Key parameters**

**Source**

| Param | What it does |
|-------|--------------|
| `taxonomy` | Taxonomy slug to traverse. Default `category`. Other common values: `product_cat`, `portfolio_category`, custom taxonomy slugs. |
| `hide_empty` | `1` hides terms that have no published posts. |
| `show_count` | `1` shows the post count next to each term. |
| `max_parent_level` | How many ancestor levels above the current term to render (1–3, default `1`). Only meaningful on a term archive page. |
| `max_child_level` | How many descendant levels below the current term to render (1–3, default `1`). |

**Layout**

| Param | What it does |
|-------|--------------|
| `show_as_accordion` | `1` renders branches as a collapsible accordion (parent terms toggle open/close their children). |
| `accordion_allow_multiple_open` | `1` lets visitors keep several branches open at once. Only meaningful when `show_as_accordion="1"`. |
| `accordion_control_icon` | Toggle icon shape — `chevron` (default), `plus`, or `triangle`. |
| `accordion_control_position` | Icon side relative to the term title — `before` or `after` (default `after`). |
| `item_style` | `links` (default — plain text rows) or `blocks` (filled blocks with hover/active backgrounds). Switching to `blocks` unlocks the color params below. |
| `item_gap` | Gap between rows. Default `0.4rem`. Accepts `px`/`rem`/`em`. |
| `item_ver_indent` / `item_hor_indent` | Inner padding of each row, only applied when `item_style="blocks"`. |

**Colors** (only when `item_style="blocks"`, except `item_color_text` / `item_color_text_hover` which apply to both styles)

| Param | What it does |
|-------|--------------|
| `item_color_bg` | Block background. Default `_content_bg_alt` (theme token). |
| `item_color_text` | Term-name text color. Default `inherit`. |
| `item_color_bg_hover` | Block background on hover. Default `_content_border`. |
| `item_color_text_hover` | Term-name color on hover. |
| `item_color_bg_active` | Block background for the current term. |
| `item_color_text_active` | Term-name color for the current term. |

**Minimal example**

```text
[us_category_nav taxonomy="category" show_count="1"]
```

**Common combinations**

WooCommerce shop sidebar, accordion mode, only categories with products:

```text
[us_category_nav taxonomy="product_cat" hide_empty="1" show_count="1"
                 show_as_accordion="1" accordion_control_icon="plus"]
```

Block-style with custom hover colors:

```text
[us_category_nav taxonomy="category"
                 item_style="blocks"
                 item_color_bg="_content_bg_alt"
                 item_color_bg_hover="_primary"
                 item_color_text_hover="#ffffff"]
```

**Anti-patterns**

- Setting `max_parent_level="3"` on a deep taxonomy (e.g. a 5-level product tree) on a non-term page — the whole tree dumps into the sidebar and the column overflows. Pair with `show_as_accordion="1"` or pick a lower value.
- `show_count="1"` with `hide_empty="0"` — visitors see lots of `(0)`s. Either hide empties or hide counts.
- `taxonomy="post_tag"` and expecting hierarchy — tags are flat; the nav renders as a single-level list and `max_*_level` does nothing.
