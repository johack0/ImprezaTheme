---
title: `us_post_list` — Post List
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_list.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_list.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_list
  Direct edits here will be lost on the next regeneration.
-->

# `us_post_list` — Post List

**When to use**: a query-driven grid of posts (or any custom post type) — blog index, news feed, portfolio archive, child-post listing, "favorites of the current user", etc. Pulls items via `WP_Query`, renders them through a saved Grid Layout, and optionally drives a Filter / Order / Search / Result-Counter / Reset companion placed elsewhere on the same page.

**Avoid when**:
- the items are products — use `us_product_list` (it has WooCommerce-aware sources, price filter, stock flags);
- the items are taxonomy terms or users — use `us_term_list` / `us_user_list`;
- you want a horizontal slider instead of a paginated grid — use `us_post_carousel` (same query params, no `pagination` / `type` / `columns` — those live on the carousel options);
- the children are arbitrary shortcodes (icon boxes, CTA blocks, etc.) not bound to posts — use `us_content_carousel` or plain `vc_row` columns.

**Source**: items are picked by the `source` attribute, then filtered by author / taxonomy / custom-field / offset conditions. Order, quantity and pagination form a second group; appearance (grid layout, columns, aspect ratio, animations) a third.

**Key parameters**

**Source / What to show**

| Param | What it does |
|-------|--------------|
| `source` | Pool to query — `all` (default), `post__in` (selected ids), `post__not_in` (all except selected), `child_posts_of_current`, `child_posts_of_selected`, `media`, `user_favorite_ids` (current visitor's favourites), `current_wp_query` (use the page's existing query — only valid on archive/search templates). |
| `ids` | Comma-separated post IDs used by `post__in` / `post__not_in` / `child_posts_of_selected`. |
| `post_type` | Comma-separated post types to query (default `post`). Examples: `page`, `us_portfolio`, `tribe_events`. Ignored when `source` is `child_posts_of_current` / `current_wp_query` / `media`. |
| `attachment_ids` | Comma-separated media IDs when `source="media"`. |
| `include_post_thumbnail` | `1` prepends the post's featured image when `source="media"`. |
| `post_author` | `any` (default), `include`, `exclude`, `current_author` (author of the current post), `current_user` (logged-in visitor). |
| `post_author_ids` | Comma-separated user IDs when `post_author="include"` / `"exclude"`. |
| `apply_url_params` | `1` lets companion filter / order / search shortcodes drive this list via URL params. Required if you want a Filter widget on the same page. |
| `ignore_sticky_posts` | `1` (default) skips sticky posts. |
| `exclude_children` | `1` excludes child posts of any post. |
| `exclude_current_post` | `1` (default) excludes the post currently being viewed. |
| `exclude_prev_posts` | `1` excludes posts already shown by an earlier `us_post_list` on the same page (de-dupe across blocks). |
| `exclude_past_events` | `1` hides past events (only meaningful for `post_type="tribe_events"`). |
| `enable_items_offset` + `items_offset` | Skip the first N posts (e.g. to leave room for a featured block above). |

**Taxonomy & custom-field filters**

| Param | What it does |
|-------|--------------|
| `tax_query_relation` | `none` (default), `AND` (all conditions must match), `OR` (any). |
| `tax_query` | JSON-encoded array of conditions. Each entry: `{ "operator": "IN"|"AND"|"NOT IN"|"CURRENT", "taxonomy": "<slug>", "terms": "<id,id,…>", "include_children": "0"|"1" }`. `CURRENT` re-uses the terms of the post being viewed. |
| `meta_query_relation` | `none` / `AND` / `OR`. |
| `meta_query` | JSON array of `{ "key": "<field>", "compare": "="|"!="|">"|">="|"<"|"<="|"LIKE"|"NOT LIKE"|"EXISTS"|"NOT EXISTS", "value": "…" }`. |

**Order & quantity**

| Param | What it does |
|-------|--------------|
| `orderby` | `date` (default), `modified`, `title`, `author`, `comment_count`, `type`, `menu_order`, `rand`, `post__in` (preserve `ids` order), `current_wp_query` (preserve the archive order), `custom` (custom field). |
| `orderby_custom_field` | Custom-field name when `orderby="custom"`. |
| `orderby_custom_type` | `1` treats the custom-field values as numbers. |
| `order_invert` | `1` flips the order direction. |
| `show_all` | `1` ignores `quantity` and returns the whole result set. |
| `quantity` | Items to show (1–30, default `12`). Hidden when `show_all="1"` or `source="current_wp_query"`. |
| `posts_per_archive_page` | Posts per page when `source="current_wp_query"`; `0` (default) uses the WP Reading setting. |
| `no_items_action` | What to render when the query returns nothing — `message` (default), `page_block`, `hide_grid`. |
| `no_items_message` | The message text when `no_items_action="message"`. |
| `no_items_page_block` | Reusable-block id when `no_items_action="page_block"`. |

