---
title: `us_add_to_favs` — "Add to Favorites" Button
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/add_to_favs.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/add_to_favs.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=add_to_favs
  Direct edits here will be lost on the next regeneration.
-->

> **Section context — where to use these.**
>
> Every shortcode in this section is **designed for Page Templates** (`us_content_template`). See [post-types.md](post-types.md) for the Page vs Template distinction.
>
> Each element looks up its value on the **current post in the loop**:
>
> - on a single post, page or CPT — the post being viewed;
> - on an archive / search / Grid Layout card — the post for the current loop iteration;
> - on a term archive — the term being viewed (the title / content elements adapt to term data).
>
> Using them **outside a Page Template** (e.g. dropped directly into the body of a regular page or post) is **not forbidden** — the parser will run them — but rarely useful: the "current post" resolves to the hosting page itself, so `[us_post_title]` on a page named "About" just outputs "About", `[us_post_taxonomy]` outputs that page's terms (usually none), and `[us_post_navigation]` walks the page hierarchy instead of a post sequence. Reach for these only when you know which post the loop will resolve to and that's the value you want surfaced.
>
> The one element with a hard gate is `us_post_content` — the builder UI only exposes it when editing a `us_content_template`. The rest is technically droppable anywhere; the guidance above is editorial, not enforced.

# `us_add_to_favs` — "Add to Favorites" Button

**When to use**: a per-post "Add to Favorites" / "In Favorites" toggle that stores the post in the visitor's favourites list (in `localStorage` for guests, in user meta when logged in — controlled by the `us_allow_guest_favs` PHP filter at the site level). Typical placement: blog / shop / portfolio card meta row, single-post hero, or a dedicated "Save" button under the title. Pair with `us_post_list` / `us_product_list` using `source="user_favorite_ids"` to render the saved list on a "My favorites" page.

**Avoid when**:
- the site has no concept of personal collections (a single-page brochure, a campaign LP) — the button has no destination;
- you want a Like / Vote / Reaction count — out of scope; this is a personal-saves toggle, not a public counter;
- the page isn't bound to a post object — the button has no target to favourite.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `style` | Button style. `0` (default) — the theme's default button style; or a numeric key from Theme Options → Buttons (`"1"`, `"2"`, …). |
| `show_icon` | `1` (default) shows the heart icon next to the label. `0` for label-only. |
| `label_before_adding` | Label when the post is *not yet* in favourites. Default "Add to Favorites". |
| `label_after_adding` | Label when the post *is* in favourites. Default "In Favorites". |
| `message_after_adding` | Optional toast message shown after a successful add. Supports `<a>`, `<strong>`, `<br>`. Dynamic-value tokens allowed. Leave blank to skip the toast. |
| `message_for_non_registered` | Toast for guest visitors when `us_allow_guest_favs` is filtered to `false` site-wide. Default "Please log in to add items to your favorites." Only present in the schema when guest favourites are disabled. |

**Minimal example**

```text
[us_add_to_favs]
```

**Common combinations**

Card meta — compact icon-only "save" toggle:

```text
[us_add_to_favs style="0"
                show_icon="1"
                label_before_adding=""
                label_after_adding=""]
```

Hero CTA next to the post title:

```text
[us_add_to_favs style="2"
                show_icon="1"
                label_before_adding="Save for later"
                label_after_adding="Saved"
                message_after_adding="Added to your favorites. <a href='/my-favorites/'>View list</a>"]
```

Guest-disabled site — pushes visitors to log in:

```text
[us_add_to_favs label_before_adding="Save"
                message_for_non_registered="Please <a href='/login/'>log in</a> to save items."]
```

**Anti-patterns**

- Empty label *and* `show_icon="0"` — the button renders as a 0×0 blank; visitors can't see it.
- `message_after_adding` with full HTML (`<div>`, `<p>`, `<strong style="…">`) — only `<a>`, `<strong>`, `<br>` survive sanitisation. Use inline text + one anchor.
- Adding this on a page that isn't bound to a single post — search results, the blog index, a hand-built "About" page — there's no meaningful "current post" to save; visitors end up favouriting whatever object WordPress happens to set up at render time (typically the host page itself), which is rarely the intent. The canonical placement is inside a Page Template (`us_content_template`) wired to single-post / single-product / single-CPT layouts, or a Grid Layout card where each iteration has a real post in the loop.
- Expecting the favourites list to persist across browsers for *guests* — it lives in `localStorage`. Only logged-in users get cross-device persistence.
