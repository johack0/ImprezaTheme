---
title: `us_person` — Team Member Card
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/person.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/person.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=person
  Direct edits here will be lost on the next regeneration.
-->

# `us_person` — Team Member Card

**When to use**: a profile card on Team / About / Authors pages — portrait, name, role, short bio, optional social links.

**Avoid when**:
- you want a testimonial (different layout) — consider a styled `us_iconbox` or custom layout;
- you only need a name + photo as plain text — `us_text` + `us_image`.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `image` | Portrait media ID. |
| `name` | Person name (required). |
| `role` | Job title or role. |
| `description` | Short bio sentence. |
| `link` | Wraps name/photo in a profile link (URL-encoded JSON, see composition-rules §3.1). |
| `style` | Visual style — `circle` / `square` / `card`. |
| `socials` | Inline list of social links (the dev spec has the exact `items`/`group` shape). |

**Minimal example**

```text
[us_person image="123" name="Jane Doe" role="Lead Designer" description="Builds the UI you actually want to use."]
```

**Anti-patterns**

- Inconsistent photo crops across cards in the same row — pre-crop to a single ratio in the media library.
- Listing all of someone's titles — pick the most relevant one for this page.
