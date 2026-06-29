---
title: Element parameters — Effects
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php
---

<!-- GENERATED — do not edit directly. Rebuild: php scripts/llms/build.php --only=common-params -->

# Element parameters — Effects

> Scroll-driven motion parameters (translate, opacity, blur, scale) injected into nearly all us-core elements. A shortcode that includes this group exposes every parameter below with the listed defaults.

**Applies to**: most us-core shortcodes. See per-shortcode entries in [shortcodes.md](shortcodes.md) for the few elements that opt out.

## Parameters

| Name | Type | Default | Responsive | Show if | Notes |
|------|------|---------|------------|---------|-------|
| `scroll_effect` | switch | 0 | — | — | Scrolling Effects |
| `scroll_translate_y` | switch | 0 | — | — | Vertical Shift |
| `scroll_translate_y_direction` | radio | up | — | `scroll_translate_y` = 1 | — |
| `scroll_translate_y_speed` | slider | 0.5x | — | `scroll_translate_y` = 1 | Speed |
| `scroll_translate_x` | switch | 0 | — | — | Horizontal Shift |
| `scroll_translate_x_direction` | radio | left | — | `scroll_translate_x` = 1 | — |
| `scroll_translate_x_speed` | slider | 0.5x | — | `scroll_translate_x` = 1 | Speed |
| `scroll_opacity` | switch | 0 | — | — | Transparency |
| `scroll_opacity_direction` | select | out-in | — | `scroll_opacity` = 1 | — |
| `scroll_blur` | switch | 0 | — | — | Blur |
| `scroll_blur_direction` | select | out-in | — | `scroll_blur` = 1 | — |
| `scroll_blur_speed` | slider | 1.0x | — | `scroll_blur` = 1 | Speed |
| `scroll_scale` | switch | 0 | — | — | Scale |
| `scroll_scale_direction` | radio | up | — | `scroll_scale` = 1 | — |
| `scroll_scale_speed` | slider | 0.5x | — | `scroll_scale` = 1 | Speed |
| `scroll_delay` | slider | 0.1s | — | `scroll_effect` = 1 | Delay |
| `scroll_from_initial_position` | switch | 0 | — | `scroll_effect` = 1 | Animate this element from its initial position |
| `scroll_start_position` | slider | 0% | — | `scroll_from_initial_position` = 0 | Animation Start Position; Distance from the bottom screen edge, where the element starts its animation |
| `scroll_end_position` | slider | 100% | — | `scroll_from_initial_position` = 0 | Animation End Position; Distance from the bottom screen edge, where the element ends its animation |


**Options for `scroll_translate_y_direction`:**

  - `up` — Up
  - `down` — Down

**Options for `scroll_translate_x_direction`:**

  - `left` — Left
  - `right` — Right

**Options for `scroll_opacity_direction`:**

  - `out-in` — Transparent → Visible
  - `in-out` — Visible → Transparent
  - `out-in-out` — Transparent → Visible → Transparent
  - `in-out-in` — Visible → Transparent → Visible

**Options for `scroll_blur_direction`:**

  - `out-in` — Blurred → Crisp
  - `in-out` — Crisp → Blurred
  - `out-in-out` — Blurred → Crisp → Blurred
  - `in-out-in` — Crisp → Blurred → Crisp

**Options for `scroll_scale_direction`:**

  - `up` — Scale Up
  - `down` — Scale Down
