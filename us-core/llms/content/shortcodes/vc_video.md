---
title: `vc_video` — Video Embed
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_video.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_video.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_video
  Direct edits here will be lost on the next regeneration.
-->

# `vc_video` — Video Embed

**When to use**: embed a YouTube/Vimeo/self-hosted video via its URL. Wrapped responsively by the theme. Use the **overlay** feature for performance — it shows a thumbnail with a play button and only loads the real video iframe after the user clicks.

**Avoid when**:
- you want a video as a row background — `vc_row.us_bg_video` is purpose-built for that;
- you only need a poster image without playback — `[us_image]`.

**Key parameters**

**Source**

| Param | What it does |
|-------|--------------|
| `link` | Full URL of the video — YouTube, Vimeo, or a direct file (mp4/webm/ogg). Required. Supports dynamic values. |

**Playback**

| Param | What it does |
|-------|--------------|
| `autoplay` | `1` autoplays the video. Most browsers require `muted="1"` for autoplay to actually run. Default `0`. |
| `muted` | `1` starts muted. Default `0`. |
| `loop` | `1` loops indefinitely. Default `0`. |
| `controls` | `1` shows player controls (default). Set `0` to hide them — visitors won't be able to pause/seek, so use sparingly. |
| `hide_video_title` | `1` hides the Vimeo title overlay (only honoured when the Vimeo owner has allowed it). |

**Aspect ratio & alignment**

| Param | What it does |
|-------|--------------|
| `align` | `none` (default), `left`, `center`, `right`. |

**Aspect ratio**: the player defaults to **16:9**. To change it, set the Design `css` attribute's `aspect-ratio` property (a CSS ratio such as `4/3`, `1`, `21/9`, `9/16`) — see element-design.md and the `css="…"` spec in composition-rules §3.3. There is no dedicated ratio attribute anymore; the legacy `ratio` / `ratio_width` / `ratio_height` are read only as a back-compat fallback.

**Lightweight overlay** (recommended for pages with multiple videos)

| Param | What it does |
|-------|--------------|
| `overlay_image` | Media library ID of a poster image. When set, the real iframe is **lazy-loaded** — only the poster is rendered on page-load; the actual video loads on click. This is the single biggest perf win for pages with embedded video. |
| `overlay_icon` | `1` (default) shows a play icon over the poster. Only relevant when `overlay_image` is set. |
| `overlay_icon_bg_color` | Background color of the play icon (HEX/RGBA). Default `rgba(0,0,0,0.5)`. |
| `overlay_icon_text_color` | Icon glyph color. Default `#fff`. |
| `overlay_icon_size` | Icon size (CSS units). Default `1.5rem`. |

**Minimal example**

```text
[vc_video link="https://www.youtube.com/watch?v=dQw4w9WgXcQ" align="center"]
```

**Common combinations**

Hero video with overlay (lazy-loaded, autoplay-on-click would happen anyway). 16:9 is the default, so no aspect-ratio override is needed:

```text
[vc_video link="https://www.youtube.com/watch?v=dQw4w9WgXcQ" overlay_image="123"]
```

Silent looping background-like video (decorative), forced to 1:1 via the Design `css` (decodes to `{"default":{"aspect-ratio":"1"}}`):

```text
[vc_video link="https://example.com/intro.mp4" autoplay="1" muted="1" loop="1" controls="0" css="%7B%22default%22%3A%7B%22aspect-ratio%22%3A%221%22%7D%7D"]
```

**Anti-patterns**

- Using the legacy `el_aspect` / `ratio` / `ratio_width` / `ratio_height` parameters — aspect ratio is now the Design `aspect-ratio` property (the player defaults to 16:9). These old attributes are read only as a back-compat fallback.
- Using `mute` — the correct name is `muted` (with the `d`).
- Autoplay without `muted="1"` — browsers block it; the video sits silent and doesn't start.
- Setting `controls="0"` on a non-decorative video — visitors can't pause; they'll bounce.
- Embedding 5+ videos on one page **without** `overlay_image` — every iframe loads up-front and pageweight balloons. Always use the overlay for any page with more than one video.
