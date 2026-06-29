---
title: `us_post_views` — Post Views
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_views.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_views.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_views
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

# `us_post_views` — Post Views

**When to use**: shows the **current** post's view count — a "1.2K views" badge in a card meta row, an "Read by N people" line on a single post. Requires a post-views counter integration on the site (the bundled Post Views Counter plugin support, or any plugin that writes the standard `views` post meta the theme reads).

**Avoid when**:
- the site doesn't track post views — the element renders `0` for every post, which is misleading;
- you want a UTM-style site-wide stat — out of scope; this is one number per post.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `result_thousand_short` | `1` formats large counts with a "K" suffix (`1.2K` instead of `1,234`). Useful for tight card meta rows. |
| `result_thousand_separator` | Character between thousand groups when `result_thousand_short="0"`. Default `,` (renders `12,345`). Set to ` ` for `12 345`, or `.` for European formats. |
| `icon` | FontAwesome icon prepended to the count. Format `<style>|<name>` (e.g. `fas|eye`). |
| `text_before` / `text_after` | Static prefix / suffix (e.g. "Read by " / " people"). Both honour dynamic-value tokens. |

**Minimal example**

```text
[us_post_views icon="fas|eye" text_after=" views"]
```

**Common combinations**

Card meta — eye icon + short thousand format:

```text
[us_post_views icon="far|eye"
               result_thousand_short="1"
               text_after=" views"]
```

Single-post body line:

```text
[us_post_views text_before="Read by "
               result_thousand_separator=","
               text_after=" people"]
```

**Anti-patterns**

- Using this on a site without a view-counter plugin or theme integration — the value is `0` for every post, which advertises low traffic. Either install a counter plugin or hide this element via Display Logic.
- `result_thousand_short="1"` plus `result_thousand_separator=","` — the separator is ignored once values short-form to `K`; keep one or the other in mind for clarity.
