# Button styles editing

Tools:
- `upsolution-list-button-styles` — return every entry currently stored under the `buttons` Theme Option (id, name, all fields). Call this before composing a section that uses buttons to confirm which ids exist.
- `upsolution-set-button-styles` — apply a sequence of `add` / `update` / `delete` / `reorder` operations atomically. Saving regenerates the site's CSS asset files automatically.

This is for the SITE-WIDE button styles list only (Theme Options → Button Styles). Each entry's numeric `id` is what shortcode markup references through `style="N"` / `btn_style="N"` (us_btn, us_cta, us_pricing, us_cform, us_popup, us_flipbox, us_post_list, us_post_taxonomy, …). The `class` field is the only opt-in extension point — anything set there is appended to the button's HTML class list so per-style CSS overrides remain possible.

For per-button overrides that should NOT live in the global list (one-off colors / spacing in a single section), use the element-level `css="..."` attribute on the `us_btn` instead — see `composition-rules` and `element-design`. The global list is for styles reused across the site.

The schema for `upsolution-set-button-styles` carries the operation shapes (add / update / delete / reorder), the two hard rules (id immutable; list cannot become empty), color-syntax rules (same as set-palette), and the `font` field special behaviour. This doc carries what schema can't — per-field enum values and the operational facts the validator doesn't enforce.

## Anatomy of one entry

A button style is an associative object. The following fields mirror the Theme Options → Button Styles editor (the UI accordion).

### Identification

| Field | Type | Notes |
|---|---|---|
| `id` | int | Auto-assigned on add (max existing + 1). Immutable — referenced by shortcode `style="N"`. Never appears in `fields`. |
| `name` | string | Editor label. Required on add. Falls back to "Style {id}" if cleared on an existing entry. |
| `class` | string | Extra HTML class appended for ad-hoc CSS overrides. |

### Hover & animation

| Field | Accepted values |
|---|---|
| `hover` | `fade`, `slide`, `slideLeft`, `slideRight`, `slideBottom`, `scaleUp`, `scaleDown`, `circle` |
| `hover_text_animation` | `fade`, `slideTop`, `slideLeft`, `slideRight`, `slideBottom`, `scaleUp`, `scaleDown` |
| `border_animation` | `none`, `play_on_hover`, `pause_on_hover`, `play_always` (only meaningful with a gradient `color_border` + non-zero `border_width`) |
| `transition_duration` | CSS time — `0.3s`, `300ms`. Free-form string. |
| `transition_timing_function` | CSS easing — `linear`, `ease`, `cubic-bezier(.7,0,.2,1)`. Free-form string. |
| `animation_duration` | CSS time — `3s`. Only used when `border_animation != none`. |

**Critical operational fact:** the first style in the list (storage index 0) is automatically applied to any third-party submit / generic button on the site via `apply_filters('us_default_btn_selector', '[type=submit]:not(.w-btn):not(.button),')` (see `templates/css-theme-options.php`). Animations other than `fade` may not work for those third-party buttons. **If you reorder, the auto-applied style changes too — keep the most "neutral" / brand-default style at position 0.**

### Colors

Eight fields. Accepted color syntax is the same as `set-palette` (hex / rgba / `transparent` / `linear-gradient` on gradient-capable fields only / `"_<slug>"` palette tokens — see schema). Empty string clears the field.

| Field | Gradient? | Notes |
|---|---|---|
| `color_bg` | yes | Idle background. |
| `color_bg_hover` | yes | Background under `:hover`. |
| `color_border` | yes | Idle border color. Gradient values activate the gradient-border render path. |
| `color_border_hover` | yes | Border on `:hover`. |
| `color_text` | no | Text color. |
| `color_text_hover` | no | Text color on `:hover`. |
| `color_shadow` | no | Box-shadow color. If empty, no shadow is rendered (the shadow_* sliders are ignored). |
| `color_shadow_hover` | no | Box-shadow color on `:hover`. Same gating. |

### Box-shadow

Two five-field groups — one for idle, one for hover. Both gated by the corresponding `color_shadow` / `color_shadow_hover` (empty color → shadow not emitted at all even if offsets are non-zero).

