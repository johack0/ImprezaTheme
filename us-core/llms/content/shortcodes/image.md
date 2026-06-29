---
title: `us_image` — Image
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/image.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/image.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=image
  Direct edits here will be lost on the next regeneration.
-->

# `us_image` — Image

**When to use**: a single image — hero illustration, product shot, screenshot, logo, decorative thumbnail. Supports linking, lazy-loading control, decorative styles (circle, shadow, phone-mockup frames), and captions from media metadata.

**Avoid when**:
- you want multiple images in a grid — use `[us_gallery]`;
- you want a single image as a row's full background — use `vc_row.us_bg_image` instead;
- you want a horizontally scrollable image strip — use `[us_image_slider]`;
- you need a lightbox click on a single image — `us_image` has no built-in lightbox; wrap it in `[us_popup]` with image content, or use `[us_gallery]` with a single ID and `items_click_action="popup_image"`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `image` | Media library ID of the image. Required. Supports dynamic values (e.g. ACF / featured image). |
| `size` | WP image size preset — `thumbnail`, `medium`, `large` (default), `full`, or any registered custom size. |
| `align` | `none` (default), `left`, `center`, `right`. |
| `link` | Optional link wrapping the image. URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank","rel":"nofollow"}`. Supports dynamic values. |
| `style` | Decorative frame — empty (default, none) / `circle` / `outlined` / `shadow-1` (simple shadow) / `shadow-2` (colored shadow) / `phone12` (phone mock-up, flat) / `phone6-1` (iPhone 6 black realistic) / `phone6-2` (iPhone 6 white realistic) / `phone6-3` / `phone6-4`. This list is hardcoded by the theme, not configurable per-site. |
| `meta` | `1` shows the image title and description from the media library. |
| `meta_style` | When `meta="1"`: `simple` (default — caption below the image) or `modern` (caption overlaid on the image). |
| `disable_lazy_loading` | `1` opts the image out of lazy-loading. Set this for above-the-fold hero images to avoid layout shift on first paint. |

**Aspect ratio**: crop the image to a fixed ratio via the Design `css` attribute's `aspect-ratio` property (a CSS ratio such as `1`, `16/9`, `2/3`) — see element-design.md and the `css="…"` spec in composition-rules §3.3. There is no dedicated ratio attribute; the legacy `has_ratio` / `ratio` / `ratio_width` / `ratio_height` are read only as a back-compat fallback. With an `aspect-ratio` set, the image fills the box (`object-fit: cover`).

**Minimal example**

```text
[us_image image="123" size="large"]
```

**Common combinations**

Centered product shot with the "Phone 12" mock-up frame:

```text
[us_image image="123" size="large" align="center" style="phone12"]
```

Above-the-fold hero image (no lazy-load), with caption overlay:

```text
[us_image image="123" size="full" disable_lazy_loading="1" meta="1" meta_style="modern"]
```

Linked image with a circle crop (good for avatars):

```text
[us_image image="123" size="medium" style="circle" link="%7B%22url%22%3A%22%2Fteam%22%7D"]
```

Image cropped to 16:9 via the Design `css` (decodes to `{"default":{"aspect-ratio":"16/9"}}`):

```text
[us_image image="123" size="large" css="%7B%22default%22%3A%7B%22aspect-ratio%22%3A%2216%2F9%22%7D%7D"]
```

**Anti-patterns**

- Using the legacy `has_ratio` / `ratio` / `ratio_width` / `ratio_height` to crop the image — replaced by the Design `aspect-ratio` property (`css="…"`). The old attributes are read only as a back-compat fallback.
- Trying to use `onclick="lightbox"` — that parameter does not exist on `us_image`. For a click-to-zoom UX, either use `[us_gallery ids="..." items_click_action="popup_image"]` with a single image, or wrap `us_image` in `[us_popup]`.
- Forgetting to set `disable_lazy_loading="1"` on a hero image — visitors see a blank above-the-fold area on first paint.
- Using `size="full"` for thumbnail placement — wastes bandwidth on mobile.
- Missing alt text in the media library — `us_image` uses it; missing alt hurts accessibility and SEO.
