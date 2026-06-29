---
title: `vc_column_text` — Rich Text (WPBakery)
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/vc_column_text.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/vc_column_text.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=vc_column_text
  Direct edits here will be lost on the next regeneration.
-->

# `vc_column_text` — Rich Text (WPBakery)

**When to use**: a block of body copy edited via the WordPress classic editor — supports lists, headings, links, inline images via standard HTML. The natural pick for long-form prose, blog-style intros, terms-of-service text. Also exposes a "Show More" reveal for long content and a `background_inside_text` flag for gradient-text effects.

**Avoid when**:
- you only need a single styled paragraph or a heading — `[us_text]` is leaner and supports `{{...}}` dynamic-value tokens;
- you need HTML pasted verbatim without WP filtering — use `[us_html]`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `content` | The body of the block. Type `editor` (rich HTML) — pass it as the shortcode's **inner content** between the opening and closing tags, **not** as a quoted attribute. |
| `background_inside_text` | `1` clips the parent row/column background image (or gradient) **inside** the text glyphs — produces the "gradient text" effect. Default `0`. |

**"Show More" reveal** (optional)

| Param | What it does |
|-------|--------------|
| `show_more_toggle` | `1` collapses the content to a fixed height and adds a "Show More" link. Useful for long Terms / FAQ answers / changelogs that should not dominate the page on first view. Default `0`. |
| `show_more_toggle_height` | Visible height before the reveal. Default `200px`. |
| `show_more_toggle_text_more` | Link label while collapsed. Default `Show More`. |
| `show_more_toggle_text_less` | Link label while expanded. Default `Show Less`. |
| `show_more_toggle_alignment` | Position of the reveal link — `none` (default — left), `left`, `center`, `right`. |

**Minimal example**

```text
[vc_column_text]
<h2>About</h2>
<p>We make software for designers.</p>
<ul><li>Fast</li><li>Reliable</li></ul>
[/vc_column_text]
```

(The body is the `content` — passed as inner content.)

**Common combinations**

Collapsible terms-of-service block with centred "Show More" link:

```text
[vc_column_text show_more_toggle="1" show_more_toggle_height="180px" show_more_toggle_alignment="center"]
<h3>Terms of service</h3>
<p>By using this site you agree to…</p>
<p>Section 2. Liability…</p>
<p>Section 3. Refunds…</p>
[/vc_column_text]
```

Gradient-text effect — the row's background image is clipped inside the headline glyphs (combine with a row that has `us_bg_image_source="media"`):

```text
[vc_column_text background_inside_text="1"]
<h1 style="font-weight:800;">GRADIENT HEADLINE</h1>
[/vc_column_text]
```

**Anti-patterns**

- Using a non-existent `text` parameter — the correct name is `content`, and it works only as the shortcode's inner content (between `[vc_column_text]…[/vc_column_text]`).
- Using `vc_column_text` for a single label or one-line heading — `[us_text]` is lighter and avoids WP's `wpautop` filtering quirks.
- Setting `show_more_toggle_height` so short that the "Show More" link is more prominent than the visible content — defeats the purpose.
- Combining `background_inside_text="1"` without an actual background on the parent — the text becomes invisible (it's clipped to nothing).
- Pasting unsanitised HTML with inline `<script>` tags — use `[us_html]` if you really need raw output.
