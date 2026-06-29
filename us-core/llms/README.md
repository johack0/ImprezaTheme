# Authoring Conventions

Maintainer reference for the `plugins/us-core/llms/` documentation set. Defines the canonical record formats so the generators and manual overlays stay consistent.

**Documentation set version**: tracks `us-core` plugin version (current: **9.0**). Each generated file declares its source version in front-matter.

Not loaded by the MCP server — purely for humans (and Claude sessions) extending the build pipeline. Per-shortcode prose format is in `scripts/llms/manual/shortcodes/*.md` (manual overlays — read the existing ones as templates).

---

## File header

Every `.md` file in `content/` begins with this block:

```markdown
---
title: <human-readable section title>
audience: content
source: us-core <version>
generated: <YYYY-MM-DD> by scripts/llms/build.php (or "manual")
---
```

`generated` says whether the file is auto-rendered, manually written, or hybrid (skeleton from script + manual overlay).

---

## Common parameter groups

The element configs in `plugins/us-core/config/elements/*.php` end with three shared parameter packs that almost every shortcode mixes in verbatim. Documenting them inside every shortcode would balloon every per-shortcode file under `content/shortcodes/` by ~50 rows, so each group is documented once in its own file:

| Group | File | Source config | Generation |
|-------|------|---------------|------------|
| Effects | [element-effects.md](content/element-effects.md) | `plugins/us-core/config/elements_effect_options.php` | auto (`common-params.php`) |
| Display Logic | [element-display-logic.md](content/element-display-logic.md) | `plugins/us-core/config/elements_conditional_options.php` | auto (`common-params.php`) |
| Design | [element-design.md](content/element-design.md) | `plugins/us-core/config/elements_design_options.php` | **manual** |

The Design file is hand-written because its central `css` parameter is a nested `design_options` config (~80 CSS sub-properties across 11 categories) that needs prose explanation of JSON encoding, breakpoint keys, the property whitelist, and anti-patterns — none of which collapse cleanly into a flat param table. Edit `content/element-design.md` directly; the build script does not touch it.

A fourth group (Hover Effect, `elements_hover_options.php`) exists in the us-core configs but is not exposed via shortcodes in the current build, so it has no dedicated documentation file.

---

## Per-site (dynamic) option values

Some parameters expose **per-site option lists** configured in Theme Options — they cannot be documented as a stable enumeration:

| Source                                | Helper                       | Affected parameters (examples)                                                                                       |
|---------------------------------------|------------------------------|----------------------------------------------------------------------------------------------------------------------|
| Theme Options → Buttons               | `us_get_btn_styles()`        | `btn.style`, `flipbox.btn_style`, `gallery.btn_style`, `cta.btn_style`, `popup.btn_style`, `pricing.btn_style`, also `back_to_top_style`, `cookie_btn_style`, `skip_to_content_btn_style`, etc. in Theme Options |
| Theme Options → Field Styles          | `us_get_field_styles()`      | `search.style`, `login.style`, `cform.field_style`, `wc_account_login.field_style`, etc.                              |
| Theme Options → Color Schemes         | `us_get_color_schemes(TRUE)` | `dark_theme` in Theme Options (and any future per-site scheme-pickers)                                               |

A parameter's `options` map is collapsed to a "per-site values" marker **only** when the complete output of one of these helpers (every `(key, value)` pair) appears in the map unchanged. Partial overlaps — e.g. `vc_row.columns` numerically reusing key `1` — do not trigger the collapse, because their labels differ. This avoids the common false-positive where button-style IDs (`'1'`, `'4'`, `'5'`) coincidentally match column-count IDs.

The detection is implemented in `llms_render_options()` / `llms_dynamic_option_sources()` in `scripts/llms/generators/_helpers.php`. Output marker:

```text
**Options for `style`:**

  - _per-site values from Theme Options → Buttons — not enumerated, configured on each install_
  - `` — None     (any static fallback options are still listed individually)
```

---

## Section template entry schema

Section templates are pre-designed shortcode blocks fetched from the UpSolution help portal (`us.api`) — the same library the live builder exposes under the **Templates** tab. The snapshot is theme-specific (Impreza vs Zephyr) and license-gated: the generator must run on a site with an activated license (or `US_DEV_SECRET` / `US_THEMETEST_CODE` defined), otherwise the API returns no usable content.

Output layout:

- [`content/sections.md`](content/sections.md) — index page. Lists every category with its template count and a link to the per-category file.
- [`content/sections/<category-id>.md`](content/sections/) — one file per category. Each file documents every template as a `### <template-id>` record with the full shortcode markup.

Per-template record shape:

```markdown
### `<template-id>`

**Category**: `<category-id>` — <Category name>
**Preview**: <category-url>#section-NN     <!-- anchored to the template's position on the category preview page -->
**Thumbnail**: <thumb-url> (W×H)            <!-- omitted if the API returned no thumbnail -->

```text
[vc_row ...]
  [vc_column ...]
    ...
  [/vc_column]
[/vc_row]
```
```

Notes:

- **`template-id`** is the key the API uses inside `templates_config.data.<category>.templates`. Not always human-readable (e.g. `"01"`, `"hero_01"`) — the heading uses it verbatim so an agent can map back to the API response.
- **Preview** is the anchored URL on the category's public preview page. The anchor format (`#section-NN`, two-digit, 1-based) matches what the builder UI uses in `usof/templates/templates_list.php`.
- **Shortcode markup** is rendered verbatim from `templates_content.data.<template-id>`. The `use:placeholder` markers the live editor replaces with site-specific image placeholders are left in place — they document the intent of those slots without binding the snapshot to a specific install.
- Code fences use the `text` language tag, matching the rest of the documentation set.

Every `--only=sections` run fetches fresh from us.api — there is no on-disk cache. Each generated file declares its fetch date in `generated:`; re-run after a us-core or theme upgrade to refresh.

---

## Source-link notation

Always use repo-relative paths with no leading slash:

```text
plugins/us-core/config/elements/btn.php
plugins/us-core/templates/elements/btn.php
plugins/us-core/templates/css-theme-options.php
```

For specific lines, append `:NN` (e.g. `plugins/us-core/config/elements/btn.php:26`).

---

## Versioning

Each generated file declares `source: us-core <version>` in its header. When `us-core` is upgraded:

1. Run `scripts/llms/build.php` to regenerate.
2. Diff the result — review changed parameters and added/removed shortcodes.
3. Update manual overlays in `scripts/llms/manual/` if any user-facing meaning changed.

The build script writes the current `US_CORE_VERSION` into every output file automatically.

---

## Conventions for manual files

Manual files (anti-patterns, "when to use") follow Markdown best practice:

- One H1 per file (the `title` from front-matter is the document title).
- H2 for sections, H3 for sub-sections.
- Code blocks for shortcode examples must use the `text` language tag (not `html` or `php`), so agents recognize them as raw shortcode markup, not interpreted code.
