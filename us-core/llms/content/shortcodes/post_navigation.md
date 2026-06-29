---
title: `us_post_navigation` — Post Prev/Next Navigation
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_navigation.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_navigation.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_navigation
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

# `us_post_navigation` — Post Prev/Next Navigation

**When to use**: a pair of "← Previous Post" / "Next Post →" links rendered under a single-post Page Template, optionally constrained to the same taxonomy term. Two layouts: inline simple links, or fixed-position arrows pinned to the left and right edges of the viewport.

**Avoid when**:
- you want a full numeric pagination of an archive — that's `pagination` on `us_post_list` (`numbered` / `numbered_ajax`), not this element;
- the page is an archive / search / standalone — there is no surrounding post sequence to step through;
- the post belongs to a non-chronological CPT (a sortable directory, a manual-order portfolio) — "prev/next" by date order won't match visitor expectations.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `layout` | `simple` (default — two links rendered inline where the element sits) or `sided` (links float to the left and right edges of the viewport, persisting as the visitor scrolls). |
| `invert` | `1` swaps the prev/next positions (next on the left, prev on the right). Useful in RTL languages or specific design preferences. |
| `in_same_term` | `1` restricts navigation to the same taxonomy term — useful for series, course chapters, category-locked browsing. |
| `taxonomy` | Taxonomy slug used when `in_same_term="1"`. Default `category`. |
| `prev_post_text` | Subtitle above the previous-post title. Default "Previous Post". `layout="simple"` only. |
| `next_post_text` | Subtitle above the next-post title. Default "Next Post". `layout="simple"` only. |

**Minimal example**

```text
[us_post_navigation]
```

**Common combinations**

End-of-post inline navigation, same-category browsing:

```text
[us_post_navigation layout="simple"
                    in_same_term="1" taxonomy="category"
                    prev_post_text="Previous article"
                    next_post_text="Next article"]
```

Floating side arrows for a long-form blog (always visible while scrolling):

```text
[us_post_navigation layout="sided"]
```

Course-chapter navigation locked to a custom "course" taxonomy:

```text
[us_post_navigation layout="simple"
                    in_same_term="1" taxonomy="course"
                    prev_post_text="Previous chapter"
                    next_post_text="Next chapter"]
```

**Anti-patterns**

- Using `layout="sided"` on a narrow viewport without responsive overrides — the arrows can overlap card content. Pair with Display Logic to hide on mobile.
- `in_same_term="1"` with a taxonomy that's not assigned to many posts — first / last items in the term get a one-sided strip (prev only, next missing), which can look broken.
- Placing this on a `us_content_template` assigned to *archive* layouts — it resolves against each card's neighbours, which is rarely what authors want. Reserve for the *single* layout.
