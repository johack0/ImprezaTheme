---
title: `us_post_image` — Post Image
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_image.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_image.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_image
  Direct edits here will be lost on the next regeneration.
-->

> **Section context — where to use these.**
>
> Every shortcode in this section is **designed for Page Templates** (`us_content_template`). See [post-types.md](post-types.md) for the Page vs Template distinction.
>
> Each element looks up its value on the **current post in the loop**:
>
> - on a single post, page or CPT — the post being viewed;
> - on an archive / search / Grid Layout card — the post for the current loop iteration;
> - on a term archive — the term being viewed (the title / content elements adapt to term data).
>
> Using them **outside a Page Template** (e.g. dropped directly into the body of a regular page or post) is **not forbidden** — the parser will run them — but rarely useful: the "current post" resolves to the hosting page itself, so `[us_post_title]` on a page named "About" just outputs "About", `[us_post_taxonomy]` outputs that page's terms (usually none), and `[us_post_navigation]` walks the page hierarchy instead of a post sequence. Reach for these only when you know which post the loop will resolve to and that's the value you want surfaced.
>
> The one element with a hard gate is `us_post_content` — the builder UI only exposes it when editing a `us_content_template`. The rest is technically droppable anywhere; the guidance above is editorial, not enforced.

# `us_post_image` — Post Image

**When to use**: renders the **current** post's featured image inside a Page Template (`us_content_template`) or Grid Layout card. Honours theme image sizes, optional placeholder for posts without a thumbnail, optional gallery-on-hover preview, and an overriding link (typically the post permalink or a popup).

**Avoid when**:
- you want a fixed image picked by hand — use `us_image` (takes a media-library ID, not a post context);
- you want a slider of gallery images, not a single thumbnail — use `us_image_slider` or a Grid Layout with `media_preview` baked in;
- the page isn't bound to a single post object — the element resolves to the loop's current post; outside that context it renders nothing (or, with `placeholder="1"`, the empty-state image).

**Key parameters**

| Param | What it does |
|-------|--------------|
| `thumbnail_size` | Image size to request. Default `large`. Any registered size: `thumbnail`, `medium`, `large`, `full`, or theme-specific (`us_350_350_crop`, `us_600_0`, etc.). |
| `placeholder` | `1` renders the theme's placeholder image when the post has no featured image. `0` (default) renders nothing. |
| `media_preview` | `1` shows the post's WP-gallery images on hover (or the video player for video-format posts). |
| `gallery_images_amount` | Max gallery images to preview (2–10, default `10`). Only when `media_preview="1"`. |
| `circle` | `1` clips the image to a circle. |
| `stretch` | `1` (default) stretches the image to the column width. Has no effect once an `aspect-ratio` is set via the Design `css` (the image then fills the ratio box). |
| `disable_lazy_loading` | `1` opts out of lazy loading (use sparingly — hero images at the top of the viewport benefit from this, lists below the fold do not). |
| `link` | Where the image links to. URL-encoded JSON, same shape as other `link` params. Common values: `{"type":"post"}` (default — the post permalink), `{"type":"homepage"}`, `{"type":"popup_image"}` (opens the image in a popup), `{"url":""}` (no link). |

**Aspect ratio**: crop the featured image to a fixed ratio via the Design `css` attribute's `aspect-ratio` property (a CSS ratio such as `1`, `16/9`, `2/3`) — see element-design.md and the `css="…"` spec in composition-rules §3.3. There is no dedicated ratio attribute; the legacy `has_ratio` / `ratio` / `ratio_width` / `ratio_height` are read only as a back-compat fallback. With a ratio set the image fills the box (`object-fit: cover`), so `stretch` no longer applies.

**Minimal example**

```text
[us_post_image thumbnail_size="us_600_0" placeholder="1"]
```

**Common combinations**

Card thumbnail linked to the post, with gallery hover preview:

```text
[us_post_image thumbnail_size="us_350_350_crop"
               link="%7B%22type%22%3A%22post%22%7D"
               media_preview="1" gallery_images_amount="5"
               placeholder="1"]
```

Circular avatar-style image for a "Team member" CPT card:

```text
[us_post_image thumbnail_size="us_200_200_crop" circle="1"
               link="%7B%22type%22%3A%22post%22%7D"]
```

Hero featured image that opens in a lightbox:

```text
[us_post_image thumbnail_size="full" stretch="1"
               link="%7B%22type%22%3A%22popup_image%22%7D"
               disable_lazy_loading="1"]
```

**Anti-patterns**

- Using this on a page that isn't bound to a single post or a loop iteration — the element silently renders nothing (without `placeholder="1"`) and the column looks broken.
- `media_preview="1"` plus `link={"type":"popup_image"}` — the popup steals the hover; only one of the two interactions can win. Use one or the other.
- `circle="1"` plus `stretch="1"` on a wide column — the circle becomes an ellipse; set a square `aspect-ratio` (`1`) on this element via the Design `css` (or use a Grid Layout that handles it).
- `disable_lazy_loading="1"` on every card of a long list — defeats the point and tanks LCP.
