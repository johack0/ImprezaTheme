---
title: `us_popup` — Popup
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/popup.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/popup.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=popup
  Direct edits here will be lost on the next regeneration.
-->

# `us_popup` — Popup

**When to use**: trigger a modal overlay from a click on a button/icon/image, an external CSS selector, or after a delay on page load — newsletter sign-up, video player, embedded form, gallery focus.

**Avoid when**:
- the content is essential and must always be visible — popups hide it behind an action;
- you only need a tooltip — popups are heavier;
- you want auto-popups on every page-load — most users dismiss them instantly.

**Key parameters**

**Content source**

| Param | What it does |
|-------|--------------|
| `use_page_block` | If set to a `us_page_block` post ID, the popup reuses that block as content (ignores `title`/`content` below). |
| `title` | Popup heading. |
| `content` | Popup body — HTML allowed via the WP editor. Shortcodes inside work. |

**Trigger**

| Param | What it does |
|-------|--------------|
| `show_on` | What activates the popup — `btn` (default), `image`, `icon`, `selector`, `load`. |
| `btn_label` | Trigger button text. Visible when `show_on="btn"`. |
| `btn_style` | Per-site button style (Theme Options → Buttons). |
| `btn_icon` | Trigger icon (`set|name`). Visible when `show_on="btn"` or `show_on="icon"`. |
| `btn_iconpos` | `left` / `right`. |
| `image` | Media ID for an image-trigger. Visible when `show_on="image"`. |
| `trigger_selector` | CSS selector of an external element that opens the popup. Visible when `show_on="selector"`. |
| `show_delay` | Auto-open delay in seconds. Visible when `show_on="load"`. |
| `show_once` | `1` shows the auto-open popup once per visitor (until `days_until_next_show` passes). |

**Appearance**

| Param | What it does |
|-------|--------------|
| `layout` | `default` / `fullscreen` / `left_panel` / `right_panel` / `top_panel` / `bottom_panel`. |
| `animation` | Opening animation — `fadeIn` (default), etc. |
| `closer_pos` | Close-button position — `outside` (default) / `inside` / `none`. |
| `popup_width` | Width when `layout` is not fullscreen/top/bottom — `600px`, `40rem`. |
| `popup_padding` | Inner padding (e.g. `5%`, `2rem`). |
| `overlay_bgcolor` | Backdrop color (default `rgba(0,0,0,0.85)`). |

**Minimal example**

```text
[us_popup show_on="btn" btn_label="Subscribe"]
  [us_cform]
[/us_popup]
```

**Anti-patterns**

- Popups inside popups — modal-in-modal traps users.
- Long-form content inside a popup — anything that needs scrolling should be a separate page.
- `show_on="load"` without `show_once="1"` — visitors see the same popup on every page-load.
