---
title: `us_list_order` — List Order
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/list_order.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/list_order.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=list_order
  Direct edits here will be lost on the next regeneration.
-->

# `us_list_order` — List Order

**When to use**: a sort dropdown that lets visitors re-order a sibling `us_post_list` / `us_product_list` (or any post-driven list) — "Sort by: Newest / Price ↑ / Price ↓ / Popularity". The element writes the chosen key into a URL param consumed by the list (the list must have `apply_url_params="1"`). Typical placement: shop / blog toolbar, archive header.

**Avoid when**:
- you want filtering by *value* (in-stock yes/no, category) — that's `us_list_filter`;
- the listing has only one natural order (e.g. a static "Our values" block) — skip the dropdown;
- the listing is a user / term list — those queries do not consume the order URL params emitted by this element.

**Key parameters**

**Options** — `orderby_items` is a JSON-encoded group; each entry is one dropdown option. Per-entry params:

| Entry param | What it does |
|-------------|--------------|
| `value` | Sort key. Built-in: `date`, `modified`, `title`, `author`, `comment_count`, `type`, `menu_order`. WooCommerce: `price`, `total_sales`, `rating`. Or `custom` (custom field). |
| `custom_field` | Custom-field key when `value="custom"`. |
| `custom_field_numeric` | `1` treats the custom-field value as a number. |
| `invert` | `1` flips this option's direction (e.g. cheapest first vs. most expensive). |
| `label` | Override the option label (leave blank for the default — "Date of creation", "Price", etc.). |

The element auto-prepends a "first value" entry (label `Default`) that clears the param and falls back to the list's own `orderby`.

**Appearance**

| Param | What it does |
|-------|--------------|
| `first_label` | Label of the "default" entry. Default "Default". |
| `text_before` | Static text rendered before the dropdown — e.g. "Sort by:". |
| `width_full` | `1` stretches the control to the column width. |
| `change_url_params` | `1` (default) writes the selection into the URL — required for shareable / bookmarkable state. |
| `scroll_to_list` | `1` (default) scrolls to the list after a change. |
| `us_field_style` | Field style key from Theme Options → Form Field Styles. |

**Minimal example**

```text
[us_list_order orderby_items="%5B%7B%22value%22%3A%22date%22%2C%22invert%22%3A%221%22%2C%22label%22%3A%22%22%7D%2C%7B%22value%22%3A%22title%22%2C%22invert%22%3A%220%22%2C%22label%22%3A%22%22%7D%5D"]
```

**Common combinations**

Shop sort dropdown with four options (newest / price asc / price desc / popularity):

```text
[us_list_order text_before="Sort by:"
               orderby_items="%5B%7B%22value%22%3A%22date%22%2C%22invert%22%3A%221%22%2C%22label%22%3A%22Newest%22%7D%2C%7B%22value%22%3A%22price%22%2C%22invert%22%3A%220%22%2C%22label%22%3A%22Price%3A%20low%20to%20high%22%7D%2C%7B%22value%22%3A%22price%22%2C%22invert%22%3A%221%22%2C%22label%22%3A%22Price%3A%20high%20to%20low%22%7D%2C%7B%22value%22%3A%22total_sales%22%2C%22invert%22%3A%221%22%2C%22label%22%3A%22Popularity%22%7D%5D"]
```

Blog sort with a custom-field option (numeric):

```text
[us_list_order orderby_items="%5B%7B%22value%22%3A%22date%22%2C%22invert%22%3A%221%22%2C%22label%22%3A%22%22%7D%2C%7B%22value%22%3A%22custom%22%2C%22custom_field%22%3A%22reading_time%22%2C%22custom_field_numeric%22%3A%221%22%2C%22invert%22%3A%220%22%2C%22label%22%3A%22Shortest%20reads%22%7D%5D"
               us_field_style="1"]
```

**Anti-patterns**

- Target list doesn't have `apply_url_params="1"` — the dropdown changes the URL but the list never re-orders.
- Duplicate entries with `value="price"` but no `invert` distinction — the dropdown shows two visually identical options.
- Pairing with `us_user_list` / `us_term_list` expecting it to drive them — only post-driven lists react.
