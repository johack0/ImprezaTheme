---
title: `us_gmaps` — Map
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/gmaps.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/gmaps.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=gmaps
  Direct edits here will be lost on the next regeneration.
-->

# `us_gmaps` — Map

**When to use**: an interactive map embed pointing to one (or several) addresses, with optional info-window text. Typical placement: a contact page, a "find us" footer block, a multi-location showroom list.

**Avoid when**:
- you only need a static map image — Google's static API or a screenshot is lighter;
- you want a route / directions widget — `us_gmaps` only renders pins, not navigation;
- the only purpose is to surface the address text — use `[us_contacts address="…"]` (no map JS, no API needed).

**Providers**: two backends are supported via `provider`:
- `google` (default) — requires a Google Maps API key configured at **Theme Options → General → Google Maps API Key**. Without it, the embed renders the "for development purposes only" watermark.
- `osm` — OpenStreetMap (Leaflet). No API key required.

**Key parameters**

**Main marker**

| Param | What it does |
|-------|--------------|
| `marker_address` | Address or geo-coordinates (e.g. `38.6774156, 34.8520661`) for the primary pin. Required. |
| `marker_text` | HTML body for the marker's info window. **Must be base64-encoded** (the param's config has `encoded => TRUE`). `{{address}}` inside the body is replaced by the resolved address string. |
| `show_infowindow` | `1` opens the info window on load (rather than only on click). |
| `custom_marker_img` | Media-library ID (or URL) of a custom pin image. PNG/JPG/SVG. |
| `custom_marker_size` | Pin image size — one of `20`/`30`/`40`/`50`/`60`/`70`/`80` (pixels). Default `30`. |
| `markers` | Group of additional pins. JSON list; each item has `marker_address`, `marker_text` (textarea, not encoded), `marker_img`, `marker_size`. |

**Appearance**

| Param | What it does |
|-------|--------------|
| `provider` | `google` (default) or `osm`. Switches API and toggles which extra params apply. |
| `type` | (`provider="google"` only) Tile style — `roadmap` (default), `terrain`, `satellite`, `hybrid`. |
| `zoom` | Map zoom level 1–20. Default `14`. |
| `hide_controls` | `1` hides all map controls (zoom buttons, fullscreen). |
| `disable_zoom` | `1` disables zoom on mouse-wheel scroll. **Strongly recommended** when the map is in the page flow — otherwise visitors who scroll over it get trapped. |
| `disable_dragging` | `1` disables one-finger dragging on touch screens (visitor must use two fingers). |
| `map_style_json` | (`provider="google"` only) Snazzymaps-style JSON to recolor the map. **Base64-encoded**. |
| `layer_style` | (`provider="osm"` only) Tile-server URL template, e.g. `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`. See leaflet-providers for the catalog. |

**Sizing**

The map height comes from the Design Options group (custom CSS `height`). There is no `height` shortcode attribute — set it via the builder's Design panel or wrap the shortcode in a fixed-height container.

**Minimal example**

```text
[us_gmaps marker_address="1600 Amphitheatre Parkway, Mountain View, CA" zoom="15" disable_zoom="1"]
```

**Common combinations**

OpenStreetMap, no API key needed:

```text
[us_gmaps provider="osm" marker_address="55.7558, 37.6173" zoom="13" disable_zoom="1"]
```

With an info window (note the base64-encoded body):

```text
[us_gmaps marker_address="Berlin, Germany" show_infowindow="1"
  marker_text="PGgyPkJlcmxpbiBPZmZpY2U8L2gyPjxwPllvdXIgdHJ1c3RlZCB0ZWFtLjwvcD4="]
```

(`marker_text` decodes to `<h2>Berlin Office</h2><p>Your trusted team.</p>`.)

**Anti-patterns**

- Forgetting `disable_zoom="1"` — the map captures mouse-wheel scroll and visitors can't continue down the page.
- Pasting raw HTML into `marker_text` — it must be base64-encoded; non-encoded HTML reads as literal text and breaks the info window.
- Embedding the map without configuring a Google API key (with `provider="google"`) — the embed renders a "for development purposes only" watermark.
- Using `markers` for a single pin — use the top-level `marker_*` params; the group is for **additional** pins.
