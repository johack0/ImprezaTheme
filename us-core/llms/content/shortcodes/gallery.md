---
title: `us_gallery` — Image Gallery
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/gallery.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/gallery.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=gallery
  Direct edits here will be lost on the next regeneration.
-->

# `us_gallery` — Image Gallery

**When to use**: a multi-image gallery in one of several layouts — grid, masonry, METRO mosaic, or horizontal mosaic — with optional lightbox on click. Typical for portfolio cases, "behind the scenes" sections, photo lists.

**Avoid when**:
- you have one image — `[us_image]`;
- you want a sliding/carousel UX — `[us_image_slider]`;
- you want product cards (image + price + CTA) — that's `[us_product_list]` — see `shortcodes/product_list`.

**Key parameters**

**Source**

| Param | What it does |
|-------|--------------|
| `ids` | Comma-separated media library IDs. Required (or use `include_post_thumbnail`). |
| `include_post_thumbnail` | `1` includes the current post's Featured image at the start. |
| `include_us_media_category` | Comma-separated category slugs to include (when using UpSolution Media Categories). |
| `exclude_us_media_category` | Comma-separated category slugs to exclude. |
| `orderby` | `post__in` (default — manual order from `ids`), `date`, `modified`, `rand`, `title`. |
| `order_invert` | `1` reverses the order (no effect for `post__in` or `rand`). |
| `quantity_type` | How many items to show — `all`, `layout_based` (smart, based on chosen layout), `custom`. |
| `quantity` | Item count when `quantity_type="custom"`. Default `12`. |

**Layout / appearance**

| Param | What it does |
|-------|--------------|
| `layout` | `grid` (default) / `masonry` / `metro_1`…`metro_5` / `mosaic_hor`. |
| `columns` | Column count for `grid` / `masonry` layouts. Default `4`. |
| `items_gap` | Gap between thumbnails (CSS units, e.g. `10px`, `1rem`). Default `10px`. |
| `item_aspect_ratio` | Aspect ratio per image, written as a CSS `aspect-ratio` value: `1` (default — square), `16/9` (landscape), `2/3` (portrait), or any `W/H`. A single number `N` means `N/1`. Hidden for `masonry` and `mosaic_hor` (those size items by their own proportions). Forced to `auto` when `columns="1"`. |
| `items_height` | Image height for `mosaic_hor` layout. Default `30cqw`. |
| `items_title` | `1` renders the image title under each thumbnail. |
| `img_fit` | `cover` (default — fill area, crops if needed) / `contain` (fit, may letterbox). |
| `img_size` | WP image size preset — `thumbnail`, `medium`, `large` (default), `full`, or custom. |

**Click behaviour**

| Param | What it does |
|-------|--------------|
| `items_click_action` | What happens when a thumbnail is clicked — `none` (default), `popup_image` (lightbox), `link` (custom URL). |
| `items_link` | Custom link target when `items_click_action="link"`. URL-encoded JSON (see composition-rules §3.1). |

**Pagination** (optional)

| Param | What it does |
|-------|--------------|
| `pagination` | `none` (default) / `load_on_scroll` (infinite-scroll) / `load_on_btn` (load-more button). Only with `quantity_type="custom"`. |
| `pagination_btn_text` | Label for the load-more button. Default `Load More`. |
| `pagination_btn_style` | Per-site button style (Theme Options → Buttons). |

**Minimal example**

```text
[us_gallery ids="12,34,56,78" columns="4" items_click_action="popup_image"]
```

**Common combinations**

Masonry portfolio with custom click-through to a project page:

```text
[us_gallery ids="12,34,56,78,90" layout="masonry" columns="3" items_gap="1rem" items_click_action="link" items_title="1"]
```

METRO mosaic for a featured gallery row (no clicks, just visual):

```text
[us_gallery ids="11,12,13,14,15" layout="metro_2" items_gap="6px" items_click_action="none"]
```

**Anti-patterns**

- Using a non-existent `onclick` parameter — the correct name is `items_click_action`, with values `popup_image` (lightbox) / `link` / `none`.
- Using a non-existent `indents` parameter — the correct name is `items_gap`.
- 30+ images in one gallery without `pagination` — page weight balloons. Set `pagination="load_on_scroll"` or `"load_on_btn"`.
- Mixed aspect ratios across thumbnails without picking `item_aspect_ratio` — set a single ratio (e.g. `item_aspect_ratio="1"`) or use `masonry` to embrace the variation.
- Using the legacy `items_ratio` / `items_ratio_width` / `items_ratio_height` (the old select + custom width/height) — replaced by the single `item_aspect_ratio` that takes a CSS ratio like `16/9`. The old names are honoured only as a back-compat fallback; don't author with them.
