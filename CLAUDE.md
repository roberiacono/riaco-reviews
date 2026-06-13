# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

**Block (JavaScript):**
```bash
npm run build        # compile src/reviews-block/ → build/reviews-block/
npm run start        # watch mode
npm run lint:js      # lint JavaScript
npm run lint:css     # lint SCSS
npm run format       # auto-format JS/SCSS
```

**Frontend CSS & Admin JS** — no build step, edit src then copy to dist:
```bash
cp assets/src/css/reviews.css assets/dist/reviews.css
cp assets/src/css/admin.css   assets/dist/admin.css
cp assets/src/js/admin.js     assets/dist/admin.js
```

**PHP autoloader:**
```bash
composer install --no-dev
```

After adding a new class in `includes/`, the PSR-4 autoloader picks it up automatically — no `composer dump-autoload` needed.

**Translations:**
```bash
wp i18n make-pot . languages/riaco-reviews.pot --exclude=vendor,node_modules,build
```

Run this after adding or changing any translatable string. The `.pot` file lives in `languages/` and is committed.

## Architecture

### Service container pattern

`Plugin.php` is a lightweight service container. Every feature is a class implementing `ServiceInterface` (single method: `register(): void`). Services are instantiated in `Plugin::load_services()` and wired to WordPress hooks inside their own `register()` method. To add a new feature, create the class in `includes/`, instantiate it in `load_services()`, and add it with `set_service()`.

The plugin initialises on `plugins_loaded` (not `init`), so all services are available when WordPress fires `init`.

`get_service(string $key)` retrieves a registered service by key (e.g. `'postType'`, `'admin'`, `'blocks'`, `'shortcodes'`, `'reviewSource'`, `'reviewProduct'`, `'dashboard'`, `'jsonLd'`). Returns `null` if the key is not found.

**Lifecycle actions fired by `Plugin::init()`** (in order):

1. `riaco_reviews_init` — after `load_services()`, before `register()`. Use this to call `set_service()` so third-party services participate in the normal registration pass.
2. `riaco_reviews_loaded` — after all services are registered. Use this for post-boot setup that doesn't require a registration slot.

**Activation / deactivation hooks** (registered in `riaco-reviews.php`):
- `register_activation_hook` → `Plugin::on_activation()`: registers the post type and calls `flush_rewrite_rules()`.
- `register_deactivation_hook` → `flush_rewrite_rules()`: ensures the CPT slug is removed from the rewrite table when the plugin is disabled.

### Rendering pipeline

Both the block and the shortcode converge on `Renderer::render(array $atts): string`, which runs a `WP_Query` and includes the PHP templates via output buffering. The two entry points differ only in how they normalise their attribute keys:

- **Shortcode** (`Shortcodes.php`) uses `snake_case` atts directly from `shortcode_atts()`.
- **Block** (`Blocks.php::render()`) receives `camelCase` block attributes and maps them to `snake_case` before calling `Renderer::render()`.

Templates live in `templates/` — `reviews.php` is the loop wrapper, `templates/partials/card.php` renders a single card. Both receive `$atts` (display options) and `$reviews` (the `WP_Query` object) as local variables via `include`.

`reviews.php` builds a `$meta` array per post that includes post-meta fields **and** taxonomy data resolved in the loop: it resolves the `riaco_review_source` term to get `source_image` and `source_name` (via `get_term_meta`), fetches `source_url` from post meta, and collects `riaco_review_product` terms for the product badge.

**Term meta cache warming** — `WP_Query` pre-warms the post meta and term caches for all posts in the result set, but WordPress does not automatically warm term meta. To prevent N+1 queries from `get_term_meta()` calls inside the loop, `Renderer::render()` calls `update_termmeta_cache()` on the deduplicated list of source term IDs **and** product term IDs immediately after the query runs and before `ob_start()`. Both taxonomies are collected in a single pre-loop pass.

**Display attributes** — all default to `true` unless noted:

