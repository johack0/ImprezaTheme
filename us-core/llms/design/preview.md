# Theme options preview links

Tools:
- `upsolution-create-preview` — bundle a draft palette + typography + button-styles change set into a shareable URL that renders the site with those settings applied. Does NOT touch the live Theme Options.
- `upsolution-delete-preview` — drop a preview by key (idempotent).

Use this when the user wants to see a change before committing — "show me the new brand color in the header before I apply it" — or to hand a sharable link to a non-logged-in reviewer (client, designer, marketing).

## Input shape

Three optional draft surfaces (at least one must be non-empty):

```json
{
  "palette": {
    "header":         { "color_header_middle_bg": "#111111" },
    "content":        { "color_content_primary": "_brand" },
    "custom_colors":  [ { "color": "#0a84ff", "name": "Brand", "slug": "brand" } ]
  },
  "typography": [
    { "tag": "body", "fields": { "font-size": "16px" } },
    { "tag": "h1",   "fields": { "font-weight": "800" }, "merge": true }
  ],
  "button_styles": {
    "operations": [
      { "op": "update", "id": 1, "fields": { "color_bg": "#0a84ff" } }
    ]
  },
  "ttl_seconds": 21600,
  "label": "Brand v2"
}
```

- **`palette`** — same shape `upsolution-set-palette` accepts. See `design/color-palette`.
- **`typography`** — ARRAY of per-tag patches (`set-typography` is one tag per call; preview takes many tags at once). See `design/typography`. Duplicate `tag` values are rejected.
- **`button_styles`** — same `{operations:[{op,...}]}` payload `upsolution-set-button-styles` accepts (add / update / delete / reorder applied atomically to the buttons list). See `design/buttons`. The same two hard rules hold (ids immutable, list cannot become empty).
- **`label`** — short string (≤60 chars) shown in the bottom-right "Preview mode" banner so reviewers distinguish concurrent previews.

The schema's `ttl_seconds`, response shape, and per-field validation rules mirror the corresponding set-* tools — pulled from the tool descriptions at call time, not repeated here.

## What's unique to preview (not in the set-* tools)

### How the URL works

- Query var is `us_theme_options_preview` and works on ANY URL of the site. The returned `url` points at `home_url('/')` for convenience; manually appending `?us_theme_options_preview=<key>` to any other path activates the preview there too.
- On a preview-activated page, all same-origin `<a href>` are rewritten on page load to carry the same key — internal navigation stays inside the preview session.
- Floating "Preview mode · 9f2c47… · {label} · expires in 23h 45m 30s · Exit preview" banner sits in the bottom-right. "Exit preview" navigates to the current URL minus the query var.
- Page caches (W3TC, WP Rocket, Varnish, object cache) are bypassed on preview responses (`Cache-Control: private, no-store`, `X-Robots-Tag: noindex`).

### Security model

Anyone with the URL sees the preview — no login check. Treat the link like a shared password:

- Generated keys are 16 random bytes (32 hex chars) — not brute-forceable.
- Bearer token: external sharing exposes the preview until TTL expires or `delete-preview` is called.
- Credentials are NEVER serialised. Options matching the credential deny-list (`api_key` / `secret` / `access_token` / `password` / `recaptcha_*` and similar) keep their live values when the preview renders — Google Maps / reCAPTCHA / etc. keep working without leaking credentials through the stored snapshot.

### Storage and lifetime

- Snapshots are stored server-side with a lifetime equal to the supplied `ttl_seconds`; expired previews are cleaned up automatically.
- The snapshot is a **frozen** copy of Theme Options at create time (minus the denylist), with the patches merged in. Later live changes do NOT show in the preview — regenerate via `create-preview` to incorporate them (fresh live values are read).
- Each `create-preview` call produces a new independent key. Multiple concurrent previews don't interact.

## Workflow

1. (Optional) `get-palette` / `get-theme-option` / `list-button-styles` to read current state.
2. `create-preview` with the desired draft. Capture the returned `url` and `key`.
3. Tell the user the URL. They open it, browse, decide.
4. To commit: `set-palette` and/or `set-typography` and/or `set-button-styles` with the SAME input. Optionally `delete-preview` afterwards to clean up.
5. To iterate: `create-preview` again with the revised input. Optionally `delete-preview` on the previous key first.

## Anti-patterns

- Don't share the preview URL on public channels (social media, indexed pages) — it's a bearer token.
- Don't generate previews with `ttl_seconds: 60` for an actual review flow — by the time you reply the link is half-dead. Use the default (6h) or longer (up to 24h).
- Don't expect dynamic JS-loaded links (AJAX-injected, infinite scroll) to auto-carry the preview key — the rewrite runs once at page load.
- Don't try to revoke a preview by any means other than `delete-preview` — there is no generic option/storage writer tool, and you'd skip the per-key validation.
- Don't assume the preview shows POST form submissions — the link rewrite handles `<a href>`, not form actions. Visitors can browse but can't submit forms with the preview still active unless they manually keep the query var on the destination URL.
