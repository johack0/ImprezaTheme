---
title: `us_pricing` — Pricing Table
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/pricing.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/pricing.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=pricing
  Direct edits here will be lost on the next regeneration.
-->

# `us_pricing` — Pricing Table

**When to use**: a full pricing comparison rendered as **a single shortcode** that lists all tiers in its `items` group — Starter / Pro / Business side-by-side, each with its own price, feature list and CTA. The shortcode handles column layout internally.

**Avoid when**:
- you have only one price to display — a `us_cta` with the price in the heading is lighter;
- you want each tier in a separate `vc_column` for full layout control — compose `us_vwrapper` + `us_text` + `us_btn` manually inside `vc_row columns="3"`. `us_pricing` is a one-piece table, not a per-card element.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `style` | Visual style of the whole table — `simple` (default), `cards`, `flat`. |
| `items` (group) | The tiers. Each item is a sub-structure with its own fields (see below). A group attribute — URL-encoded JSON array in double quotes (composition-rules §3.5); order in the array = display order. |

**Per-item fields** (inside each `items` entry)

| Field | What it does |
|-------|--------------|
| `title` | Plan name (e.g. `Starter`, `Pro`, `Business`). |
| `type` | `1` highlights this tier as the featured/recommended plan; `0` (default) is a regular tier. |
| `price` | Price display — typically `29` or `$29` or `€29`. The currency symbol lives **inside** this string; there is no separate currency parameter. |
| `substring` | Price unit / period text shown next to the number — `/mo`, `per month`, `/yr`. |
| `features` | Feature list, **one item per line** (newlines inside the textarea). Each line becomes a row in the plan's feature list. |
| `btn_text` | CTA button label. |
| `btn_link` | CTA destination — URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank"}`. |
| `btn_style` | Per-site button style (Theme Options → Buttons). Default `1`. |
| `btn_size` | Custom button font-size (CSS units). |
| `btn_icon` | Optional button icon (`set|name`). |
| `btn_iconpos` | Icon position — `left` (default) or `right`. |

**Minimal example**

Single tier (just to show shape):

```text
[us_pricing style="cards"]
```

The `items` group is a nested `items="..."` payload — URL-encoded JSON array in double quotes (composition-rules §3.5); there is no concise inline syntax for it.

**Common combinations**

A three-tier table with the middle plan featured: build it as **one** `us_pricing` shortcode with three `items`. The middle item sets `type="1"`. The shortcode renders all three side-by-side automatically — do not wrap them in `vc_row columns="3"`.

**Anti-patterns**

- Trying to render three pricing cards as three separate `[us_pricing]` shortcodes inside `vc_row columns="3"` — this is the wrong mental model. `us_pricing` is the whole table; tiers are its `items`.
- Splitting price and currency between separate fields — both live in `price` as one string.
- Hidden fees in fine print on the card — surface them or move to a "compare" page.