| Attribute (`snake_case` / `camelCase`) | Default | Notes |
|---|---|---|
| `show_title` / `showTitle` | `true` | review post title |
| `show_author_name` / `showAuthorName` | `true` | |
| `show_avatar` / `showAvatar` | `true` | falls back to initials if no URL |
| `show_date` / `showDate` | `false` | |
| `show_rating` / `showRating` | `true` | |
| `show_source` / `showSource` | `true` | source logo in card header |
| `show_product` / `showProduct` | `true` | neutral pill badge |
| `show_shadow` / `showShadow` | `true` | drop shadow on cards |
| `count` | `6` | |
| `layout` | `'grid'` | `'grid'` or `'masonry'` — controls card *arrangement* |
| `card_style` / `cardStyle` | `'default'` | `'default'`, `'modern'`, or `'minimal'` — controls card *visual design* |
| `heading_level` / `headingLevel` | `3` | HTML heading level for the review title (2–6); clamped and sanitised in `Renderer.php` |
| `min_width` / `minWidth` | `300` | minimum card width in px; drives the CSS `--riaco-card-min-width` variable |
| `orderby` | `'date'` | `'date'`, `'rating'`, or `'rand'` |
| `order` | `'DESC'` | `'ASC'` or `'DESC'` |
| `product` / `productFilter` | `''` | product slug to filter by; comma-separated for multiple; empty = all products |

**Colour / typography attributes** (block and shortcode, empty = use CSS default):

| Attribute (`snake_case` / `camelCase`) | CSS variable injected |
|---|---|
| `card_bg` / `cardBg` | `--riaco-card-bg` |
| `card_text_color` / `cardTextColor` | `--riaco-card-text` |
| `card_border_color` / `cardBorderColor` | `--riaco-card-border` |
| `star_color` / `starColor` | `--riaco-star-color` |
| `product_bg` / `productBg` | `--riaco-product-bg` |
| `product_text_color` / `productTextColor` | `--riaco-product-text` |
| `font_size` / `fontSize` | `--riaco-font-size` (rem) |
| `line_height` / `lineHeight` | `--riaco-line-height` |

`Renderer::render()` sanitizes these values (hex colours via `sanitize_hex_color()`, typography as bounded floats, `min_width` as a positive integer) and injects them as a `style="…"` attribute on the `.riaco-reviews` wrapper. `min_width` values other than `280` are injected as `--riaco-card-min-width` (the PHP default is `300`, so the var is injected on every render unless `min_width=280` is passed explicitly). When `show_shadow` is false, `--riaco-card-shadow:none` is injected to suppress the drop shadow. Hex colour values are sanitized both before and after the `riaco_reviews_atts` filter runs. Product badge colours are injected as `--riaco-product-bg` and `--riaco-product-text`.

### Gutenberg block

- Source: `src/reviews-block/` — `index.js`, `edit.js`, `editor.scss`, `style.scss`, `block.json`
- Build output: `build/reviews-block/` (committed — regenerate with `npm run build` after editing JS or SCSS)
- The block uses **server-side rendering**: `save()` returns `null`; `Blocks::render()` is the PHP render callback registered in `register_block_type()`.
- The editor preview uses `ServerSideRender` — the inspector sidebar has panels for Display Settings, Field Visibility, Sort Order, Card Colours, and Typography.
- `style.scss` is intentionally empty — frontend styles are loaded separately from `assets/dist/reviews.css` via `wp_enqueue_block_style()` in `Blocks::register_block()`. This hook loads the CSS both on the frontend (when the block is present) and inside the editor canvas iframe, which is required for the `ServerSideRender` preview to be styled correctly.
- `block.json` has no `style` field. `wp_enqueue_block_style()` in `Blocks::register_block()` is the sole owner of frontend style loading; omitting the field prevents WordPress from registering a redundant empty stylesheet.
- **Align support**: `block.json` declares `"supports": { "align": ["wide", "full"] }`, giving the block Wide and Full alignment options in the block toolbar.
- **Product data for the editor**: `Blocks::localize_editor_data()` fires on `enqueue_block_editor_assets` and injects `window.riacoReviewsData = { products: [...] }` as an inline script before `riaco-reviews-reviews-block-editor-script`. The `edit.js` reads this to populate the "Filter by Product" `SelectControl` without requiring `show_in_rest` on the taxonomy.
- **Inspector controls**: Display Settings panel includes a "Reset all settings to defaults" button (uses the `DEFAULTS` constant in `edit.js`) and a help text on the Min Card Width slider. Field Visibility panel shows a "Title Heading Level" `SelectControl` (H2–H6) when Show Title is enabled.

### Admin JS

`assets/src/js/admin.js` (copied verbatim to `assets/dist/admin.js`) handles all WordPress media-uploader interactions in the admin:
- **Avatar upload** on the review edit screen (`#riaco_author_avatar` / `#riaco_avatar_preview`)
- **Source logo upload** on the Sources taxonomy term screens (`#riaco_source_image` / `#riaco_source_image_preview`)

