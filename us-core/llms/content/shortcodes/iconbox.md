---
title: `us_iconbox` — Icon Box
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/iconbox.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/iconbox.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=iconbox
  Direct edits here will be lost on the next regeneration.
-->

# `us_iconbox` — Icon Box

**When to use**: a feature / benefit / service card with an icon (or small image), a title, and an optional rich-text description. Typical content for "Features" or "How it works" sections.

**Avoid when**:
- the feature is just text without a visual — use `[us_text]`;
- you need an interactive flip card — use `[us_flipbox]`;
- the icon is the whole CTA — use `[us_btn]` with `icon=...`;
- you need a full pricing/comparison card — `[us_pricing]`.

**Key parameters**

**Content**

| Param | What it does |
|-------|--------------|
| `icon` | Required when there's no `img`. Format `set|name` (e.g. `fas|bolt`, `far|heart`). Default `fas|star`. |
| `img` | Media library ID — used **instead of** `icon` to render a small image where the icon would go. |
| `title` | The card headline. Supports dynamic values. |
| `title_tag` | HTML tag for the title — `h4` (default), `h1`–`h6`, `div`, `p`, `span`. |
| `title_size` | Custom CSS font-size for the title (e.g. `1.25rem`). |
| `content` | Description. **Tip**: this is an `editor`-typed param (rich HTML allowed), so pass it as the shortcode's inner content between the opening and closing tags rather than as a quoted attribute. |
| `link` | Optional link — makes the whole card clickable. URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank","rel":"nofollow"}`. |

**Appearance**

| Param | What it does |
|-------|--------------|
| `style` | Visual style of the icon container — `default` (default, simple), `circle` (solid filled circle), `outlined` (outlined circle). |
| `color` | Icon color palette — `primary` (default, theme primary), `secondary`, `light` (theme border color), `contrast` (theme text color), `custom`. |
| `icon_color` | Custom HEX/RGBA for the icon when `color="custom"`. |
| `circle_color` | Custom HEX/RGBA for the icon's circle/background when `color="custom"`. |
| `size` | Icon size in CSS units. Default `2rem`. |
| `iconpos` | Icon position relative to the title/description — `top` (default), `left`, `right`. |
| `alignment` | Text alignment — `none` (default — left), `left`, `center`, `right`. Centred is typical when `iconpos="top"`. |

**Minimal example**

```text
[us_iconbox icon="fas|bolt" title="Fast"]Cold-start under 50ms.[/us_iconbox]
```

(The body is the rich `content` — passed as inner content of the shortcode.)

**Common combinations**

Three feature cards in a row, icon on top, centred text:

```text
[vc_row columns="3"]
  [vc_column][us_iconbox icon="fas|bolt"  title="Fast"     iconpos="top" alignment="center"]Cold-start under 50ms.[/us_iconbox][/vc_column]
  [vc_column][us_iconbox icon="fas|lock"  title="Secure"   iconpos="top" alignment="center"]End-to-end encryption.[/us_iconbox][/vc_column]
  [vc_column][us_iconbox icon="fas|heart" title="Friendly" iconpos="top" alignment="center"]Real humans on chat.[/us_iconbox][/vc_column]
[/vc_row]
```

Icon-left service card with custom colors:

```text
[us_iconbox icon="fas|truck" title="Same-day delivery" iconpos="left" alignment="left" style="circle" color="custom" icon_color="#fff" circle_color="#1a73e8"]Order before 14:00 for delivery today.[/us_iconbox]
```

**Anti-patterns**

- Using a non-existent `text` parameter for the body — the correct name is `content`, and it works best as inner content between `[us_iconbox]…[/us_iconbox]`.
- Using non-existent `style` values like `badge` or `desc` — only `default`, `circle`, `outlined` exist.
- Long paragraphs in `content` — the card should fit one screen; move long copy to a `vc_column_text` next to the iconbox.
- Different `iconpos` values across cards in the same row — visual rhythm breaks; pick one for the whole row.
- Setting both `icon` and `img` — `img` wins; pick one source up front.
