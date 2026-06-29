---
title: `us_post_content` — Post Content
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_content.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_content.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_content
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

# `us_post_content` — Post Content

**When to use**: outputs the body of the current post in a templated layout — a Page Template (`us_content_template` post type) for single posts / pages / CPTs / archives, or a custom archive layout. The element renders the post's excerpt, a trimmed slice, or the full content with optional "Show More" toggle.

**Avoid when**:
- you're authoring a regular page and just want some prose — write directly in a `vc_column_text` or `us_text`; this element re-renders the *parent* post's content and on a normal page that would be the page itself (an infinite loop in some setups, plus a stripped paragraph wrapper);
- you want short, hand-curated text for a hero / CTA — use `us_text`;
- you want to display the *term* description on a taxonomy archive — `us_post_content` also handles that case automatically when the rendering context is a term archive (it falls back to `term_description` from the dummy data shape), but for a hand-built term page you'd be better off using `us_text`.

**Context gate**: the builder UI only exposes this element when editing a `us_content_template`. The shortcode tag itself works anywhere, but its output depends on the loop context — outside the canonical Page Template, the result is whichever post WordPress thinks is "current" at render time.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `type` | What to render — `excerpt_content` (default — excerpt, fall back to a trimmed slice of the content), `excerpt_only` (excerpt or nothing), `part_content` (force a trimmed slice regardless of excerpt), `full_content`. |
| `excerpt_length` | Word count for excerpt modes. `0` (default) keeps the full excerpt with HTML tags; any positive value strips HTML and trims to N words. |
| `length` | Word count for the trimmed content modes (`excerpt_content` fallback, `part_content`). Default `30`. HTML, line breaks and shortcodes are stripped. |
| `remove_rows` | Only meaningful for `type="full_content"`. `''` (default — keep the post's own row structure), `1` (strip every `vc_row` / `vc_column` *inside* the post content — useful when the content is already inside another layout grid), `parent_row` (strip the `vc_row` *containing* this element — for hero overlays). |
| `force_fullwidth_rows` | `1` stretches preserved rows to full viewport width. Ignored when `remove_rows="1"`. |
| `show_more_toggle` | `1` clips long content and shows a "Show More" link. |
| `show_more_toggle_height` | Visible-content height when clipped. Default `200px`. Px only. |
| `show_more_toggle_text_more` / `show_more_toggle_text_less` | Link labels (defaults "Show More" / "Show Less"). Leave `_less` blank to make the expansion one-way. |
| `show_more_toggle_alignment` | `none` (default), `left`, `center`, `right`. |

**Minimal example**

```text
[us_post_content type="full_content"]
```

**Common combinations**

Excerpt with 25-word cap, used in a card grid Page Template:

```text
[us_post_content type="excerpt_content" excerpt_length="25" length="25"]
```

Hero block above the single-post layout — strip the row this element sits in so the post's own rows can take over the page:

```text
[us_post_content type="full_content" remove_rows="parent_row"]
```

Long single-post body with a 400-px clip and a Show More toggle:

```text
[us_post_content type="full_content"
                 show_more_toggle="1"
                 show_more_toggle_height="400px"
                 show_more_toggle_text_more="Read more"
                 show_more_toggle_text_less="Collapse"]
```

**Anti-patterns**

- Dropping this on a regular page expecting it to render *other* content — it always resolves to the **current** post; use `us_page_block` to embed a different reusable block, or `[us_post_content]` inside a Page Template that's *assigned* to a layout.
- `remove_rows="1"` together with `force_fullwidth_rows="1"` — `force_fullwidth_rows` is automatically hidden when rows are stripped; the attribute is silently ignored.
- `excerpt_length="0"` on `excerpt_content` — fine when you control the excerpt, but on posts without an excerpt the *content* fallback inherits `length`, not `excerpt_length`; set `length` too.
- `show_more_toggle="1"` with `type="excerpt_only"` — there's nothing to expand to; the toggle never shows.