Each is a self-contained IIFE using `wp.media`. Enqueued by `Admin::enqueue_assets()` (review post screens) and `ReviewSource::enqueue_assets()` (taxonomy screens); both call `wp_enqueue_media()`. In `Admin::enqueue_assets()`, `wp_enqueue_media()` is gated to `post.php` / `post-new.php` only — the media uploader is not loaded on the list table screen.

**i18n**: Both `Admin::enqueue_assets()` and `ReviewSource::enqueue_assets()` inject a `riacoAdminI18n` global object via `wp_add_inline_script(..., 'before')` containing `selectAvatar`, `useThisImage`, and `selectLogo` as PHP-translated strings. `admin.js` reads these instead of hardcoded English strings, making them extractable by `wp i18n make-pot`.

### Admin help tab

`Admin::add_help_tab()` fires on `current_screen` and adds a "Shortcode Reference" contextual help tab on the Reviews list screen (`edit-riaco_review`). It lists every `[riaco_reviews]` parameter with its default and description in a `<table>`, built by the private `get_help_tab_content()` method.

### Dashboard widget

`Dashboard.php` implements `ServiceInterface` and is registered as `'dashboard'` in `Plugin::load_services()`. Its `add_widget()` registers a `wp_dashboard_widget` ("Reviews Overview") that calls `render_widget()`: shows the published review count (via `wp_count_posts()`), the average rating (direct `$wpdb->get_var()` across `_riaco_review_rating` meta), and a "Manage Reviews" link.

### CPT & meta

Post type: `riaco_review`. Review body = `post_content`; headline = `post_title`.

`public => false`, `show_in_rest => false`, `has_archive => false`, `exclude_from_search => true`. The CPT admin screen supports `title` and `editor` only (no Custom Fields meta box).

All review data is stored as `wp_postmeta` with underscore-prefixed keys:

| Meta key | Notes |
|---|---|
| `_riaco_review_author_name` | |
| `_riaco_review_author_avatar` | URL |
| `_riaco_review_rating` | integer 1–5 |
| `_riaco_review_date` | `Y-m-d` string |
| `_riaco_review_source_url` | link to the original review |

### Taxonomies

Both taxonomies are flat (non-hierarchical), registered on `riaco_review`, with a custom `meta_box_cb` that renders a single-select dropdown in the review editor.

`riaco_review_source` — source/platform (e.g. "WordPress.org", "G2"). Term meta `_riaco_source_image` stores the logo URL (supports SVG — unlocked for `manage_options` users via `upload_mimes` + `wp_check_filetype_and_ext` filters in `ReviewSource.php`). Managed in **Reviews → Sources**. The logo is displayed in the card header alongside the review title (`.riaco-reviews__source`, inside a flex row), optionally linked to `_riaco_review_source_url`.

`riaco_review_product` — the product or subject the review refers to. Managed in **Reviews → Products**. Implemented in `ReviewProduct.php`. Displayed on the frontend card as a neutral pill badge (`.riaco-reviews__card-product`, shadcn-inspired: `border-radius: 9999px`, zinc-100 background, `font-weight: 500`, no uppercase); toggled via `show_product` / `showProduct`.

Term meta stored on `riaco_review_product` terms:

| Meta key | Notes |
|---|---|
| `_riaco_product_url` | URL of the product / subject being reviewed; used in JSON-LD `itemReviewed.url` |
| `_riaco_product_type` | schema.org type for the reviewed item; one of `Thing` (default), `Product`, `SoftwareApplication`, `LocalBusiness`, `Organization`, `Book`, `Movie`, `Course`, `Event` |

Both fields are editable on the Add/Edit Product screens in the admin. `_riaco_product_url` is sanitized with `esc_url_raw()`; `_riaco_product_type` is validated against the allowed list before saving.

### CSS

Frontend card styles use BEM with the `.riaco-reviews__` prefix.

**Layouts:**

- **Grid** — `grid-template-columns: repeat(auto-fill, minmax(var(--riaco-card-min-width, 280px), 1fr))`. No fixed breakpoints; column count adjusts automatically to the container width and the configured min card width.
- **Masonry** — `column-width: var(--riaco-card-min-width, 280px)` with `break-inside: avoid` on cards. Same adaptive behaviour as grid.

**Card DOM order (all styles):**

The title heading element is dynamic: `card.php` sets `$hl = 'h' . absint( $atts['heading_level'] )` and uses it for all three styles. The CSS targets class names, not the element, so any heading level renders correctly.

