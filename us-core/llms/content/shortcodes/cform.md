---
title: `us_cform` — Contact Form
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/cform.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/cform.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=cform
  Direct edits here will be lost on the next regeneration.
-->

# `us_cform` — Contact Form

**When to use**: a simple lead-gen form (Name, Email, Message) or any short multi-field form that mails the submission. Pure shortcode-driven — no separate form plugin needed.

**Avoid when**:
- you need conditional logic, multi-step or payment integration — use a dedicated plugin (Gravity Forms, Contact Form 7);
- you need a search box — `[us_search]`;
- you only need a newsletter opt-in tied to MailChimp/etc. — use the provider's embed.

**Key parameters**

**Fields**

| Param | What it does |
|-------|--------------|
| `items` | The form's fields. **URL-encoded JSON array** of field objects (see "Encoding the `items` group" below). If omitted, the default 3-field set (Name + Email + Message) is rendered. The submit button is **not** an item — it is configured by the `button_*` params below. |

**Button** (the submit button)

| Param | What it does |
|-------|--------------|
| `button_text` | Submit-button label. Default `Submit`. Supports dynamic values. |
| `button_style` | Visual style id from Theme Options → Buttons (e.g. `1`, `2`, `3`). Default `1`. |
| `button_size` | Custom CSS font-size for the submit button (e.g. `1.1rem`). |
| `button_size_mobiles` | Submit-button font-size on mobile. |
| `button_align` | `none` (default), `left`, `center`, `right`, `justify` (stretch full-width). Responsive. |
| `icon` | Submit-button icon (`set|name`, e.g. `fas|paper-plane`). |
| `iconpos` | `left` (default) or `right`. |

**Appearance**

| Param | What it does |
|-------|--------------|
| `us_field_style` | Field style id from Theme Options → Field Styles. Default `default`. |
| `fields_layout` | `ver` (default — vertical stack) or `hor` (horizontal — fields wrap inline). |
| `fields_gap` | Gap between fields. Default `1rem`. |
| `action_after_sending` | What happens after a successful submit — `show_message` (default), `show_reusable_block`, `redirect`, `open_popup`, `close_popup`. |
| `success_message` | Shown after submit when `action_after_sending="show_message"`. **Must be `base64_encode(rawurlencode("plain text"))`** — the runtime calls `rawurldecode(base64_decode($value, TRUE))`, and a value that does not pass strict base64 decoding becomes empty silently. HTML is allowed inside the plain text. Omit to use the theme's default localised string. |
| `reusable_block` | ID of a `us_page_block` post to render in place of the form when `action_after_sending="show_reusable_block"`. |
| `redirect_url` | Destination URL when `action_after_sending="redirect"`. |
| `popup_selector` | Class or ID of the popup to open when `action_after_sending="open_popup"` (e.g. `.my-popup`, `#my-popup`). |
| `hide_form_after_sending` | `1` removes the form from the DOM after `show_message` / `show_reusable_block`. Default `0`. |

**Mail**

| Param | What it does |
|-------|--------------|
| `receiver_email` | Recipient(s). Comma-separate for multiple addresses. Defaults to the site admin email. |
| `email_subject` | Subject line. Default `Message from [page_title]`. Placeholders `[page_title]`, `[page_url]` and field labels like `[field_list]` are replaced at send time. |
| `email_message` | Body of the outgoing email. **Same encoding as `success_message`** — `base64_encode(rawurlencode("html"))`. Default body contains `[field_list]` (auto-expands to all submitted field values) plus a link to the page. |
| `bcc_email` | Blind carbon copy recipient. |
| `reply_to` | Reply-To header. Empty → the value of the first `email` field becomes the Reply-To address. |
| `auto_respond` | `1` sends an acknowledgement email to the submitter. Default `0`. |
| `auto_respond_subject` | Subject of the acknowledgement email. |
| `auto_respond_message` | Body of the acknowledgement. Same `base64(rawurlencode(...))` encoding as `email_message`. |

**Field types** (the `type` of each `items[]` entry)

