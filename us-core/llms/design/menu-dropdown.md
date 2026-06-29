# Menu dropdown (mega-menu) settings

Tool:
- `upsolution-set-menu-dropdown` — style the dropdown of ONE first-level menu item. Read the current settings from the item's `dropdown` node in `upsolution-get-menu`.

This edits the per-item "Dropdown Settings" panel from wp-admin → Appearance → Menus (stored as the `us_mega_menu_settings` post-meta on a `nav_menu_item`). It controls how a top-level item's dropdown **looks** — it does NOT change the dropdown's **contents** (those are the item's sub-items / embedded Reusable Block, edited with `upsolution-set-menu-items`). See `design/menus` for the item tree.

## Scope — first-level items with a dropdown only

The theme honours these settings only on items at the **top level** (`menu_item_parent == 0`) that actually open a dropdown — i.e. they have sub-items, or an embedded `reusable_block` child (a mega-menu). Setting them on a nested item is rejected. Setting them on a top-level item that has no dropdown yet is allowed but returns a `warning` (nothing renders until the item gets sub-items).

Saving regenerates the site CSS asset files — changes take effect on the next page load.

## Patch semantics

`settings` is a **partial patch by default** (`merge: true`): only the keys you pass change; everything else keeps its stored value. Pass `merge: false` to reset every other field to its default first (a clean re-style). The stored object is always complete — you never have to send all fields.

Fields are interdependent (the admin form shows/hides them by condition). A field you pass whose **dependency is not met in the resulting state** is NOT stored — it comes back in the response's `ignored` array with the reason, e.g. you sent `custom_width` but `width` is not `custom`. This is silent-safe: the rest of your patch still applies. So set the controlling field in the same call (e.g. `width: "custom"` together with `custom_width: "700px"`).

## Field reference

Switch fields take `true` / `false`. Color fields accept the same syntax as `upsolution-set-palette` (hex / `rgba()` / `"transparent"` / palette tokens `"_<slug>"`; `linear-gradient(...)` only where noted). `bg_image` takes an image attachment id (resolve via `upsolution-list-media` / `upsolution-upload-media`).

### Columns (standard dropdown)

| Field | Values | Depends on |
|---|---|---|
| `columns` | integer `1`–`10` — number of columns the sub-items flow into | — |
| `columns_fill_direction` | `hor` / `ver` | `columns` ≠ 1 |
| `padding` | CSS padding string (inner indents), e.g. `20px` or `1rem 2rem` | — |

### Side panel

| Field | Values | Depends on |
|---|---|---|
| `has_side_panel` | `true` / `false` — second-level items shown aside | — |
| `side_item_font_size` | CSS size (e.g. `1.15em`) | `has_side_panel` on |
| `side_item_font_weight` | CSS font-weight | `has_side_panel` on |
| `side_item_ver_indent` / `side_item_hor_indent` | CSS length | `has_side_panel` on |
| `side_item_width` | CSS length (e.g. `250px`) | `has_side_panel` on |
| `dropdown_height` | CSS length (e.g. `400px`) | `has_side_panel` on |

Turning on `has_side_panel` forces the dropdown to full width, so the width / position fields below do not apply in that mode.

### Width & position (standard dropdown)

| Field | Values | Depends on |
|---|---|---|
| `width` | `auto` / `full` / `custom` | `has_side_panel` off |
| `custom_width` | CSS width (e.g. `700px`) | `width` = `custom` and `has_side_panel` off |
| `stretch` | `true` / `false` — stretch background to screen edges | `width` = `full` |
| `drop_from` | `menu_item` / `header` — where the dropdown drops from | `width` ≠ `full` and `has_side_panel` off |
| `drop_to` | `left` / `center` / `right` | `width` ≠ `full` and `has_side_panel` off |

### Background & text

| Field | Values | Depends on |
|---|---|---|
| `color_bg` | color — gradient allowed | — |
| `color_text` | color — solid only | — |
| `bg_image` | image attachment id, or `""` / `0` to clear | — |
| `bg_image_size` | `cover` / `contain` / `initial` | `bg_image` set |
| `bg_image_repeat` | `repeat` / `repeat-x` / `repeat-y` / `no-repeat` | `bg_image` set |
| `bg_image_position` | `top left` / `top center` / `top right` / `center left` / `center center` / `center right` / `bottom left` / `bottom center` / `bottom right` | `bg_image` set |

### Mobile

| Field | Values | Depends on |
|---|---|---|
| `override_settings` | `true` / `false` — override mobile behaviour for this item | — |
| `mobile_behavior` | `arrow` / `label` — what opens the dropdown on mobile | `override_settings` on |

## Mega-menu recipe

A mega-menu is usually: a top-level parent (often a `custom` item with `url: "#"`), one or more `reusable_block` sub-items holding the content, then dropdown styling on the parent. Two calls:

1. `upsolution-set-menu-items` — add the parent and its `reusable_block` child (see `design/menus`).
2. `upsolution-set-menu-dropdown` — on the parent's id, e.g. a full-width banner dropdown:

```json
{"menu_id": 12, "item_id": 88, "settings": {"width": "full", "stretch": true, "color_bg": "_content_bg_alt"}}
```

A 3-column link mega-menu:

```json
{"menu_id": 12, "item_id": 88, "settings": {"columns": 3, "columns_fill_direction": "hor", "padding": "20px"}}
```

## Workflow

1. `upsolution-get-menu` — find the first-level item id; its current styling (if any) is on its `dropdown` node.
2. `upsolution-set-menu-dropdown` — patch the fields you want.
3. Check the response: `after` is the stored result; `ignored` lists fields dropped for unmet dependencies; `warning` flags a dropdown-less item.

## Anti-patterns

- **Styling a nested item.** Only first-level items render these settings — the call is rejected. Target the top-level ancestor.
- **A dependent field without its controller.** `custom_width` without `width: "custom"`, `stretch` without `width: "full"`, any `side_item_*` without `has_side_panel: true` — they land in `ignored` and do nothing. Set the controller in the same patch.
- **Confusing styling with contents.** This tool never adds links or blocks to the dropdown — that is `upsolution-set-menu-items`. Reaching for it to "add items to the mega-menu" is the wrong tool.
- **Literal HEX where a palette token fits.** Prefer `"_content_bg"` etc. (see `design/color-palette`) so the dropdown tracks the site palette.
