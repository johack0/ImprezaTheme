---
title: `us_image_slider` — Image Slider
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/image_slider.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/image_slider.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=image_slider
  Direct edits here will be lost on the next regeneration.
-->

# `us_image_slider` — Image Slider

**When to use**: a slideshow of images with prev/next or thumbnail navigation, optional auto-rotation and full-screen view — hero rotations, screenshot carousels, before/after sets.

**Avoid when**:
- you want a grid of thumbnails — `[us_gallery]`;
- you want a slider of mixed-content cards (image + caption + button) — `[us_content_carousel]`;
- you only have one image — `[us_image]`.

**Key parameters**

**Source**

| Param | What it does |
|-------|--------------|
| `ids` | Comma-separated media library IDs. Required. Supports dynamic values. |
| `include_post_thumbnail` | `1` includes the current post's Featured image first. |
| `orderby` | `1` randomises the slide order (the param is a switch — set to `1` for random, `0` for the order given in `ids`). |
| `meta` | `1` shows title and description from the media library on each slide. |

**Image sizing**

| Param | What it does |
|-------|--------------|
| `img_aspect_ratio` | Fixed aspect ratio for every slide, written as a CSS `aspect-ratio` value: `1` (square), `16/9` (landscape), `2/3` (portrait), or any `W/H`. **Empty (default)** → slides auto-scale to each image's natural size. Setting it locks all slides to one shape. |
| `img_fit` | How each image fits the slide box — `scaledown` (default), `contain` (letterbox), `cover` (fill, may crop). |
| `img_size` | WP image size preset — `large` (default), `thumbnail`, `medium`, `full`, etc. |
| `style` | Decorative frame around each slide, used when `img_aspect_ratio` is empty — `none` (default), or a phone-mock-up style (`phone6-1`, `phone6-2`, …). Hardcoded set, same family as `us_image.style`. |
| `fullscreen` | `1` shows a button that opens the active slide in full-screen view. |

**Slider behaviour**

| Param | What it does |
|-------|--------------|
| `autoplay` | `1` enables auto-rotation. The two params below only apply when this is on. |
| `autoplay_period` | Time between automatic slide advances (e.g. `3s`, `5s`). Default `3s`. |
| `pause_on_hover` | `1` (default) pauses auto-rotation while the cursor is over the slider. |
| `arrows` | Prev/Next arrow visibility — `always` (default), and other options. |
| `nav` | Additional navigation — `none` (default), `dots`, `thumbs`. This is the replacement for the "show dots" toggle some other libraries use. |
| `thumbs_width` / `thumbs_gap` | Size and spacing of thumbnails when `nav="thumbs"`. |
| `transition` | `slide` (default) or `crossfade`. |
| `transition_speed` | Animation duration (e.g. `250ms`, `500ms`). |

**Minimal example**

```text
[us_image_slider ids="12,34,56" autoplay="1" autoplay_period="5s" arrows="always" nav="dots"]
```

**Common combinations**

Hero rotation, 16:9, crossfade with thumbs:

```text
[us_image_slider ids="11,12,13" img_aspect_ratio="16/9" transition="crossfade" autoplay="1" autoplay_period="6s" nav="thumbs"]
```

Screenshot carousel with full-screen viewer:

```text
[us_image_slider ids="21,22,23,24" img_fit="contain" fullscreen="1" nav="dots"]
```

**Anti-patterns**

- Using `interval` or `dots` — those parameters do not exist. Use `autoplay_period` for the rotation timing and `nav` (`dots`/`thumbs`/`none`) for the additional navigation.
- Setting `autoplay_period` under `3s` — visitors can't read meta captions before the next slide arrives.
- Slider at the very top of a page with `nav="none"` and `arrows` hidden — visitors don't realise there's more content.
- Mixing aspect ratios across slides without setting `img_aspect_ratio` — slide heights jump on every transition. Lock them to one shape, e.g. `img_aspect_ratio="16/9"`.
- Using the legacy `has_ratio` / `ratio` / `ratio_width` / `ratio_height` — superseded by the single `img_aspect_ratio` (a CSS ratio like `16/9`). The old names are read only as a back-compat fallback.
