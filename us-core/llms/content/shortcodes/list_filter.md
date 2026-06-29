---
title: `us_list_filter` — List Filter
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/list_filter.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/list_filter.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=list_filter
  Direct edits here will be lost on the next regeneration.
-->

# `us_list_filter` — List Filter

**When to use**: a faceted filter widget for a post / product list on the same page — narrow by post type, author, category / tag (or any taxonomy), price, stock status, custom field, date. Filter selections are pushed into URL params and consumed by a sibling `us_post_list` / `us_product_list` that has `apply_url_params="1"`. Typical placement: a shop sidebar, an archive header, an "Advanced search" page.

**Avoid when**:
- the visitor should pick *one* sort key, not a filter — that's `us_list_order`;
- they should type free-form text — that's `us_list_search`;
- you just need a hierarchical category nav, not a multi-facet widget — that's `us_category_nav`;
- the page has no `us_post_list` / `us_product_list` with `apply_url_params="1"` — the filter has nothing to drive.

**Wiring**: by default Filter looks for the *first* List on the page (`list_to_filter="first"`); for multiple lists, set `list_to_filter="selector"` and target the list element with `list_selector_to_filter=".my-list"` (CSS class or ID, the `el_class` on the `us_post_list` is the usual hook). The target list **must** have `apply_url_params="1"`, otherwise the URL params change but the list ignores them.

**Key parameters**

**Filter items** — `items` is a JSON-encoded group; each entry is one filter row in the widget. Per-entry params:

