---
title: `us_post_title` — Post Title
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_title.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_title.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_title
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

# `us_post_title` — Post Title

**When to use**: outputs the **current** post / page / CPT / term's title inside a Page Template, archive layout, or Grid Layout card. Picks the HTML tag, alignment, link target, and an optional shortened length for tight grids.

**Avoid when**:
- you want a fixed, hand-written heading — use `us_text` (its `tag` param plays the same role and the value is whatever you type, not the post title);
- the page isn't bound to a post object — there is no title to resolve.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `tag` | HTML tag — `h1`, `h2` (default), `h3`, `h4`, `h5`, `h6`, `div`, `p`, `span`. Use `h1` exactly once per page (typically the single-post hero); cards under a parent heading should use `h3` / `h4`. |
| `align` | `none` (default — inherit), `left`, `center`, `right`. |
| `link` | Where the title links to. URL-encoded JSON. Common values: `{"type":"post"}` (default — post permalink), `{"type":"homepage"}`, `{"type":"elm_value"}` (when the post's title looks like an email / phone / URL — turns it into a `mailto:` / `tel:` / external link), `{"url":""}` (no link). |
| `color_link` | `1` (default) keeps the link colour equal to the text colour; `0` uses the theme's link colour. |
| `shorten_length` | `1` clips the title at `shorten_length_count` characters and appends "…". |
| `shorten_length_count` | Character cap when `shorten_length="1"`. 1–60, default `30`. |
| `show_count` | `1` appends the term's post count after the title. Only meaningful when the title is a term name (i.e. when this element is inside a Grid Layout for a `us_term_list` / `us_term_carousel`). |

**Minimal example**

```text
[us_post_title tag="h2" link="%7B%22type%22%3A%22post%22%7D"]
```

**Common combinations**

Single-post hero, h1, no link (it's the destination):

```text
[us_post_title tag="h1" align="center" link="%7B%22url%22%3A%22%22%7D"]
```

Card title in a 4-up blog grid — h4, linked, clipped to 40 chars:

```text
[us_post_title tag="h4" link="%7B%22type%22%3A%22post%22%7D"
               shorten_length="1" shorten_length_count="40"]
```

Term card with the post count badge:

```text
[us_post_title tag="h3" show_count="1"
               link="%7B%22type%22%3A%22post%22%7D"]
```

**Anti-patterns**

- Using `tag="h1"` in a Page Template that's assigned to **archive** layouts — every card in the archive gets an `h1`, killing the page outline.
- `shorten_length_count="10"` on a long-title blog — visitors see "Five things ab…" and can't tell posts apart. Pair with a generous Grid Layout image so the card has secondary cues.
- `show_count="1"` on a single-post Page Template — there's no term context, the count never renders.
- `link={"type":"elm_value"}` on a normal post — it only kicks in for titles that *parse* as email / phone / URL (typically used for directory CPTs).