**Pagination**

| Param | What it does |
|-------|--------------|
| `pagination` | `none` (default), `numbered`, `numbered_ajax`, `load_on_btn`, `load_on_scroll`. |
| `pagination_style` | Style key from Theme Options → Buttons (numbered pagination only). |
| `pagination_btn_text` / `pagination_btn_size` / `pagination_btn_style` / `pagination_btn_fullwidth` | Load-More button label / size / style key / full-width toggle (only when `pagination="load_on_btn"`). |

**Appearance**

| Param | What it does |
|-------|--------------|
| `items_layout` | Grid Layout id (saved post type `us_grid_layout`). Default `blog_1`. This single param drives the entire card markup — image position, meta rows, hover, etc. |
| `type` | `grid` (default), `masonry`, `metro`. |
| `items_valign` | `1` vertically centers items in their grid cell (only when `type="grid"`). |
| `ignore_items_size` | `1` ignores any per-item custom size set in the Grid Layout (only when `type!="metro"`). |
| `columns` | 1–10, default `4`. Auto-overridden by responsive breakpoints. |
| `items_gap` | Gap between cards. Accepts `px` / `rem` / `em`. Default `1.5rem`. |
| `load_animation` | `none` (default), `fade`, `afc`, `afl`, `afr`, `afb`, `aft`, `hfc`, `wfc`. |
| `items_preload_style` | Placeholder while ajax-paginating / filtering — `spinner` (default), `fade`, `placeholders`, `none`. |
| `img_size` | Image size — `default` (as in Grid Layout) or any registered size (`thumbnail`, `medium`, `large`, `full`, theme sizes). |
| `img_aspect_ratio` | Aspect ratio of the **post image inside each card**, as a CSS `aspect-ratio` value — `1` (square), `16/9` (landscape), `2/3` (portrait), or any `W/H`. Empty (default) = whatever size the Grid Layout / image dictates. Drives `--img-aspect-ratio` on the grid. |
| `title_size` | Override the title font size — CSS length (`1.2rem`, `24px`, …). |
| `item_aspect_ratio` | Aspect ratio of the **whole card box**, as a CSS `aspect-ratio` value (same format as above). Empty (default) = inherit the fixed item ratio from the chosen Grid Layout (`items_layout`) if it sets one, otherwise the card sizes to its content. Drives `--item-aspect-ratio`. |
| `overriding_link` | Wraps the whole card in a link. URL-encoded JSON, same shape as other `link` params. Values: a custom URL, the post permalink, `popup_post` (open the post in a popup), `popup_image` (open the featured image). Disables every link inside the card. |
| `popup_page_template` / `popup_width` / `popup_arrows` | Popup behaviour when `overriding_link` opens a popup. |

**Responsive overrides**: `_columns` / `_items_gap` / `_columns_layout` etc. are read from the shared responsive pack — see [element-effects.md](element-effects.md) for the breakpoint syntax (`_columns_laptops`, `_columns_tablets`, `_columns_mobiles`).

**Minimal example**

```text
[us_post_list post_type="post" quantity="6" columns="3" items_layout="blog_1"]
```

**Common combinations**

Latest 9 posts from two categories, 3-up grid with Load-More:

```text
[us_post_list post_type="post" quantity="9" columns="3"
              tax_query_relation="OR"
              tax_query="%5B%7B%22operator%22%3A%22IN%22%2C%22taxonomy%22%3A%22category%22%2C%22terms%22%3A%2212%2C15%22%2C%22include_children%22%3A%221%22%7D%5D"
              pagination="load_on_btn" pagination_btn_text="Show more"]
```

Child posts of the current page, no pagination, masonry:

```text
[us_post_list source="child_posts_of_current" type="masonry"
              show_all="1" columns="2"]
```

Listing wired to URL params so a `us_list_filter` + `us_list_order` elsewhere on the page can drive it:

```text
[us_post_list post_type="post" apply_url_params="1"
              pagination="numbered_ajax" quantity="12"]
```

**Anti-patterns**

- Setting `source="current_wp_query"` on a regular page (not an archive / search template) — the query is empty and the list renders the "no results" branch.
- Hand-setting `columns="3"` and expecting it to win on desktop — that's correct, but the value is auto-overridden by `_columns_laptops` / `_columns_tablets` / `_columns_mobiles` from the responsive pack; set those too when you need a specific layout on smaller screens.
- Forgetting `apply_url_params="1"` when adding a Filter / Order / Search alongside — the companions emit URL params but the list will ignore them silently.
- Pairing `pagination="numbered_ajax"` with `source="current_wp_query"` — ajax pagination needs the list to own its own query.
- Using the legacy `items_ratio` / `items_ratio_width` / `items_ratio_height` — replaced by `img_aspect_ratio` (the post image) and `item_aspect_ratio` (the whole card), each a CSS ratio like `16/9`. The old names are read only as a back-compat fallback.
