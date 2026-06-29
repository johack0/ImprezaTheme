---
title: `us_content_carousel` — Content Carousel
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/content_carousel.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/content_carousel.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=content_carousel
  Direct edits here will be lost on the next regeneration.
-->

# `us_content_carousel` — Content Carousel

**When to use**: a horizontally-rotating slider of arbitrary children (icon boxes, person cards, CTA blocks, testimonial cards…). Anything you can put inside a `vc_column` you can put inside one slide here.

**Avoid when**:
- the children are all images — `[us_image_slider]` is purpose-built for that (smaller markup, fullscreen view, thumb nav);
- the children are post entries — `[us_post_list]` / `[us_post_carousel]` know how to query them;
- you only need a static row — `[us_hwrapper]` or two `vc_column`s avoid the JS init cost.

**Children**: any leaf element. **Not allowed as children** (the container blocks them): `vc_column`, other carousels (`us_carousel`, `us_content_carousel`, `us_post_carousel`…), `vc_tta_*` tabs/accordions, `us_gmaps`, `us_cform`, `us_dropdown`, `us_search`, `us_login`, `us_page_block`, `us_separator`, `us_gallery`, `us_image_slider`. Each direct child becomes one slide.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `items_gap` | Gap between slides, **px only** (default `30px`). Unlike other carousels this one has no rem/em option. |
| `items_quantity` | Slides visible at once. Default `4`. Responsive JSON — URL-encoded in DOUBLE quotes (e.g. `items_quantity="%7B%22default%22%3A%224%22%2C%22tablets%22%3A%222%22%2C%22mobiles%22%3A%221%22%7D"` decodes to `{"default":"4","tablets":"2","mobiles":"1"}`). Single-quoted raw JSON is mangled by `wptexturize`. See composition-rules §3.4. |
| `arrows` | `1` shows prev/next arrows. |
| `arrows_style` | Arrow style preset — `circle`, `square`, `none`. |
| `arrows_pos` | Arrow position relative to the carousel — `inside`, `outside`. |
| `arrows_offset` | Pixel offset of arrows from the carousel edge. |
| `dots` | `1` shows pagination dots under the slides. |
| `auto_play` | `1` enables auto-rotation. |
| `timeout` | Auto-rotation period in ms (e.g. `5000`). Only applies when `auto_play="1"`. |
| `transition_speed` | Slide transition duration in ms. |
| `loop` | `1` (default) wraps around at the ends; `0` stops at first/last. |
| `mouse_drag` | `1` lets visitors drag-swipe with the mouse. |
| `touch_swipe` | `1` (default) enables touch swipe on mobile. |

**Minimal example**

```text
[us_content_carousel arrows="1" dots="1" items_quantity="%7B%22default%22%3A%223%22%2C%22tablets%22%3A%222%22%2C%22mobiles%22%3A%221%22%7D"]
  [us_iconbox icon="fas|star" title="Fast" text="Loads in under 1s."]
  [us_iconbox icon="fas|shield" title="Secure" text="End-to-end encryption."]
  [us_iconbox icon="fas|bolt" title="Scalable" text="From 1 to 1M users."]
[/us_content_carousel]
```

**Common combinations**

Testimonials rotation, one card at a time, auto-advance every 6s:

```text
[us_content_carousel items_quantity="1" auto_play="1" timeout="6000" arrows="1" dots="1"]
  [us_person name="Anna Lee" role="CTO, Acme" text="..." image="123"]
  [us_person name="Mark Yu"  role="Lead, Beta" text="..." image="124"]
[/us_content_carousel]
```

**Anti-patterns**

- Wrapping a single child — there's nothing to carousel; use the child directly.
- Putting `vc_column` inside (the container's `except` list blocks it — the markup will render but the column won't behave as a slide).
- `items_gap` written as `1rem` — only `px` is accepted here. Use a number with `px` or none.
- `items_quantity` set to `4` on mobile — slides become unreadable. Always set a per-breakpoint JSON.