`default`:
1. `.riaco-reviews__header` — flex row: `<h{heading_level} class="riaco-reviews__title">` (if `show_title`) + `.riaco-reviews__source` (logo, if `show_source`)
2. `.riaco-reviews__rating` — five `★` spans
3. `.riaco-reviews__card-product` — product badge (has `title` attribute for truncation tooltip)
4. `.riaco-reviews__body` — review text
5. `<footer class="riaco-reviews__footer">` — avatar + author name (has `title` attribute) + date

`modern`:
1. `<h{heading_level} class="riaco-reviews__title--modern">` (if `show_title`)
2. `.riaco-reviews__modern-header` — flex row: avatar + `.riaco-reviews__author` (name with `title` attr + date) + `.riaco-reviews__rating-compact` (★ + numeric value)
3. `.riaco-reviews__body` — review text
4. `.riaco-reviews__modern-footer` — flex row with `flex-wrap: wrap`: product badge (has `title` attr, left) + source link/logo (right)

`minimal`:
1. `<h{heading_level} class="riaco-reviews__title--minimal">` (if `show_title`) — 1.5rem bold title
2. `.riaco-reviews__rating` — five `★` spans; filled stars use `currentColor` (inherits text colour, never amber)
3. `.riaco-reviews__body` — review text
4. `.riaco-reviews__card-product` — product badge (has `title` attr); `background: transparent` (border only)
5. `<footer class="riaco-reviews__footer--minimal">` — author name as `<a>` to `source_url` (falls back to `<span>`) + date (off by default) + source name as small muted text

**Minimal style behaviour notes:**
- Source logo is never rendered. The "Show Source Logo" block editor toggle is hidden when this style is selected.
- Source name text always renders if the term has a name (not gated by `show_source`).
- `show_avatar` has no effect — no avatar is rendered.

**Card style modifier classes** sit on `<article class="riaco-reviews__card riaco-reviews__card--{style}">`:

| Class | Visual treatment |
|---|---|
| `.riaco-reviews__card--default` | White card, drop shadow, 12px border-radius; header flex row for title + source logo |
| `.riaco-reviews__card--modern` | Same base card; top row collapses avatar + author + compact rating; footer splits product badge and source link |
| `.riaco-reviews__card--minimal` | Same base card; large title, no avatar or source logo; filled stars use text colour; product badge has transparent background; footer shows linked author name + source name as small text |

`layout` and `card_style` are orthogonal — any combination is valid.

**Responsive & dark mode:**

- Cards reduce padding to `1.25rem` at `max-width: 480px`.
- `.riaco-reviews__modern-footer` has `flex-wrap: wrap` so product badge + source never overflow narrow cards.
- Cards have a hover lift effect (`translateY(-2px)` + deeper shadow) guarded by `@media (prefers-reduced-motion: reduce)`.
- A `@media (prefers-color-scheme: dark)` block sets dark defaults on `.riaco-reviews` CSS custom properties; these are always overrideable by inline style values injected by `Renderer::render()`.
- All interactive links (`.riaco-reviews__source-link`, `.riaco-reviews__source-link--modern`, `a.riaco-reviews__author-link--minimal`) have `:focus-visible` outlines for keyboard accessibility.
- Muted text (author handle, date, source name) uses `#6b7280` (WCAG 2.1 AA on white at 4.5:1+).

**CSS custom properties** (with fallback defaults):

| Property | Default | Used on |
|---|---|---|
| `--riaco-card-bg` | `#ffffff` | card background |
| `--riaco-card-text` | `#444444` | review text colour |
| `--riaco-card-border` | `transparent` | card border + minimal accent |
| `--riaco-star-color` | `#f59e0b` | filled stars |
| `--riaco-product-bg` | `#f4f4f5` | product badge background |
| `--riaco-product-text` | `#18181b` | product badge text |
| `--riaco-product-border` | `#e4e4e7` | product badge border |
| `--riaco-font-size` | `0.9375rem` | review text size |
| `--riaco-line-height` | `1.7` | review text line height |
| `--riaco-card-min-width` | `280px` | grid column / masonry column width floor |
| `--riaco-card-shadow` | `0 2px 12px rgba(0,0,0,0.07)` | card drop shadow; set to `none` when `show_shadow` is false |

## Developer hooks

All hooks follow the `riaco_reviews_*` naming convention. Filters return the (possibly modified) first argument; actions receive context but return nothing.

### Plugin lifecycle (`Plugin.php`)

| Hook | Type | Args | Purpose |
|---|---|---|---|
| `riaco_reviews_init` | action | `$plugin` | Fires after `load_services()`, before `register()`. Add third-party services here. |
| `riaco_reviews_loaded` | action | `$plugin` | Fires after all services are registered. |

