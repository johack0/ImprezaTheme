---
title: `us_sharing` — Sharing Buttons
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/sharing.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/sharing.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=sharing
  Direct edits here will be lost on the next regeneration.
-->

# `us_sharing` — Sharing Buttons

**When to use**: a row of social-network share buttons that post the current page (or a custom URL) to Facebook / X / LinkedIn / etc. Typical placement: under a blog post title, on a product page, in a "share this" footer block.

**Avoid when**:
- you want links to **your** social profiles — `[us_socials]` is that;
- you want a single "share via…" button — a forms plugin or a `us_btn` with a sharer URL is simpler;
- the page isn't publicly accessible — share targets resolve to 404 / login walls.

**Modes**:
- inline row (default styles `simple`, `solid`, `outlined`) — renders in the page flow;
- `type="fixed"` — sticky rail attached to the side of the viewport.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `providers` | Comma-separated list of platforms to show. Default `facebook,twitter`. Allowed values: `email`, `facebook`, `twitter`, `linkedin`, `pinterest`, `vk`, `whatsapp`, `xing`, `reddit`, `telegram`. |
| `type` | Visual style — `simple` (default — minimal icons), `solid` (filled circles), `outlined` (bordered), `fixed` (sticky side rail). |
| `align` | Inline alignment — `none` (default), `left`, `center`, `right`, `justify`. Ignored when `type="fixed"`. |
| `color` | Icon coloring — `default` (brand colors), `primary` (theme primary), `secondary` (theme secondary). |
| `text_selection` | `1` shows a floating share panel when the visitor selects text on the page (and the share posts the selected text). |
| `text_selection_post` | `1` restricts the text-selection panel to **inside the main post content** only — quoted UI text doesn't trigger it. Requires `text_selection="1"`. |
| `url` | Custom share URL. Empty (default) uses the current page URL. |

**Minimal example**

```text
[us_sharing providers="facebook,twitter,linkedin,telegram"]
```

**Common combinations**

Sticky side rail with solid brand colors:

```text
[us_sharing type="fixed" providers="facebook,twitter,linkedin,whatsapp,email" color="default"]
```

Centered row under a blog post, with text-selection sharing limited to the post body:

```text
[us_sharing align="center" providers="twitter,linkedin,reddit"
            text_selection="1" text_selection_post="1"]
```

**Anti-patterns**

- `providers` including platforms your audience doesn't use (Xing on a non-DACH site, VK outside Russia) — clutters the row.
- Setting a custom `url` and forgetting to update it when the page is renamed — the share posts a 404.
- Using `type="fixed"` on a page with a fixed-position chat widget on the same side — the two overlap.
- More than 5–6 platforms in one row — visitors don't pick from a dozen; pick the channels that actually drive traffic.
