---
title: `us_breadcrumbs` — Breadcrumbs
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/breadcrumbs.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/breadcrumbs.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=breadcrumbs
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

# `us_breadcrumbs` — Breadcrumbs

**When to use**: a "Home › Section › Subsection › *current page*" trail at the top of any post / page / archive. Auto-computed from the page hierarchy and the current taxonomy / post-type relationship — you don't list the items by hand. Typical placement: just under the site header, inside a slim row with a small bottom margin.

**Avoid when**:
- the site is a single landing page — there's no hierarchy worth showing;
- you want a hand-crafted nav with custom items — write the markup directly in `us_text` or use `vc_widget_sidebar` with a custom-menu widget.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `home` | Label of the first item, the homepage link. Default "Home". Set to **blank** to hide the homepage link entirely (the trail will start from the next level). Supports dynamic-value tokens (e.g. for translations). |
| `show_current` | `1` appends the current page's title as the last (non-link) crumb. `0` (default) stops one level earlier. |
| `align` | `none` (default — inherit), `left`, `center`, `right`. |
| `separator_type` | `icon` (default) or `custom` (a typed symbol). |
| `separator_icon` | Icon between crumbs when `separator_type="icon"`. Default `far|angle-right`. Format `<style>|<name>`. |
| `separator_symbol` | Text between crumbs when `separator_type="custom"`. Default `/`. Common alternatives: ` › `, ` » `, ` &middot; `, ` | `. |

**Minimal example**

```text
[us_breadcrumbs]
```

**Common combinations**

Compact left-aligned trail with `›` separators and the current page shown:

```text
[us_breadcrumbs home="Home"
                show_current="1"
                align="left"
                separator_type="custom"
                separator_symbol=" › "]
```

Shop archive header — home label blanked because the page title already says "Shop":

```text
[us_breadcrumbs home=""
                show_current="0"
                separator_type="icon"
                separator_icon="fas|chevron-right"]
```

**Anti-patterns**

- Setting `home=" "` (a single space) expecting to hide the homepage link — only an **empty** value hides it; a space renders as a clickable space character.
- `show_current="1"` on a page where the title is already in the H1 directly above — the page title appears twice, two characters apart, looking like a bug.
- Custom `separator_symbol=" - "` with literal spaces — the surrounding values lean too close; use ` - ` with non-breaking spaces or switch to `separator_type="icon"`.
