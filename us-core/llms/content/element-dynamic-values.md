---
title: Dynamic Values
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (hybrid: prose from manual overlay, per-shortcode matrix auto-extracted)
---

<!-- GENERATED — do not edit directly. Rebuild: php scripts/llms/build.php --only=dynamic-values -->

# Dynamic Values

> Dynamic values are placeholders the renderer replaces with **per-page, per-post, per-user, or per-site data** at output time — so a shortcode written once in a Page Template (`us_content_template`) can produce a different result on every post the template is assigned to.
>
> Two unrelated subsystems share the name. Knowing which one a parameter belongs to is everything:
>
> | Subsystem | Used in | Replacement syntax | Resolved by |
> |---|---|---|---|
> | **Text tokens** | text / textarea / number / image-id / icon params marked `dynamic_values=TRUE` (or with a `for_text`/`for_image`/`for_textarea` family in `config/dynamic-values.php`) | `{{token}}` substrings inside the attribute value | `us_replace_dynamic_value()` in `plugins/us-core/functions/helpers.php` — runs on the rendered output, substring-replacing every `{{…}}` it recognises |
> | **Link enums** | every parameter of `type=link` whose config sets `dynamic_values`. The value is a URL-encoded JSON document (`{"type":"post","url":"","target":"_self",…}`), and the agent picks `type` from a per-param enum | the JSON's `type` key — the link builder swaps it for the resolved URL at render time | `us_link_to_dynamic_value()` and the link picker UI |
>
> The two subsystems do **not** mix. You cannot put `{{the_title}}` inside a `link` JSON's `url` field expecting it to resolve to the current post — for that you use `"type":"post"` (the link-enum form). Conversely, the link-enum keys like `homepage`, `post`, `author_page` are **not** valid `{{token}}` substitutions in a text field — they only mean something inside a `link` JSON's `type`.

## Text tokens

These work inside any param marked `dynamic_values=TRUE` or assigned to the `for_text` / `for_textarea` family. The renderer replaces each `{{…}}` substring with the resolved value; non-token text passes through unchanged, so they freely mix with literal copy (`Welcome to {{site_title}}!`).

### Global tokens

| Token | Resolves to |
|---|---|
| `{{site_title}}` | The WordPress "Site Title" option (`get_bloginfo('name')`). |
| `{{site_icon}}` | The Site Icon's media ID (suitable for image-ID params). |
| `{{post_count}}` | Total number of published posts (`wp_count_posts()->publish`). |
| `{{user_count}}` | Total registered users (`count_users()['total_users']`). |
| `{{favs_count}}` | How many posts the current visitor has marked as favourites (works with the `us_add_to_favs` flow). |
| `{{current_id}}` | The current object's ID (post, term, or user — whichever `us_get_current_id()` resolves to). |

### Current-post tokens

Resolve against the post in the current loop iteration — usually meaningful only inside a Page Template / Reusable Block / Grid Layout card.

| Token | Resolves to |
|---|---|
| `{{the_title}}` | The current post's title (same value `us_post_title` renders). |
| `{{the_thumbnail}}` | The current post's featured-image media ID (suitable for image-ID params). |
| `{{post_type_singular}}` | The post type's singular label (e.g. "Post", "Product"). |
| `{{post_type_plural}}` | The post type's plural label (e.g. "Posts", "Products"). |
| `{{comment_count}}` | Approved comment count for the current post. |
| `{{us_tile_additional_image}}` | The post's "Additional Images" gallery (theme-provided meta — used as image source). |
| `{{_product_image_gallery}}` | WooCommerce product-gallery image IDs (only when WooCommerce is active). |

### Current-term tokens

| Token | Resolves to |
|---|---|
| `{{taxonomy_label_singular}}` | Singular label of the term's taxonomy ("Category", "Tag"). |
| `{{taxonomy_label_plural}}` | Plural label of the term's taxonomy. |
| `{{tax|<taxonomy-slug>}}` | Comma-separated names of the terms the current post has in the given taxonomy. Separator is filterable via `us_replace_dynamic_value_term_separator`. Example: `{{tax|category}}`, `{{tax|product_cat}}`. |

### Date tokens

