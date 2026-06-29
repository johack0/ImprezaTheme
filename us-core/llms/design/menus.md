# Navigation menus editing

Tools:
- `upsolution-list-menus` — every menu (id, name, slug, item count). Call this first to resolve a menu name or slug to its `menu_id`.
- `upsolution-get-menu` — one menu's items as a nested tree. Item ids, nesting and positions for every operation come from here — read it immediately before composing operations.
- `upsolution-set-menu-items` — apply a sequence of `add` / `update` / `remove` / `reorder` operations to one menu's items as one transaction.

This is the WP-native menu system (wp-admin: Appearance → Menus). The tools edit menu ITEMS — see the scope note under "Operational facts" for what stays wp-admin-only.

## Where menus appear in this theme

- The **Header Builder's Menu element** renders the menu picked in its settings, referenced by the menu **slug**. `upsolution-list-menus` lists every menu with its slug, so match the one the header uses by slug.
- The `[us_additional_menu source="<slug>"]` shortcode renders a menu inside page content (footer columns, sidebars) — see `shortcodes/additional_menu`.

Editing a menu's items changes every place that menu is rendered, site-wide, immediately.

## Item anatomy

An item's `type` decides which fields identify it. **`type` is immutable** — to convert an item, `remove` it and `add` a new one.

| `type` | Identified by | Label when `title` is empty | Notes |
|---|---|---|---|
| `post_type` | `object_id` = page / post / portfolio id | Inherits the object's CURRENT title — follows renames automatically | The default choice for internal links. The item always links to the object's current permalink, so it survives slug changes. |
| `taxonomy` | `object_id` = term id | Inherits the term's current name | Links to the term archive. Menus themselves are terms but cannot be linked. |
| `custom` | `url` | — `title` is REQUIRED (no inherit source) | For external URLs, anchors (`#contact`), `mailto:` / `tel:`. |
| `post_type_archive` | `object` = post type name | Inherits the archive label | The post type must have an archive page, otherwise the add is rejected. |
| `reusable_block` | `object_id` = Reusable Block (`us_page_block`) id | Inherits the block's title (admin label only — not rendered) | Embeds the Reusable Block's content into the dropdown instead of rendering a link. This is the building block of a mega-menu. See below. |

`object` is always derived from `object_id` (or, for archives, given explicitly) — never pass it alongside `object_id`. For `reusable_block` it is always `us_page_block`.

### Reusable Block items (mega-menu content)

A `reusable_block` item renders no link — the menu walker injects the linked Reusable Block's content where the dropdown would be. So it is almost always placed as a **sub-item of a parent** (commonly a `custom` item with `url: "#"`): hovering the parent opens a dropdown showing the block. Put the block id in `object_id` (resolve names via `upsolution-list-posts` with `post_type="us_page_block"`).

