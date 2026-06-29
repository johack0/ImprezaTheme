# Color palette editing

Tools:
- `upsolution-get-palette` — read the current value of every color picker in the requested section(s) plus the Custom Global Colors list.
- `upsolution-set-palette` — patch any subset of the predefined color pickers and / or replace the Custom Global Colors list. Saving regenerates the site's CSS asset files automatically.

This is for the SITE-WIDE palette only (Theme Options → Colors). For per-element backgrounds, text colors, borders, overlays — use the `css="..."` attribute on the row / element itself (see `composition-rules` and `element-design`).

Out of scope here: the predefined color SCHEMES ("alternate", "dark", ...) that the user manages from Theme Options → Color Schemes, and the per-shortcode `color_scheme="..."` attribute. Both are fixed enums on this site and are not exposed via MCP.

The schema description on `upsolution-set-palette` carries the full color-syntax spec (hex / rgba / linear-gradient / `"_<slug>"` palette tokens / HSL & non-linear gradients rejected / slug sanitization / `custom_colors` replace-not-patch). This doc carries what schema can't — per-key gradient policy and section semantics.

## Sections

Seven sections, all read by `get-palette` and patchable by `set-palette`. The six picker sections mirror the admin UI headings under Theme Options → Colors verbatim:

- `header` — 7 fields. **Site chrome:** active header — middle area, transparent state, mobile-browser chrome toolbar. Always rendered.
- `alternate_header` — 6 fields. **Site chrome:** top bar above the header (`color_header_top_*`). Always rendered.
- `content` — 11 fields. **Default row palette:** applied to any row that does NOT set a `color_scheme` attribute.
- `alternate_content` — 11 fields. Applied to rows with `color_scheme="alternate"` (`color_alt_content_*`).
- `footer` — 7 fields. Applied to rows with `color_scheme="footer-bottom"` (`color_footer_*`).
- `alternate_footer` — 7 fields. Applied to rows with `color_scheme="footer-top"` (`color_subfooter_*`).
- `custom_colors` — the Custom Global Colors group. List of `{color, name, slug}` entries that each become a CSS variable `--color<slug>` (underscores in the slug rendered as hyphens).

The four "row palette" sections (`content` / `alternate_content` / `footer` / `alternate_footer`) are NOT positional — none of them is automatically used by a specific area of the page. They're four of the six values the per-row `color_scheme` attribute can take (the other two — `primary` / `secondary` — re-use `color_content_primary` / `color_content_secondary` directly and have no dedicated picker group). The `footer` / `alternate_footer` names and `color_subfooter_*` storage keys are historical: practically, they're two more themable row palettes that any row anywhere can opt into via the attribute.

## Per-key gradient policy

Only fields marked `Gradient? yes` accept `linear-gradient(...)` values. Solid-only fields reject gradients with HTTP 400. (Only `linear-gradient` is supported anywhere — `radial-gradient` / `conic-gradient` / `repeating-*` are rejected by the validator everywhere.)

### header

| Key | Gradient? |
|---|---|
| `color_header_middle_bg` | yes |
| `color_header_middle_text` | no |
| `color_header_middle_text_hover` | no |
| `color_header_transparent_bg` | yes |
| `color_header_transparent_text` | no |
| `color_header_transparent_text_hover` | no |
| `color_chrome_toolbar` | no |

### alternate_header

| Key | Gradient? |
|---|---|
| `color_header_top_bg` | yes |
| `color_header_top_text` | no |
| `color_header_top_text_hover` | no |
| `color_header_top_transparent_bg` | yes |
| `color_header_top_transparent_text` | no |
| `color_header_top_transparent_text_hover` | no |

### content

| Key | Gradient? |
|---|---|
| `color_content_bg` | yes |
| `color_content_bg_alt` | yes |
| `color_content_border` | no |
| `color_content_heading` | yes |
| `color_content_text` | no |
| `color_content_link` | no |
| `color_content_link_hover` | no |
| `color_content_primary` | yes |
| `color_content_secondary` | yes |
| `color_content_faded` | no |
| `color_content_overlay` | yes |

### alternate_content

Each key is the 1:1 sibling of `color_content_*` with the same gradient policy:

