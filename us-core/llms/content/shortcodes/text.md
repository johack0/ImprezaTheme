---
title: `us_text` — Text Block
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/text.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/text.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=text
  Direct edits here will be lost on the next regeneration.
-->

# `us_text` — Text Block

**When to use**: a **single line / short string** wrapped in an HTML tag of your choice (`div`, `p`, `h1`–`h6`, `span`). Pick this for headings, sub-headings, taglines, small labels, button-adjacent text — anything that is one piece of text under one wrapping tag. The text supports `{{...}}` dynamic-value tokens (post title, ACF fields, etc.) written literally into `text=` — see element-dynamic-values for the token list.

**Avoid when**:
- you need long-form prose with multiple paragraphs, lists, embedded images or other rich HTML — use `[vc_column_text]`;
- you need to paste a third-party HTML snippet — use `[us_html]`;
- you need a clickable CTA — use `[us_btn]`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `text` | The text string — **passed as a parameter value**, not as inner content of the shortcode. Supports `{{...}}` dynamic-value tokens written literally into the value (e.g. `text="{{post.title}}"` — token list in element-dynamic-values). |
| `tag` | HTML wrapper tag — `div` (default), `p`, `h1`/`h2`/`h3`/`h4`/`h5`/`h6`, `span`, and other tags from the theme's allow-list. Use `h1`–`h6` to make the text a real heading for SEO/accessibility. |
| `link` | Wrap the text in a link. URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank","rel":"nofollow"}`. |
| `hide_with_empty_link` | `1` hides the element entirely when `link.url` is empty — useful when the text is driven by dynamic values that may be absent. |
| `icon` | Optional icon (`set|name`, e.g. `fas|chevron-right`). Available when `fit_to_width="0"`. |
| `iconpos` | Icon position — `left` (default, before text) or `right`. |
| `fit_to_width` | `1` auto-sizes the font so the text spans the full container width. Hides `icon`/`iconpos` when on. Default `0`. |
| `background_inside_text` | `1` clips a parent's background image/gradient inside the text glyphs (for the "gradient text" look). Default `0`. |

**Minimal example**

```text
[us_text text="Welcome" tag="h1"]
```

**Common combinations**

Hero stack — H1 + sub-line + CTA, vertically centered:

```text
[us_vwrapper alignment="center" inner_items_gap="1rem"]
  [us_text text="Build better, faster" tag="h1"]
  [us_text text="A workflow your team will actually use." tag="p"]
  [us_btn label="Try it" link="%7B%22url%22%3A%22%23signup%22%7D" align="center"]
[/us_vwrapper]
```

Heading with a leading icon:

```text
[us_text text="Why us" tag="h2" icon="fas|star" iconpos="left"]
```

**Anti-patterns**

- Wrapping a paragraph of HTML between `[us_text]...[/us_text]` tags — `us_text` ignores inner content, only the `text` attribute is rendered. Use `[vc_column_text]` for prose with multiple tags or paragraphs.
- Multiple `tag="h1"` on the same page — keep one `<h1>` per page for SEO. Use `h2`/`h3` for subsequent headings.
- Stuffing many sentences into one `text=` string just to avoid `vc_column_text` — long values become unwieldy to edit and you lose paragraph semantics.
