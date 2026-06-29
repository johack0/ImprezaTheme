---
title: `us_product_carousel` — Product Carousel
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/product_carousel.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/product_carousel.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=product_carousel
  Direct edits here will be lost on the next regeneration.
-->

# `us_product_carousel` — Product Carousel

**When to use**: a horizontally-rotating slider of products — "Featured", "On sale", "Related products" rail under a single product, upsells / cross-sells, a homepage shop teaser. Same query model as [`us_product_list`](#us_product_list--product-list) but the result is an Owl carousel; same WooCommerce gate.

**Avoid when**:
- items aren't products — use `us_post_carousel` / `us_term_carousel` / `us_user_carousel`;
- you need pagination, masonry, METRO, or full-grid columns — that's `us_product_list` (carousels strip those params via the parent's `exclude_for_carousel` filter).

**Source / Order / Appearance**: identical to `us_product_list` for all source params (`source`, `ids`, `onsale_only`, `featured_only`, `exclude_*`, `enable_items_offset`/`items_offset`, `price_compare`/`price`/`price_max`, `tax_query*`, `meta_query*`, `apply_url_params`), order params (`orderby`, `orderby_custom_field`, `orderby_recent_sales_days`, `orderby_custom_type`, `order_invert`, `show_all`, `quantity`, `no_items_*`), and item-level appearance params (`items_layout`, `items_gap`, `img_size`, `title_size`, `img_aspect_ratio` (Product Image Aspect Ratio), `item_aspect_ratio` (Items Aspect Ratio), `overriding_link`, `popup_*`). The carousel-specific params (`items`, `next_item_offset`, `items_valign`, `center_item`, `slide_by_one`, `autoheight`, `loop`, `autoplay*`, `transition_*`, `arrows*`, `dots`, `dots_style`, `mouse_drag`, `touch_drag`, `responsive`) work as documented under [`us_post_carousel`](#us_post_carousel--post-carousel).

**Minimal example**

```text
[us_product_carousel quantity="10" items="4" arrows="1" dots="0"]
```

**Common combinations**

"Related products" carousel under a single product, same category, auto-rotate, 1-up on mobile:

```text
[us_product_carousel source="current_wp_query" quantity="12"
                     tax_query_relation="AND"
                     tax_query="%5B%7B%22operator%22%3A%22CURRENT%22%2C%22taxonomy%22%3A%22product_cat%22%2C%22terms%22%3A%22%22%2C%22include_children%22%3A%220%22%7D%5D"
                     exclude_current_product="1"
                     items="4" autoplay="1" loop="1"
                     responsive="%5B%7B%22breakpoint%22%3A%22tablets%22%2C%22items%22%3A%222%22%7D%2C%7B%22breakpoint%22%3A%22mobiles%22%2C%22items%22%3A%221%22%7D%5D"]
```

Featured-products strip with custom-styled arrows:

```text
[us_product_carousel featured_only="1" quantity="8"
                     items="4" items_gap="20px"
                     arrows="1" arrows_style="square"
                     dots="0" loop="1"]
```

**Anti-patterns**

- Setting `pagination` here — it's stripped on the parent's `exclude_for_carousel` pass; users won't see a Load-More button even if the attribute appears in the markup.
- `items="auto"` plus `center_item="1"` — centering needs a fixed item count.
- Forgetting `responsive` overrides — 4 product cards squashed into a 320px viewport are unusable.
