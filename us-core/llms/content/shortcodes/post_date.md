---
title: `us_post_date` — Post Date
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/post_date.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/post_date.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=post_date
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

# `us_post_date` — Post Date

**When to use**: outputs the **current** post's publish or modified date (or any ACF date / date-time / time-picker field) in a Page Template / Grid Layout card. Supports human-readable formatting ("Today at 11:04", "3 months ago"), classic WP date formats, or a fully custom format string.

**Avoid when**:
- you want a hand-picked, fixed date — write text in `us_text` instead;
- the date is an event start / end date and the site uses The Events Calendar — use `us_event_date` (its format and TEC integration are wired in);
- you want a per-post custom date with no plugin support — use `us_post_custom_field` with the appropriate `key` (and the value will be rendered as a plain string).

**Key parameters**

| Param | What it does |
|-------|--------------|
| `type` | Which date to show — `published` (default — date of creation), `modified` (date of last update). When ACF is active, every ACF date-picker / date-time-picker / time-picker field is also listed here by its field name. |
| `format` | Output format — `smart` ("Today at 11:04", "Yesterday at 08:55"), `time_diff` (default — "5 hours ago", "3 months ago"), `default` (the site's WP "Date Format" setting), or one of the presets `jS F Y`, `j M, G:i`, `m/d/Y`, `j.m.y`. `custom` enables `format_custom`. |
| `format_custom` | PHP `date()`-style format string when `format="custom"`. Default `F j, Y`. WordPress's [date and time formatting docs](https://wordpress.org/support/article/formatting-date-and-time/) apply. |
| `icon` | Optional FontAwesome icon prepended to the date. Format `<style>|<name>` (e.g. `far|calendar`). |
| `text_before` / `text_after` | Static prefix / suffix text (e.g. "Posted on " / " UTC"). Both honour dynamic-value tokens. |

**Minimal example**

```text
[us_post_date format="time_diff"]
```

**Common combinations**

Blog card metadata — "Posted 3 days ago" with a calendar icon:

```text
[us_post_date type="published" format="time_diff"
              icon="far|calendar" text_before="Posted "]
```

Single-post header with a localised long-form date:

```text
[us_post_date type="published" format="default"
              text_before="Published on "]
```

"Last updated" line under a doc article:

```text
[us_post_date type="modified" format="jS F Y"
              text_before="Last updated: "]
```

ACF date field rendered with a custom format:

```text
[us_post_date type="event_start_date"
              format="custom" format_custom="l, F jS"
              icon="far|calendar-check"]
```

**Anti-patterns**

- `format="time_diff"` on long-archived posts — "7 years ago" reads as unmaintained content; prefer `default` or a year-aware preset on evergreen pages.
- `type="modified"` on every card — visitors see "Updated 5 minutes ago" after every minor edit; use `published` for blog-card meta unless freshness is the point.
- `format="custom"` with a format string that includes timezone characters (`e`, `T`) — WordPress already calls `date_i18n`, the timezone is baked in; redundant characters render literally.