| Entry param | What it does |
|-------------|--------------|
| `source` | The field to filter by. Built-in keys: `post_type`, `post_author`, `post_date`, `post_modified`. WooCommerce keys (when active): `price`, `instock`, `onsale`, `featured`. Any registered taxonomy slug is also valid (`category`, `post_tag`, `product_cat`, custom taxonomy slugs). |
| `post_type` | Comma-separated post types to offer when `source="post_type"`. |
| `post_author` | `all` (default) / `include` (offer selected authors only) / `exclude`. Pair with `post_author_ids`. |
| `term_compare` | For taxonomy sources — `all` (default), `include`, `exclude`. Pair with `term_ids` and optionally `term_show_children` / `term_exclude_children`. |
| `selection_type` | UI control for taxonomy / post-type / author / bool sources — `checkbox` (default), `radio`, `dropdown`, `range_slider`, `range_input`. |
| `term_operator` | `OR` (default — any selected term matches) or `AND` (all must match). Checkbox UI only. |
| `bool_value_label` | Label next to the checkbox for `instock` / `onsale` / `featured`. |
| `numeric_selection_type` | For numeric sources (`price`, custom numeric fields) — `range_slider` (default), `range_input`, `checkbox`, `radio`, `dropdown`. |
| `num_values_range` / `num_step_size` / `num_min_value` / `num_max_value` / `text_before_value` / `text_after_value` | Numeric tweaks (bucket size, slider step, min/max, prefix/suffix). |
| `date_selection_type` | For date sources — `date_picker` (default), `checkbox`, `radio`, `dropdown`, `range_slider`, `range_input`. |
| `date_picker_fields` | `exact` (default), `start`, `end`, `start_end`. |
| `date_values_format` | jQuery UI datepicker format. Default `d MM yy`. |
| `date_values_range` / `date_month_format` / `date_invert_order` | Bucketing for grouped date filters. |
| `label` | Override the row title (leave blank for the default — taxonomy name / "Author" / "Price" / etc.). |
| `first_value_label` | Adds a synthetic first entry to the value list that represents the "no filter applied" state — appears as an "All" / "Any" pill, checked by default. Default `"Any"`. Use this on a single-row radio filter as the proper "show everything" affordance (the dedicated `us_list_filter_reset` element is only worth adding when there are multiple filter rows). |
| `has_search` / `search_placeholder` | Show a search field inside the values list (checkboxes and radios only). |
| `values_as_btn` | `1` renders values as buttons (checkboxes and radios only). When set, `values_btn_style` and `values_btn_cols` (below) become meaningful. |
| `values_btn_style` | Style applied to each value pill. Accepts **two kinds of values**: a built-in non-themed filter-pill style — `style_1` (outlined, brand-colored when active — CSS in `common/css/elements/grid-filter.css:69–79`), `style_2` (text-link colored, alt-bg when active, solid brand on hover — `:81–99`), `style_3` (underline-only tabs — `:101–122`); OR a numeric key from Theme Options → Buttons (`"1"`, `"2"`, …) which applies the full themed-button class `w-btn us-btn-style_N` to every value (typically too heavy for a filter strip). Default `style_1`. The branching happens at `templates/elements/list_filter.php:805` via `is_numeric($value)` — `"style_2"` and `"2"` are **not** equivalent. |
| `values_btn_cols` | Layout of the pill row. `auto` (default — natural flex row, pills size to content) is what you want for a horizontal category strip. A number `2`–`5` switches to a fixed-column CSS grid (`grid-template-columns: repeat(N, 1fr)`) — only set this when you genuinely want a uniform-width grid (vertical sidebar with many short labels, etc.); with three pills in a horizontal strip and `values_btn_cols="4"`, you get three pills and an empty fourth cell. **Must be set explicitly** when `values_as_btn=1` — us-core reads the key without a null-coalesce and emits a `Undefined array key "values_btn_cols"` PHP Notice otherwise (see [composition-rules §3.5](../composition-rules.md#35-group-json-attributes)). |
| `show_color_swatch` / `hide_color_swatch_label` | Show color swatches for taxonomy terms that have a colour set in term meta. |

**Layout / Appearance**

| Param | What it does |
|-------|--------------|
| `layout` | `ver` (default — vertical, sidebar) or `hor` (horizontal — header bar). |
| `item_layout` | `default` (titles on top), `toggle` (titles collapse / expand groups), `dropdown` (titles as dropdowns), `no_titles`. |
| `dropdown_field_style` | Field style key from Theme Options → Form Field Styles (only when `item_layout="dropdown"`). |
| `values_drop` | `hover` (default) or `click` (when `item_layout="dropdown"`). |
| `align` | `none` (default), `left`, `center`, `right`, `justify`. Only when `layout="hor"`. |
| `items_gap` | Gap between filter rows. Default `1.5em`. |
| `values_max_height` | Max height of each values list (scroll inside). |
| `us_field_style` | Field style key for dropdown / range inputs. |

**Mobiles**: at and below `mobile_width` (default `600px`) the whole widget collapses into a single button labelled `mobile_button_label` (default "Filters") that opens an off-canvas drawer. Tweak with `mobile_button_style` / `mobile_button_icon` / `mobile_button_iconpos` / `mobile_button_badge_color`. Set `mobile_width=""` to disable the mobile collapse.

**More Options**

| Param | What it does |
|-------|--------------|
| `list_to_filter` | `first` (default) — auto-target the first list on the page; `selector` — explicit. |
| `list_selector_to_filter` | CSS class or ID of the list element when `list_to_filter="selector"`. |
| `change_url_params` | `1` (default) writes selections into the URL — required for shareable / bookmarkable filtered states and for browser back/forward. |
| `scroll_to_list` | `1` (default) scrolls to the list after a filter change. |
| `faceted_filtering` | `1` adapts available values to the visible result set (greys out empty options). Requires running the filter indexer in Theme Options → Advanced; an inline message reminds you. |
| `hide_post_count` / `hide_disabled_values` / `hide_disabled_items` | Faceted-only display tweaks. |
| `post_type_for_values` | Restrict the source of values to specific post types when faceted filtering is **off**. |

**Minimal example**

```text
[us_list_filter items="%5B%7B%22source%22%3A%22product_cat%22%2C%22term_compare%22%3A%22all%22%2C%22selection_type%22%3A%22checkbox%22%2C%22label%22%3A%22%22%7D%2C%7B%22source%22%3A%22price%22%2C%22numeric_selection_type%22%3A%22range_slider%22%2C%22num_step_size%22%3A%2210%22%2C%22label%22%3A%22%22%7D%5D"]
```

**Common combinations**

Shop sidebar with category checkboxes, price slider, in-stock toggle:

```text
[us_list_filter layout="ver"
                items="%5B%7B%22source%22%3A%22product_cat%22%2C%22term_compare%22%3A%22all%22%2C%22selection_type%22%3A%22checkbox%22%2C%22term_operator%22%3A%22OR%22%2C%22label%22%3A%22%22%7D%2C%7B%22source%22%3A%22price%22%2C%22numeric_selection_type%22%3A%22range_slider%22%2C%22num_step_size%22%3A%2210%22%2C%22label%22%3A%22%22%7D%2C%7B%22source%22%3A%22instock%22%2C%22selection_type%22%3A%22checkbox%22%2C%22bool_value_label%22%3A%22In%20stock%20only%22%7D%5D"
                faceted_filtering="1"]
```

Horizontal filter bar above a blog grid, targeting a specific list by class:

```text
[us_list_filter layout="hor" align="center"
                items="%5B%7B%22source%22%3A%22category%22%2C%22term_compare%22%3A%22all%22%2C%22selection_type%22%3A%22radio%22%2C%22label%22%3A%22%22%7D%2C%7B%22source%22%3A%22post_date%22%2C%22date_selection_type%22%3A%22date_picker%22%2C%22date_picker_fields%22%3A%22start_end%22%2C%22label%22%3A%22%22%7D%5D"
                list_to_filter="selector" list_selector_to_filter=".news-grid"]
```

Portfolio category-pill bar (single facet, idiomatic — `first_value_label` gives an "All" pill as part of the filter itself; `values_btn_cols="auto"` keeps the row to a natural flex of as many pills as you have; `style_2` uses the built-in light text-link pill style instead of a heavy themed button):

```text
[us_list_filter layout="hor" item_layout="no_titles" align="center" items_gap="0px" change_url_params="0"
                items="%5B%7B%22source%22%3A%22us_portfolio_category%22%2C%22term_compare%22%3A%22all%22%2C%22selection_type%22%3A%22radio%22%2C%22term_operator%22%3A%22OR%22%2C%22values_as_btn%22%3A%221%22%2C%22values_btn_style%22%3A%22style_2%22%2C%22values_btn_cols%22%3A%22auto%22%2C%22first_value_label%22%3A%22All%22%2C%22label%22%3A%22%22%7D%5D"
                list_to_filter="selector" list_selector_to_filter=".my-projects-grid"]
```

decodes to:

```json
[{"source":"us_portfolio_category","term_compare":"all","selection_type":"radio","term_operator":"OR","values_as_btn":"1","values_btn_style":"style_2","values_btn_cols":"auto","first_value_label":"All","label":""}]
```

**Anti-patterns**

- Forgetting `apply_url_params="1"` on the target list — selections appear in the URL but the list never updates.
- Setting `faceted_filtering="1"` without running the filter indexer in Theme Options → Advanced — values look static and never grey out.
- Two filters on the same page both with `list_to_filter="first"` — both target the same list; use `selector` + per-list classes for multi-list pages.
- Numeric filter on a string custom field — values look right in the UI, but `WP_Query`'s numeric compare returns nothing.
- Pairing a **single-row filter** (one `items` entry, e.g. a portfolio category strip) with `us_list_filter_reset` for "show all". This filter already renders a per-row reset link (`<a class="w-filter-item-reset">Reset</a>` inside the row, hidden until a value is selected) — that is the right "back to all" affordance for a single facet. `us_list_filter_reset` is meant for compound widgets with several sources; on a single-row filter it's a redundant second control and is easily mis-labelled as a filter *value* like "All". See the [`us_list_filter_reset`](#us_list_filter_reset--list-filter-reset) anti-patterns.
