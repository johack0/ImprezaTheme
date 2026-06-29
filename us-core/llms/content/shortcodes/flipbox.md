---
title: `us_flipbox` — Flip Box
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/flipbox.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/flipbox.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=flipbox
  Direct edits here will be lost on the next regeneration.
-->

# `us_flipbox` — Flip Box

**When to use**: a card whose front shows a teaser (icon/image + title + short text) and whose back reveals extra detail (text + optional CTA) on hover or tap.

**Avoid when**:
- you have a lot of back-side text — users won't keep their cursor over the card;
- you need a static feature card — `[us_iconbox]` is simpler;
- the experience must work on touch — flipboxes are awkward there because hover doesn't exist.

**Key parameters**

**Front side**

| Param | What it does |
|-------|--------------|
| `front_title` | Front-side title. |
| `front_title_tag` | `h4` (default), `h1`–`h6`, `div`, `span`. |
| `front_desc` | Front-side description (multi-line). |
| `front_icon_type` | `none` (default), `font` (FA icon), `image` (uploaded). |
| `front_icon_name` | FA icon `set|name` — used when `front_icon_type="font"`. |
| `front_icon_image` | Media ID — used when `front_icon_type="image"`. |
| `front_icon_pos` | `above_title` (default), and other positions relative to the title. |
| `front_bgcolor` / `front_textcolor` | Background and text colors. |
| `front_bgimage` | Background image media ID. |

**Back side** — same shape with `back_*` prefix: `back_title`, `back_title_tag`, `back_desc`, `back_bgcolor`, `back_textcolor`, `back_bgimage`.

**Link / CTA**

| Param | What it does |
|-------|--------------|
| `link_type` | `none` (default) / `container` (the whole card is a link) / `btn` (an explicit button on the back). |
| `link` | The link URL/target (URL-encoded JSON, see composition-rules §3.1). Visible when `link_type="container"` or `"btn"`. |
| `btn_label` | Button text. Visible when `link_type="btn"`. |
| `btn_style` | Per-site button style. |

**Animation**

| Param | What it does |
|-------|--------------|
| `animation` | Flip style — `cardflip` (default), `cubetilt`, `cubeflip`, `coveropen`. |
| `direction` | Where the back comes from — `n`, `e`, `s`, `w`, `ne`, `se`, `sw`, `nw`. Default `w`. |
| `duration` | Animation duration in seconds (e.g. `0.5s`). |
| `easing` | Easing function — `ease`, `easeInOutExpo`, `easeInOutCirc`. |

**Minimal example**

```text
[us_flipbox front_icon_type="font" front_icon_name="fas|bolt" front_title="Fast" back_title="Sub-50ms" back_desc="Cold-start metrics from p99." link_type="btn" btn_label="See benchmark" link="%7B%22url%22%3A%22%23benchmark%22%7D"]
```

**Anti-patterns**

- Critical info hidden only on the back of a flipbox — duplicate it on the front or use a static block.
- Multiple flipboxes with different `direction` in one row — visually inconsistent.