Applicable fields: `object_id` (required), `remove_rows` (bool, default `true`), `title` (admin label only), `classes` (added to the item's `<li>`). The link-only fields (`url`, `target`, `xfn`, `description`, `attr_title`) are rejected — they would do nothing.

- `remove_rows` (→ "Exclude Rows and Columns" in the admin) — when `true` (default), the block's outermost `vc_row` / `vc_column` wrappers are stripped at render so the block's content flows into the menu dropdown's own columns/grid. Set `false` to keep the block's own row/column layout intact (e.g. the block is a self-contained banner).

The dropdown's **styling** (columns, side panel, width, background — the admin "Dropdown Settings" / `us_mega_menu_settings` on the parent item) is NOT editable here; it is preserved untouched across edits. Configure it in wp-admin → Appearance → Menus if needed.

### Writable fields

| Field | Applies to | Accepted values |
|---|---|---|
| `title` | all | Navigation label, single line. Pass `null` (or `""`) on an object-backed item to fall back to the object's current title; rejected on custom links. |
| `url` | custom only | Non-empty, no whitespace. Absolute `http(s)://`, site-relative `/path/`, `#anchor`, `mailto:`, `tel:`. Schemes outside the WP allow-list are rejected. |
| `object_id` | post_type / taxonomy / reusable_block | Repoints the item at another existing object (also clears the `invalid` flag). Resolve titles to ids via `upsolution-list-posts` / `upsolution-list-terms`. |
| `remove_rows` | reusable_block only | Bool, default `true`. Strips the block's wrapping rows/columns so its content flows into the menu grid. |
| `target` | all except reusable_block | `""` (same tab) or `"_blank"` (new tab). |
| `classes` | all | Extra HTML classes — space-separated string or array. Tokens limited to letters / digits / `-` / `_` (WordPress strips anything else on save). |
| `xfn` | all except reusable_block | XFN rel tokens, same token rules as `classes`. |
| `description` | all except reusable_block | Multi-line text; only rendered when the active menu template shows descriptions. |
| `attr_title` | all except reusable_block | The link's `title` attribute (hover tooltip). |

## Operations

Validated and applied to an in-memory copy of the tree in order; if ANY operation fails, the call returns the error and **nothing is persisted**. (The only non-atomic case is a database failure mid-write — the error then lists exactly what had been written; re-read with `upsolution-get-menu` before retrying.)

Anywhere an item id is expected, the token `"new:<i>"` references the item created by the `add` at `operations[<i>]` (earlier in the same call) — its real id appears in the `applied` audit of the response.

### add `{ fields, parent_id?, position? }`

`parent_id` omitted / `0` = top level; `position` is the 0-based index among that parent's children, omit to append.

"Add the About page right after Home" — `upsolution-get-menu` shows top-level order `[Home, Services, Contact]`, About's page id is 42:

```json
{"op": "add", "fields": {"type": "post_type", "object_id": 42}, "position": 1}
```

A parent with its sub-items in one call:

```json
[
  {"op": "add", "fields": {"type": "custom", "url": "#", "title": "Company"}},
  {"op": "add", "fields": {"type": "post_type", "object_id": 42}, "parent_id": "new:0"},
  {"op": "add", "fields": {"type": "post_type", "object_id": 43}, "parent_id": "new:0"}
]
```

(`url: "#"` is the standard pattern for a non-clickable dropdown parent.)

A mega-menu — a Reusable Block shown in the dropdown of a "Shop" parent (block id 88):

```json
[
  {"op": "add", "fields": {"type": "custom", "url": "#", "title": "Shop"}},
  {"op": "add", "fields": {"type": "reusable_block", "object_id": 88}, "parent_id": "new:0"}
]
```

### update `{ id, fields }`

Partial patch — only the passed fields change. `null` clears a field (title → inherit, the rest → empty). Structure is NOT a field: passing `position` / `parent_id` here is rejected — moving an item is a `reorder`.

### remove `{ id, children? }`

Takes the item out of the menu — **the linked page / post / term itself is untouched**. `children`:
- `"reparent"` (default) — its sub-items move up into its place, order preserved;
- `"cascade"` — the whole subtree is removed.

### reorder `{ tree }`

Declarative: pass the COMPLETE target structure — every item of the menu exactly once, `{id, children?}` nodes only. This is the single way to move and re-nest existing items, which makes parent-cycles impossible by construction. Take the current tree from `upsolution-get-menu`, rearrange the ids, drop the other node fields:

```json
{"op": "reorder", "tree": [
  {"id": 10},
  {"id": 14, "children": [{"id": 12}, {"id": 18}]},
  {"id": 21}
]}
```

Items being dropped in the same call are removed with explicit `remove` ops first — `reorder` rejects a tree with missing ids rather than guessing.

## Operational facts the validator does not enforce

- Scope: the menu objects themselves — creating, renaming or deleting a menu, assigning it to a theme location — have no tools; those edits happen in wp-admin (Appearance → Menus). The items of existing menus are fully editable here.
- Any structural operation renumbers the whole menu's stored order to the depth-first sequence — that is how WordPress renders order, and it matches what the admin screen does on save.
- Items whose linked object was deleted come back from `upsolution-get-menu` with `invalid: true` — they render as dead entries. Repoint them (`update` with a new `object_id`) or `remove` them.
- Items orphaned by out-of-band edits (stored parent no longer exists) are shown at the top level and get re-attached there on the first write.
- An explicit `title` that exactly equals the linked object's current title is stored as "inherited" by WordPress itself — the item then follows future renames. Harmless, but explains a `title_inherited: true` you did not ask for.
- Hard cap: 300 items per menu.
- Depth: WordPress allows any nesting depth, but the theme's desktop dropdowns are designed for 2–3 levels — deeper levels may be hard to reach with a mouse.

## Workflow

1. `upsolution-list-menus` — pick the menu by name or slug.
2. `upsolution-get-menu` — read the current tree; all ids and positions come from here.
3. `upsolution-set-menu-items` — one call with the whole operation sequence.
4. Verify against the returned `after` tree (it is re-read from the database, not echoed input).

## Anti-patterns

- **Custom links to internal pages.** `{"type": "custom", "url": "/about/"}` breaks silently when the page slug changes. Use `{"type": "post_type", "object_id": N}` — it follows slug and title changes.
- **Explicit titles that duplicate the object's title.** Omit `title` on object-backed items instead — the label then keeps following renames.
- **Moving items via repeated remove + add.** That destroys item ids (and any classes / targets set on them). Use one `reorder` op.
- **Hardcoding navigation as links inside `us_html` or text elements** when the links belong in a menu — admins lose the ability to edit them under Appearance → Menus (same rule as in `shortcodes/additional_menu`).
- **A Reusable Block as a top-level item.** It renders nothing where a top-level label belongs — nest it under a parent so the parent's dropdown shows it.
- **A Reusable Block added as `type: "post_type"`.** A `us_page_block` id is rejected on `post_type` (it has no public URL) with a pointer to `reusable_block`. Use `type: "reusable_block"`.
- **Guessing ids.** Menu ids, item ids, page ids and term ids all come from their lookup tools (`upsolution-list-menus`, `upsolution-get-menu`, `upsolution-list-posts`, `upsolution-list-terms`) — a wrong id is either a 404 or, worse, a valid id of the wrong thing.
