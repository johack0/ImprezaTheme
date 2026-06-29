---
title: `vc_row` — Row / Section
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_row.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_row.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_row
  Direct edits here will be lost on the next regeneration.
-->

# `vc_row` — Row / Section

**When to use**: the top-level container of any page block. Every shortcode in `post_content` must be wrapped in `vc_row → vc_column` at the root; nothing else can sit directly under `post_content`. This is also the only place that can carry a section-level background (image, video, image slider), a sticky behaviour, shape dividers between sections, and full-viewport height.

**Avoid when**:
- you only need to group a couple of inline elements with a gap — use `[us_hwrapper]` or `[us_vwrapper]` inside a column instead;
- you need a nested grid inside a column — use `vc_row_inner` + `vc_column_inner` (it has only column-layout params, no section-level features);
- you want a horizontally scrollable list — use `[us_content_carousel]` — see `shortcodes/content_carousel`.

**Key parameters**

**Width & vertical size**

| Param | What it does |
|-------|--------------|
| `width` | **Content** width — `default` (theme default ~1240px), `full` (content bleeds edge-to-edge), `full_with_indents` (full content + side gutters), `custom`. Controls the inner content container only. **The row's background** (color set via `css="…"`, `us_bg_*` image / video / slider, overlay, shape dividers) **is always full-viewport-width regardless of this param** — at render time `<section class="l-section">` is the full-width host of all background layers, and `width_*` modifies only the child `.l-section-h` content wrapper. So a hero with a coloured / imaged background does **not** need `width="full"` to get an edge-to-edge background; set `full` only when the **content itself** should reach the screen edges (full-width images, edge-to-edge banner copy, wide carousels). |
| `width_custom` | Custom width when `width="custom"`. Default `1240px`. Slider in `px`. |
| `height` | Vertical padding preset — `default` (theme default ~4vh), `auto` (no padding), `small`, `medium`, `large`, `huge`. |
| `full_height` | `1` makes the row fill the viewport height (typical for hero sections). Default `0`. |
| `v_align` | Vertical alignment of content when `full_height="1"` — `top`, `center` (default), `bottom`. |
| `color_scheme` | Color palette — empty (content colors), `alternate`, `primary` (primary bg + white text), `secondary` (secondary bg + white text), `footer-top`, `footer-bottom`. |
| `row_title` | Optional editor-only label for this row (shown in the builder UI, not on the front-end). |

**Background — image**

