---
title: `us_user_carousel` — User Carousel
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/user_carousel.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/user_carousel.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=user_carousel
  Direct edits here will be lost on the next regeneration.
-->

# `us_user_carousel` — User Carousel

**When to use**: a horizontally-rotating slider of registered users — "Meet the team" rail on a homepage, contributors strip on a blog landing. Same query model as [`us_user_list`](#us_user_list--user-list); Owl carousel instead of a grid.

**Avoid when**:
- you want a static grid — use `us_user_list`;
- you want curated cards not tied to the WP users table — use `us_person` inside `us_content_carousel`.

**Source / Order / Appearance**: identical to `us_user_list` for `source`, `user_ids`, `role`, `has_published_posts`, `exclude_current`, `orderby*`, `order_invert`, `show_all`, `number`, `meta_query*`, `no_items_*`, `items_layout`, `items_gap`, `load_animation`, `overriding_link`, `popup_*`. The carousel params (`items`, `next_item_offset`, `items_valign`, `center_item`, `slide_by_one`, `autoheight`, `loop`, `autoplay*`, `transition_*`, `arrows*`, `dots`, `dots_style`, `mouse_drag`, `touch_drag`, `responsive`) work as documented under [`us_post_carousel`](#us_post_carousel--post-carousel).

**Minimal example**

```text
[us_user_carousel source="role__in" role="editor,author"
                  items="3" arrows="1" loop="1"]
```

**Common combinations**

Team strip with auto-rotation, 1-up on mobile:

```text
[us_user_carousel source="include" user_ids="12,7,18,4,9"
                  orderby="include" items_layout="user_1"
                  items="4" autoplay="1" autoplay_timeout="5s" loop="1"
                  responsive="%5B%7B%22breakpoint%22%3A%22tablets%22%2C%22items%22%3A%222%22%7D%2C%7B%22breakpoint%22%3A%22mobiles%22%2C%22items%22%3A%221%22%7D%5D"]
```

**Anti-patterns**

- Setting `columns` here — there is no `columns` param on user carousels; `items` controls slides visible at once.
- Pairing with `us_list_filter` — filter widgets drive post queries, not user queries.
