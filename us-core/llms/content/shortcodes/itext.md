---
title: `us_itext` — Interactive Text
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/itext.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/itext.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=itext
  Direct edits here will be lost on the next regeneration.
-->

# `us_itext` — Interactive Text

**When to use**: animated heading where one phrase cycles through several alternatives — "We build [websites|apps|brands]". Great for hero headlines, taglines.

**Avoid when**:
- the headline must be static for SEO ranking — animated text still indexes, but reads strangely in metadata;
- you need plain styled prose — `[us_text]`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `texts` | The phrase set. **Each alternate goes on its own line** (literal newlines inside the param value). The first line is shown first; the rest are cycled. |
| `tag` | Wrapping HTML tag — `h2` (default), `h1`/`h3`–`h6`, `div`, `p`, `span`. |
| `align` | `none` / `left` / `center` / `right`. Default `center`. |
| `animation_type` | Cycling animation — `fadeIn` (default), `zoomIn`, `slide`, `rotate`, `blur`, `reveal`, `zoomInChars`, `typingChars`. |
| `duration` | Single animation duration (e.g. `0.3s`). |
| `delay` | Pause between cycles (e.g. `5s`). |
| `disable_part_animation` | `1` keeps the whole line static (the multi-line `texts` becomes a single visible block). Default `0`. |
| `dynamic_bold` | `1` renders the changing word in bold. Only when `disable_part_animation="0"`. |
| `dynamic_color` | Color (HEX/RGBA/palette var) for the changing word. Only when `disable_part_animation="0"`. |

**Minimal example**

```text
[us_itext texts="We build websites
We build apps
We build brands" tag="h1" animation_type="typingChars"]
```

**Anti-patterns**

- 10+ alternates — visitors get dizzy before the message lands.
- Animating critical legal/marketing lines — risk of being mis-screen-shotted mid-cycle.
