---
title: `us_product_list` — Product List
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/product_list.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/product_list.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=product_list
  Direct edits here will be lost on the next regeneration.
-->

# `us_product_list` — Product List

**When to use**: a WooCommerce-aware grid of products — shop landing, "Featured products" block, "On sale" section, upsells/cross-sells, or any custom product subset filtered by price / stock / category. Requires WooCommerce to be active (the shortcode is hidden from the builder otherwise).

**Avoid when**:
- the items are not products — use `us_post_list` (and pass `post_type="product"` only if you need raw post behaviour; the WooCommerce-specific filters below won't work);
- you want a horizontal slider — use `us_product_carousel` (same query model, no pagination / type / columns);
- you want the default WooCommerce shop archive layout — that's controlled by the theme's `shop_layout` Theme Option, not by a manual shortcode.

**Source / Order / Appearance**: most params mirror [`us_post_list`](#us_post_list--post-list) — see that entry for `apply_url_params`, `enable_items_offset` / `items_offset`, `meta_query*`, `orderby*`, `order_invert`, `show_all`, `quantity`, `posts_per_archive_page`, `no_items_*`, `pagination*`, `items_layout`, `type`, `items_valign`, `ignore_items_size`, `columns`, `items_gap`, `load_animation`, `items_preload_style`, `img_size`, `title_size`, `img_aspect_ratio`, `item_aspect_ratio`, `overriding_link`, `popup_*`. The differences are listed below.

**WooCommerce-specific source**

| Param | What it does |
|-------|--------------|
| `source` | `all` (default), `post__in` (selected ids), `post__not_in`, `upsells` (the current product's upsells — only on a product page), `crosssell` (current product's cross-sells — only in cart), `recently_viewed`, `user_favorite_ids`, `current_wp_query`. |
| `ids` | Comma-separated product IDs for `post__in` / `post__not_in`. |
| `onsale_only` | `1` keeps only on-sale products. |
| `featured_only` | `1` keeps only featured products. |
| `exclude_out_of_stock` | `1` hides products with `outofstock` stock status. |
| `exclude_hidden` | `1` (default) hides products with the catalog-visibility "hidden". |
| `exclude_current_product` | `1` (default) excludes the product being viewed (ignored for `upsells` / `crosssell` / `current_wp_query`). |

**Price filter**

| Param | What it does |
|-------|--------------|
| `price_compare` | `none` (default), `greater`, `greater_equal`, `less`, `less_equal`, `equal`, `not_equal`, `in_range`. |
| `price` | The threshold price (or the minimum when `in_range`). |
| `price_max` | The maximum when `price_compare="in_range"`. |

**Taxonomies**: same `tax_query_relation` + `tax_query` shape as `us_post_list`, but the default taxonomy is `product_cat`; the dropdown is restricted to WooCommerce taxonomies (`product_cat`, `product_tag`, `pa_*` product attributes).

**Order**: extra `orderby` options on top of the post-list set — `price`, `rating`, `comment_count` (reviews), `recent_sales` (sales in last N days), `total_sales`, `menu_order`, `title`. When `orderby="recent_sales"`, set `orderby_recent_sales_days` (default `30`).

**Grid Layout default**: `items_layout="shop_standard"`. The selector is restricted to grid layouts of `blog` / `portfolio` / `shop` type. Default `columns="4"`.

**Minimal example**

```text
[us_product_list quantity="12" columns="4"]
```

**Common combinations**

Featured products grid, 3-up, no pagination:

```text
[us_product_list featured_only="1" exclude_out_of_stock="1"
                 quantity="6" columns="3" items_layout="shop_standard"]
```

On-sale block ordered by deepest discount (custom field via store plugin):

```text
[us_product_list onsale_only="1" quantity="8" columns="4"
                 orderby="recent_sales" orderby_recent_sales_days="14"]
```

Shop archive with a filter widget elsewhere on the page:

```text
[us_product_list source="current_wp_query" apply_url_params="1"
                 columns="4" items_layout="shop_standard"
                 pagination="numbered_ajax"]
```

**Anti-patterns**

- Using this on a site without WooCommerce — the shortcode is gated behind `class_exists('woocommerce')` and renders nothing.
- `source="upsells"` outside a single product page (or `crosssell` outside the cart) — there are no upsells to query, the list returns empty.
- `price_compare="in_range"` with only `price` set and no `price_max` — the upper bound defaults silently and the range may be wider than intended.
- `orderby="recent_sales"` without `orderby_recent_sales_days` — the window defaults to 30; pass an explicit value for predictable results.