| Value | Renders | Notable per-type keys |
|-------|---------|-----------------------|
| `text` | single-line input | `inputmode` (`text` / `decimal` / `numeric` / `tel` / `url`), `placeholder`, `icon`, `is_used_as_from_name` (`"1"` uses this field's value as the email's `From:` display name) |
| `textarea` | multi-line input | `placeholder`, `icon` |
| `email` | email input with format validation | `placeholder`, `icon`, `is_used_as_from_email` (`"1"` uses this field's value as the email's `From:` address — see anti-patterns) |
| `date` | date picker | `date_format` (jQuery UI format, default `d MM yy`), `placeholder`, `icon` |
| `select` | dropdown | `values` (newline-separated string — see encoding notes) |
| `checkboxes` | multi-check group | `values` (newline-separated) |
| `radio` | radio group | `values` (newline-separated), `pre_select_first_value` (`"1"` selects the first option by default) |
| `file` | file upload | `accept` (extensions or MIME, e.g. `.pdf,.jpg` or `image/*`), `file_max_size` (default `10MB`) |
| `info` | static text block (not a real field, just a heading / note inline with other fields) | `value` (the text — plain) |
| `agreement` | required-consent checkbox (T&C, privacy etc.) | `value` (label text — plain, may contain inline HTML) |
| `captcha` | math captcha | `icon` |
| `reCAPTCHA` | Google reCAPTCHA v3 | (requires keys in Theme Options → Advanced) |

There is **no** `tel`, `checkbox`, `hidden` or `submit` type at the item level. For a phone field use `type="text"` with `inputmode="tel"`; the multi-check type is `checkboxes` (plural); the submit button is the `button_*` shortcode params, not an item.

**Common per-item keys** (apply across most types)

| Key | What it does |
|-----|--------------|
| `type` | Field type (see table above). Required. |
| `label` | Title shown above the field. Not applicable to `info` / `reCAPTCHA`. |
| `description` | Hint shown below the field. |
| `placeholder` | Empty-state text inside the field. Honoured by `text` / `email` / `date` / `textarea`. |
| `required` | `"1"` makes the field mandatory. Default `"0"`. |
| `cols` | Per-field width inside the form grid: `"1"` (full — default), `"2"` (1/2), `"3"` (1/3), `"4"` (1/4). Two consecutive `cols="2"` fields sit side by side; one `cols="2"` followed by `cols="1"` leaves a half-row gap on the right. |
| `icon` | Inline icon inside the field (`set|name`). Available on text / email / date / textarea / select / captcha. |
| `move_label` | `"1"` floats the label above the field on focus. Available on text / email / date / textarea / captcha. |

**Encoding the `items` group**

At render time the template calls `json_decode( urldecode( $items ), TRUE )` — so `items` is a **URL-encoded JSON array of field objects**. Two practical shapes:

