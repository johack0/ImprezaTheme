---
title: `us_contacts` — Contact Info
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/contacts.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/contacts.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=contacts
  Direct edits here will be lost on the next regeneration.
-->

# `us_contacts` — Contact Info

**When to use**: a compact block listing address, phone, mobile, and email — designed for the footer "Get in touch" column, a contact-page side rail, or a header strip. Each non-empty attribute renders as one labelled line with an icon; empty attributes are hidden entirely.

**Avoid when**:
- you need a contact form — `[us_cform]`;
- you need a map with a pin — `[us_gmaps]`;
- you need social-platform icons — `[us_socials]`;
- you only have one line (e.g. just an email) — author it as `[us_iconbox]` with a free-form text and link.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `address` | Postal address line. Renders only if non-empty. Supports dynamic values (e.g. `{{custom_field}}`). |
| `phone` | Primary phone number. Rendered as a `tel:` link. Supports dynamic values. |
| `fax` | Despite the param name, this is the **Mobile** number in the UI (legacy naming). Rendered as a `tel:` link. Supports dynamic values. |
| `email` | Email address. Rendered as a `mailto:` link. Supports dynamic values. |

**Minimal example**

```text
[us_contacts address="221B Baker St, London" phone="+44 20 7946 0958" email="hello@example.com"]
```

**Common combinations**

Footer column, mobile-only field included:

```text
[us_contacts
  address="Office 14, 22 Marshala Zhukova"
  phone="+7 495 123 45 67"
  fax="+7 916 000 00 00"
  email="info@example.com"]
```

**Anti-patterns**

- Storing the email as plain text inside `us_text` — visitors can't tap to send mail, and you lose dynamic-value support.
- Filling `fax` thinking it's a fax number — it's the Mobile field. If you really need a fax line, put it in `address` or another `us_iconbox`.
- Wrapping the block in a link (`us_hwrapper link="…"`) — each line already has its own action; the outer link disables them.