### Renderer (`Renderer.php`)

| Hook | Type | Args | Purpose |
|---|---|---|---|
| `riaco_reviews_layouts` | filter | `string[]` | Extend the allowed `layout` values (default: `['grid','masonry']`). |
| `riaco_reviews_card_styles` | filter | `string[]` | Extend the allowed `card_style` values (default: `['default','modern','minimal']`). |
| `riaco_reviews_orderby_options` | filter | `string[]` | Extend the allowed `orderby` values (default: `['date','rating','rand']`). |
| `riaco_reviews_atts` | filter | `$atts` | Override any sanitised display attribute before CSS vars are built. Hex colour values are re-sanitized after this filter runs, so non-hex colour strings are stripped. |
| `riaco_reviews_query_args` | filter | `$query_args, $atts` | Modify the `WP_Query` args before the query runs (tax queries, `post__in`, etc.). |

### Template loop (`templates/reviews.php`)

| Hook | Type | Args | Purpose |
|---|---|---|---|
| `riaco_reviews_before_loop` | action | `$atts` | Output before the `.riaco-reviews` wrapper div. |
| `riaco_reviews_after_loop` | action | `$atts` | Output after the wrapper div. |
| `riaco_reviews_card_meta` | filter | `$meta, $post_id, $atts` | Add or modify per-review meta before the card template receives it. |
| `riaco_reviews_card_template_path` | filter | `$path, $card_style, $post_id, $meta` | Return a custom template file path for a given card style. The path is validated with `is_file()` before inclusion; a non-existent path silently no-ops. |
| `riaco_reviews_before_card` | action | `$post_id, $meta, $atts` | Output before each `<article>`. |
| `riaco_reviews_after_card` | action | `$post_id, $meta, $atts` | Output after each `</article>`. |
| `riaco_reviews_no_reviews_html` | filter | `$html, $atts` | Replace the "No reviews found" paragraph HTML. |

### Block renderer (`Blocks.php`)

| Hook | Type | Args | Purpose |
|---|---|---|---|
| `riaco_reviews_block_render_atts` | filter | `$atts, $attributes` | Map additional block attributes (added via WP core's `register_block_type_args` + `editor.BlockEdit` JS filter) into the snake_case `$atts` array that reaches `Renderer::render()`. |

### JSON-LD structured data (`JsonLd.php`)

`JsonLd` implements `ServiceInterface` and is registered as `'jsonLd'`. It hooks into `riaco_reviews_after_card` to **accumulate** schema.org `Review` objects (one per rendered card), then outputs a single `<script type="application/ld+json">` block in `wp_footer`. If only one review is collected the payload is a bare `Review` object; for multiple reviews it wraps them in a `@graph` array. This approach handles pages with multiple blocks or shortcodes correctly — all reviews land in one script tag.

The `$meta` keys used for JSON-LD output:

| `$meta` key | JSON-LD field |
|---|---|
| `rating` | `reviewRating.ratingValue` (omitted if 0) |
| `author_name` | `author.name` |
| `review_date` | `datePublished` |
| `source_url` | `url` (top-level review URL) |
| `product_name` | `itemReviewed.name` (falls back to post title) |
| `product_type` | `itemReviewed.@type` (falls back to `'Thing'`) |
| `product_url` | `itemReviewed.url` (omitted if empty) |
| `post_content` | `reviewBody` (tags stripped via `wp_strip_all_tags()`) |

| Hook | Type | Args | Purpose |
|---|---|---|---|
| `riaco_reviews_json_ld_data` | filter | `$data, $post_id, $meta, $atts` | Modify or suppress (return falsy) a single review's JSON-LD object before it is collected. |

### Admin meta box (`Admin.php`)

| Hook | Type | Args | Purpose |
|---|---|---|---|
| `riaco_reviews_meta_box_after_fields` | action | `$post` | Append `<tr>` rows to the Review Details meta box table. |
| `riaco_reviews_save_meta` | action | `$post_id` | Save additional review meta after the free plugin's own fields are saved. |

## Naming conventions

| Context | Pattern |
|---|---|
| PHP namespace | `RIACO\Reviews\` |
| PHP constants | `RIACO_REVIEWS_*` |
| WordPress hooks / option keys | `riaco_reviews_*` |
| Post type | `riaco_review` |
| Meta keys | `_riaco_review_*` |
| Block name | `riaco-reviews/reviews-block` |
| CSS classes | `.riaco-reviews__*` (BEM) |
| Text domain | `riaco-reviews` |
