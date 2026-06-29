---
title: Shortcodes for Content Generation
audience: content
source: us-core 9.0
generated: 2026-06-23 by scripts/llms/build.php (index; per-shortcode bodies in shortcodes/<id>.md)
---

<!-- GENERATED — do not edit directly. Rebuild: php scripts/llms/build.php --only=content-shortcodes -->

# Shortcodes for Content Generation

> Compact guide for AI agents that compose page content (`post_content`) using UpSolution shortcodes. Each entry tells you **when to use**, **when to avoid**, key parameters with valid values, minimal copy-paste examples, and common combinations.
>
> Cross-cutting rules — root structure, nesting graph, attribute encoding, `icon="…"` picker (mandatory before any icon attribute), palette tokens, anti-patterns — live in [`composition-rules.md`](composition-rules.md). Read the "HARD RULES" card at the top of that file first.
>
> Shared parameter packs documented separately: [Effects](element-effects.md), [Display Logic](element-display-logic.md), [Design](element-design.md). [Dynamic Values](element-dynamic-values.md) covers `{{…}}` text tokens and `link` enums.
>
> Tags appear with their `us_*` / `vc_*` prefix exactly as required in `post_content`. Containers (`vc_row`, `vc_column`, `us_hwrapper`, `vc_tta_*`) are listed first — every block must live inside `vc_row` → `vc_column` at the top level.
>
> Per-shortcode doc ids drop the `us_` prefix but keep `vc_`: `us_btn` → `shortcodes/btn`, `vc_row` → `shortcodes/vc_row`. Keeping the `us_` prefix in the doc id is a 404.

## How to use this index

Each shortcode below links to its own file under [`shortcodes/`](shortcodes/) containing the full **When to use / Avoid when / Key parameters / Examples / Anti-patterns** record. Open only the entries you need — the table of contents is intentionally compact so the agent can browse the full set without paying the cost of every body.

## Containers

- [`vc_row` — Row / Section](shortcodes/vc_row.md)
- [`vc_row_inner` — Inner Row](shortcodes/vc_row_inner.md)
- [`vc_column` — Column](shortcodes/vc_column.md)
- [`vc_column_inner` — Inner Column](shortcodes/vc_column_inner.md)
- [`us_hwrapper` — Horizontal Wrapper](shortcodes/hwrapper.md)
- [`us_vwrapper` — Vertical Wrapper](shortcodes/vwrapper.md)
- [`vc_tta_accordion` — Accordion](shortcodes/vc_tta_accordion.md)
- [`vc_tta_tabs` — Tabs](shortcodes/vc_tta_tabs.md)
- [`vc_tta_tour` — Vertical Tabs (Tour)](shortcodes/vc_tta_tour.md)
- [`vc_tta_section` — Tab / Accordion / Tour Item](shortcodes/vc_tta_section.md)
- [`us_content_carousel` — Content Carousel](shortcodes/content_carousel.md)
- [`us_timeline` — Timeline](shortcodes/timeline.md)
- [`us_timeline_section` — Timeline Section](shortcodes/timeline_section.md)

## Basic

- [`vc_column_text` — Rich Text (WPBakery)](shortcodes/vc_column_text.md)
- [`us_text` — Text Block](shortcodes/text.md)
- [`us_btn` — Button](shortcodes/btn.md)
- [`us_iconbox` — Icon Box](shortcodes/iconbox.md)
- [`us_image` — Image](shortcodes/image.md)
- [`us_separator` — Separator](shortcodes/separator.md)

## Interactive

- [`us_gallery` — Image Gallery](shortcodes/gallery.md)
- [`us_image_slider` — Image Slider](shortcodes/image_slider.md)
- [`us_counter` — Animated Counter](shortcodes/counter.md)
- [`us_countdown_timer` — Countdown Timer](shortcodes/countdown_timer.md)
- [`us_flipbox` — Flip Box](shortcodes/flipbox.md)
- [`us_ibanner` — Interactive Banner](shortcodes/ibanner.md)
- [`us_itext` — Interactive Text](shortcodes/itext.md)
- [`us_message` — Message Box](shortcodes/message.md)
- [`us_popup` — Popup](shortcodes/popup.md)
- [`us_progbar` — Progress Bar](shortcodes/progbar.md)
- [`us_scroller` — Page Scroller](shortcodes/scroller.md)
- [`us_color_scheme_switch` — Color Scheme Switch](shortcodes/color_scheme_switch.md)

## Other

