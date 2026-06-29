# Field styles editing

Tools:
- `upsolution-list-field-styles` — return every entry currently stored under the `input_fields` Theme Option (id, name, all fields). Call this before composing a section that uses form fields to confirm which ids exist.
- `upsolution-set-field-styles` — apply a sequence of `add` / `update` / `delete` / `reorder` operations atomically. Saving regenerates the site's CSS asset files automatically.

This is for the SITE-WIDE field styles list only (Theme Options → Field Styles). Each entry's numeric `id` is what shortcode markup references through `us_field_style="N"` (us_cform, us_search, us_login, us_grid_filter, us_grid_order, us_list_filter — which also has a separate `dropdown_field_style` param — us_list_search, us_list_order, contact-form-7, gravityform, WooCommerce checkout/cart elements, …). The `class` field is the only opt-in extension point — anything set there is appended to the element's HTML class list alongside `us-field-style_<id>` so per-style CSS overrides remain possible.

For per-element overrides that should NOT live in the global list (one-off colors / spacing in a single section), use the element-level `css="..."` attribute instead — see `composition-rules` and `element-design`. The global list is for styles reused across the site.

The schema for `upsolution-set-field-styles` carries the operation shapes (add / update / delete / reorder), the hard rules (id immutable; list cannot become empty; first entry = site-wide default), color-syntax rules (same as set-palette), and the `font` field special behaviour. This doc carries what schema can't — per-field enum values and the operational facts the validator doesn't enforce.

**Critical operational fact:** the first style in the list (storage index 0) IS the site-wide default. The CSS renderer (`templates/css-theme-options.php`, FIELD STYLES block) emits its values as `--inputs-*` variables on `:root`, so every input / textarea / select on the site with no explicit style class uses it. Other entries become `.us-field-style_<id>` overrides. `us_get_field_style_class()` resolves `us_field_style="default"`, an omitted attribute, AND any unknown id to the first entry. **If you reorder (or `add` at position 0), the default for the entire site's forms changes — keep the brand-default style at position 0.**

## Anatomy of one entry

A field style is an associative object. The following fields mirror the Theme Options → Field Styles editor (the UI accordion).

### Identification

| Field | Type | Notes |
|---|---|---|
| `id` | int | Auto-assigned on add (max existing + 1). Immutable — referenced by shortcode `us_field_style="N"`. Never appears in `fields`. |
| `name` | string | Editor label. Required on add. Falls back to "Style {id}" if cleared on an existing entry. |
| `class` | string | Extra HTML class appended for ad-hoc CSS overrides. |

### Colors

Eight fields — four for the idle state, four for `:focus`. Accepted color syntax is the same as `set-palette` (hex / rgba / `transparent` / `linear-gradient` on gradient-capable fields only / `"_<slug>"` palette tokens — see schema). Empty string clears the field.

| Field | Gradient? | Notes |
|---|---|---|
| `color_bg` | yes | Idle background. |
| `color_bg_focus` | yes | Background under `:focus`. |
| `color_border` | no | Idle border color. Only visible with non-zero `border_width`. |
| `color_border_focus` | no | Border on `:focus`. |
| `color_text` | no | Text color. |
| `color_text_focus` | no | Text color on `:focus`. |
| `color_shadow` | no | Box-shadow color. If empty, no shadow is rendered (the shadow_* sliders are ignored). |
| `color_shadow_focus` | no | Box-shadow color on `:focus`. Same gating. |

### Box-shadow

Two five-field groups — one for idle, one for `:focus`. Both gated by the corresponding `color_shadow` / `color_shadow_focus` (empty color → shadow not emitted at all even if offsets are non-zero). The default style ships shadows as a subtle bottom hairline (idle, `shadow_inset="1"`) and a 2px spread ring (focus) — the conventional focus-visible affordance.

| Field | Type | Notes |
|---|---|---|
| `shadow_offset_h` | CSS length | Horizontal offset (`2px`, `0.1em`, `-4px`). |
| `shadow_offset_v` | CSS length | Vertical offset. |
| `shadow_blur` | CSS length | Non-negative. |
| `shadow_spread` | CSS length | Can be negative. |
| `shadow_inset` | `""` or `"1"` | Inner shadow. Accepts boolean / `[]` / `["1"]` on input; stored as string form. |
| `shadow_focus_offset_h`, …, `shadow_focus_inset` | same | Same five fields under `:focus`. |