- **A. Single-quoted raw JSON** — readable, works when the JSON contains no `+` characters (PHP's `urldecode` turns `+` into a space) and no characters that would break the shortcode parser:

  ```text
  items="%5B%7B%22type%22%3A%22text%22%2C%22label%22%3A%22Your%20name%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22email%22%2C%22label%22%3A%22Email%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22textarea%22%2C%22label%22%3A%22Message%22%7D%5D"
  ```

- **B. URL-encoded JSON inside double quotes** — always safe, less readable. Use when item values contain `+`, `&`, multiline content, or other characters that could collide with shortcode/attribute parsing:

  ```text
  items="%5B%7B%22type%22%3A%22text%22%2C%22label%22%3A%22Your%20name%22%2C%22required%22%3A%221%22%7D%5D"
  ```

Authoring recipe — same two-step pattern as for `css="…"`:

1. Write the array as readable JSON: `[{"type":"text","label":"Name","required":"1"}, …]`.
2. Either drop it in between single quotes (Form A) or `rawurlencode` it and drop into double quotes (Form B).

Inside `values` strings, line breaks stay literal JSON `\n` escapes: `"values":"One\nTwo\nThree"`. The JSON decoder turns them into real newlines before the field template runs `explode("\n", $values)`. **`values` is plain — do not base64-encode it** (that's only for `success_message` / `email_message` / `auto_respond_message`).

Boolean-like switches (`required`, `is_used_as_from_email`, `is_used_as_from_name`, `pre_select_first_value`, `move_label`) take string `"0"` / `"1"`, not JSON booleans.

**Minimal example**

```text
[us_cform receiver_email="hello@example.com"]
```

(The default `items` set provides Name + Email + Message. The submit button defaults to `button_text="Submit"`. `success_message` falls back to the theme's default localised string when omitted.)

**Common combinations**

Lead form with phone (`text` + `inputmode="tel"`), a service dropdown, and a consent checkbox:

```text
[us_cform receiver_email="hello@example.com" button_text="Send request" items="%5B%7B%22type%22%3A%22text%22%2C%22label%22%3A%22Your%20name%22%2C%22required%22%3A%221%22%2C%22is_used_as_from_name%22%3A%221%22%7D%2C%7B%22type%22%3A%22email%22%2C%22label%22%3A%22Email%22%2C%22required%22%3A%221%22%2C%22is_used_as_from_email%22%3A%221%22%7D%2C%7B%22type%22%3A%22text%22%2C%22label%22%3A%22Phone%22%2C%22inputmode%22%3A%22tel%22%2C%22placeholder%22%3A%22%2B1%20555%20%E2%80%A6%22%7D%2C%7B%22type%22%3A%22select%22%2C%22label%22%3A%22Service%22%2C%22values%22%3A%22Bath%20%26%20Brush%5CnFull%20Haircut%5CnSpa%20Day%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22textarea%22%2C%22label%22%3A%22Tell%20us%20about%20your%20pet%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22agreement%22%2C%22value%22%3A%22I%20agree%20to%20be%20contacted%20about%20my%20request.%22%2C%22required%22%3A%221%22%7D%5D"]
```

Two-column form layout — name halves on top row, full-width message below:

```text
[us_cform items="%5B%7B%22type%22%3A%22text%22%2C%22label%22%3A%22First%20name%22%2C%22cols%22%3A%222%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22text%22%2C%22label%22%3A%22Last%20name%22%2C%22cols%22%3A%222%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22email%22%2C%22label%22%3A%22Email%22%2C%22cols%22%3A%222%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22text%22%2C%22label%22%3A%22Phone%22%2C%22inputmode%22%3A%22tel%22%2C%22cols%22%3A%222%22%7D%2C%7B%22type%22%3A%22textarea%22%2C%22label%22%3A%22Message%22%2C%22cols%22%3A%221%22%2C%22required%22%3A%221%22%7D%5D"]
```

File-upload form (job application — CV attachment + cover letter):

```text
[us_cform receiver_email="careers@example.com" email_subject="Job application from [page_title]" items="%5B%7B%22type%22%3A%22text%22%2C%22label%22%3A%22Full%20name%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22email%22%2C%22label%22%3A%22Email%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22file%22%2C%22label%22%3A%22Upload%20CV%22%2C%22accept%22%3A%22.pdf%2C.doc%2C.docx%22%2C%22file_max_size%22%3A%225MB%22%2C%22required%22%3A%221%22%7D%2C%7B%22type%22%3A%22textarea%22%2C%22label%22%3A%22Cover%20letter%22%7D%5D"]
```

Custom success message — pre-encoded as `base64_encode(rawurlencode("Thanks — we'll reply within 24 hours."))`:

```text
[us_cform receiver_email="hello@example.com" success_message="VGhhbmtzJTIwJUUyJTgwJTk0JTIwd2UlMjdsbCUyMHJlcGx5JTIwd2l0aGluJTIwMjQlMjBob3Vycy4="]
```

**Anti-patterns**

- **Passing `success_message` / `email_message` / `auto_respond_message` as plain text** — the runtime runs strict `base64_decode`; non-base64 strings decode to `FALSE`, so the message disappears entirely with no fallback. Always pre-encode as `base64_encode(rawurlencode("plain text"))`, or omit the param to use the theme default.
- **Using the old attribute names** `btn_label` / `btn_style` / `field_style` / `email_to` — none of these exist. The real names are `button_text` / `button_style` / `us_field_style` / `receiver_email`.
- **Item types `tel` / `checkbox` / `hidden` / `submit`** — none exist at the item level. Phone is `type="text"` with `inputmode="tel"`; the multi-check type is `type="checkboxes"` (plural); the submit button is configured by `button_*` shortcode params, not an item.
- **Base64-encoding `values` on a `select` / `radio` / `checkboxes` item** — that param is plain newline-separated text in the JSON. The base64 layer applies only to `success_message` / `email_message` / `auto_respond_message`.
- **JSON booleans** (`"required":true`) — the framework expects the string `"1"` / `"0"`. Booleans get coerced inconsistently.
- 10+ fields in a lead form — conversion drops sharply past 4-5. Match fields to the offer.
- **`is_used_as_from_email="1"` plus strict SPF/DKIM on the sending host** — many mail providers silently drop messages whose `From:` is a domain they don't authorise. Prefer leaving `From:` as the site admin address and rely on the auto-populated `reply_to` (the email field's value) for replies.
- **Omitting `receiver_email`** on a multi-author install — the form lands in whoever happens to be the WP admin's inbox, which is rarely the right person.