| Param | What it does |
|-------|--------------|
| `us_bg_image_source` | `none` (default), `media` (custom upload), `featured` (post's featured image). |
| `us_bg_image` | Media library ID when `us_bg_image_source="media"`. |
| `us_bg_size` | `cover` (default — fill, may crop), `contain` (fit, may letterbox), `initial`. |
| `us_bg_pos` | One of 9 positions: `top left`, `top center`, `top right`, `center left`, `center center` (default), `center right`, `bottom left`, `bottom center`, `bottom right`. |
| `us_bg_repeat` | `repeat` (default), `repeat-x`, `repeat-y`, `no-repeat`. |
| `us_bg_parallax` | Parallax effect — empty (none, default), `vertical`, `horizontal`, `still` (fixed). |
| `us_bg_parallax_width` | Background width for `horizontal` parallax. Default `130%`. |
| `us_bg_parallax_reverse` | `1` reverses `vertical` parallax direction. |
| `us_bg_overlay_color` | Overlay tint on top of the background (HEX/RGBA or palette var, e.g. `rgba(0,0,0,0.5)`). |

**Background — video or image slider**

| Param | What it does |
|-------|--------------|
| `us_bg_show` | Foreground background-media — empty (none), `video`, `img_slider`. Combines with the image background above. |
| `us_bg_video` | Video URL (YouTube/Vimeo/mp4/webm/ogg). Supports dynamic values. Visible when `us_bg_show="video"`. |
| `us_bg_video_disable_width` | Below this screen width, hide the video and fall back to the static image. Default `600px`. |
| `us_bg_slider_ids` | Comma-separated media IDs for the slider when `us_bg_show="img_slider"`. |
| `us_bg_slider_include_post_thumbnail` | `1` prepends the post's featured image. |
| `us_bg_slider_orderby` | `1` randomises slide order. |
| `us_bg_slider_transition` | `slide` (default) or `crossfade`. |
| `us_bg_slider_speed` | Transition duration. Default `1000ms`. |
| `us_bg_slider_interval` | Auto-rotation interval. Default `3.0s`. |

**Sticky behaviour**

| Param | What it does |
|-------|--------------|
| `sticky` | `1` pins the row to the top of the viewport during scroll. Useful for context bars, ToC headers. |
| `sticky_shadow` | Shadow when stuck — `none` (default), `thin`, `wide`. |

**Columns layout**

The **desktop column layout is determined by the `width` attribute on each child `vc_column`**, not by an attribute on `vc_row` itself. The `columns` attribute below is auto-computed from those widths at render time and cannot be set manually — passing `columns="3"` on the row alone does nothing if the columns do not carry `width="1/3"`. See `vc_column` for the full list of allowed `width` fractions and the layouts they produce. The responsive overrides below (`laptops_columns`, `tablets_columns`, `mobiles_columns`) **do** work as attributes on the row, because there are no narrower-viewport equivalents on child columns.

| Param | What it does |
|-------|--------------|
| `columns` | _Computed automatically from child `vc_column` widths._ Do not set on the row — it is overwritten at render time. Documented here only so you do not mistake it for an input. |
| `columns_layout` | CSS `grid-template-columns` value when the child widths reduce to `custom`. Also auto-computed from children — do not set manually. |
| `laptops_columns` | Override columns layout on laptops (≤1380px). Accepts the same enum as the computed `columns` (`1`, `2`, `3`, `4`, `5`, `6`, `1-2`, `2-1`, `1-3`, `3-1`, `1-4`, `4-1`, `1-5`, `5-1`, `2-3`, `3-2`, `1-2-1`, `1-3-1`, `1-4-1`, `custom`). `inherit` (default) reuses desktop. |
| `tablets_columns` | Override on tablets (≤1024px). `inherit` reuses laptop. |
| `mobiles_columns` | Override on mobile (≤600px). Defaults to `1`. |
| `columns_gap_source` | Where the gap between columns comes from — `default` (default: use the site-wide gap from Theme Options → Layout) or `custom` (use this row's `columns_gap`). To set a per-row gap, emit the pair: `columns_gap_source="custom" columns_gap="…"` — that is the form the builder saves. Omit both to follow the site-wide value. A bare `columns_gap` without the source attribute still renders (legacy content), but don't author it that way. |
| `columns_gap` | Per-row spacing between columns, applied with `columns_gap_source="custom"`. Default `3rem`. Two shapes (no separate `tablets_columns_gap` / `mobiles_columns_gap` attribute exists): **scalar** — `columns_gap="2rem"`, applied to every breakpoint, becomes the vertical gap when columns stack on mobile; **per-breakpoint JSON** — URL-encoded JSON in DOUBLE quotes: `columns_gap="%7B%22default%22%3A%226rem%22%2C%22mobiles%22%3A%221rem%22%7D"` (decodes to `{"default":"6rem","mobiles":"1rem"}`). Single-quoted raw JSON is mangled by `wptexturize` and silently falls back to defaults — never use it. Keys: `default`, `laptops`, `tablets`, `mobiles` — all optional. See composition-rules §3.4 for the full pattern. |
| `equal_columns_height` | `1` forces all columns to the same height (useful for cards). |
| `content_placement` | Vertical position of column content when `equal_columns_height="0"` — `top` (default), `middle`, `bottom`. |
| `gap` | Additional gap on top of `columns_gap` (free-form CSS units). |
| `columns_type` | `1` adds extra padding around each column's content — improves cards with backgrounds. |
| `columns_reverse` | `1` reverses column stacking on mobile (last column becomes first when stacked). |
| `ignore_columns_stacking` | `1` opts the row out of the global "Columns Stacking Width" theme option (useful for icon-grids that should stay multi-column on narrow screens). |

**Shape dividers** (decorative wave / triangle / curve between sections)

Top and bottom each have their own independent set with the same shape:

| Param | What it does |
|-------|--------------|
| `us_shape_show_top` / `us_shape_show_bottom` | `1` enables the divider at the top / bottom edge of the row. |
| `us_shape_top` / `us_shape_bottom` | Shape style — `tilt`, `curve`, `curve-inv`, `triangle`, `triangle-inv`, `triangle-2`, `triangle-2-inv`, `wave`, `zigzag`, `custom`. Default `tilt`. |
| `us_shape_custom_top` / `us_shape_custom_bottom` | Media ID of a custom SVG when `us_shape_*="custom"`. |
| `us_shape_height_top` / `us_shape_height_bottom` | Divider height (CSS units). Default `15vmin`. |
| `us_shape_color_top` / `us_shape_color_bottom` | Divider color (HEX/RGBA/palette var, e.g. `_content_bg` to match the next section). |
| `us_shape_overlap_top` / `us_shape_overlap_bottom` | `1` lets the row's content peek over the divider. |
| `us_shape_flip_top` / `us_shape_flip_bottom` | `1` flips the shape horizontally. |

**Minimal example**

```text
[vc_row][vc_column][us_text text="Hello"][/vc_column][/vc_row]
```

**Common combinations**

Hero section — background image, dark overlay, centered content, viewport height. The background image and overlay span the full viewport regardless of `width`, so leave `width` at its default and let the content stay within the theme's content column:

```text
[vc_row full_height="1" v_align="center" us_bg_image_source="media" us_bg_image="123" us_bg_overlay_color="rgba(0,0,0,0.5)" color_scheme="primary"]
  [vc_column]
    [us_text text="Headline" tag="h1"]
    [us_btn label="Get started" link="%7B%22url%22%3A%22%23signup%22%7D" align="center"]
  [/vc_column]
[/vc_row]
```

Two equal-width columns of features, with a wave divider into the next section:

```text
[vc_row us_shape_show_bottom="1" us_shape_bottom="wave" us_shape_color_bottom="_content_bg_alt"]
  [vc_column width="1/2"][us_iconbox icon="fas|bolt" title="Fast"]Cold-start under 50ms.[/us_iconbox][/vc_column]
  [vc_column width="1/2"][us_iconbox icon="fas|lock" title="Secure"]End-to-end encryption.[/us_iconbox][/vc_column]
[/vc_row]
```

Image slider as the row background (auto-rotating, fades between slides):

```text
[vc_row full_height="1" v_align="center" us_bg_show="img_slider" us_bg_slider_ids="11,12,13" us_bg_slider_transition="crossfade" us_bg_slider_interval="5.0s" us_bg_overlay_color="rgba(0,0,0,0.3)" color_scheme="primary"]
  [vc_column]
    [us_text text="Built for builders" tag="h1"]
  [/vc_column]
[/vc_row]
```

Three equal columns that become 2 on tablet, 1 on mobile:

```text
[vc_row tablets_columns="2" mobiles_columns="1" columns_gap="2rem" equal_columns_height="1"]
  [vc_column width="1/3"][us_iconbox icon="fas|bolt"  title="Fast"]…[/us_iconbox][/vc_column]
  [vc_column width="1/3"][us_iconbox icon="fas|lock"  title="Secure"]…[/us_iconbox][/vc_column]
  [vc_column width="1/3"][us_iconbox icon="fas|heart" title="Friendly"]…[/us_iconbox][/vc_column]
[/vc_row]
```

Sticky context bar at the top of a long page:

```text
[vc_row sticky="1" sticky_shadow="thin" height="auto" color_scheme="alternate"]
  [vc_column][us_text text="📍 You are reading: Section title"][/vc_column]
[/vc_row]
```

**Anti-patterns**

- Putting elements directly inside `vc_row` without a `vc_column` — they will not render correctly.
- Stacking `vc_row` inside `vc_column` to add a nested grid — use `vc_row_inner` + `vc_column_inner` for nested grids instead.
- Setting `full_height="1"` on every row — reserve it for hero sections, otherwise the page feels endless.
- **Setting `columns="N"` on the row and omitting `width="…"` on the child columns.** The attribute is recomputed from the children at render time and silently collapses to `cols_1`. Always put `width="<num>/<den>"` on each `vc_column`. See the **Columns layout** section above.
- Setting `us_bg_show="video"` with autoplay on a long page without `us_bg_video_disable_width` — mobile devices struggle to render a background video and battery drains. The disable-width fallback (default `600px`) is your friend.
- Loading background images for rows below the fold without considering page weight — combine with theme-level image optimization or set `us_bg_image_source="featured"` so WP serves the correct size.
