---
title: `us_list_result_counter` — List Result Counter
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/list_result_counter.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/list_result_counter.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=list_result_counter
  Direct edits here will be lost on the next regeneration.
-->

# `us_list_result_counter` — List Result Counter

**When to use**: a small "1 – 12 of 47 results" line shown above or next to a `us_post_list` / `us_product_list`. Updates in place when the visitor filters / sorts / searches the list. Typical placement: shop or blog toolbar.

**Avoid when**:
- the listing has no Filter / Order / Search companion and a fixed item count — the counter is redundant and adds noise;
- you want the total to include unfiltered results too — use the `[total_unfiltered]` placeholder in `text` (otherwise only the filtered total is shown).

**Wiring**: same `list_to_count` / `list_selector_to_count` selector as `us_list_search` / `us_list_filter`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `list_to_count` | `first` (default) or `selector`. |
| `list_selector_to_count` | CSS class or ID of the target list. Required when `list_to_count="selector"`. |
| `text` | Template string. Default `[lower] - [upper] of [total] results`. Placeholders: `[lower]` (first index on the current page), `[upper]` (last index), `[total]` (filtered total), `[total_unfiltered]` (total before filters). |
| `text_single` | Replacement when there is exactly one result. Default "1 result". |
| `text_no_results` | Replacement when there are zero results. Leave blank to hide the element completely (the list itself shows its own `no_items_message`). |

**Minimal example**

```text
[us_list_result_counter]
```

**Common combinations**

Shop toolbar showing filtered vs. catalog total:

```text
[us_list_result_counter text="Showing [lower]–[upper] of [total] ([total_unfiltered] in catalog)"
                        text_single="1 product"
                        text_no_results="No products match these filters."]
```

Compact "of N" badge for a blog grid:

```text
[us_list_result_counter text="[total] articles"
                        text_single="1 article"
                        text_no_results=""]
```

**Anti-patterns**

- Setting `text=""` — the element renders empty markup. Use `text_no_results=""` to hide it only on zero results.
- Using `[total]` and `[total_unfiltered]` interchangeably — `[total]` is the count *after* the active filters; on an unfiltered page they're equal, but they diverge as soon as the visitor narrows the list.
- Placing two counters with `list_to_count="first"` on a multi-list page — both attach to the same list. Use `selector` + class hooks for multi-list layouts.
