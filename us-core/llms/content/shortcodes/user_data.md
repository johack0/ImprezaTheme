---
title: `us_user_data` — User Data
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/user_data.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/user_data.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=user_data
  Direct edits here will be lost on the next regeneration.
-->

# `us_user_data` — User Data

**When to use**: renders a single field from a WordPress user — the post's author by default, or any other user in the current loop context. Typical placement: a "by {{author}}" line in a blog template, an author-bio card, an account-page greeting, a member directory entry.

**Avoid when**:
- you need a full author-card (avatar + name + bio + social) — `[us_person]` is purpose-built for that;
- you want a list of multiple users — that's `[us_user_list]` — see `shortcodes/user_list`;
- you want a post field (date, category, title) — that's `[us_post_*]` shortcodes (also out of scope).

**Context**: this shortcode reads from the current user-loop item, or — if no loop is active — from the author of the current post. Inside a `us_user_list` item template, each iteration sets the current user.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `type` | What to render. One of: `display_name` (default), `first_name`, `last_name`, `nickname`, `user_email`, `user_url`, `description` (biographical info), `role`, `user_registered` (registration date), `post_count` (number of posts authored), `custom` (a usermeta field). |
| `custom_field` | Usermeta key to read, when `type="custom"`. |
| `post_type` | Comma-separated post type slugs to count, when `type="post_count"`. Default `post`. |
| `date_format` | PHP date format, when `type="user_registered"`. Default `F j, Y`. |
| `link` | Optional link wrapping the value. URL-encoded JSON (see composition-rules §3.1), decoded `{"url":"...","target":"_blank"}`. The special sentinel `{"url":"elm_value"}` makes the value itself clickable (only for email/phone/website types — turns into `mailto:`/`tel:`/the URL). |
| `color_link` | `1` makes the link inherit the surrounding text color instead of the theme link color. |
| `tag` | HTML tag to wrap the value in. Default `div`. Use `h1`-`h6` for headings, `span` for inline. |
| `text_before` / `text_after` | Static text rendered before/after the value (e.g. `text_before="by "`). Supports dynamic values. |

**Minimal example**

Display the current post author's name:

```text
[us_user_data type="display_name"]
```

Author byline with prefix:

```text
[us_user_data type="display_name" text_before="by " tag="span"]
```

**Common combinations**

Clickable email link, inheriting text color:

```text
[us_user_data type="user_email" link="%7B%22type%22%3A%22elm_value%22%7D" color_link="1"]
```

Post count for a specific custom post type, in an author-card sidebar:

```text
[us_user_data type="post_count" post_type="portfolio" text_after=" projects"]
```

Registration date, custom format:

```text
[us_user_data type="user_registered" date_format="M Y" text_before="Member since "]
```

**Anti-patterns**

- `type="custom"` without `custom_field` — renders nothing.
- `type="post_count"` with `post_type=""` — the count silently falls back to `post`, missing your CPTs.
- Wrapping with a generic `link` URL when the value should be the link (e.g. email) — use the `{"url":"elm_value"}` sentinel instead so the value itself becomes the `mailto:` link.
- Reading `description` and forgetting it may contain HTML — wrap in a `tag="div"` (the default), never `tag="span"` if the bio has paragraphs.
