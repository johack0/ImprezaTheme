# Typography editing

Tools:
- `upsolution-list-fonts` — discover acceptable `font-family` values for this install, plus each font's weight capabilities (`variable` axis ranges / `static_weights` lists).
- `upsolution-get-theme-option` name=`body` / `h1` / `h2` / `h3` / `h4` / `h5` / `h6` — read the current tag dict.
- `upsolution-set-typography` — patch ONE tag per call. The schema lists every field, its accepted values, and the merge semantics — pull that first.

## Tags

Seven tags map to Theme Options → Typography:
- `body` — Global Text. Does NOT support `margin-bottom`.
- `h1` .. `h6` — Heading 1..6.

Heading color (`color` / `color_override` on h1..h6) is NOT typography — it lives in `upsolution-set-palette`. `set-typography` preserves those two keys across `merge=false` calls so the palette tool's state survives a full-replace.

## Inheritance tokens

On h2..h6 you can point a field at h1 instead of supplying a value: `font-weight: "var(--h1-font-weight)"`, `font-style: "var(--h1-font-style)"`, etc. — useful when several heading levels share Heading 1's setting. For `font-family` specifically, `"inherit"` (h2..h6) inherits Global Text instead.

These tokens are NOT valid on `body` or on h1 itself.

## Font weight and Variable Fonts

`font-weight` / `bold-font-weight` accept any whole number (integer) from 1 to 1000 — not just 100-step values. Fractional weights are rejected. What actually makes sense depends on the font, so consult `upsolution-list-fonts` first:

- **Variable Fonts** appear in its `variable` map with axis ranges. Any integer inside the `wght` range is valid, intermediate ones included (350, 425, …).
- **Static fonts** appear in `static_weights` with the exact weights they ship. Other values pass validation, but the browser synthesizes them — expect faux-bold artifacts.
- Fonts in neither map (Adobe Fonts, web-safe stacks) have unknown weight sets — stick to the common 100..900 step-100 values.

`font-stretch` is a percentage (e.g. `"85%"`; default `100%`) and only has effect on Variable Fonts that expose a `wdth` axis — its valid range is the `wdth` entry in the `variable` map. On h2..h6 it can also be `var(--h1-font-stretch)`.

## Responsive object shape

Every field EXCEPT `font-family` accepts either a plain string OR a per-breakpoint object:

```json
{
  "default": "20px",
  "laptops": "18px",
  "tablets": "16px",
  "mobiles": "14px"
}
```

- `default` is REQUIRED when sending an object.
- `laptops` / `tablets` / `mobiles` are optional — omitted breakpoints inherit `default` (not the next-wider breakpoint).
- A plain string is equivalent to `{"default": "…"}`.

## Workflow

1. `upsolution-list-fonts` once per session to discover acceptable `font-family` values and each font's available weights / axis ranges.
2. `upsolution-get-theme-option` name=`<tag>` to read current state.
3. `upsolution-set-typography` with `tag` and just the fields you're changing.
4. Repeat for each tag — one tag per call. After save, CSS asset files regenerate automatically (`usof_after_save` → `us_generate_asset_files`); changes take effect on next page load.

## Anti-patterns

- Don't pass a font name not returned by `upsolution-list-fonts` — rejected with HTTP 422 (not installed).
- Don't send `var(--h1-…)` on h1 itself or on body — these inheritance tokens only make sense on h2..h6.
- Don't send `inherit` as anything other than `font-family` on h1..h6.
- Don't send a responsive object without a `default` key.
- Don't send `margin-bottom` on `body` — that field doesn't exist on the Global Text tag.
- Don't pick a weight a static font doesn't provide (see `static_weights` in `list-fonts`) — the call succeeds, but the browser fakes the weight and bold text may look smeared.
- Don't set `font-stretch` for a font without a `wdth` axis — it validates but has no visual effect.
- Don't try to change heading colour through `set-typography` — `color` and `color_override` are rejected as unknown fields. Use `upsolution-set-palette`.
- Don't bypass `set-typography` and write into `body` / `h1` / .. via a generic option-writer — there's no such tool, and tampering with `usof_options_<theme>` directly skips `usof_after_save` and leaves the CSS stale.
