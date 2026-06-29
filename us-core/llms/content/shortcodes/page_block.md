---
title: `us_page_block` — Reusable Block
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/page_block.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/page_block.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=page_block
  Direct edits here will be lost on the next regeneration.
-->

# `us_page_block` — Reusable Block

**When to use**: embed a saved Reusable Block (post type `us_page_block`) on a page — a single source-of-truth block that updates everywhere it is used. Typical uses: a shared footer CTA, a "trusted by" logo row, a pricing tier card reused across landing pages.

**Avoid when**:
- the content only appears on this page — author it inline;
- you need a real site-wide template part — that's a Header / Footer / Page Template in Theme Options, not a Reusable Block.

**Authoring**: create the block content first in **WP Admin → Reusable Blocks** (or via the REST endpoint for the `us_page_block` post type) and copy its post ID. The `id` attribute below takes that ID.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `id` | ID of the Reusable Block post to embed. Required. |
| `remove_rows` | Strip wrapping rows/columns so the embedded content inherits the host's layout context. Values: empty (default — keep all markup), `1` (drop rows inside the embedded block), `parent_row` (drop the row that wraps **this** `us_page_block` on the host page). |
| `force_fullwidth_rows` | `1` stretches embedded rows to the full container width regardless of their own `width` attribute. Only meaningful when `remove_rows != "1"`. |

**Minimal example**

```text
[us_page_block id="142"]
```

**Common combinations**

Drop the embedded block's outer row so it merges with the host column:

```text
[vc_row][vc_column]
  [us_text text="See plans"]
  [us_page_block id="142" remove_rows="1"]
[/vc_column][/vc_row]
```

**Anti-patterns**

- Referencing a draft or trashed Reusable Block — nothing renders and there is no visible error in the front-end.
- Using `us_page_block` for content that already changes per-page (CTA text, target URL) — the whole point is one source. If you need variants, build them as separate blocks.
- Nesting one `us_page_block` inside another more than one level deep — debugging the resulting cascade is painful and the editor cannot preview it.
