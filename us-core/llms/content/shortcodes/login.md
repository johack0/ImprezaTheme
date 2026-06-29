---
title: `us_login` — Login
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/login.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/login.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=login
  Direct edits here will be lost on the next regeneration.
-->

# `us_login` — Login

**When to use**: an inline WordPress login form (username + password) on a public page — typical for a customer portal landing page, a members-area gate, or a header dropdown that flips between "Sign in" and "My account" depending on auth state.

**Avoid when**:
- you only need a link to `wp-login.php` — that's a `[us_btn]` with `link="%7B%22url%22%3A%22wp-login.php%22%7D"`;
- you need a full registration form with custom fields — use a forms plugin (Gravity Forms, etc.) or WP's standard registration flow with `register` set;
- you want a social-login button row — that's a third-party plugin's territory.

**Auth state**: when the visitor is already logged in, the shortcode renders a logout link instead of the form (using `logout_redirect` if set).

**Key parameters**

| Param | What it does |
|-------|--------------|
| `us_field_style` | Per-site form-field style. Comes from Theme Options → Field Styles. Default `default`. |
| `register` | URL of the "Register" link shown under the form. Empty hides it. |
| `lost_password` | URL of the "Lost your password?" link. Empty falls back to `wp_lostpassword_url()`. |
| `login_redirect` | URL the visitor lands on after a successful login. Empty redirects to the home page. |
| `logout_redirect` | URL the visitor lands on after logging out (when the shortcode renders the logout link for an authenticated visitor). |
| `use_ajax` | `1` submits via AJAX. **Recommended when page caching is enabled for logged-in visitors** — otherwise the cached HTML may show the form to an already-logged-in user. |

**Minimal example**

```text
[us_login]
```

**Common combinations**

Cached site, AJAX submit, register link points to a custom signup page:

```text
[us_login use_ajax="1" register="https://example.com/signup/" login_redirect="https://example.com/dashboard/"]
```

**Anti-patterns**

- Placing `us_login` on a page that's behind the login itself — logged-in visitors get a logout link in a place they didn't expect.
- Mixing `us_login` with a forms plugin (Gravity Forms login form) on the same page — visitors see two competing forms.
- Leaving `use_ajax="0"` on a cached site — the form may auto-submit a stale nonce, or the page may show a "you are logged in" message to a logged-out visitor.
- Hard-coding `lost_password` to a third-party URL when WP can handle it natively — just leave it empty.