| Field | Type | Notes |
|---|---|---|
| `shadow_offset_h` | CSS length | Horizontal offset (`2px`, `0.1em`, `-4px`). |
| `shadow_offset_v` | CSS length | Vertical offset. |
| `shadow_blur` | CSS length | Non-negative. |
| `shadow_spread` | CSS length | Can be negative. |
| `shadow_inset` | `""` or `"1"` | Inner shadow. Accepts boolean / `[]` / `["1"]` on input; stored as string form. |
| `shadow_hover_offset_h`, …, `shadow_hover_inset` | same | Same five fields under `:hover`. |

### Typography & sizes

| Field | Type | Notes |
|---|---|---|
| `font` | enum (see below) | What font-family the button text uses. |
| `height` | CSS length | Vertical padding ("Relative Height" in editor). Typical em values. |
| `width` | CSS length | Horizontal padding ("Relative Width"). |
| `font_size` | CSS length | `1rem`, `16px`, `clamp(0.9rem, 1.5vw, 1.1rem)`. |
| `line_height` | unitless or CSS length | `1.2`, `28px`. |
| `border_width` | CSS length | Typical px values; `0px` removes the border. |
| `font_weight` | 100..900 or keyword | Numeric in steps of 100, OR CSS keyword: `normal`, `bold`, `lighter`, `bolder`. |
| `border_radius` | CSS length / var | `4px`, `999em`, `var(--site-border-radius)`. |
| `letter_spacing` | CSS length | Typical em values (`-0.02em`, `0.05em`, `0`). |
| `text_style` | string or array | Subset of `["uppercase","italic"]`. Stored comma-separated. Pass `""` / `[]` to clear; `["uppercase","italic"]` or `"uppercase,italic"` interchangeably. |

The `font` field accepts a wider set than typography's `font-family`:

- `""` — use the default body font.
- A typography tag (`body`, `h1` .. `h6`) — resolves at render time to `var(--<tag>-font-family)` so the button inherits that tag's family.
- Any font name returned by `upsolution-list-fonts` (Google, Adobe, web-safe, uploaded).
- Any name from Additional Google Fonts (Theme Options → Typography → Additional Google Fonts).

Unknown font names return HTTP 422 — call `upsolution-list-fonts` first.

## Workflow

1. `upsolution-list-button-styles` to see what's there. Note the ids.
2. (Optional, if touching `font`) `upsolution-list-fonts` to see acceptable family names.
3. (Optional, if previewing before persisting) Wrap operations in `upsolution-create-preview` under the `button_styles` key — same `{operations:[...]}` payload — and share the returned URL.
4. `upsolution-set-button-styles` with the operations. Inspect `applied` / `before` / `after` in the response.
5. After save, CSS asset files regenerate automatically (`usof_after_save` → `us_generate_asset_files`). New look takes effect on next page load.

## Anti-patterns

- Don't pass `id` in `fields` — neither on `add` (auto-assigned) nor on `update` (immutable). Rejected with HTTP 400.
- Don't try to delete the last style. Add a replacement first (in the same call if needed — operations run in order).
- Don't put one-off per-button styling in the global list. The list is for reused styles; per-section overrides belong in the `us_btn`'s `css="..."` attribute.
- Don't try to write `wrapper_shadow_start` / `wrapper_shadow_end` / `wrapper_shadow_hover_start` / `wrapper_shadow_hover_end` — those are admin-UI accordion markers, not stored fields. Rejected as unknown.
- Don't pass a font name that isn't in `upsolution-list-fonts` or the typography tags list — HTTP 422 (not installed).
- Don't use a gradient value on `color_text` / `color_text_hover` / `color_shadow` / `color_shadow_hover` — those fields are solid-only and reject gradients with HTTP 400.
- Don't emit `radial-gradient(...)` or `conic-gradient(...)` (or any `repeating-*` variant) on **any** field, including gradient-capable ones. Same restriction applies to inline `css="..."` `background-color` on shortcodes — only `linear-gradient` is reliable across the framework.
- Don't bypass `set-button-styles` and write the `buttons` Theme Option via any other mechanism. Direct writes skip `usof_after_save`, leave the CSS stale, won't enforce the hard rules, and can corrupt id assignment.
- Don't fight the renderer: the first style in storage order is applied to third-party submit buttons via `us_default_btn_selector`. If you reorder, the auto-applied style changes too — keep the most "neutral" / brand-default style at position 0.
