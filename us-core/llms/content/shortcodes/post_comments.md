---
title: `us_post_comments` — Post Comments
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_comments.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_comments.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_comments
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

# `us_post_comments` — Post Comments

**When to use**: either the *amount* of comments (a small "5 comments" badge for a card meta row), or the *full comments template* — list of existing comments plus the reply form — rendered through the theme's `comments.php`. Typical placement: blog-card meta strip (`layout="amount"`), single-post body (`layout="comments_template"`).

**Avoid when**:
- you want a generic discussion widget unrelated to WP comments — out of scope;
- you want comments on a page that isn't bound to a post — there's no thread to render.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `layout` | `comments_template` (default — list + reply form via the theme template) or `amount` (just the count). |
| `hide_zero` | `1` hides the element when the post has no comments. Only meaningful when `layout="amount"`. |
| `number` | `1` shows just the number (`12`) instead of the full string (`12 Comments`). `layout="amount"` only. |
| `link` | Where the count links to. URL-encoded JSON. Default `{"type":"post_comments"}` (jumps to the comments anchor on the post page). Other useful values: `{"type":"post"}`, `{"url":""}` (no link). `layout="amount"` only. |
| `color_link` | `1` (default) inherits the surrounding text colour. |
| `icon` | FontAwesome icon prepended to the count. Format `<style>|<name>` (e.g. `far|comment`). `layout="amount"` only. |

**Minimal example**

```text
[us_post_comments layout="amount" icon="far|comment"]
```

**Common combinations**

Blog card meta — comment count linked to the post's comments section, hidden when zero:

```text
[us_post_comments layout="amount" number="0"
                  link="%7B%22type%22%3A%22post_comments%22%7D"
                  icon="far|comment"
                  hide_zero="1"]
```

Single-post body — full comments template at the bottom:

```text
[us_post_comments layout="comments_template"]
```

Compact numeric badge above the title:

```text
[us_post_comments layout="amount" number="1"
                  icon="fas|comments"]
```

**Anti-patterns**

- `layout="comments_template"` on an archive Page Template — every card renders its own comments list (and the duplicate `<form id="commentform">` IDs break the page). Use `layout="amount"` on archives, `layout="comments_template"` only on the single-post template.
- `hide_zero="1"` plus `number="1"` — visitors see nothing, no context. Either keep a label ("0 comments" is a valid CTA to be the first), or drop the element entirely on no-comments posts via Display Logic.
- Pairing `layout="comments_template"` with `link` / `icon` / `number` — those params are only read in `amount` mode; harmless but noisy.
