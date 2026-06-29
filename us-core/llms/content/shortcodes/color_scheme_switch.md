---
title: `us_color_scheme_switch` — Color Scheme Switch
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/color_scheme_switch.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/color_scheme_switch.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=color_scheme_switch
  Direct edits here will be lost on the next regeneration.
-->

# `us_color_scheme_switch` — Color Scheme Switch

**When to use**: an inline toggle that flips the visitor between two color schemes — typically light/dark. The two schemes themselves live in Theme Options → Colors; this shortcode only renders the UI control and persists the visitor's choice.

**Avoid when**:
- Theme Options → General → "Dark Theme" is set to anything other than "None" — that setting already wires up a dark-theme toggle site-wide, and this shortcode then surfaces a notice rather than a working switch;
- you want different colors on a specific page only — that's a per-section background/text override on `vc_row`, not a global scheme switch.

**Placement**: usually in a Header layout (next to the menu) or in a corner of the page. No children.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `color_scheme` | The scheme applied when the switch is **on**. Values come from Theme Options → Colors (per-install). Required. |
| `text_before` | Label shown to the left of the switch (default `Light`). |
| `text_after` | Label shown to the right of the switch (default `Dark`). |
| `inactive_switch_bg` | Background of the switch in the "off" state (default `#ddd`). |
| `active_switch_bg` | Background of the switch in the "on" state (default `#222`). |

**Minimal example**

```text
[us_color_scheme_switch color_scheme="dark"]
```

**Common combinations**

In a horizontal wrapper next to a button, with custom labels:

```text
[us_hwrapper inner_items_gap="1rem" valign="middle"]
  [us_color_scheme_switch color_scheme="dark" text_before="☀" text_after="☾"]
  [us_btn label="Sign in" link="%7B%22url%22%3A%22%23login%22%7D"]
[/us_hwrapper]
```

**Anti-patterns**

- Multiple instances on one page — they share state but render independent UIs that can briefly desync. Use one.
- Setting `color_scheme` to a non-existent scheme slug — the switch renders but does nothing. Verify the slug in Theme Options → Colors first.
- Using this when the "Dark Theme" theme-option is already enabled — the shortcode will display a "use the global setting instead" message rather than a working toggle.
