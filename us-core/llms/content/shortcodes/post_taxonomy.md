---
title: `us_post_taxonomy` — Post Taxonomy
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_taxonomy.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_taxonomy.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_taxonomy
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

# `us_post_taxonomy` — Post Taxonomy

**When to use**: outputs the **current** post's terms in one taxonomy — "Categories: Design, Marketing", "Tags: #foo #bar", product categories on a shop card, custom-taxonomy chips on a CPT card. Renders as comma-separated text or as a row of button-styled badges, with optional colour swatches from term meta.

**Avoid when**:
- you want a list of *all* terms in a taxonomy, not the ones attached to the current post — use `us_term_list` / `us_term_carousel`;
- you want a hierarchical sidebar of categories — use `us_category_nav`;
- the page isn't bound to a single post object — there's nothing to look up.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `taxonomy_name` | Taxonomy slug. Default `category`. Common values: `post_tag`, `product_cat`, `product_tag`, `us_portfolio_category`, custom taxonomy slugs. |
| `style` | `simple` (default — text values with a separator) or `badge` (each term becomes a button-styled pill). |
| `btn_style` | Pill style when `style="badge"` — `badge` (default — theme's badge style), or a numeric key from Theme Options → Buttons (`"1"`, `"2"`, …). |
| `separator` | Text inserted between values when `style="simple"`. Default `, `. Common alternatives: ` • `, ` / `, ` &middot; `. |
| `link` | Where each term links to. URL-encoded JSON. Default `{"type":"archive"}` (the term-archive page). Set to `{"url":""}` to render the values without links. |
| `color_link` | `1` (default) inherits the surrounding text colour for the links. |
| `icon` | Optional FontAwesome icon prepended to the *first* value. Format `<style>|<name>`. |
| `text_before` / `text_after` | Static prefix / suffix (e.g. "Filed under: " / "."). |
| `show_color_swatch` | `1` shows a colour swatch next to each term, sourced from the term's "Color Swatch" meta (set in the term edit screen). |
| `hide_color_swatch_label` | `1` hides the term name when `show_color_swatch="1"` — useful for product-attribute swatches. |
| `apply_swatch_colors` | `1` uses the swatch colours as the badge background (only meaningful with `style="badge"`). |

**Minimal example**

```text
[us_post_taxonomy taxonomy_name="category"]
```

**Common combinations**

Blog card meta — categories as comma-separated text with a folder icon:

```text
[us_post_taxonomy taxonomy_name="category"
                  style="simple" separator=", "
                  icon="fas|folder" text_before="In "]
```

Tag chips under an article, button style 2:

```text
[us_post_taxonomy taxonomy_name="post_tag"
                  style="badge" btn_style="2"
                  text_before="Tags: "]
```

Product colour swatches with no labels (visual-only attribute strip):

```text
[us_post_taxonomy taxonomy_name="pa_color"
                  style="badge" btn_style="badge"
                  show_color_swatch="1"
                  hide_color_swatch_label="1"
                  apply_swatch_colors="1"
                  link="%7B%22url%22%3A%22%22%7D"]
```

**Anti-patterns**

- `taxonomy_name="category"` on a post type that doesn't use the default `category` taxonomy (e.g. `product`, `us_portfolio`) — the element renders empty silently. Pass the correct slug.
- `style="simple"` with `show_color_swatch="1"` — swatches require the pill markup; switch to `style="badge"`.
- `apply_swatch_colors="1"` on terms without a colour in their meta — pills fall back to the default badge colour; no error, just no effect.
- Heavy `btn_style="3"` (or any themed-button style) on a long tag list — pills become unreadable. Keep filter / meta rows on simple styles.
