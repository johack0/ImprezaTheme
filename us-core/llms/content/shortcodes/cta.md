---
title: `us_cta` — Call To Action
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/cta.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/cta.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=cta
  Direct edits here will be lost on the next regeneration.
-->

# `us_cta` — Call To Action

**When to use**: a compact promotional block — large heading, sub-text and one (or two) buttons, framed as a section break inviting action. Typical pre-footer block.

**Avoid when**:
- the page already has a clear hero CTA — multiple `us_cta` blocks dilute focus;
- you only need a button — `[us_btn]`;
- you need a feature card — `[us_iconbox]`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `title` | Headline (required). |
| `text` | Sub-text under the headline. |
| `btn_label` | Primary button text. |
| `btn_link` | Primary button link (URL-encoded JSON, see composition-rules §3.1). |
| `btn_style` | Per-site button style. |
| `second_button` | `1` to add a secondary button (then `second_btn_label`, `second_btn_link`, `second_btn_style` apply). |
| `controls_align` | Position of the buttons relative to text — `bottom` / `right`. |
| `color_bg` | Background color/gradient. |

**Minimal example**

```text
[us_cta title="Ready to start?" text="Free for 14 days, no credit card." btn_label="Start now" btn_link="%7B%22url%22%3A%22%23signup%22%7D"]
```

**Anti-patterns**

- Vague titles (`Get started today!`) without saying what — pair with concrete value (`Start your free 14-day trial`).
- Two CTAs pointing to the same URL — one is enough.
