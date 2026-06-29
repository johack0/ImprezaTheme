# UpSolution — Post Types Matrix

> Reference for `upsolution-list-posts` / `get-post` / `create-post` / `update-post` / `duplicate-post` / `delete-post` — all take a required `post_type` argument; there are no per-type tool variants. Tells you which `post_type` to pick, which optional input fields apply to each type (`excerpt`, `featured_image_id`, `terms`, `meta`), and which taxonomies / meta keys are accepted.

The shortcode body in `content=` follows the same rules for every type — see [composition-rules.md](composition-rules.md) and [shortcodes.md](shortcodes.md). What changes per type is the surrounding metadata.

## The six types at a glance

| `post_type`            | What it is                                                                 | Public URL? | Excerpt | Featured img            | Taxonomies                                       |
|------------------------|-----------------------------------------------------------------------------|-------------|---------|--------------------------|--------------------------------------------------|
| `page`                 | Standard WP page (landing pages, marketing pages, "About us", …)            | yes         | yes     | yes                      | —                                                |
| `post`                 | Standard WP blog post                                                       | yes         | yes     | yes                      | `category`, `post_tag`                           |
| `us_portfolio`         | Portfolio item (a "case", project, work sample)                             | yes         | yes     | yes                      | `us_portfolio_category`, `us_portfolio_tag`      |
| `us_testimonial`       | Single testimonial / quote                                                  | no (admin)  | —       | **yes — = author photo** | `us_testimonial_category`                        |
| `us_content_template`  | "Page Template" — layout assigned to single / archive / shop in Theme Opts  | no (admin)  | —       | —                        | —                                                |
| `us_page_block`        | "Reusable Block" — included by id from pages or templates                   | no (admin)  | —       | —                        | —                                                |

Optional input fields (`excerpt`, `featured_image_id`, `terms`, `meta`) sent for a type that does not support them are **silently ignored** — except `terms` / `meta` keys that fail the whitelist, which return 400.

## Page vs Content Template vs Reusable Block

These three get confused often. Quick decider:

- **`page`** — has its own URL, shown to visitors directly. Each is a one-off layout. Use this when the user asks for "an About page", "a landing page for product X", etc.
- **`us_content_template`** ("Page Templates" in admin) — a *layout* reused across many real posts. The template itself is not visited; instead it's bound to single-post / archive / shop / 404 / search layouts via **Theme Options → Pages Layout / Archives Layout / Shop Layout**. Includes special template-context shortcodes (`[us_post_content]`, `[us_post_title]`, `[us_post_taxonomy]`, …) that resolve against the loop post at render time. **Edit one template → every post that uses it re-renders with the new layout.**
- **`us_page_block`** ("Reusable Blocks" in admin) — a fragment included into other pages / templates via `[us_page_block id="N"]` or via the per-type Theme Options ("Before page content" / "After page content" / etc.). Good for repeated sections (CTAs, footers-within-content, "as featured in" strips). **Edit one block → every place it's included updates.**

If the user wants "a footer that shows on every blog post", that's a Reusable Block referenced from the single-post Content Template — not a Page.

For `us_content_template` and `us_page_block`, `status` is the inclusion gate — `publish` makes them available to the inclusion mechanisms, `draft` hides them. (Neither type has a public URL, so the difference is invisible to visitors.)

## Publication date & scheduling

