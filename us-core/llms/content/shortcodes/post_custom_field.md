---
title: `us_post_custom_field` — Post Custom Field
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_custom_field.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_custom_field.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_custom_field
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

# `us_post_custom_field` — Post Custom Field

**When to use**: renders the value of an arbitrary custom field (post meta) for the **current** post — ACF fields, theme-provided "Additional Settings" (`us_tile_icon`, `us_tile_additional_image`), Testimonial sub-fields, or any raw `wp_postmeta` key. Typical placement: Page Template / Grid Layout card, surfacing per-post values that aren't covered by Title / Date / Taxonomy / Author.

**Avoid when**:
- the value is the post date / title / image / taxonomy / author — use the dedicated `us_post_date` / `us_post_title` / `us_post_image` / `us_post_taxonomy` / `us_post_author` (they handle formatting, links and i18n properly);
- you want a price / stock / rating from WooCommerce — those have their own elements (out of scope here);
- the field is an ACF type the element can't render (`clone`, `file`, `gallery`, `google_map`, `group`, `link`, `message`, `post_object`, `relationship`, `tab`, `taxonomy`, `true_false`, `user`) — those are explicitly filtered out of the selector.

**Key parameters**

**Source**

| Param | What it does |
|-------|--------------|
| `key` | Field id. Built-in: `us_tile_additional_image`, `us_tile_icon` (Additional Settings); `us_testimonial_author`, `us_testimonial_role`, `us_testimonial_company`, `us_testimonial_rating` (Testimonial fields, when the Testimonial CPT is enabled). ACF fields appear as their field name. `custom` falls back to a raw meta key entered in `custom_key`. |
| `custom_key` | The `wp_postmeta` key when `key="custom"`. |
| `hide_empty` | `1` removes the element entirely when the field has no value. |

**Image / repeater handling** (only when `key` points at an image or ACF repeater field)

| Param | What it does |
|-------|--------------|
| `display_type` | `1` renders an ACF repeater as a table. |
| `stretch` | `1` stretches the image to the column width. Has no effect once an `aspect-ratio` is set via the Design `css` (the image then fills the ratio box). |
| `disable_lazy_loading` | `1` opts out of lazy loading. |
| `thumbnail_size` | Image size — `thumbnail`, `medium`, `large` (default), `full`, or any theme-registered size. |

**Aspect ratio**: crop image fields to a fixed ratio via the Design `css` attribute's `aspect-ratio` property (a CSS ratio such as `1`, `16/9`, `2/3`) — see element-design.md and the `css="…"` spec in composition-rules §3.3. There is no dedicated ratio attribute; the legacy `has_ratio` / `ratio` / `ratio_width` / `ratio_height` are read only as a back-compat fallback. With a ratio set the image fills the box (`object-fit: cover`), so `stretch` no longer applies.

**Checkbox handling** (only when `key` points at an ACF checkbox)

| Param | What it does |
|-------|--------------|
| `list_display_options` | `comma_separated` (default), `unordered_list`, `ordered_list`, `separate_divs`. |

**Linking**

| Param | What it does |
|-------|--------------|
| `link` | Wrap the value in a link. URL-encoded JSON. Useful values: `{"type":"elm_value"}` (auto-detect email/phone/URL in the value), `{"type":"popup_image"}` (image fields), `{"type":"custom_field|<another_key>"}` (use another field as the URL), `{"url":""}` (no link). |
| `hide_with_empty_link` | `1` hides the element when the linked custom field is empty. Only meaningful when `link` references a custom field. |
| `color_link` | `1` (default) inherits the surrounding text colour for the link. |

**Appearance**

| Param | What it does |
|-------|--------------|
| `tag` | HTML tag for the value wrapper — `div` (default), `span`, `p`, `h2`–`h6`. Ignored for non-comma-separated checkboxes. |
| `icon` | FontAwesome icon prepended to the value. Format `<style>|<name>` (e.g. `fas|envelope`). |
| `text_before` / `text_before_tag` | Static prefix text (e.g. "Price: ") and its HTML tag. `_tag` only renders when the outer `tag` is `div`. |
| `text_after` / `text_after_tag` | Static suffix text (e.g. " min") and its HTML tag. |

**Minimal example**

```text
[us_post_custom_field key="custom" custom_key="duration"
                      text_before="" text_after=" min"]
```

**Common combinations**

ACF price field formatted as currency, hidden when empty:

```text
[us_post_custom_field key="price" hide_empty="1"
                      text_before="$" tag="span"
                      icon="fas|tag"]
```

Star-rating from the Testimonial CPT (no link, no extra text):

```text
[us_post_custom_field key="us_testimonial_rating"]
```

Image field cropped to 16:9 (via the Design `css`, decodes to `{"default":{"aspect-ratio":"16/9"}}`) with a lightbox link:

```text
[us_post_custom_field key="featured_attachment" thumbnail_size="large"
                      css="%7B%22default%22%3A%7B%22aspect-ratio%22%3A%2216%2F9%22%7D%7D"
                      link="%7B%22type%22%3A%22popup_image%22%7D"]
```

ACF email field, click-to-mail:

```text
[us_post_custom_field key="contact_email" tag="span"
                      icon="fas|envelope"
                      link="%7B%22type%22%3A%22elm_value%22%7D"
                      hide_empty="1"]
```

**Anti-patterns**

- Using `key="custom"` with a `custom_key` that points at one of the gated ACF types (`group`, `gallery`, `post_object`, …) — the element renders nothing or a serialized PHP value. Pick a different field, or use the dedicated element for that data shape.
- `hide_empty="1"` on `us_testimonial_rating` — explicitly gated off (a `0` rating is still a value); use Display Logic instead.
- Image-field params (`thumbnail_size`, `stretch`, or a Design `aspect-ratio`) on a text custom field — silently ignored.
- Using the legacy `has_ratio` / `ratio` / `ratio_width` / `ratio_height` to crop an image field — replaced by the Design `aspect-ratio` property (`css="…"`). The old attributes are read only as a back-compat fallback.
- `link={"type":"elm_value"}` on a numeric / freeform field — the auto-detect runs once on each value; if the value isn't a parseable email / phone / URL, no link is emitted.
