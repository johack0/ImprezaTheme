---
title: `us_html` — Raw HTML
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (body from scripts/llms/manual/shortcodes/html.md)
---

<!--
  GENERATED FILE — do not edit directly.
  Body comes from scripts/llms/manual/shortcodes/html.md
  Rebuild:  php scripts/llms/build.php --wp-load=… --only=content-shortcodes --only-shortcode=html
  Direct edits here will be lost on the next regeneration.
-->

# `us_html` — Raw HTML

**When to use**: paste arbitrary HTML/JS/CSS into the page — third-party embed snippets (Calendly, Typeform, custom map widgets), inline `<script>`/`<style>` for one-off pages, structured-data JSON-LD.

**Avoid when**:
- you have a single styled paragraph or heading — `us_text` is the right call;
- the content is rich-edited prose — `vc_column_text`;
- the snippet is large and needs editor highlighting — keep it in a child-theme template and just place an anchor here.

**Key parameters**

| Param | What it does |
|-------|--------------|
| `html` (content) | The raw HTML/JS body — base64-encoded under the hood, so any character is safe. Wrap your content between the opening and closing tag. |

**Minimal example**

```text
[us_html]
<script async src="https://example.com/widget.js"></script>
<div id="widget-host"></div>
[/us_html]
```

**Anti-patterns**

- Pasting tracking scripts that should live in `<head>` site-wide — put them in Theme Options → Code instead.
- Inline `<style>` that overrides theme variables on every page — extract to a child-theme stylesheet.
- Using `us_html` for everything — you lose the structured editing benefits of UpSolution shortcodes.
