---
title: `us_event_date` — Event Date and Time
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/event_date.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/event_date.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=event_date
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

# `us_event_date` — Event Date and Time

**When to use**: outputs an event's date / time (start, end, or the formatted "start–end" range) for a post from **The Events Calendar** (`tribe_events` CPT). Typical placement: an event card in a `us_post_list` filtered to `post_type="tribe_events"`, or the single-event Page Template.

**Avoid when**:
- the site doesn't have The Events Calendar active — the element is gated by `class_exists('Tribe__Events__Query')` and won't appear in the builder UI;
- you want a regular post's publish / modified date — use `us_post_date`;
- you want a custom date field unrelated to TEC — use `us_post_custom_field` with the appropriate `key`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `type` | What to show — `default` (the TEC-formatted "Start – End" string, honouring all-day / multi-day rules), `start` (start date only), `end` (end date only). |
| `format` | PHP `date()`-style format for `start` / `end` (hidden when `type="default"` — TEC owns the format there). Empty (default) → TEC's date format setting. Examples: `F j, Y`, `D, j M G:i`, `Y-m-d`. |
| `icon` | FontAwesome icon prepended to the date. Format `<style>|<name>` (e.g. `far|calendar`). |
| `text_before` / `text_after` | Static prefix / suffix (e.g. "Starts on " / " UTC"). Dynamic-value tokens allowed. |

**Minimal example**

```text
[us_event_date]
```

**Common combinations**

Event card meta — calendar icon + the TEC default range:

```text
[us_event_date type="default" icon="far|calendar"]
```

"Doors open at …" line on a single event page:

```text
[us_event_date type="start" format="l, F jS \a\t g:i a"
               icon="far|clock"
               text_before="Doors open: "]
```

"Until …" badge on a multi-day event card:

```text
[us_event_date type="end" format="j M Y"
               text_before="Through "]
```

**Anti-patterns**

- Using this on a regular post / page — the TEC lookup returns nothing; the element renders empty. Pair with Display Logic gated on `post_type=tribe_events` or use it only inside the Events single template.
- Setting `format` when `type="default"` — silently ignored (TEC controls the range format). Switch to `start` or `end` to take over the format.
- Custom `format` with timezone characters that TEC also strips — verify in the live preview; some characters look right in the schema but TEC sanitises the output.