- [`us_cform` — Contact Form](shortcodes/cform.md)
- [`us_cta` — Call To Action](shortcodes/cta.md)
- [`us_dropdown` — Dropdown](shortcodes/dropdown.md)
- [`us_person` — Team Member Card](shortcodes/person.md)
- [`us_pricing` — Pricing Table](shortcodes/pricing.md)
- [`us_additional_menu` — Additional Menu](shortcodes/additional_menu.md)
- [`us_search` — Search Form](shortcodes/search.md)
- [`us_socials` — Social Links](shortcodes/socials.md)
- [`vc_video` — Video Embed](shortcodes/vc_video.md)
- [`us_html` — Raw HTML](shortcodes/html.md)
- [`us_page_block` — Reusable Block](shortcodes/page_block.md)
- [`vc_widget_sidebar` — Sidebar with Widgets](shortcodes/vc_widget_sidebar.md)
- [`us_contacts` — Contact Info](shortcodes/contacts.md)
- [`us_gmaps` — Map](shortcodes/gmaps.md)
- [`us_login` — Login](shortcodes/login.md)
- [`us_category_nav` — Category Navigation](shortcodes/category_nav.md)
- [`us_sharing` — Sharing Buttons](shortcodes/sharing.md)
- [`us_user_data` — User Data](shortcodes/user_data.md)

## Lists

- [`us_post_list` — Post List](shortcodes/post_list.md)
- [`us_post_carousel` — Post Carousel](shortcodes/post_carousel.md)
- [`us_product_list` — Product List](shortcodes/product_list.md)
- [`us_product_carousel` — Product Carousel](shortcodes/product_carousel.md)
- [`us_term_list` — Term List](shortcodes/term_list.md)
- [`us_term_carousel` — Term Carousel](shortcodes/term_carousel.md)
- [`us_user_list` — User List](shortcodes/user_list.md)
- [`us_user_carousel` — User Carousel](shortcodes/user_carousel.md)
- [`us_list_filter` — List Filter](shortcodes/list_filter.md)
- [`us_list_filter_reset` — List Filter Reset](shortcodes/list_filter_reset.md)
- [`us_list_order` — List Order](shortcodes/list_order.md)
- [`us_list_search` — List Search](shortcodes/list_search.md)
- [`us_list_result_counter` — List Result Counter](shortcodes/list_result_counter.md)

## Post Elements

> **Section context — where to use these.**
>
> Every shortcode in this section is **designed for Page Templates** (`us_content_template`). See [post-types.md](post-types.md) for the Page vs Template distinction.
>
> Each element looks up its value on the **current post in the loop**:
>
> - on a single post, page or CPT — the post being viewed;
> - on an archive / search / Grid Layout card — the post for the current loop iteration;
> - on a term archive — the term being viewed (the title / content elements adapt to term data).
>
> Using them **outside a Page Template** (e.g. dropped directly into the body of a regular page or post) is **not forbidden** — the parser will run them — but rarely useful: the "current post" resolves to the hosting page itself, so `[us_post_title]` on a page named "About" just outputs "About", `[us_post_taxonomy]` outputs that page's terms (usually none), and `[us_post_navigation]` walks the page hierarchy instead of a post sequence. Reach for these only when you know which post the loop will resolve to and that's the value you want surfaced.
>
> The one element with a hard gate is `us_post_content` — the builder UI only exposes it when editing a `us_content_template`. The rest is technically droppable anywhere; the guidance above is editorial, not enforced.

- [`us_post_content` — Post Content](shortcodes/post_content.md)
- [`us_post_image` — Post Image](shortcodes/post_image.md)
- [`us_post_title` — Post Title](shortcodes/post_title.md)
- [`us_post_custom_field` — Post Custom Field](shortcodes/post_custom_field.md)
- [`us_post_date` — Post Date](shortcodes/post_date.md)
- [`us_post_taxonomy` — Post Taxonomy](shortcodes/post_taxonomy.md)
- [`us_post_author` — Post Author](shortcodes/post_author.md)
- [`us_post_comments` — Post Comments](shortcodes/post_comments.md)
- [`us_post_navigation` — Post Prev/Next Navigation](shortcodes/post_navigation.md)
- [`us_post_views` — Post Views](shortcodes/post_views.md)
- [`us_breadcrumbs` — Breadcrumbs](shortcodes/breadcrumbs.md)
- [`us_add_to_favs` — "Add to Favorites" Button](shortcodes/add_to_favs.md)
- [`us_event_date` — Event Date and Time](shortcodes/event_date.md)

