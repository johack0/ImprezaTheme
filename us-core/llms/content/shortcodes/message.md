---
title: `us_message` — Message Box
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/message.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/message.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=message
  Direct edits here will be lost on the next regeneration.
-->

# `us_message` — Message Box

**When to use**: an inline notification/alert inside content — info banner, warning, success acknowledgement, error notice.

**Avoid when**:
- you need a modal popup — `[us_popup]`;
- you need a site-wide cookie/maintenance notice — those are configured in Theme Options;
- you need a friendly empty-state — a styled `[us_text]` may be enough.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `content` | Body of the message. Passed as the inner content of the shortcode (between opening and closing tags); HTML is allowed. |
| `color` | Color palette of the box — `blue` (default), `yellow`, `green`, `red`. These are visual colors, not semantic levels — pick the one whose meaning matches the message (red for errors, green for success, yellow for warnings, blue for info). |
| `icon` | Optional icon prefix (`set|name`, e.g. `fas|info-circle`, `fas|exclamation-triangle`). |
| `closing` | `1` adds a dismiss button so the visitor can close the message; the dismissed state may not persist across reloads. Default `0`. |

**Minimal example**

```text
[us_message color="blue" icon="fas|info-circle"]New feature: dark mode is live.[/us_message]
```

**Common combinations**

Dismissible success notice after a form-like action:

```text
[us_message color="green" icon="fas|check-circle" closing="1"]Thanks — your message has been sent.[/us_message]
```

Warning that explains a constraint:

```text
[us_message color="yellow" icon="fas|exclamation-triangle"]This action cannot be undone.[/us_message]
```

**Anti-patterns**

- Using `color="red"` purely for visual emphasis on regular copy — visitors and screen readers will read it as an error. Reserve `red` for actual errors/blockers.
- Multiple stacked messages at the top of a page — pick the most important one; the rest become noise.
- Empty `content` with only an `icon` — the box looks broken; either remove the message or put a sentence in it.