### Typography & sizes

| Field | Type | Notes |
|---|---|---|
| `font` | enum (see below) | What font-family the field text uses. |
| `height` | CSS length | Field height (`3em`, `48px`). Textareas scale from it. |
| `font_size` | CSS length | `1rem`, `16px`, `clamp(0.9rem, 1.5vw, 1.1rem)`. |
| `padding` | CSS length | Side indents (`1em`, `12px`). |
| `font_weight` | 100..900 or keyword | Numeric in steps of 100, OR CSS keyword: `normal`, `bold`, `lighter`, `bolder`. `""` inherits. |
| `border_width` | CSS length | Typical px values; `0px` removes the border (the default style has none — bg + shadow only). |
| `letter_spacing` | CSS length | Typical em values (`-0.02em`, `0.05em`, `0em`). |
| `border_radius` | CSS length / var | `4px`, `999em`, `var(--site-border-radius)`. |
| `text_transform` | enum | `none`, `uppercase`, `lowercase`, `capitalize`. |
| `checkbox_size` | CSS length | Checkbox / radio square size (`1.5em`, `20px`). |

The `font` field accepts a wider set than typography's `font-family`:

- `""` — inherit the surrounding font.
- A typography tag (`body`, `h1` .. `h6`) — resolves at render time to `var(--<tag>-font-family)` so fields inherit that tag's family.
- Any font name returned by `upsolution-list-fonts` (Google, Adobe, web-safe, uploaded).
- Any name from Additional Google Fonts (Theme Options → Typography → Additional Google Fonts).

Unknown font names return HTTP 422 — call `upsolution-list-fonts` first.

## Workflow

1. `upsolution-list-field-styles` to see what's there. Note the ids and which entry sits at position 0 (the site-wide default).
2. (Optional, if touching `font`) `upsolution-list-fonts` to see acceptable family names.
3. (Optional, if previewing before persisting) Wrap operations in `upsolution-create-preview` under the `field_styles` key — same `{operations:[...]}` payload — and share the returned URL.
4. `upsolution-set-field-styles` with the operations. Inspect `applied` / `before` / `after` in the response.
5. After save, CSS asset files regenerate automatically (`usof_after_save` → `us_generate_asset_files`). New look takes effect on next page load.

## Anti-patterns

- Don't pass `id` in `fields` — neither on `add` (auto-assigned) nor on `update` (immutable). Rejected with HTTP 400.
- Don't try to delete the last style. The first entry seeds the site-wide `--inputs-*` variables — an empty list unstyles every form field on the site. Add a replacement first (in the same call if needed — operations run in order).
- Don't put one-off per-form styling in the global list. The list is for reused styles; per-section overrides belong in the element's `css="..."` attribute.
- Don't try to write `wrapper_shadow_start` / `wrapper_shadow_end` / `wrapper_shadow_focus_start` / `wrapper_shadow_focus_end` — those are admin-UI accordion markers, not stored fields. Rejected as unknown.
- Don't pass a font name that isn't in `upsolution-list-fonts` or the typography tags list — HTTP 422 (not installed).
- Don't use a gradient value on `color_border*` / `color_text*` / `color_shadow*` — those fields are solid-only and reject gradients with HTTP 400 (only `color_bg` / `color_bg_focus` take gradients).
- Don't emit `radial-gradient(...)` or `conic-gradient(...)` (or any `repeating-*` variant) on **any** field, including gradient-capable ones. Same restriction applies to inline `css="..."` `background-color` on shortcodes — only `linear-gradient` is reliable across the framework.
- Don't bypass `set-field-styles` and write the `input_fields` Theme Option via any other mechanism. Direct writes skip `usof_after_save`, leave the CSS stale, won't enforce the hard rules, and can corrupt id assignment.
- Don't fight the renderer: the first style in storage order is the site-wide default applied to every unstyled form field. If you reorder or insert at position 0, the whole site's forms change — keep the brand-default style at position 0.
