---
title: Composition Rules for post_content
audience: content
source: us-core 9.0
generated: 2026-06-12 (manual)
---

# Composition Rules for `post_content`

> Cross-cutting rules for assembling valid `post_content` from UpSolution shortcodes. Per-shortcode "when to use / avoid" notes live in the per-shortcode files under [`shortcodes/`](shortcodes/), with the index at [`shortcodes.md`](shortcodes.md); ready-made blocks live in [`sections.md`](sections.md). This document covers the structural and syntactic invariants that span shortcodes.
>
> Conventions:
>
> - Code blocks use the `text` language tag — they are raw shortcode markup, **not** PHP or HTML.
> - "Leaf" = shortcode that cannot have children (e.g. `us_btn`, `us_text`, `us_image`). "Container" = shortcode that wraps children (e.g. `vc_row`, `vc_column`, `us_hwrapper`).
> - Tag prefixes are kept exactly as they must appear in `post_content`: `us_*` for UpSolution-native, `vc_*` for WPBakery-compatible tags. Do not invent variants.

## HARD RULES — read before composing markup

These six invariants are violated most often and fail in ways your self-check pass won't catch — usually no PHP notice and no console warning; the page still loads, but the affected element silently falls back to defaults, drops its content, or (the structural one, #6) mangles everything below it. The full spec for each lives further down in this file; this card is a checklist so they cannot be skipped:

1. **`icon="set|name"` — never invent a name.** Every `icon=` value MUST come from §3.6's known-safe list OR a fresh lookup on the FontAwesome **5** search URL for that set. The bundled font is FA5, not FA6/7 — FA6 names like `circle-info`, `xmark`, `gear`, `house`, `magnifying-glass` render an empty `<i>` box. See §3.6.
2. **Group JSON attributes (`items=`, `responsive=`, `tax_query=`, …) MUST be URL-encoded in double quotes.** Single-quoted raw JSON gets mangled by `wptexturize` and the shortcode silently falls back to defaults. See §3.5.
3. **CSS gradients go into `background-color`, not `background-image`.** Only `linear-gradient(…)` is supported. `background-image` is a media-ID / `url(…)` field — a `gradient(…)` value there is invalid and ignored. See §3.3.
4. **Inline tags inside attributes are written literally — never entity-encoded.** Allowlisted inline tags (`<br>`, `<strong>`, `<sup>`, …) must appear as raw `<…>`. The entity-encoded form `text="…&lt;br&gt;…"` is **not** decoded, so the page shows the visible characters `<br>` instead of a line break. Write `text="Spotless Homes,<br>Happy Families"`, not `&lt;br&gt;`. See §4.1.
5. **Never invent a parameter, value, or group-attribute key — read the shortcode's doc first.** Open the shortcode's `shortcodes/<config-id>` record before you compose it and use only the attributes it documents. Attribute names guessed "from logic" do not exist; the parser drops them and the element renders with defaults. This bites hardest on group attributes (`items=`, …), whose object keys are **element-specific** — `us_socials` items use `type`, not `icon` — so copy the doc's shape rather than reasoning it out. See §3.5.
6. **A container may never hold another container with the same tag name.** WordPress's shortcode parser is not recursive per tag, so a tag nested inside itself — at any depth in its subtree — mis-pairs its closing tag and mangles the block from that point down (front end breaks, builder can no longer edit it). The trap is the inner grid: there is exactly **one** level of it, so `vc_row_inner > vc_column_inner > vc_row_inner` is illegal. A `vc_column_inner` holds leaves and wrappers, never a second `vc_row_inner`. See §2.1.

---

## 1. Root structure

`post_content` is a flat string of top-level shortcodes. The **only** shortcode that may appear at the top level is `vc_row`. Every other shortcode must be nested inside a `vc_row → vc_column` chain.

Valid root:

```text
[vc_row][vc_column][us_text text="Hi"][/vc_column][/vc_row]
[vc_row][vc_column][us_btn label="Click"][/vc_column][/vc_row]
```

Invalid root (renders, but not as a section — alignment, backgrounds, responsive layout all break):

```text
[us_text text="Hi"]
[us_btn label="Click"]
```

A page is a **sequence of `vc_row` blocks**, one per visual section. There is no wrapping element above `vc_row` — concatenate rows directly.

---

## 2. Nesting graph

The builder enforces these parent/child constraints. Violating them produces a block that the builder cannot edit and that may render incorrectly.

| Shortcode | Allowed parents | Allowed children |
|-----------|-----------------|------------------|
| `vc_row` | `post_content` root only | `vc_column` only |
| `vc_column` | `vc_row` only | leaves, `vc_row_inner`, `us_hwrapper`, `us_vwrapper`, `vc_tta_tabs`, `vc_tta_tour`, `vc_tta_accordion` |
| `vc_row_inner` | `vc_column`, `vc_tta_section` | `vc_column_inner` only |
| `vc_column_inner` | `vc_row_inner` only | same as `vc_column` **but never `vc_row_inner`** — one inner-grid level only (§2.1) |
| `us_hwrapper`, `us_vwrapper` | any column-like container (`vc_column`, `vc_column_inner`, `vc_tta_section`) | leaves only (no nested containers, no other wrappers) |
| `vc_tta_tabs`, `vc_tta_tour`, `vc_tta_accordion` | `vc_column`, `vc_column_inner` | `vc_tta_section` only |
| `vc_tta_section` | `vc_tta_tabs`, `vc_tta_tour`, `vc_tta_accordion` only | leaves, `vc_row_inner`, `us_hwrapper`, `us_vwrapper` |
| leaves (`us_btn`, `us_text`, `us_iconbox`, …) | any container | none |

Practical consequences:

