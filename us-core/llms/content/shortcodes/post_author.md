---
title: `us_post_author` — Post Author
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_author.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_author.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_author
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

# `us_post_author` — Post Author

**When to use**: outputs the **current** post's author — display name, optional avatar, optional posts count / website link / biographical info, optional icon. Typical placement: under a blog-post title ("by Jane Doe"), in an author-card row at the bottom of a single-post layout, or in a Grid Layout card's meta row.

**Avoid when**:
- you want a list / grid of users — use `us_user_list` / `us_user_carousel`;
- you want hand-curated team bios that aren't tied to WP users — use `us_person`;
- the page has no post in context — the element resolves to whoever the current loop says is "the author" (usually empty on standalone pages).

**Key parameters**

| Param | What it does |
|-------|--------------|
| `link` | Where the author name links to. URL-encoded JSON, supports dynamic values. Default `{"type":"author_page"}` (the WP author archive). Set `{"url":""}` for no link. |
| `color_link` | `1` (default) inherits the surrounding text colour for the link. |
| `avatar` | `1` shows the author's Gravatar / profile picture. |
| `avatar_width` | Avatar size. Default `96px`. Px only. |
| `avatar_pos` | `top` (default — avatar above the name) or `left` (avatar beside the name). |
| `posts_count` | `1` shows "N posts" next to the name. |
| `website` | `1` shows the URL the author entered in their profile's "Website" field. |
| `info` | `1` shows the author's "Biographical Info". |
| `icon` | Optional FontAwesome icon prepended to the name. Format `<style>|<name>` (e.g. `fas|user`). |

**Minimal example**

```text
[us_post_author]
```

**Common combinations**

Blog card meta — "by Jane Doe", with a small user icon:

```text
[us_post_author icon="fas|user"
                avatar="0" link="%7B%22type%22%3A%22author_page%22%7D"]
```

Bottom-of-post author card — large avatar on the left, bio underneath:

```text
[us_post_author avatar="1" avatar_width="120px" avatar_pos="left"
                info="1" website="1" posts_count="1"]
```

Single-post hero byline — name only, no link, no avatar:

```text
[us_post_author link="%7B%22url%22%3A%22%22%7D"]
```

**Anti-patterns**

- `avatar="1"` on a 12-card grid — every cell loads a Gravatar request; pair with a tight avatar size and consider hiding on mobile via Display Logic.
- `info="1"` in a Grid Layout card — bios are paragraphs of text, cards become uneven. Reserve `info`/`website` for the post page itself.
- `avatar_width` in non-px units — only px are accepted; other units render at default.
- `link="%7B%22type%22%3A%22author_page%22%7D"` on a single-author site — every link goes to the same archive; consider `{"url":""}` instead.
