---
title: `us_post_carousel` — Post Carousel
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_carousel.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_carousel.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_carousel
  Direct edits here will be lost on the next regeneration.
-->

# `us_post_carousel` — Post Carousel

**When to use**: a horizontally-rotating slider of posts — "Latest news" strip on a homepage, "Related posts" rail, partner logos pulled from a CPT, etc. Same query model as [`us_post_list`](#us_post_list--post-list) but the result is an Owl carousel instead of a paginated grid.

**Avoid when**:
- the items are products / terms / users — use `us_product_carousel` / `us_term_carousel` / `us_user_carousel`;
- you want pagination, Load-More, masonry, METRO, or items-per-page control — those are `us_post_list` only (the carousel has no `pagination`, `type`, `columns`, `load_animation`, `items_preload_style`, `items_valign`, `ignore_items_size` — they are stripped from the carousel build);
- the slides are arbitrary blocks unrelated to posts — use `us_content_carousel`.

**Source / Order / No-results**: identical to `us_post_list` — see that entry for `source`, `post_type`, `ids`, `post_author`, `apply_url_params`, `exclude_*`, `tax_query*`, `meta_query*`, `orderby*`, `order_invert`, `show_all`, `quantity`, `posts_per_archive_page`, `no_items_action`, `no_items_message`, `no_items_page_block`. The `items_layout`, `items_gap`, `img_size`, `title_size`, `img_aspect_ratio` (Post Image Aspect Ratio), `item_aspect_ratio` (Items Aspect Ratio), `overriding_link`, `popup_*` attributes also work the same way.

**Carousel-specific parameters**

| Param | What it does |
|-------|--------------|
| `items` | Slides visible at once on desktop — `auto` (for variable widths) or `1`–`10`. Default `3`. |
| `next_item_offset` | Px reveal of the next slide's edge (peek). Default `0px`. |
| `items_valign` | `stretch` (default), `top`, `middle`, `bottom`. |
| `center_item` | `1` puts the active slide in the middle (requires `items` ≥ 2). |
| `slide_by_one` | `1` (default) advances one slide at a time; `0` advances `items` at a time. |
| `autoheight` | `1` re-measures height to the active slide (only when `items="1"`). |
| `loop` | `1` wraps around at the ends. |
| `autoplay` | `1` enables auto-rotation. |
| `autoplay_pause_on_hover` / `autoplay_continual` / `autoplay_continual_css` / `autoplay_timeout` | Auto-rotation tweaks (only meaningful when `autoplay="1"`). `autoplay_timeout` accepts `s` units (default `3s`). |
| `transition_speed` | Slide transition duration in `ms` (default `350ms`). |
| `transition_animation` | `none` (default — sliding) or `fade` (only for `items="1"`). |
| `transition_timing_function` | CSS timing function name or `cubic-bezier(…)`. |
| `arrows` | `1` (default) shows prev/next arrows. |
| `arrows_style` | `circle` (default), `square`, or a button-style key from Theme Options → Buttons. |
| `arrows_size` | Arrow size — `px` / `rem` / `em`. Default `1.5rem`. |
| `arrows_ver_pos` | `middle` (default), `stretch`, `top_outside`, `top_inside`, `bottom_outside`, `bottom_inside`. |
| `arrows_hor_pos` | `on_sides_outside` (default), `on_sides_inside`, `left_inside`, `center`, `right_inside`. |
| `arrows_ver_offset` / `arrows_hor_offset` / `arrows_gap` | Fine-tune arrow placement. |
| `arrows_disabled` | `hide` (default) or `fade` for the disabled arrow when `loop="0"`. |
| `dots` | `1` shows pagination dots under the carousel. |
| `dots_style` | `circle` (default), `diamond`, `dash`, `smudge`. |
| `mouse_drag` / `touch_drag` | `1` (default) enables drag-swipe. |
| `responsive` | JSON array of breakpoint overrides — each entry `{ "breakpoint": "laptops"|"tablets"|"mobiles"|"custom", "breakpoint_width": "1024px", "items": "1", "items_offset": "0px", "center_item": 0, "autoheight": 0, "loop": 0, "autoplay": 0, "arrows": 0, "dots": 0 }`. Settings apply below the chosen width. |

**Minimal example**

```text
[us_post_carousel post_type="post" quantity="8" items_layout="blog_1"
                  items="3" arrows="1" dots="1"]
```

**Common combinations**

"Related posts" rail — same category as the current post, auto-rotation, 1-up on mobile:

```text
[us_post_carousel source="current_wp_query" quantity="10"
                  tax_query_relation="AND"
                  tax_query="%5B%7B%22operator%22%3A%22CURRENT%22%2C%22taxonomy%22%3A%22category%22%2C%22terms%22%3A%22%22%2C%22include_children%22%3A%220%22%7D%5D"
                  exclude_current_post="1"
                  items="3" autoplay="1" autoplay_timeout="5s" loop="1"
                  responsive="%5B%7B%22breakpoint%22%3A%22tablets%22%2C%22items%22%3A%222%22%2C%22arrows%22%3A%221%22%2C%22dots%22%3A%220%22%7D%2C%7B%22breakpoint%22%3A%22mobiles%22%2C%22items%22%3A%221%22%2C%22arrows%22%3A%220%22%2C%22dots%22%3A%221%22%7D%5D"]
```

**Anti-patterns**

- Using `pagination` / `type` / `columns` / `load_animation` / `items_preload_style` here — none of them exist on the carousel; the params are silently dropped by the parent's `exclude_for_carousel` filter.
- `items="auto"` together with `center_item="1"` — centering needs a known item count.
- `transition_animation="fade"` with `items` ≠ `1` — fade is single-slide only; the carousel falls back to sliding without warning.
- Forgetting the `responsive` JSON — desktop `items="4"` becomes unreadable on phones.