- **Need a nested grid inside a column?** Use `vc_row_inner` + `vc_column_inner`. Do not nest `vc_row` inside `vc_column` — `vc_row` is root-only and the builder will refuse to edit it. This is the **only** level of inner grid: do not place a second `vc_row_inner` inside the `vc_column_inner` (same-tag nesting — §2.1).
- **Need to group a few inline elements with a gap?** Use `us_hwrapper` (horizontal) or `us_vwrapper` (vertical) inside the column. These accept leaves only.
- **Need tabs or an accordion?** A `vc_tta_tabs|tour|accordion` block contains one `vc_tta_section` per tab/panel. `vc_tta_section` cannot live anywhere else — placing it directly under `vc_column` produces an orphan.

### 2.1 Same-tag containers cannot nest (WordPress parser limit)

WordPress's shortcode parser (`do_shortcode`) is **not recursive for a given tag**: when it sees `[vc_row_inner]…[/vc_row_inner]` it matches the **first** closing tag it finds. So when a container holds another container **with the same tag name** — at any depth in its subtree, even separated by other tags — the inner closing tag terminates the outer element and everything from there down is parsed wrong. The result is a mangled block on the front end that the builder can no longer open.

Most tags are already safe because the graph above never lists a tag as its own descendant (`vc_row` is root-only; `vc_column`, `us_hwrapper`, `vc_tta_section` don't list themselves). **The one path that bites is the inner grid** — there is exactly one level of it:

```text
[vc_row_inner]
  [vc_column_inner]
    [vc_row_inner]            ← vc_row_inner inside vc_row_inner — BREAKS, do not do this
      [vc_column_inner]…[/vc_column_inner]
    [/vc_row_inner]
  [/vc_column_inner]
[/vc_row_inner]
```

When a `vc_column_inner` looks like it needs its own multi-column grid, do one of these instead:

- **A few elements side by side with a gap** → put `us_hwrapper` (horizontal) or `us_vwrapper` (vertical) inside the `vc_column_inner`. These are leaf-only wrappers, not grids, but cover most "two things next to each other" cases.
- **A genuine second grid level** → you've hit WordPress's ceiling. Flatten it: give the **outer** `vc_row_inner` all the columns you need, or place two sibling `vc_row_inner` blocks one after another inside the same `vc_column`. Do not reach for a nested `vc_row_inner`.

The same rule forbids `us_hwrapper` inside `us_hwrapper`, `vc_column` inside `vc_column`, and so on — but the graph already blocks those; the inner grid is the only hole it used to leave open.

### How a row's column layout is determined

A row's **desktop** column count and proportions are **derived from the `width` attribute on each child `vc_column`**, not from any attribute on the row itself. At render time us-core scans the row's children, collects their `width` values, and reduces them to the layout descriptor — so a row with three bare `[vc_column]` children renders as a **single full-width column**, no matter what `columns="3"` you set on the row.

Always put `width="<num>/<den>"` on every `vc_column` when the row has more than one column. The same rule applies to `vc_row_inner` → `vc_column_inner`.

| Intent | Markup |
|--------|--------|
| 2 equal columns | `[vc_column width="1/2"]…[/vc_column][vc_column width="1/2"]…[/vc_column]` |
| 3 equal columns | three `vc_column` with `width="1/3"` each |
| 4 equal columns | four `vc_column` with `width="1/4"` each |
| 1/3 + 2/3 | `[vc_column width="1/3"]…[/vc_column][vc_column width="2/3"]…[/vc_column]` |
| 2/5 + 3/5 | `[vc_column width="2/5"]…[/vc_column][vc_column width="3/5"]…[/vc_column]` |
| 1/4 + 1/2 + 1/4 | three columns with widths `1/4`, `1/2`, `1/4` |

Valid `width` values: `1/1`, `1/2`, `1/3`, `2/3`, `1/4`, `3/4`, `1/5`, `2/5`, `3/5`, `4/5`, `1/6`, `5/6`. Any other combination is treated as a custom CSS-grid layout (each width becomes a `1fr`-scaled track) — usually not what you want.

**Tablet and mobile column layouts** still come from row-level attributes (`laptops_columns`, `tablets_columns`, `mobiles_columns`) because there are no narrower-viewport equivalents on individual columns. See §7 for those.

---

## 3. Attribute encoding

Shortcode attributes follow the WordPress shortcode syntax: `name="value"`. A few attribute *families* have extra encoding on top of that.

### 3.1 `link="..."` — link picker

**Format — URL-encoded JSON in double quotes** (same encoding as `css=`, see §3.3):

```text
link="%7B%22url%22%3A%22https%3A%2F%2Fexample.com%2Fpath%22%2C%22target%22%3A%22_blank%22%2C%22rel%22%3A%22nofollow%22%7D"
```

decodes to:

```json
{"url":"https://example.com/path","target":"_blank","rel":"nofollow"}
```

Keys (all optional except `url` for static links): `url`, `target` (`_blank`), `rel` (`nofollow`), `title` (tooltip text), `onclick` (inline JS — pair it with `"url":"#"`).

Examples:

```text
[us_btn label="Get started" link="%7B%22url%22%3A%22%23signup%22%7D"]
[us_btn label="Docs" link="%7B%22url%22%3A%22https%3A%2F%2Fdocs.example.com%22%2C%22target%22%3A%22_blank%22%2C%22rel%22%3A%22nofollow%22%7D"]
[us_btn label="Email" link="%7B%22url%22%3A%22mailto%3Asupport%40example.com%22%7D"]
```

(decoded: `{"url":"#signup"}`, `{"url":"https://docs.example.com","target":"_blank","rel":"nofollow"}`, `{"url":"mailto:support@example.com"}`)

**Legacy pipe format — recognize, never emit.** Content saved before us-core 8.16 (and old examples) may carry `link="url:<urlencoded>|title:…|target:_blank|rel:nofollow"`. At render time only some elements convert it (`us_btn`, `us_iconbox`, `vc_column`, `us_ibanner`, `us_cta`, `us_person`, `us_flipbox` run a legacy-attribute converter); on the rest — including `us_text`, `us_image`, `us_hwrapper`, `us_vwrapper` — a pipe-format link is **silently dropped** and the element renders without its link. When you edit content that contains a pipe-format link, re-encode it as URL-encoded JSON.

**Dynamic targets** (post permalink, author archive, popup-image, "use a custom-field value as the URL", etc.) are picked from a per-shortcode enum and use the same URL-encoded JSON attribute with a `type` key instead of `url` (`{"type":"post"}`, `{"type":"popup_image"}`, `{"type":"custom_field|us_tile_link"}`, …). See [Dynamic Values → Link enums](element-dynamic-values.md#link-enums) for the full enum list per `link` parameter, and [Text tokens](element-dynamic-values.md#text-tokens) for `{{…}}` substitution in text / textarea / image-id attributes.

### 3.2 Strings with spaces, quotes, newlines

- Always quote values: `text="Hello world"` — bare `text=Hello world` does not parse.
- Inside a quoted value, **literal newlines** are allowed and meaningful — `us_itext`, for example, uses them to separate animated lines:

  ```text
  [us_itext texts="Build it
  Ship it
  Love it"]
  ```

- HTML entities (`&quot;`, `&amp;`) are not decoded inside attribute values. Use a different quoting strategy or move the content into `vc_column_text` (see §4).
- Backticks, percent signs, and curly braces have no special meaning in attribute values and need no escaping.

### 3.3 `css="…"` — per-element CSS rules

The `css="…"` attribute is available on **every** shortcode and holds a URL-encoded JSON document describing breakpoint-specific CSS overrides for that one element. It is the standard way to change colors, sizes, paddings, borders, shadows, transforms etc. on a specific element without touching theme CSS.

**Format — URL-encoded JSON, in double quotes:**

```text
css="%7B%22default%22%3A%7B%22color%22%3A%22%23ff0000%22%2C%22font-size%22%3A%2240px%22%7D%2C%22mobiles%22%3A%7B%22font-size%22%3A%2224px%22%7D%7D"
```

decodes to:

```json
{
  "default": { "color": "#ff0000", "font-size": "40px" },
  "mobiles": { "font-size": "24px" }
}
```

**Important — single-quoted raw JSON silently breaks every JSON-bearing attribute.** §3.4 and §3.5 reference back here:

1. **`wptexturize`.** WordPress treats the opening apostrophe as a typographic quote and converts every straight quote that follows on the same shortcode tag (inner `"` of each JSON key/value pair, closing apostrophe of the attribute, **and** quotes of every attribute after it) to `&#8217;` / `&#8221;`. The shortcode regex then drops those attributes silently — the element falls back to defaults. Symptoms vary: filters render with the wrong taxonomy, carousels collapse to default `items="3"`, classes contain literal `&#8217;` / `&#8221;` entities.
2. **`css=`-specific.** Even bypassing `wptexturize`, the post-save extractor that compiles the stylesheet only matches `css="…"` with double quotes — the CSS rule would never be emitted.

Always use the URL-encoded double-quoted form.

**JSON shape:**

- Top-level keys are breakpoint names: `default` (desktop), `laptops`, `tablets`, `mobiles`. Same semantics as §3.4 — all optional, missing keys leave the property at its CSS default.
- Each breakpoint's value is an object mapping CSS property names (kebab-case) to value strings.
- Property names **must use a hyphen** (`font-size`, not `font_size`). The set of allowed properties is fixed — anything outside the whitelist below is dropped silently.

**URL-encoding cheatsheet** (the characters that need encoding inside the attribute value):

| Char | Encoded |
|------|---------|
| `{`  | `%7B` |
| `}`  | `%7D` |
| `"`  | `%22` |
| `:`  | `%3A` |
| `,`  | `%2C` |
| `#`  | `%23` |
| ` `  (space) | `%20` |
| `/`  | `%2F` |
| `(`  | `%28` |
| `)`  | `%29` |

JSON syntax characters (`{`, `}`, `"`, `:`, `,`) appear in every value; numeric values and ASCII identifiers pass through unchanged. The decoder is `rawurldecode`, so `+` is **not** treated as a space — use `%20` for literal spaces (rare; mostly inside `transform`, `clip-path`, multi-value `box-shadow-*` etc.).

**Recipe — author in two steps:**

1. Write the rule as readable JSON first:
   ```json
   {"default":{"padding-top":"40px","padding-bottom":"40px","background-color":"_content_bg_alt"}}
   ```
2. URL-encode the entire string using the table above, then drop it into `css="…"`:
   ```text
   css="%7B%22default%22%3A%7B%22padding-top%22%3A%2240px%22%2C%22padding-bottom%22%3A%2240px%22%2C%22background-color%22%3A%22_content_bg_alt%22%7D%7D"
   ```
   One-liners: PowerShell `[System.Uri]::EscapeDataString($rawJson)`, PHP `rawurlencode($rawJson)`, JS `encodeURIComponent(rawJson)`.

**Whitelist of CSS properties** (everything outside this list is silently dropped):

- **Text**: `color`, `text-align`, `font-size`, `line-height`, `letter-spacing`, `font-family`, `font-weight`, `text-transform`, `text-wrap`, `font-style`.
- **Background**: `background-color`, `background-image`, `background-position`, `background-size`, `background-blend-mode`, `background-repeat`, `background-attachment`, `backdrop-filter`.
- **Sizes**: `width`, `height`, `max-width`, `max-height`, `min-width`, `min-height`, `aspect-ratio`.
- **Spacing**: `margin-top`, `margin-right`, `margin-bottom`, `margin-left`, `padding-top`, `padding-right`, `padding-bottom`, `padding-left`. (Use the four sub-properties; the compiler auto-collapses to `padding`/`margin` shorthand when all four are equal.)
- **Border**: `border-radius`, `border-style`, `border-top-width`, `border-right-width`, `border-bottom-width`, `border-left-width`, `border-color`.
- **Position**: `position`, `top`, `right`, `bottom`, `left`, `z-index`.
- **Text shadow** (set all four — partial values get default-filled): `text-shadow-h-offset`, `text-shadow-v-offset`, `text-shadow-blur`, `text-shadow-color`.
- **Box shadow** (set all five — partial values get default-filled): `box-shadow-h-offset`, `box-shadow-v-offset`, `box-shadow-blur`, `box-shadow-spread`, `box-shadow-color`.
- **Overflow / clip**: `overflow`, `clip-path`.
- **Transformation**: `transform`, `transform-origin`.
- **Entry animation**: `animation-name`, `animation-delay`.

**Special-case values:**

- `background-image` takes a WordPress **media ID** (e.g. `"background-image": "123"`), not a `url(…)` string. The compiler resolves it to `url(<media URL>)` at render time. If you must inline a URL, the value can also be `"url(https://…)"` — but media ID is preferred. **Do NOT put CSS gradients here** — that channel is `background-color` (see next bullet).
- `background-color` is the **only** color field that accepts a `linear-gradient(…)` value (alongside solid colors and palette tokens): `"background-color":"linear-gradient(180deg,#000,rgba(0,0,0,0))"`, `"background-color":"linear-gradient(90deg,#f06 0%,#06f 100%)"`. Only `linear-gradient` is supported — `radial-gradient` / `conic-gradient` are not wired up. The other color properties (`color`, `border-color`, `text-shadow-color`, `box-shadow-color`) reject gradients silently — to put a gradient anywhere other than the element's own background, layer it on a sibling/parent. URL-encode parens and commas inside the gradient value as `%28`, `%29`, `%2C`.
- `font-family` accepts either a font name from Theme Options → Typography or one of the special values `body`, `h1`, `h2`, `h3`, `h4`, `h5`, `h6`, which resolve to the theme's `--<tag>-font-family` CSS variables — use those when you want the element to inherit the typography role of a tag.
- Color values accept palette tokens (e.g. `_content_primary`, `_content_bg_alt`) in addition to literal `#hex` and `rgba()` — see §5 for the token list. Prefer tokens.
- `text-shadow-*` / `box-shadow-*` are set as separate sub-properties. The compiler joins them into the final `text-shadow` / `box-shadow` rule. Missing sub-values get sensible defaults (`color` → `currentColor`, offsets/blur/spread → `0`), so setting only `box-shadow-color` and `box-shadow-blur` is fine.
- `transform` and `clip-path` accept raw CSS function syntax: `"transform": "translateY(-50%) rotate(5deg)"`, `"clip-path": "polygon(25% 0%, 100% 0%, 75% 100%, 0% 100%)"`. Inner parentheses and commas inside the value must be URL-encoded as `%28`, `%29`, `%2C`.

**How it renders:**

Each unique `css="…"` value gets hashed into a class `us_custom_<crc32>` and the element receives that class. The corresponding CSS rule is compiled and stored on the post; on save WP injects it as a `<style>` tag. Two elements with byte-identical `css="…"` share the same class and the same compiled rule — useful for repeated cards, harmless either way.

**Examples:**

Red centered H2, smaller on mobile:

```text
[us_text text="Important notice" tag="h2" css="%7B%22default%22%3A%7B%22color%22%3A%22%23ff0000%22%2C%22text-align%22%3A%22center%22%2C%22font-size%22%3A%2240px%22%7D%2C%22mobiles%22%3A%7B%22font-size%22%3A%2224px%22%7D%7D"]
```

Column with extra vertical padding and an alt-palette background, less padding on mobile:

```text
[vc_column width="1/2" css="%7B%22default%22%3A%7B%22padding-top%22%3A%2260px%22%2C%22padding-bottom%22%3A%2260px%22%2C%22background-color%22%3A%22_content_bg_alt%22%7D%2C%22mobiles%22%3A%7B%22padding-top%22%3A%2230px%22%2C%22padding-bottom%22%3A%2230px%22%7D%7D"]
```

Image with rounded corners and a soft drop shadow:

```text
[us_image image="123" css="%7B%22default%22%3A%7B%22border-radius%22%3A%2212px%22%2C%22box-shadow-v-offset%22%3A%228px%22%2C%22box-shadow-blur%22%3A%2224px%22%2C%22box-shadow-color%22%3A%22rgba%280%2C0%2C0%2C0.15%29%22%7D%7D"]
```

**Anti-patterns:**

- **Single-quoted `css='{"…"}'`** — the class is added but no CSS is emitted; the element looks unchanged. Always double-quote and URL-encode.
- **Using a property name outside the whitelist** (e.g. `gap`, `display`, `flex-direction`) — silently dropped. If the layout requires it, restructure with the appropriate container shortcode (`us_hwrapper` for inline gap, `vc_row`/`vc_row_inner` columns for grids) instead.
- **Using underscores in property names** (`font_size`, `background_color`) — silently dropped. Use kebab-case.
- **Stripping `css="…"` from a pre-built section** to "clean it up" — see §6.

### 3.4 Responsive single-value attributes (`columns_gap`, sizes, paddings)

A class of size-like attributes (`columns_gap` on rows, font sizes on text, gaps on wrappers, several `us_bg_*` props) can vary per breakpoint. The value lives in a **single attribute** with one of two equivalent shapes — there is **no** separate `tablets_<param>` / `mobiles_<param>` form for them.

**Form A — scalar (use when one value works at every viewport):**

```text
columns_gap="2rem"
```

Applies to every breakpoint. When columns stack on mobile, the same scalar becomes the vertical gap.

**Form B — per-breakpoint JSON, URL-encoded inside double quotes:**

```text
columns_gap="%7B%22default%22%3A%226rem%22%2C%22mobiles%22%3A%221rem%22%7D"
```

decodes to:

```json
{"default":"6rem","mobiles":"1rem"}
```

Use **only** this shape. Single-quoted raw JSON (`columns_gap='{"default":"6rem"}'`) is mangled by `wptexturize` — see §3.3. Applies identically regardless of how short the JSON is.

**JSON shape:**

- Keys are breakpoint names. Allowed keys, in render order:
  - `default` — desktop (≥1381px by default)
  - `laptops` — 1025–1380px
  - `tablets` — 601–1024px
  - `mobiles` — ≤600px
- All keys are optional. A missing key falls back to the param's CSS default (which is whatever us-core / Theme Options set globally for that property, **not** the value of the next-wider breakpoint).
- Values are CSS unit strings — `3rem`, `40px`, `100%`, `0`. Use the same units across breakpoints when you can; mixing rem / px works but is harder to reason about.

**Common patterns** — author the JSON readably first, then URL-encode the whole string and paste into `<attr>="…"`:

```json
{"default":"4rem","mobiles":"1.5rem"}                  // columns_gap: wide on desktop, tight on mobile
{"mobiles":"0rem"}                                     // columns_gap: only override mobile; desktop keeps the 3rem default
{"default":"6rem","tablets":"3rem","mobiles":"1rem"}   // columns_gap: three steps
{"default":"4rem","tablets":"3rem","mobiles":"2rem"}   // size= on us_text: responsive font size
```

**Encoding** — same character table and one-liners as §3.3.

**Authoring tips:**

- Set `default` whenever you set any other breakpoint — otherwise the desktop value falls back to a CSS default that may not be what you want.
- Mobile gaps generally want to be 30–50% of desktop gaps. A `6rem` desktop gap looks correct, but the same `6rem` between stacked mobile blocks creates huge dead space.
- Other row params accept the same JSON shape: `gap`, `content_placement`, several `us_bg_*` sizes. Per-shortcode `Key parameters` notes mark which ones are responsive.

The few row attributes that genuinely accept per-breakpoint overrides as **separate** attributes (`laptops_columns`, `tablets_columns`, `mobiles_columns`) are listed in §7; that pattern is specific to column-count and does not generalise to any other param.

### 3.5 Group JSON attributes (`items`, `orderby_items`, `responsive`, `tax_query`, `meta_query`, …)

A second class of attributes takes a **JSON array or array-of-objects** describing a *group* of sub-elements rather than a single value. Examples across the in-scope shortcode set:

| Shortcode(s) | Attribute(s) |
|--------------|--------------|
| `us_list_filter` | `items` (filter rows) |
| `us_list_order` | `orderby_items` (sort options) |
| `us_post_list` / `us_product_list` / their `_carousel` siblings | `tax_query`, `meta_query` |
| `us_post_carousel` / `us_product_carousel` / `us_content_carousel` / `us_term_carousel` / `us_user_carousel` | `responsive` (per-breakpoint overrides) |
| `us_socials` | `items` (social entries) |
| `us_cform` | `items` (form fields) |

**Required shape — URL-encoded JSON inside double quotes:**

```text
items="%5B%7B%22source%22%3A%22us_portfolio_category%22%2C%22selection_type%22%3A%22radio%22%2C%22values_as_btn%22%3A%221%22%2C%22values_btn_cols%22%3A%22auto%22%7D%5D"
```

decodes to:

```json
[{"source":"us_portfolio_category","selection_type":"radio","values_as_btn":"1","values_btn_cols":"auto"}]
```

**Do not use single-quoted raw JSON** — `items='[{"source":"…"}]'` looks readable but is mangled by `wptexturize` (see §3.3 for the full mechanism). Affects every group attribute regardless of nesting context. The section-template snapshots in [`sections/`](sections/) use the URL-encoded form exclusively (e.g. 23 `us_socials items=` occurrences across `co.md`, `ab.md`, `fo.md`, `po.md`; zero single-quoted) — that's the de-facto correct form across the codebase.

**Encoding** — same character table and one-liners as §3.3, plus `[` → `%5B`, `]` → `%5D` for the JSON array brackets.

**The object keys are element-specific — read the doc, never guess them.** Each shortcode defines its own key set inside the array, and they do not transfer between elements. `us_socials` items use `{"type":"<platform>","url":…}` — the platform goes in `type` (the icon is derived from it), and there is **no** `icon` key unless `type` is `"custom"`. `us_cform` items use form-field keys; `us_list_filter` items use `{"source","selection_type",…}`. Putting a key where the element expects a different one (e.g. `icon` instead of `type`) makes the shortcode drop that entry and fall back to its defaults — silently. Always pull the shortcode's `shortcodes/<config-id>` record and copy its example. Some sub-values are themselves encoded — a `us_socials` item's `url` is a `link` value carrying its own URL-encoded `{"url":…}` object (so it ends up double-encoded inside `items=`) — which is another reason to start from a shipped section template that already has the element and swap the values, rather than building the string by hand.

**`us_list_filter` gotcha:** when an `items` entry has `"values_as_btn":"1"`, always set `"values_btn_cols":"auto"` — the auto value lays buttons out in a natural flex row. A numeric value (`"4"`) produces a fixed grid and leaves empty cells when item count doesn't match; omitting it triggers a PHP Notice in `WP_DEBUG_DISPLAY` mode.

### 3.6 `icon="…"` — icon picker

Invalid icon names render an **empty `<i>` box** with no warning, no PHP notice — your self-check pass will not catch this. The rules below are NOT advisory.

Shortcodes that carry an `icon` attribute: `us_btn` (with `iconpos`), `us_iconbox`, `us_message`, `us_text` (with `iconpos`), `us_cform` field items, `us_socials` items, `us_search`, `us_cart`, `us_breadcrumbs` (`separator_type="icon"`), `us_dropdown` items, `vc_tta_section`, `us_post_author` / `us_post_date` / `us_post_taxonomy` / `us_post_comments` / `us_post_views` / `us_post_custom_field`, `us_flipbox` (with `*_icon_type="font"`), `us_separator` (`type="icon"`).

**Hard rules:**

1. **Do not emit an icon name from memory.** Every `set|name` pair MUST come from either the known-safe list below OR a deliberate lookup on the v5 search URL for the chosen set.
2. **Include the `<set>|` prefix.** Bare `icon="bolt"` parses but renders nothing.
3. **When in doubt, omit the `icon` attribute.** Every shortcode has a sensible default or works without one.
4. **Brand logos live ONLY under `fab|`** (`github`, `facebook`, `twitter`, `linkedin`, `instagram`, `youtube`, `whatsapp`, `telegram`, `discord`, `slack`, `figma`, `apple`, `windows`, `linux`, …). Non-brand glyphs (`envelope`, `phone`, `user`, `search`) are NOT in `fab` — they're in `fas` / `far` / `fal`.

**Format — pipe-separated `<set>|<name>` in double quotes:**

```text
icon="fas|bolt"
icon="far|envelope"
icon="fab|github"
icon="material|dashboard"
```

**Allowed `<set>` slugs** (full list lives in `plugins/us-core/config/icon-sets.php`):

| Slug | What it is | Scope | Lookup |
|------|------------|-------|--------|
| `fas` | Font Awesome **5** Solid | general UI / content / commerce | <https://fontawesome.com/v5/search?s=solid> |
| `far` | Font Awesome **5** Regular | outline variants | <https://fontawesome.com/v5/search?s=regular> |
| `fal` | Font Awesome **5** Light | thin outline (FA Pro — sparser catalogue) | <https://fontawesome.com/v5/search?s=light> |
| `fad` | Font Awesome **5** Duotone | two-tone (FA Pro) | <https://fontawesome.com/v5/search?s=duotone> |
| `fab` | Font Awesome **5** Brands | **logos only** — `github`, `apple`, `facebook`, … | <https://fontawesome.com/v5/search?s=brands> |
| `material` | Google Material Icons | **separate namespace, different names** from FA | <https://fonts.google.com/icons?selected=Material+Icons> |

**FontAwesome 5, NOT 6 or 7.** us-core ships the FA5 font files. FA6/7 noun-first names (`circle-info`, `xmark`, `gear`, `house`, `magnifying-glass`, `trash-can`, `pen-to-square`, `bars-staggered`, `arrow-up-right-from-square`) do not exist in the bundled font and render as a blank box. Common traps:

| ❌ FA6 (hallucinated) | ✅ FA5 (correct) |
|----------------------|-----------------|
| `fas|circle-info` | `fas|info-circle` |
| `fas|circle-check` | `fas|check-circle` |
| `fas|circle-question` | `fas|question-circle` |
| `fas|square-check` | `fas|check-square` |
| `fas|xmark` | `fas|times` |
| `fas|gear` | `fas|cog` |
| `fas|magnifying-glass` | `fas|search` |
| `fas|trash-can` | `fas|trash` |
| `fas|arrow-up-right-from-square` | `fas|external-link-alt` |
| `fas|bars-staggered` | `fas|stream` |
| `fas|house` | `fas|home` |
| `fas|phone` (brand) | `fab|<brand-slug>` (brands always need `fab`) |

Pattern: FA6 is noun-first, FA5 is noun-suffix — prefer the FA5 form. Don't invent plausible-sounding names (`fas|robot-head`, `fas|chart-pie-alt`, `fas|brain-circuit`, `fas|magic-wand-sparkles` all render empty). If your target isn't in the safe-list or a v5 lookup, omit the attribute.

**Known-safe icon list** (taken from shipped sections and per-shortcode examples — guaranteed to render):

*Solid (`fas|…`):* `arrow-down`, `arrow-right`, `bolt`, `border-all`, `bullhorn`, `bullseye`, `calendar-week`, `car`, `chart-line`, `check`, `check-circle`, `chess`, `chevron-down`, `chevron-right`, `code`, `cog`, `coins`, `comments`, `drafting-compass`, `envelope`, `exclamation-triangle`, `eye`, `fingerprint`, `folder`, `gem`, `graduation-cap`, `heart`, `home`, `icons`, `info-circle`, `layer-group`, `list`, `lock`, `magic`, `palette`, `paper-plane`, `pencil-ruler`, `phone`, `phone-alt`, `play`, `play-circle`, `question`, `question-circle`, `rocket`, `search`, `shield`, `shipping-fast`, `shopping-cart`, `star`, `sync`, `tachometer-alt`, `tag`, `thumbs-up`, `truck`, `user`, `users`, `user-shield`.

*Regular (`far|…`) — outline variants:* `angle-right`, `arrow-right`, `bell`, `calendar`, `calendar-check`, `clock`, `comment`, `compass`, `envelope`, `eye`, `futbol`, `gem`, `hand-point-up`, `heart`, `image`, `object-ungroup`, `thumbs-up`, `window-restore`.

*Light (`fal|…`) — Pro, narrow catalogue:* `clock`, `envelope-open`, `map-marker-alt`, `phone`.

*Brands (`fab|…`) — logos only:* `apple`, `behance`, `bitbucket`, `discord`, `dribbble`, `facebook`, `facebook-f`, `figma`, `github`, `gitlab`, `instagram`, `linkedin`, `linkedin-in`, `linux`, `medium-m`, `pinterest`, `reddit-alien`, `slack`, `snapchat-ghost`, `telegram`, `tiktok`, `tumblr`, `twitter`, `vimeo`, `vimeo-v`, `whatsapp`, `whatsapp-square`, `windows`, `wordpress-simple`, `youtube`.

*Material (`material|…`) — different naming:* `analytics`, `api`, `check`, `check-circle`, `dashboard`, `devices`, `draw`, `group`, `mail`, `paid`, `phone`, `public`, `sailing`, `savings`, `search`, `settings`, `support`.

**Anti-patterns:**

- **Inventing names** — `fas|chart-pie-alt`, `fas|robot-head`. Sounds right, does not exist. Renders empty.
- **FA6 names** — `circle-info`, `xmark`, `gear`, `house`. Use the FA5 trap-table equivalents.
- **Wrong set prefix** — `fas|github` (correct: `fab|github`); `fab|envelope` (correct: `fas|envelope`).
- **Mixing FA and Material** — `material|info-circle` is invalid (Material uses `info`); `fas|dashboard` is invalid (FA5 uses `tachometer-alt`).
- **Omitting the set prefix** — `icon="bolt"` renders nothing. Always `icon="fas|bolt"`.

---

## 4. HTML inside shortcode values

By default, shortcode attribute values are **plain text** — HTML tags inside an attribute are stripped before rendering. There are three exceptions, in increasing order of permissiveness.

### 4.1 Inline-HTML-aware attributes

A handful of label/title/text attributes accept a small allowlist of inline tags. Anything outside the allowlist is silently stripped.

| Shortcode | Attribute | Allowed tags |
|-----------|-----------|--------------|
| `us_text` | `text` | `<br>`, `<code>`, `<i>`, `<small>`, `<span>`, `<strong>`, `<sub>`, `<sup>` |
| `us_btn` | `label` | `<br>`, `<code>`, `<i>`, `<small>`, `<span>`, `<strong>`, `<sub>`, `<sup>` |
| `us_ibanner` | `title` | `<strong>`, `<br>` |
| `us_separator` | `text` | `<strong>`, `<br>` |
| `us_flipbox` | `front_title`, `back_title` | `<br>` |

Typical uses:

```text
[us_btn label="Save <strong>30%</strong> today"]
[us_text text="Built for<br>builders" tag="h1"]
[us_text text="H<sub>2</sub>O reacts with CO<sub>2</sub>"]
[us_text text="Call <code>do_shortcode()</code> from a template"]
[us_separator text="<strong>OR</strong>"]
```

Limits:

- **Emit the tag literally, never entity-encoded.** `text="Spotless Homes,<br>Happy Families"` renders a line break; `text="Spotless Homes,&lt;br&gt;Happy Families"` shows the literal characters `<br>` on the page, because attribute values are **not** entity-decoded before the element's `strip_tags`/output runs (§3.2). The same holds for every allowlisted tag — write raw `<strong>` / `<sup>` / `<br>`, never `&lt;strong&gt;` / `&lt;sup&gt;` / `&lt;br&gt;`. This is the single most common `<br>` failure.
- Use `<i>` (not `<em>`), `<strong>` (not `<b>`) — only the allowlist names render.
- Block-level tags (`<p>`, `<div>`, `<h1>`–`<h6>`, `<ul>`, `<li>`, `<a>`) are stripped from every attribute. For block content or hyperlinks use `vc_column_text` (§4.3).
- Literal `"` inside the value must be encoded as `&quot;` or `&#34;`. Inline `<span style="…">` works but is brittle — prefer the shortcode's `color` / `dynamic_color` params.

### 4.2 Attributes that strip all HTML

Every label/title/text param **not** listed in §4.1 strips all tags before output. This covers most other titles across in-scope shortcodes (`us_dropdown > title`, `us_popup > title`, and similar). When you need formatting and no allowlisted attribute exists, put the rich-text portion in an adjacent `vc_column_text`.

### 4.3 `vc_column_text` — full HTML body

`vc_column_text` is the only shortcode whose **body** accepts arbitrary HTML (run through WordPress's `widget_text_content` filter — paragraphs, lists, blockquotes, inline links, images, headings, inline styles). Use it whenever the content needs anything beyond §4.1's inline tags or needs hyperlinks within body copy:

```text
[vc_column_text]
<p>First paragraph with an <a href="https://example.com">inline link</a>.</p>
<ul><li>Bullet one</li><li>Bullet two</li></ul>
<blockquote>A pull quote.</blockquote>
[/vc_column_text]
```

Guidelines:

- For headings, prefer `us_text tag="h1"…"h6"` over a raw `<h2>` inside `vc_column_text` — `us_text` exposes typographic params (`font`, `size`, alignment) and integrates with site typography settings.
- Standalone CTA links should be `us_btn`, not `<a class="button">` inside `vc_column_text`.
- Inside any *other* shortcode body, raw `<p>` / `<ul>` / `<h2>` tags are stripped — wrap that prose in a `vc_column_text` next to the other shortcodes inside the same column.

---

## 5. Color and style references (palette tokens)

Many color params accept symbolic theme-palette tokens in place of literal HEX/RGBA. These resolve to the active theme's CSS variables on render and survive site re-theming. Prefer them over hard-coded colors.

Common tokens:

| Token | What it maps to |
|-------|-----------------|
| `_content_primary` | brand/primary accent color |
| `_content_link` | link color |
| `_content_link_hover` | link hover color |
| `_content_heading` | headings color |
| `_content_bg` | body/section background |
| `_content_bg_alt` | alternate section background |
| `_alt_content_*` | the same set under the "alternate" color scheme (rows with `color_scheme="alternate"`) |

Row-level `color_scheme` switches the active palette for everything inside the row:

| `color_scheme` value | Effect |
|----------------------|--------|
| empty (default) | content colors |
| `alternate` | alternate content colors (`_alt_content_*` tokens) |
| `primary` | primary background, white text |
| `secondary` | secondary background, white text |
| `footer-top` | alternate footer palette |
| `footer-bottom` | main footer palette |

Practical use:

```text
[vc_row color_scheme="alternate"]
  [vc_column]
    [us_text text="On alt background" css_color="_alt_content_heading"]
    [us_btn label="Action" color="_content_primary"]
  [/vc_column]
[/vc_row]
```

Literal `#rrggbb` and `rgba(…)` work but break site theming — prefer tokens, fall back to literals only when no token fits.

---

## 6. Reusing pre-built sections

**Consult [`sections/`](sections/) any time** the user asks you to create / build / add a section, block, or full page that maps to one of the standard content patterns (hero, about, features, services, CTA, stats, pricing, steps, FAQ, team, contact, blog list, portfolio grid, testimonials, gallery, footer). Adapting a pre-tested template is dramatically more reliable than composing from scratch. From-scratch composition is reserved for patterns no category in [`sections.md`](sections.md) covers.

Section blocks are **self-contained `vc_row` blocks**. To use one:

1. Pick a category file under [`sections/`](sections/) and copy the `text` block under any `### <template-id>` entry.
2. Paste it into `post_content` as-is — it already starts with `[vc_row …]` and ends with `[/vc_row]`, so it slots in at the top level next to other rows.
3. Edit text, labels, link URLs, image IDs in place. Preserve `css="…"` attributes (they carry the design); to override, edit the JSON per §3.3.

Adjacent rows that would share the same background should use contrasting `color_scheme` values to produce visible separation.

### `use:placeholder` markers

Section snapshots reproduce shortcode markup verbatim from `us.api`. Image references without a default media ID appear as the literal token `use:placeholder` (or `use%3Aplaceholder` inside `css="…"` blobs):

```text
[us_image image="use:placeholder"]
```

Either replace with a real media ID (`image="1234"`) or leave as-is — the front-end renderer substitutes `use:placeholder` with a generic placeholder image, useful for drafting before assets are ready.

---

## 7. Responsive overrides

Column layout and several other layout params accept per-breakpoint overrides. The breakpoints, from widest to narrowest:

| Breakpoint | Param suffix | Default screen width |
|------------|--------------|----------------------|
| desktop | derived from child `vc_column` `width` values (see §2) | wider than `1380px` |
| laptops | `laptops_columns` on the row | up to `1380px` |
| tablets | `tablets_columns` on the row | up to `1024px` |
| mobiles | `mobiles_columns` on the row | up to `600px` |

Rules:

- **Desktop layout comes from child column widths** (see §2). The narrower-viewport overrides below are row attributes.
- **`inherit` cascades down**: `laptops_columns="inherit"` reuses the desktop value (i.e. whatever was computed from child widths), `tablets_columns="inherit"` reuses laptop (which may itself be desktop), and so on. Omitting the attribute is equivalent to `inherit`.
- **Mobile defaults to one column** for `vc_row` / `vc_row_inner`: `mobiles_columns="1"` is the assumed default unless explicitly overridden. A three-column row on desktop becomes single-column on mobile automatically.
- **Override only what changes** — do not set `laptops_columns="3" tablets_columns="3" mobiles_columns="3"` to "keep three columns everywhere"; let the desktop layout cascade and add `ignore_columns_stacking="1"` only if you genuinely need three columns on mobile (rare, usually icon grids).
- The `laptops_columns` / `tablets_columns` / `mobiles_columns` attributes accept the same enum as the auto-computed desktop `columns`: `1`, `2`, `3`, `4`, `5`, `6`, `1-2`, `2-1`, `1-3`, `3-1`, `1-4`, `4-1`, `1-5`, `5-1`, `2-3`, `3-2`, `1-2-1`, `1-3-1`, `1-4-1`, `inherit`, `custom`.

Example — three desktop columns, two on tablet, one on mobile:

```text
[vc_row tablets_columns="2" mobiles_columns="1"]
  [vc_column width="1/3"]…[/vc_column]
  [vc_column width="1/3"]…[/vc_column]
  [vc_column width="1/3"]…[/vc_column]
[/vc_row]
```

Other params that accept similar suffixes: `columns_gap`, `content_placement`, several `us_bg_*` props. Check the per-shortcode file under [`shortcodes/`](shortcodes/) for the responsive flag on each parameter.

---

## 8. Cross-shortcode anti-patterns

One-line checklist of silent-fail patterns. Details in the referenced sections; per-shortcode anti-patterns live in [`shortcodes/`](shortcodes/).

- **`columns="3"` on `vc_row` without `width="…"` on each child** — row layout is derived from child widths; row attribute is overwritten (§2).
- **Inventing per-breakpoint variants** (`tablets_columns_gap`, `mobiles_size`) — only `*_columns` are split; everything else uses the responsive JSON form (§3.4).
- **Nesting `vc_row` inside `vc_column`** — `vc_row` is root-only; use `vc_row_inner` + `vc_column_inner` (§2).
- **Nesting a container inside the same tag** (`vc_row_inner > vc_column_inner > vc_row_inner`, or any tag inside itself) — WordPress's shortcode parser is non-recursive per tag and mangles the block; one inner-grid level only (§2.1).
- **`vc_tta_section` outside a tabs/accordion/tour container** — renders nothing (§2).
- **Single-quoted `css='{…}'` or any single-quoted JSON attribute** — mangled by `wptexturize`, attributes silently dropped (§3.3).
- **Leaf directly under `vc_row`** — `vc_row` accepts `vc_column` only; wrap in a column (§1, §2).
- **Mixing raw HTML and `vc_column_text` in the same column** — pick one (a single `vc_column_text` block, or a sequence of leaves). Interleaving strays orphans them.
- **Literal hex colors when a palette token exists** — breaks site theming; prefer `_content_*` tokens (§5).
- **Hard-coding `laptops_columns` = `tablets_columns` = `mobiles_columns` to the same value** — `inherit` is the default; override only what changes (§7).
- **Stripping `css="…"` from a section template** — that attribute carries the design (§6).
- **Inventing tag names** (`us_section`, `us_hero`, `us_cta_box`) — the tag set is fixed by registered shortcodes; see [`shortcodes.md`](shortcodes.md).