`create-post` / `update-post` take an optional `date` (the post's publication time) and accept a `future` status. Together they schedule a post to go live later.

- **Schedule for later** — pass a **future** `date`. WordPress automatically switches the status to `future` and publishes the post at that time; you do not have to set `status` yourself. Setting `status: "future"` explicitly works too, but WordPress derives the real status from the date — `future` with a missing or past `date` is published immediately — so always pair `status: "future"` with a future `date`.
- **Publish now** — omit `date` (or pass a past one) with `status: "publish"` (the create default).
- **Back-date** — pass a past `date` with `status: "publish"`.

`date` is an ISO 8601 string:

| Form                                                   | Read as                                            |
|--------------------------------------------------------|----------------------------------------------------|
| `2026-07-01T09:00:00+03:00` / `2026-07-01T06:00:00Z`   | absolute instant (carries its own offset / `Z`)    |
| `2026-07-01 09:00:00` / `2026-07-01T09:00:00`          | site-local wall-clock time                         |
| `2026-07-01`                                           | site-local midnight                                |

On `update-post`, omit `date` to keep the stored publication date; pass it to move the post earlier or later. Switching a scheduled post to `status: "publish"` before its date publishes it right away; switching it to `draft` unschedules it.

`get-post` / `list-posts` return `date` (ISO 8601 GMT) next to `status`, so a `status: "future"` post shows when it will go live. List the scheduled queue with `list-posts({ status: "future" })`.

## Duplicating a post

`duplicate-post` makes a server-side copy in **one call** — you do not have to `get-post` first and re-send the body. It clones, from the source:

- `post_content`, `excerpt`, and the page attributes (parent, menu order, comment / ping status, author);
- **every** taxonomy term (all taxonomies of the type, not just the ones in the table below);
- the featured image (by reference — the same attachment, no media re-upload);
- **all** custom meta — builder data, Page Layout / SEO / Additional Settings, and any third-party custom fields. This is broader than the `create-post` / `update-post` `meta` whitelist on purpose: a duplicate is meant to be faithful.

WordPress-internal bookkeeping meta (edit locks, old-slug / old-date history, trash markers) is **not** copied.

Defaults that keep a duplicate safe and distinct:

- `status` → `draft` (a copy never publishes itself; override with `status` / schedule with a future `date`);
- `title` → `"<source title> (copy)"` (override with `title`);
- slug → a fresh unique slug derived from the title (override with `new_slug`).

```jsonc
duplicate-post({ post_type: "page", id: 451 })
// → new draft "<title> (copy)", same content + meta + terms, plus source_id: 451

duplicate-post({ post_type: "post", slug: "launch", title: "Launch v2", status: "draft" })
```

## Per-type meta whitelist

`update-post` / `create-post` accept a `meta` object whose keys are restricted per type. Sending other keys → 400. Whitelists below mirror the `config/meta-boxes.php` metabox fields for each type, minus composite fields the agent cannot reliably encode (multi-uploads, link blobs).

### Shared groups

The next two groups are reused across types — the matrix at the end shows which types get which.

#### Page Layout (`us_page_settings` metabox)

Per-post overrides to the site's Theme Options. Most values are switches or selects; the `*_id` fields are post-id references.

| Key                                  | Type             | Notes                                                                                                  |
|--------------------------------------|------------------|--------------------------------------------------------------------------------------------------------|
| `us_header_id`                       | string           | `__defaults__` (use Theme Options) / `"0"` (do not display) / id of a `us_header` post.                |
| `us_header_sticky_override`          | `0` / `1`        | Enable per-post override of Sticky Header.                                                             |
| `us_header_sticky`                   | string           | Comma-joined responsive-state slugs (e.g. `default,tablets,mobiles`) where sticky is ON.               |
| `us_header_transparent_override`     | `0` / `1`        | Enable per-post override of Transparent Header.                                                        |
| `us_header_transparent`              | string           | Comma-joined responsive-state slugs where transparent is ON.                                           |
| `us_header_shadow`                   | `0` / `1`        | `1` removes the header shadow.                                                                         |
| `us_remove_header_offset_override`   | `0` / `1`        | Enable per-post override of content offset.                                                            |
| `us_remove_header_offset`            | string           | Comma-joined responsive-state slugs where the content offset is removed.                               |
| `us_header_sticky_pos`               | string           | `""` (top) / `bottom` / `above` / `below` — initial position of the sticky header relative to content. |
| `us_titlebar_id`                     | string           | `__defaults__` (use Theme Options) / `"0"` (do not display) / id of a `us_page_block` (only if Theme Options exposes per-post titlebar). |
| `us_content_id`                      | string           | `__defaults__` (use Theme Options) / `"0"` (no Page Template) / id of a `us_content_template` ("Page Template"). |
| `us_sidebar_id`                      | string           | `__defaults__` (use Theme Options) / `"0"` (no sidebar) / sidebar slug OR `us_page_block` id, depending on Theme Options. |
| `us_sidebar_pos`                     | `left` / `right` | Sidebar position. Only meaningful when `us_sidebar_id` is set.                                         |
| `us_footer_id`                       | string           | `__defaults__` (use Theme Options) / `"0"` (do not display) / id of a `us_page_block`.                 |

Post-id references (`us_header_id`, `us_titlebar_id`, `us_content_id`, `us_footer_id`, `us_sidebar_id` when sidebars are Reusable Blocks) need an actual post id. Resolve via `list-posts` against the relevant type before writing. `us_header` is not exposed via this server — pick the id from admin or rely on `__defaults__`.

##### `*_id` value semantics — what each value does

Applies to `us_header_id`, `us_titlebar_id`, `us_content_id`, `us_sidebar_id`, `us_footer_id`:

| Value you send       | What is stored        | Effect at render                                                                                       |
|----------------------|-----------------------|---------------------------------------------------------------------------------------------------------|
| key omitted          | (unchanged)           | whatever was stored before.                                                                              |
| `null` or `""`       | **meta key deleted**  | Theme Options defaults — same as a post that never had the override.                                     |
| `"__defaults__"`     | `__defaults__`        | Theme Options defaults (explicit "no per-post override"); equivalent to a deleted key.                    |
| `"0"`                | `"0"`                 | **Do not display** this area on the post.                                                                |
| `"123"` (post id)    | `"123"`               | Use that entity. The id must be a **published** post of the right type — a draft / trashed / deleted id is silently ignored at render and the area falls back to Theme Options. |
| sidebar slug (`us_sidebar_id` only) | the slug | That registered sidebar; an unregistered slug is silently ignored.                                        |

Legacy caveat: content saved with theme versions ≤ 8.14 may store `""` in these keys, and at render an **existing** empty value also means "do not display". You cannot author that state via this server (`""` deletes the key) — send `"0"` instead.

#### SEO meta (`us_seo_settings` metabox, only when Theme Options' `og_enabled` is on)

| Key                   | Type    | Notes                                                                                       |
|-----------------------|---------|---------------------------------------------------------------------------------------------|
| `us_meta_title`       | string  | Override `<title>` for the post. Empty = use the default.                                   |
| `us_meta_description` | string  | `<meta name="description">`.                                                                |
| `us_meta_robots`      | string  | Robots directives, e.g. `noindex`, `nofollow`, `none`.                                      |
| `us_meta_itemtype`    | string  | schema.org type override, e.g. `FAQPage`, `QAPage`, `Person`. Empty = default.              |

#### Additional Settings (metabox id `us_portfolio_settings`, kept for legacy reasons)

The metabox's `post_types` come from the `additional_settings_post_types` theme option which defaults to **all public post types**. So these keys apply to `page`, `post`, and `us_portfolio` — they affect how the post is rendered as a tile inside a grid that displays it (portfolio grid, post grid, etc.).

Composite fields excluded: `us_tile_additional_image` (multi-upload, use the WP media REST API), `us_tile_link` (composite link blob).

| Key                  | Type   | Notes                                                                                  |
|----------------------|--------|----------------------------------------------------------------------------------------|
| `us_tile_icon`       | string | Icon class (e.g. `fas|star`). See [composition-rules.md §3.6](composition-rules.md#36-icon--icon-picker) for the icon picker spec. |
| `us_tile_size`       | string | One of `1x1` (default), `2x1`, `1x2`, `2x2`. Custom tile size in masonry / grid grids. |
| `us_tile_bg_color`   | string | Hex / rgba.                                                                            |
| `us_tile_text_color` | string | Hex / rgba.                                                                            |

For `us_portfolio`, the portfolio gallery image is the **featured image** — set via `featured_image_id`, not `meta`.

### Per-type meta

#### `us_testimonial` (`us_testimonials_settings`)

Composite field excluded: `us_testimonial_link` (link blob).

| Key                      | Type   | Notes                                                              |
|--------------------------|--------|--------------------------------------------------------------------|
| `us_testimonial_author`  | string | Author's name (e.g. "John Doe").                                   |
| `us_testimonial_role`    | string | Author's role / job title.                                         |
| `us_testimonial_company` | string | Author's company.                                                  |
| `us_testimonial_rating`  | string | One of `none`, `1`, `2`, `3`, `4`, `5`. Renders the star rating.   |

The testimonial body is the **post content** — write the quote as shortcode markup. The author **photo** is the **featured image** (`featured_image_id`).

#### `us_content_template` (`us_content_template_settings`)

| Key                              | Type      | Notes                                                                      |
|----------------------------------|-----------|----------------------------------------------------------------------------|
| `us_header_transparent_override` | `0` / `1` | Enable transparent-header override for posts that use this Page Template.  |
| `us_header_transparent`          | string    | Comma-joined responsive-state slugs where transparent is ON.               |

### Per-type whitelist matrix

| `post_type`           | Page Layout | SEO meta | Additional Settings | Type-specific                        |
|-----------------------|:-----------:|:--------:|:-------------------:|--------------------------------------|
| `page`                | ✓           | ✓        | ✓                   | —                                    |
| `post`                | ✓           | ✓        | ✓                   | —                                    |
| `us_portfolio`        | ✓           | ✓        | ✓                   | —                                    |
| `us_testimonial`      | —           | —        | —                   | Testimonial author fields            |
| `us_content_template` | —           | —        | —                   | Transparent-header override (2 keys) |
| `us_page_block`       | —           | —        | —                   | — (no per-post meta)                 |

## Assigning taxonomy terms

`terms` is `{ taxonomy_slug: [term_id, …] }`. The taxonomy slug **must** match one registered for the post type (see the table above) — unknown taxonomy keys return 400.

You almost never know term ids up front. Use **`upsolution-list-terms`** first:

```jsonc
// 1. Resolve "Web Design" → id
list-terms({ taxonomy: "us_portfolio_category", search: "Web Design" })
// → [{ id: 17, name: "Web Design", slug: "web-design", parent: 0, count: 12 }]

// 2. Use it
update-post({
  post_type: "us_portfolio",
  id: 451,
  terms: { us_portfolio_category: [17] }
})
```

Passing `terms` **replaces** the existing assignment for that taxonomy (there is no "append" mode — to add a term, send the full desired list). Omit a taxonomy key to leave its assignments untouched. Send `[]` to clear all terms in a taxonomy.

If the term you need does not exist yet, call **`upsolution-create-term`** (`{ taxonomy, name, slug?, description?, parent? }` → same id/name/slug/parent/count shape as `list-terms`). To remove a term entirely, **`upsolution-delete-term`** (`{ taxonomy, id }`) — note this is a hard delete, not a trash.

## Featured image

`featured_image_id` is an attachment id (from WP's media library). This server does **not** upload media — use the standard `POST /wp-json/wp/v2/media` REST endpoint outside MCP for that, then pass the resulting id here.

- `featured_image_id: 1234` — set the thumbnail to attachment 1234.
- `featured_image_id: null` (or `0`) — clear the thumbnail.
- Omit the key entirely — leave the thumbnail untouched.

`get-post` returns both `featured_image_id` (int|null) and `featured_image_url` (string|null) for types that support thumbnails.
