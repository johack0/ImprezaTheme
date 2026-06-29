---
title: `us_btn` — Button
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/btn.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/btn.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=btn
  Direct edits here will be lost on the next regeneration.
-->

# `us_btn` — Button

**When to use**: a stand-alone call-to-action link (Sign up, Learn more, Download). Pairs naturally with a `text` block above it inside the same column.

**Avoid when**:
- you need a button inside a form submit — use the submit control of `[us_cform]` instead;
- you want a clickable icon without text — use `[us_iconbox]` with a link param.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `label` | Visible button text. Required for any CTA — never leave default `"Click Me"`. |
| `link` | Destination. URL-encoded JSON (see composition-rules §3.1), decoded: `{"url":"...","target":"_blank","rel":"nofollow"}`. |
| `style` | Visual style id from Theme Options → Buttons (per-site). Common values: `1` primary, `2` outline, `3` ghost — but the actual set depends on the install. |
| `align` | `left`, `center`, `right`, `justify`, or `none` (= default). Responsive. |
| `icon` | Optional icon, format `set|name` (e.g. `fas|arrow-right`). |
| `iconpos` | `left` (before text) or `right` (after text). |

**Minimal example**

```text
[us_btn label="Get started" link="%7B%22url%22%3A%22%23pricing%22%7D"]
```

**Common combinations**

Centered CTA with icon-after-text:

```text
[us_btn label="Sign up" link="%7B%22url%22%3A%22https%3A%2F%2Fexample.com%2Fsignup%22%7D" align="center" icon="fas|arrow-right" iconpos="right"]
```

Secondary button next to a primary one (wrap both in `us_hwrapper` so they sit side-by-side):

```text
[us_hwrapper valign="middle" inner_items_gap="1rem"]
  [us_btn label="Get started" link="%7B%22url%22%3A%22%23pricing%22%7D" style="1"]
  [us_btn label="Learn more"   link="%7B%22url%22%3A%22%23features%22%7D" style="2"]
[/us_hwrapper]
```

**Anti-patterns**

- Setting the link to `{"url":"#"}` and leaving it like that — produces a button that does nothing. If the destination isn't known yet, use a placeholder anchor on the same page.
- Mixing more than two styles in one row — pick one primary CTA and at most one secondary; reserve the third style for a follow-up section.