| Token | Resolves to |
|---|---|
| `{{today}}` | Current date as `Ymd` (e.g. `20260526`) — for comparing with ACF date-pickers. |
| `{{today_now}}` | Current date-time as `YmdHis`. |
| `{{now}}` | Current time as `His`. |
| `{{date|<format>}}` | Current date / time formatted with a PHP `date()` format string. Example: `{{date|F j, Y}}` → "May 26, 2026". The format string accepts letters, digits, `|`, `/`, `\`, `-`, `_`, `:`, `.`, `,` and spaces. |

### Current-user tokens

`{{user|<user_option_key>}}` reads a single value from the currently logged-in user's profile via `get_user_option()`. Returns an empty string for guests. The keys `user_pass` and `user_activation_key` are blocked. Common keys: `first_name`, `last_name`, `display_name`, `nickname`, `user_email`, `description`. Example: `Hello, {{user|first_name}}!`.

### Custom-field tokens

`{{<any_post_meta_key>}}` falls through to `us_get_custom_field()`, which reads the current post's meta. Use this for raw `wp_postmeta` keys *and* for ACF field names (the resolver handles ACF formatting via the `$acf_format` flag).

- **ACF group sub-fields**: address with `{{parent_group_field/sub_field}}` (slash-separated path).
- **ACF gallery / multi-value fields**: the resolver flattens an array of scalars into a comma-separated string (so `{{gallery_field}}` yields `12,34,675` — usable directly as an image-IDs list).
- **ACF image / file fields**: the resolver returns the attachment ID (suitable for image-ID params).
- **Empty / non-scalar values**: resolved to an empty string — the token disappears from the output.

### Filter hooks

Two filters let plugins extend the system without forking us-core:

- `us_replace_dynamic_value` — `apply_filters('us_replace_dynamic_value', $match, $current_id)`. Return a non-default string to short-circuit the built-in handlers. Use this to register custom `{{my_thing}}` tokens.
- `us_replace_dynamic_value_thumbnail` — filters the resolved `{{the_thumbnail}}` ID.
- `us_replace_dynamic_value_term_separator` — filters the separator used by `{{tax|<slug>}}` (default `', '`).

## Link enums

Inside any `type=link` parameter the agent stores a URL-encoded JSON object. The `type` key picks from a **per-param enum** declared in the shortcode's config under `dynamic_values`. The link builder resolves `type` to a real URL at render time.

The base JSON shape is `{"type":"<enum-key>","url":"<custom-url>","target":"_self|_blank","rel":"…","title":"…"}` — when `type` is set the resolver ignores `url`; when `type` is unset (or `"url"`), the literal `url` field is used.

### Universal enum keys

The four "always available" enum keys (no per-shortcode config needed):

| Key | Resolves to |
|---|---|
| `url` (or unset `type`) | The literal `url` field of the JSON. The default for a hand-typed link. |
| `popup_post` | Opens the current item's full page in a modal popup (used by list / carousel `overriding_link` to preview entries without a hard navigation). |
| `popup_image` | Opens the current item's featured image in a lightbox. |
| `custom_field|<meta_key>` | Uses the value of the named custom field as the URL. Common variants: `custom_field|us_tile_link` (theme's "Additional Settings → Custom Link"), `custom_field|us_testimonial_link`, any ACF URL / page-link / post-object field. |

### Predefined family keys (from `config/dynamic-values.php`)

These are offered by params whose `dynamic_values` is one of the predefined family arrays (`global` / `post` / `user`):

| Family | Keys |
|---|---|
| `global` | `homepage` — the site's front page (`home_url()`). |
| `post` | `post` — the current post's permalink. `custom_field|us_tile_link`, `custom_field|us_testimonial_link` — convenience entries. |
| `user` | `author_page` — the WP author-archive URL. `author_website` — the URL the author entered in their profile (empty when not set). |
| `term` | `archive` — the term-archive page URL. |

### Per-param enums

Most `link` params layer additional keys on top of the base set. Common per-element extensions:

- `us_post_title.link` — adds `elm_value` (turn a title that *looks* like an email / phone / URL into a `mailto:` / `tel:` / external link).
- `us_post_comments.link` — adds `post_comments` (jump-anchor to the post's comments section).
- `us_post_taxonomy.link` — restricts to the term family (`archive`).
- `us_image.link` / `us_post_image.link` — adds `popup_image`.
- `us_post_list.overriding_link` / `us_post_carousel.overriding_link` / `us_product_*` — adds `popup_post` and (for posts) `popup_image`; **all inner card links become non-clickable**.

The per-shortcode matrix below lists every `link` param with its full enum.

## Param-type → subsystem cheat sheet

| Param type | Subsystem | Notes |
|---|---|---|
| `text` / `textarea` / `text_html` | Text tokens | `{{…}}` substitution; mix freely with literal copy. |
| `link` | Link enums | URL-encoded JSON; pick `type` from the enum below. |
| `upload` / `image` / `images` | Text tokens (image flavour) | Accepts a media ID, comma-separated IDs, or an image-yielding token like `{{the_thumbnail}}` / `{{site_icon}}` / `{{us_tile_additional_image}}` / `{{_product_image_gallery}}` / `{{<acf_image_field>}}`. |
| `icon` | Text tokens (rare) | Most icon params take a static `<style>|<name>` — only the few that set `dynamic_values=TRUE` (e.g. some form-field icons) accept `{{…}}`. |
| Anything else | — | Static value only. No replacement happens. |

## Conventions when authoring shortcode markup

1. **Use text tokens in text params**, link enums in link params — never cross over.
2. **Spaces inside tokens are not allowed**. `{{ the_title }}` is a literal string the resolver does not recognise. Use `{{the_title}}` exactly.
3. **`{{date|<format>}}` uses PHP `date()` codes**, not the format-string fragments shown in WP admin. `F j, Y` → "May 26, 2026"; `Y-m-d` → "2026-05-26".
4. **Empty tokens vanish silently** — the resolver returns `''` for unknown / not-applicable values, so `[us_text text="By {{user|first_name}}"]` becomes `By ` for guest visitors. Pair sensitive uses with Display Logic.
5. **Quoting in shortcode attributes**: tokens contain only safe characters, so they don't need URL-encoding inside attribute values. They DO survive the `wptexturize` pass — no special escaping required. (`{{` and `}}` are not converted to typographic curly quotes.)
6. **Link JSON must be URL-encoded** as usual for the `link` attribute family — see [composition-rules §3.1](composition-rules.md#31-link-picker-link).

## Per-shortcode matrix

> Every in-scope (shortcode, parameter) pair that opts into dynamic values, grouped by manifest category. Auto-extracted from `plugins/us-core/config/elements/<id>.php` at build time — re-run the generator after adding new params.

### Containers

| Shortcode | Parameter | Type | Subsystem |
|-----------|-----------|------|-----------|
| `[vc_row]` | `us_bg_video` | `text` | Text token |
| `[vc_row]` | `us_bg_slider_ids` | `upload` | Text token (image / media-ID) |
| `[vc_column]` | `link` | `link` | Link enum |
| `[vc_column_inner]` | `link` | `link` | Link enum |
| `[us_hwrapper]` | `link` | `link` | Link enum |
| `[us_vwrapper]` | `link` | `link` | Link enum |
| `[vc_tta_section]` | `title` | `text` | Text token |
| `[vc_tta_section]` | `tab_link` | `link` | Link enum |

### Basic

| Shortcode | Parameter | Type | Subsystem |
|-----------|-----------|------|-----------|
| `[us_text]` | `text` | `text` | Text token |
| `[us_text]` | `link` | `link` | Link enum |
| `[us_btn]` | `label` | `text` | Text token |
| `[us_btn]` | `link` | `link` | Link enum |
| `[us_iconbox]` | `img` | `upload` | Text token (image / media-ID) |
| `[us_iconbox]` | `link` | `link` | Link enum |
| `[us_iconbox]` | `title` | `text` | Text token |
| `[us_image]` | `image` | `upload` | Text token (image / media-ID) |
| `[us_image]` | `link` | `link` | Link enum |
| `[us_separator]` | `text` | `text` | Text token |
| `[us_separator]` | `link` | `link` | Link enum |

### Interactive

| Shortcode | Parameter | Type | Subsystem |
|-----------|-----------|------|-----------|
| `[us_gallery]` | `ids` | `upload` | Text token (image / media-ID) |
| `[us_gallery]` | `pagination_btn_text` | `text` | Text token |
| `[us_gallery]` | `items_link` | `link` | Link enum |
| `[us_image_slider]` | `ids` | `upload` | Text token (image / media-ID) |
| `[us_counter]` | `final` | `text` | Text token |
| `[us_counter]` | `title` | `text` | Text token |
| `[us_countdown_timer]` | `expired_message` | `html` | Text token (html) |
| `[us_flipbox]` | `front_title` | `text` | Text token |
| `[us_flipbox]` | `front_desc` | `textarea` | Text token |
| `[us_flipbox]` | `front_bgimage` | `upload` | Text token (image / media-ID) |
| `[us_flipbox]` | `front_icon_image` | `upload` | Text token (image / media-ID) |
| `[us_flipbox]` | `back_title` | `text` | Text token |
| `[us_flipbox]` | `back_desc` | `textarea` | Text token |
| `[us_flipbox]` | `back_bgimage` | `upload` | Text token (image / media-ID) |
| `[us_flipbox]` | `link` | `link` | Link enum |
| `[us_flipbox]` | `btn_label` | `text` | Text token |
| `[us_ibanner]` | `image` | `upload` | Text token (image / media-ID) |
| `[us_ibanner]` | `title` | `text` | Text token |
| `[us_ibanner]` | `desc` | `textarea` | Text token |
| `[us_ibanner]` | `link` | `link` | Link enum |
| `[us_itext]` | `texts` | `textarea` | Text token |
| `[us_message]` | `content` | `textarea` | Text token |
| `[us_popup]` | `title` | `text` | Text token |
| `[us_popup]` | `btn_label` | `text` | Text token |
| `[us_popup]` | `image` | `upload` | Text token (image / media-ID) |
| `[us_progbar]` | `count` | `text` | Text token |
| `[us_progbar]` | `final_value` | `text` | Text token |
| `[us_progbar]` | `title` | `text` | Text token |

### Other

| Shortcode | Parameter | Type | Subsystem |
|-----------|-----------|------|-----------|
| `[us_cform]` | `button_text` | `text` | Text token |
| `[us_cform]` | `success_message` | `html` | Text token (html) |
| `[us_cform]` | `redirect_url` | `text` | Text token |
| `[us_cform]` | `popup_selector` | `text` | Text token |
| `[us_cform]` | `email_subject` | `text` | Text token |
| `[us_cform]` | `email_message` | `html` | Text token (html) |
| `[us_cform]` | `receiver_email` | `text` | Text token |
| `[us_cform]` | `bcc_email` | `text` | Text token |
| `[us_cform]` | `reply_to` | `text` | Text token |
| `[us_cform]` | `auto_respond_subject` | `text` | Text token |
| `[us_cform]` | `auto_respond_message` | `html` | Text token (html) |
| `[us_cta]` | `btn_label` | `text` | Text token |
| `[us_cta]` | `btn_link` | `link` | Link enum |
| `[us_cta]` | `btn2_label` | `text` | Text token |
| `[us_cta]` | `btn2_link` | `link` | Link enum |
| `[us_dropdown]` | `link_title` | `text` | Text token |
| `[us_person]` | `image` | `upload` | Text token (image / media-ID) |
| `[us_person]` | `image_hover` | `upload` | Text token (image / media-ID) |
| `[us_person]` | `name` | `text` | Text token |
| `[us_person]` | `role` | `text` | Text token |
| `[us_person]` | `content` | `textarea` | Text token |
| `[us_person]` | `link` | `link` | Link enum |
| `[us_person]` | `custom_link` | `link` | Link enum |
| `[vc_video]` | `link` | `text` | Text token |
| `[vc_video]` | `overlay_image` | `upload` | Text token (image / media-ID) |
| `[us_contacts]` | `address` | `text` | Text token |
| `[us_contacts]` | `phone` | `text` | Text token |
| `[us_contacts]` | `fax` | `text` | Text token |
| `[us_contacts]` | `email` | `text` | Text token |
| `[us_gmaps]` | `marker_address` | `text` | Text token |
| `[us_gmaps]` | `marker_text` | `html` | Text token (html) |
| `[us_gmaps]` | `map_style_json` | `html` | Text token (html) |
| `[us_user_data]` | `link` | `link` | Link enum |
| `[us_user_data]` | `text_before` | `text` | Text token |
| `[us_user_data]` | `text_after` | `text` | Text token |

### Lists

| Shortcode | Parameter | Type | Subsystem |
|-----------|-----------|------|-----------|
| `[us_post_list]` | `attachment_ids` | `upload` | Text token (image / media-ID) |
| `[us_post_list]` | `pagination_btn_text` | `text` | Text token |
| `[us_post_list]` | `overriding_link` | `link` | Link enum |
| `[us_product_list]` | `pagination_btn_text` | `text` | Text token |
| `[us_product_list]` | `overriding_link` | `link` | Link enum |
| `[us_term_list]` | `overriding_link` | `link` | Link enum |
| `[us_user_list]` | `overriding_link` | `link` | Link enum |

### Post Elements

| Shortcode | Parameter | Type | Subsystem |
|-----------|-----------|------|-----------|
| `[us_post_image]` | `link` | `link` | Link enum |
| `[us_post_title]` | `link` | `link` | Link enum |
| `[us_post_custom_field]` | `link` | `link` | Link enum |
| `[us_post_custom_field]` | `text_before` | `text` | Text token |
| `[us_post_custom_field]` | `text_after` | `text` | Text token |
| `[us_post_date]` | `text_before` | `text` | Text token |
| `[us_post_date]` | `text_after` | `text` | Text token |
| `[us_post_taxonomy]` | `link` | `link` | Link enum |
| `[us_post_taxonomy]` | `text_before` | `text` | Text token |
| `[us_post_taxonomy]` | `text_after` | `text` | Text token |
| `[us_post_author]` | `link` | `link` | Link enum |
| `[us_post_comments]` | `link` | `link` | Link enum |
| `[us_post_views]` | `text_before` | `text` | Text token |
| `[us_post_views]` | `text_after` | `text` | Text token |
| `[us_breadcrumbs]` | `home` | `text` | Text token |
| `[us_add_to_favs]` | `message_after_adding` | `textarea` | Text token |
| `[us_add_to_favs]` | `message_for_non_registered` | `textarea` | Text token |
| `[us_event_date]` | `text_before` | `text` | Text token |
| `[us_event_date]` | `text_after` | `text` | Text token |

## Link param enums

> The `type` key of each `link` parameter selects from one of these per-param enums. Every entry below is the value you put in the JSON's `"type"` slot. Universal keys (`url`, `popup_post`, `popup_image`, `custom_field|<key>`) are described in the intro and not repeated per param.

**Accept all predefined families (post / global / user / term) with no restriction beyond the universal keys:**

- `[vc_column]` → `link`
- `[vc_column_inner]` → `link`
- `[us_hwrapper]` → `link`
- `[us_vwrapper]` → `link`
- `[vc_tta_section]` → `tab_link`
- `[us_separator]` → `link`
- `[us_flipbox]` → `link`
- `[us_ibanner]` → `link`
- `[us_cta]` → `btn_link`
- `[us_cta]` → `btn2_link`
- `[us_post_author]` → `link`

### `[us_text]` → `link`

**Enum:**
  - **global**: `homepage` (Homepage), `elm_value` (Clickable value (email, phone, website))

### `[us_btn]` → `link`

**Enum:**
  - **global**: `homepage` (Homepage), `elm_value` (Clickable value (email, phone, website))

### `[us_iconbox]` → `link`

**Enum:**
  - **global**: `homepage` (Homepage), `elm_value` (Clickable value (email, phone, website))

### `[us_image]` → `link`

**Enum:**
  - **global**: `homepage` (Homepage), `popup_image` (Open Image in a Popup)

### `[us_gallery]` → `items_link`

**Enum:**
  - **media**: `custom_field|us_attachment_link` (Custom Link)
  - universal keys only in: **global**, **post**, **term**, **user**

### `[us_person]` → `link`

**Enum:**
  - **global**: `homepage` (Homepage), `popup_image` (Open Image in a Popup)

### `[us_person]` → `custom_link`

**Enum:**
  - **global**: `homepage` (Homepage), `popup_image` (Open Image in a Popup)

### `[us_user_data]` → `link`

**Enum:**
  - **global**: `elm_value` (Clickable value (email, phone, website))
  - universal keys only in: **post**

### `[us_post_list]` → `overriding_link`

**Enum:**
  - **post**: `post` (Post Link), `popup_post` (Open Post Page in a Popup), `popup_image` (Open Post Image in a Popup), `custom_field|us_tile_link` (Additional Settings: Custom Link)
  - **media**: `custom_field|us_attachment_link` (Custom Link)

### `[us_product_list]` → `overriding_link`

**Enum:**
  - **post**: `post` (Product Link), `popup_post` (Open Product Page in a Popup), `popup_image` (Open Product Image in a Popup), `custom_field|us_tile_link` (Additional Settings: Custom Link)

### `[us_term_list]` → `overriding_link`

**Enum:**
  - **term**: `post` (Archive Page), `popup_post` (Open Archive Page in a Popup), `custom_field|us_tile_link` (Additional Settings: Custom Link)
  - universal keys only in: **post**, **user**

### `[us_user_list]` → `overriding_link`

**Enum:**
  - universal keys only in: **global**, **post**, **term**

### `[us_post_image]` → `link`

**Enum:**
  - **global**: `homepage` (Homepage), `popup_image` (Open Image in a Popup)

### `[us_post_title]` → `link`

**Enum:**
  - **global**: `homepage` (Homepage), `elm_value` (Clickable value (email, phone, website))

### `[us_post_custom_field]` → `link`

**Enum:**
  - **global**: `homepage` (Homepage), `elm_value` (Clickable value (email, phone, website)), `popup_image` (Open Image in a Popup)

### `[us_post_taxonomy]` → `link`

**Enum:**
  - **term**: `archive` (Archive Page)

### `[us_post_comments]` → `link`

**Enum:**
  - **post**: `post` (Post Link), `post_comments` (Post Comments), `custom_field|us_tile_link` (Additional Settings: Custom Link)

