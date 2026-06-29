---
title: `us_countdown_timer` — Countdown Timer
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/countdown_timer.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/countdown_timer.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=countdown_timer
  Direct edits here will be lost on the next regeneration.
-->

# `us_countdown_timer` — Countdown Timer

**When to use**: live timer counting down to a fixed datetime — sales, launches, webinars, "early bird closes in...".

**Avoid when**:
- the deadline is recurring (every Monday) — needs a custom widget;
- the value is static or animated from 0 to a target — use `[us_counter]`.

**Key parameters**

**Date** — the target moment is set by separate fields, not a single date string:

| Param | What it does |
|-------|--------------|
| `date_source` | `custom` (default — uses the `time_*` fields below). Additional values (the slug of an ACF datetime field on the current post) appear **only** when the ACF plugin is active. |
| `time_year` | Target year (4-digit). Default is the current year; the range rolls forward each year. |
| `time_month` | Target month (`01`–`12`). |
| `time_day` | Target day (`01`–`31`). |
| `time_hour` | Target hour (`00`–`23`). Default `00`. |
| `time_minute` | Target minute (`00`–`59`). Default `00`. |

**After expiry**

| Param | What it does |
|-------|--------------|
| `action_after_end` | `hide` (default — timer disappears) / `show_message` (replace timer with text). |
| `expired_message` | HTML shown when `action_after_end="show_message"`. The shortcode stores this value **base64-encoded** — write your message normally in the builder, it gets encoded automatically. |

**Appearance**

| Param | What it does |
|-------|--------------|
| `animation` | Digit transition — `none` (default), or one of the slide/zoom/flip variants. |
| `days_label` | Caption under (or beside) the days digits. Default `days`. Set empty to hide the days unit altogether. |
| `hours_label` | Caption for hours. Default `hours`. |
| `minutes_label` | Caption for minutes. Default `minutes`. |
| `seconds_label` | Caption for seconds. Default `seconds`. |
| `label_pos` | Caption position relative to the digits — `bottom` (default) or `aside`. |
| `label_size` | Caption font size (CSS units). Default `1rem`. |
| `label_weight` | Caption font weight (`100`–`900`, or theme default). |
| `label_color` | Caption text color (HEX/RGBA/palette var). |

**Minimal example**

```text
[us_countdown_timer time_year="2026" time_month="12" time_day="31" time_hour="23" time_minute="59" action_after_end="show_message" expired_message="Offer ended"]
```

**Common combinations**

Sale timer that disappears once expired:

```text
[us_countdown_timer time_year="2026" time_month="06" time_day="30" time_hour="23" time_minute="59" animation="slide" action_after_end="hide"]
```

Webinar timer with side-by-side labels (no seconds spinner):

```text
[us_countdown_timer time_year="2026" time_month="11" time_day="15" time_hour="18" time_minute="00" label_pos="aside" seconds_label=""]
```

**Anti-patterns**

- Past dates without `action_after_end="show_message"` — visitors see a row of zeros with no context.
- Showing the seconds caption for multi-day deadlines — the spinning seconds are distracting; set `seconds_label=""` or `label_pos="aside"`.
- Trying to pass a single ISO string (e.g. `date="2026-12-31"`) — that parameter does not exist; date is composed of `time_year` + `time_month` + `time_day` + `time_hour` + `time_minute`.
