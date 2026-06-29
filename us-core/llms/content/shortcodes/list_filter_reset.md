---
title: `us_list_filter_reset` — List Filter Reset
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/list_filter_reset.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/list_filter_reset.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=list_filter_reset
  Direct edits here will be lost on the next regeneration.
-->

# `us_list_filter_reset` — List Filter Reset

**When to use**: a "Reset All" button that clears every filter on the same page, optionally with chips for the currently selected values (each chip removes its own value when clicked). Typical placement: directly above or below a `us_list_filter` widget, or next to a `us_list_result_counter` in the toolbar of a shop / blog listing.

**Avoid when**:
- there is no `us_list_filter` on the page — the button has nothing to reset and renders as inert markup;
- you want a free-text search reset — `us_list_search` has its own clear control;
- you want to reset only one filter row — there is no per-row reset; this element clears the lot. Use the per-value chips (`show_selected_values="1"`) for granular removal.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `reset_all_label` | Button label. Default "Reset All". |
| `reset_all_style` | Button style — `style_1` (default), `style_2`, or a key from Theme Options → Buttons. |
| `show_selected_values` | `1` (default) renders a chip per active filter value next to the button; clicking a chip removes only that value. |
| `selected_values_style` | Chip style — `style_1` (default), `style_2`, or a Theme Options → Buttons key. |
| `selected_values_pos` | `before` (default) — chips render before the Reset All button; `after` — after. |
| `values_gap` | Gap between chips. Default `10px` (`px` / `em` / `rem`). |

**Minimal example**

```text
[us_list_filter_reset]
```

**Common combinations**

Shop toolbar with result counter + reset chips + reset button:

```text
[us_list_result_counter]
[us_list_filter_reset reset_all_label="Clear filters"
                      show_selected_values="1"
                      selected_values_pos="before"]
```

Compact "Clear" pill on a horizontal filter bar:

```text
[us_list_filter_reset reset_all_label="Clear"
                      reset_all_style="style_2"
                      show_selected_values="0"]
```

**Anti-patterns**

- Placing this without a `us_list_filter` on the page — the button has no active state and never lights up.
- `show_selected_values="1"` plus dozens of taxonomy terms — the chip strip can run wider than the toolbar; pair with `values_max_height` on the filter or use `show_selected_values="0"`.
- **Adding this when the page has only one filter row.** `us_list_filter_reset` is for compound filter widgets — *multiple* sources (post-type + price + stock + date + category, or several taxonomy facets) where the visitor benefits from a single "clear everything" affordance. A page whose only filter is a single `us_list_filter` with one `items` entry — e.g. a portfolio category strip — does **not** need it. The `us_list_filter` itself already renders a per-row reset link (`<a class="w-filter-item-reset">Reset</a>` inside the filter row, default-hidden, shown when any value is selected) which is the correct "back to show-all" affordance for a single facet. Adding `us_list_filter_reset` next to a single-facet filter just produces a second, redundant control — and tempts the author to relabel it as a filter value (see next bullet).
- **Disguising the reset as a filter value with `reset_all_label="All"`** (or "Все", "Any", a category-like name) next to a row of category buttons. To the visitor it reads as "show everything in this category" — a *filter value*, not a reset. They click it, every facet on the page clears with no warning. Keep the label clearly state-clearing: `"Reset"`, `"Reset all"`, `"Clear filters"`, `"Очистить"`. If the design genuinely calls for an "All categories" pill in the same row of category buttons, model it as part of the `us_list_filter` (`selection_type="radio"` already gives an unselected "any" state via the first-value entry, and the filter's per-item reset link is the proper "show all for this row" affordance) — do not press `us_list_filter_reset` into that role.
