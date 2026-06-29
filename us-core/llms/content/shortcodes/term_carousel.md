---
title: `us_term_carousel` — Term Carousel
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/term_carousel.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/term_carousel.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=term_carousel
  Direct edits here will be lost on the next regeneration.
-->

# `us_term_carousel` — Term Carousel

**When to use**: a horizontally-rotating slider of taxonomy terms — "Browse by category" rail, portfolio-category teaser, A–Z directory in a tight column. Same query model as [`us_term_list`](#us_term_list--term-list); Owl carousel instead of a paginated grid.

**Avoid when**:
- you want a static grid — use `us_term_list`;
- you want a hierarchical sidebar — use `us_category_nav`.

**Source / Order / Appearance**: identical to `us_term_list` for `source`, `taxonomy`, `term_ids`, `include_children`, `hide_empty`, `exclude_current`, `orderby`, `orderby_custom_field`, `orderby_custom_type`, `order_invert`, `limit_number`, `number`, `meta_query*`, `no_items_*`, `items_layout`, `items_gap`, `img_size`, `title_size`, `img_aspect_ratio` (term image aspect ratio), `item_aspect_ratio` (Items Aspect Ratio), `overriding_link`, `popup_*`. The carousel params (`items`, `next_item_offset`, `items_valign`, `center_item`, `slide_by_one`, `autoheight`, `loop`, `autoplay*`, `transition_*`, `arrows*`, `dots`, `dots_style`, `mouse_drag`, `touch_drag`, `responsive`) work as documented under [`us_post_carousel`](#us_post_carousel--post-carousel).

**Minimal example**

```text
[us_term_carousel taxonomy="product_cat" hide_empty="1" items="4" arrows="1"]
```

**Common combinations**

"Shop by category" carousel, 4-up desktop / 2-up tablet / 1-up mobile:

```text
[us_term_carousel taxonomy="product_cat" hide_empty="1"
                  items_layout="tile_1" item_aspect_ratio="1"
                  items="4" loop="1" arrows="1" dots="0"
                  responsive="%5B%7B%22breakpoint%22%3A%22tablets%22%2C%22items%22%3A%222%22%7D%2C%7B%22breakpoint%22%3A%22mobiles%22%2C%22items%22%3A%221%22%2C%22arrows%22%3A%220%22%2C%22dots%22%3A%221%22%7D%5D"]
```

**Anti-patterns**

- `source="current_term"` on a page that isn't a term archive — empty carousel.
- `items="auto"` plus `center_item="1"` — centering needs a fixed item count.
- Setting grid-only params (`type`, `columns`, `load_animation`) here — they're stripped on build.
