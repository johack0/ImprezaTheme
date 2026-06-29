---
title: `us_ibanner` — Interactive Banner
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/ibanner.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/ibanner.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=ibanner
  Direct edits here will be lost on the next regeneration.
-->

# `us_ibanner` — Interactive Banner

**When to use**: a clickable image card with a hover-revealed overlay containing a title and description — promo tiles, category cards, "discover X" blocks. Often used in 2-/3-column rows. The whole banner is one link (no separate CTA button).

**Avoid when**:
- you want a plain image — `[us_image]`;
- you want a slideshow of banners — `[us_image_slider]`;
- you need an explicit call-to-action button on the card — `[us_iconbox]` (link) or a `[us_cta]` works better, since `us_ibanner` has no `btn_*` of its own.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `image` | Media library ID of the banner image. Required. |
| `size` | WP image size preset — `thumbnail`, `medium`, `large` (default), `full`, or any registered custom size. |
| `title` | Overlay heading shown on hover (default `Title`). |
| `title_size` | Custom CSS font-size for the title. |
| `title_tag` | Heading tag — `h2` (default), `h1`/`h3`–`h6`, `div`, `span`. |
| `desc` | Overlay description text (multi-line). |
| `link` | URL the whole banner clicks to. URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank","rel":"nofollow"}`. |
| `animation` | Hover-overlay animation — `melete` (default), `soter`, `phorcys`, `aidos`, `caeros`, `hebe`, `aphelia`, `nike`. |
| `easing` | Animation easing — `ease` (default), `easeInOutExpo`, `easeInOutCirc`. |

**Aspect ratio**: set through the Design `css` attribute's `aspect-ratio` property (a CSS ratio such as `4/3`, `16/9`, `2/3`) — see element-design.md and the `css="…"` spec in composition-rules §3.3. Defaults to a square (`1/1`) when unset. The banner has **no** dedicated ratio attribute anymore; the legacy `ratio` select is read only as a back-compat fallback.

**Minimal example**

```text
[us_ibanner image="123" title="Summer Sale" desc="Up to 50% off" link="%7B%22url%22%3A%22%23shop%22%7D"]
```

**Common combinations**

Three category tiles in a row, 4:3 banners with the `soter` hover animation:

The `css` blob below decodes to `{"default":{"aspect-ratio":"4/3"}}`:

```text
[vc_row columns="3"]
  [vc_column][us_ibanner image="11" title="Apparel" link="%7B%22url%22%3A%22%2Fcat%2Fapparel%22%7D" css="%7B%22default%22%3A%7B%22aspect-ratio%22%3A%224%2F3%22%7D%7D" animation="soter"][/vc_column]
  [vc_column][us_ibanner image="12" title="Shoes"   link="%7B%22url%22%3A%22%2Fcat%2Fshoes%22%7D"   css="%7B%22default%22%3A%7B%22aspect-ratio%22%3A%224%2F3%22%7D%7D" animation="soter"][/vc_column]
  [vc_column][us_ibanner image="13" title="Bags"    link="%7B%22url%22%3A%22%2Fcat%2Fbags%22%7D"    css="%7B%22default%22%3A%7B%22aspect-ratio%22%3A%224%2F3%22%7D%7D" animation="soter"][/vc_column]
[/vc_row]
```

**Anti-patterns**

- Heavy text in `desc` over a busy image — overlay readability drops. Keep `desc` short, or pre-darken the image.
- Different `aspect-ratio` values across banners in the same row — visual rhythm breaks; give every banner in the row the same Design `aspect-ratio`.
- Trying to add a separate button overlay — `us_ibanner` doesn't have button params; the whole banner is the click target via `link`. For text + image + CTA-button cards, compose `us_image` + `us_text` + `us_btn` inside a column instead.