| Key | Gradient? |
|---|---|
| `color_alt_content_bg` | yes |
| `color_alt_content_bg_alt` | yes |
| `color_alt_content_border` | no |
| `color_alt_content_heading` | yes |
| `color_alt_content_text` | no |
| `color_alt_content_link` | no |
| `color_alt_content_link_hover` | no |
| `color_alt_content_primary` | yes |
| `color_alt_content_secondary` | yes |
| `color_alt_content_faded` | no |
| `color_alt_content_overlay` | yes |

### footer

| Key | Gradient? |
|---|---|
| `color_footer_bg` | yes |
| `color_footer_bg_alt` | yes |
| `color_footer_border` | no |
| `color_footer_heading` | yes |
| `color_footer_text` | no |
| `color_footer_link` | no |
| `color_footer_link_hover` | no |

### alternate_footer

Each key is the 1:1 sibling of `color_footer_*` with the same gradient policy (legacy storage prefix `color_subfooter_*`):

| Key | Gradient? |
|---|---|
| `color_subfooter_bg` | yes |
| `color_subfooter_bg_alt` | yes |
| `color_subfooter_border` | no |
| `color_subfooter_heading` | yes |
| `color_subfooter_text` | no |
| `color_subfooter_link` | no |
| `color_subfooter_link_hover` | no |

## Custom Global Colors

Each entry has three required fields:

```json
{ "color": "#0a84ff", "name": "Brand", "slug": "brand" }
```

Each saved entry generates two CSS custom properties at `:root`:

```css
:root {
  --color-brand: #0a84ff;
  --color-brand-grad: #0a84ff;
}
```

For gradient values, `--color-brand` falls back to the gradient's first color stop and `--color-brand-grad` carries the full gradient — pick whichever variable matches what you need.

Reference these in a `css="..."` attribute on a shortcode element:

```text
css="%7B%22default%22%3A%7B%22background-color%22%3A%22var(--color-brand)%22%7D%7D"
```

(That's `{"default":{"background-color":"var(--color-brand)"}}` URL-encoded.)

## Workflow

1. `upsolution-get-palette` — without arguments to see everything, or `sections=["custom_colors"]` (etc.) for one slice.
2. `upsolution-set-palette` with a partial input. Example: darken the main header bg, retint the top bar, point the content primary at a new brand custom color, and seed the Custom Global Colors list:

```json
{
  "header": { "color_header_middle_bg": "#111111" },
  "alternate_header": { "color_header_top_bg": "#0a0a0a" },
  "content": { "color_content_primary": "_brand" },
  "custom_colors": [
    { "color": "#0a84ff", "name": "Brand",  "slug": "brand" },
    { "color": "#34c759", "name": "Accent", "slug": "accent" }
  ]
}
```

A picker key sent under the wrong section is rejected — match the section to the key prefix (`color_header_top_*` belongs to `alternate_header`, `color_alt_content_*` to `alternate_content`, `color_subfooter_*` to `alternate_footer`).

3. Response includes `applied` (per-section list of keys that actually changed) and `before` / `after` snapshots of the sections you touched. `regenerated_assets: true` confirms `usof_after_save` fired and the CSS files were rebuilt.

There is no automatic rollback — read `before` from the response (or call `get-palette` again) if you need to undo.

## Anti-patterns

- Don't send a gradient to a field whose `Gradient?` column is `no` — rejected with HTTP 400. Pick the matching `_bg` field if you wanted a gradient background.
- Don't emit `radial-gradient(...)` or `conic-gradient(...)` (or any `repeating-*` variant) on **any** field, including gradient-capable ones and including the `color` of a `custom_colors` entry. Same restriction applies to inline `css="..."` `background-color` on shortcodes — only `linear-gradient` is reliable across the framework.
- Don't paste hex literals into every predefined picker when a single brand palette can drive them. Define the brand colors in `custom_colors` once, then point the relevant predefined pickers at those slugs via `"_<slug>"` — gives you one editable source of truth.
- Don't write `"_<slug>"` for a slug you haven't created yet — the validator rejects it. Add the entry to `custom_colors` in the same `set-palette` call (or a preceding one) before referencing it.
- Don't expect `custom_colors` to merge — you must send the full intended list. Read first, then replace.
- Don't paint per-row / per-element backgrounds here — that belongs in the element's own `css="…"` attribute, not in the global palette.
- Don't bypass `set-palette` and write into `usof_options_<theme>` via a generic option writer — tampering skips `usof_after_save` and leaves the CSS stale.
- Don't try to switch color schemes ("alternate" / "dark" / ...) from here — those are fixed enums managed by the site owner.
