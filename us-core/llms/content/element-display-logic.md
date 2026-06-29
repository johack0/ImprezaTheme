---
title: Element parameters — Display Logic
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php
---

<!-- GENERATED — do not edit directly. Rebuild: php scripts/llms/build.php --only=common-params -->

# Element parameters — Display Logic

> Conditional-rendering parameters that decide whether an element is output on a given request (logged-in state, user role, device, custom field values, etc.).

**Applies to**: most us-core shortcodes. See per-shortcode entries in [shortcodes.md](shortcodes.md) for the few elements that opt out.

## Parameters

| Name | Type | Default | Responsive | Show if | Notes |
|------|------|---------|------------|---------|-------|
| `conditions_operator` | select | always | — | — | Display this Element |
| `conditions` | group | [] | — | `conditions_operator` != [`always`, `never`] | — |


**Options for `conditions_operator`:**

  - `always` — Always
  - `and` — If EVERY condition below is met
  - `or` — If ANY condition below is met
  - `never` — Never
