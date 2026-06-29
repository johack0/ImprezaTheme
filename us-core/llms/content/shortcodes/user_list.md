---
title: `us_user_list` — User List
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/user_list.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/user_list.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=user_list
  Direct edits here will be lost on the next regeneration.
-->

# `us_user_list` — User List

**When to use**: a grid of registered WordPress users rendered through a user-type Grid Layout — "Our team" page, contributor index, author directory. Cards typically show avatar / display name / role / bio / a link to the author archive.

**Avoid when**:
- you want a curated team page with hand-written titles and photos — use `us_person` cards inside a row, those are not bound to the WP users table;
- you want a horizontal slider — use `us_user_carousel`;
- you want the author of a single post — use `us_post_author` (a different element, out of scope here).

**Key parameters**

**Source**

| Param | What it does |
|-------|--------------|
| `source` | `all` (default), `include` (selected user IDs), `exclude`, `role__in` (selected roles), `role__not_in`, `current_post_author` (author of the post being viewed). |
| `user_ids` | Comma-separated user IDs for `source="include"` / `"exclude"`. |
| `role` | Comma-separated role slugs (`administrator`, `editor`, `author`, `subscriber`, `customer`, …) for `source="role__in"` / `"role__not_in"`. Default `administrator`. |
| `has_published_posts` | `1` keeps only users with at least one published post (any post type). |
| `exclude_current` | `1` excludes the currently viewed author archive's user. |

**Order & quantity**

| Param | What it does |
|-------|--------------|
| `orderby` | `display_name` (default), `post_count`, `registered`, `rand`, `include` (preserves `user_ids` order), `custom`. |
| `orderby_custom_field` / `orderby_custom_type` | Custom user-meta key + `1` to sort numerically. |
| `order_invert` | `1` flips direction. |
| `show_all` | `1` ignores `number`. |
| `number` | Max users, 1–30, default `12`. |

**Custom-field filter**: `meta_query_relation` (`none` / `AND` / `OR`) + `meta_query` JSON, same shape as `us_post_list`. The keys are entries from the `wp_usermeta` table, not post meta.

**No-results**: `no_items_action` (`message` / `hide_grid` — _no_ `page_block` option here) + `no_items_message`.

**Appearance**

| Param | What it does |
|-------|--------------|
| `items_layout` | Grid Layout id — restricted to `user`-type layouts. Default `user_1`. |
| `columns` | 1–10, default `3`. |
| `items_gap` | Gap. Default `10px`. |
| `load_animation` | Same enum as `us_post_list`. |
| `overriding_link` | Wraps the card in a link. Dynamic options are smaller than for posts — typically a custom URL or a popup; there is no built-in "author archive" dynamic value. |
| `popup_width` / `popup_arrows` | Popup behaviour when `overriding_link` opens a popup. |

**Notes**

- There are no `type` (grid/masonry/metro) or `img_aspect_ratio` / `item_aspect_ratio` params on `us_user_list` (unlike the post / product / term lists) — the layout is always a plain grid and aspect ratio comes from the chosen Grid Layout itself.

**Minimal example**

```text
[us_user_list source="role__in" role="editor,author" columns="3"]
```

**Common combinations**

"Our team" page — selected users, fixed order, 4 columns:

```text
[us_user_list source="include" user_ids="12,7,18,4,9"
              orderby="include" columns="4" items_layout="user_1"]
```

Active contributors only (one published post and up):

```text
[us_user_list source="role__in" role="author,contributor"
              has_published_posts="1"
              orderby="post_count" order_invert="1"
              number="12" columns="3"]
```

**Anti-patterns**

- Using this for hand-crafted team bios where most "team members" aren't WP users — the directory will be incomplete; use `us_person` blocks instead.
- Pairing with `us_list_filter` / `us_list_order` — those are wired to post lists (they emit URL params consumed by `WP_Query`); they will not drive a `WP_User_Query`.
- `no_items_action="page_block"` — not available on user lists; use `message` or `hide_grid`.
