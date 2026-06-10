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

## Architecture

### Service container pattern

`Plugin.php` is a lightweight service container. Every feature is a class implementing `ServiceInterface` (single method: `register(): void`). Services are instantiated in `Plugin::load_services()` and wired to WordPress hooks inside their own `register()` method. To add a new feature, create the class in `includes/`, instantiate it in `load_services()`, and add it with `set_service()`.

The plugin initialises on `plugins_loaded` (not `init`), so all services are available when WordPress fires `init`.

`get_service(string $key)` retrieves a registered service by key (e.g. `'postType'`, `'admin'`, `'blocks'`, `'shortcodes'`, `'reviewSource'`, `'reviewTag'`). Returns `null` if the key is not found.

**Lifecycle actions fired by `Plugin::init()`** (in order):

1. `riaco_reviews_init` — after `load_services()`, before `register()`. Use this to call `set_service()` so third-party services participate in the normal registration pass.
2. `riaco_reviews_loaded` — after all services are registered. Use this for post-boot setup that doesn't require a registration slot.

### Rendering pipeline

Both the block and the shortcode converge on `Renderer::render(array $atts): string`, which runs a `WP_Query` and includes the PHP templates via output buffering. The two entry points differ only in how they normalise their attribute keys:

- **Shortcode** (`Shortcodes.php`) uses `snake_case` atts directly from `shortcode_atts()`.
- **Block** (`Blocks.php::render()`) receives `camelCase` block attributes and maps them to `snake_case` before calling `Renderer::render()`.

Templates live in `templates/` — `reviews.php` is the loop wrapper, `templates/partials/card.php` renders a single card. Both receive `$atts` (display options) and `$reviews` (the `WP_Query` object) as local variables via `include`.

`reviews.php` builds a `$meta` array per post that includes post-meta fields **and** taxonomy data resolved in the loop: it resolves the `riaco_review_source` term to get `source_image` and `source_name` (via `get_term_meta`), fetches `source_url` from post meta, and collects `riaco_review_tag` terms for the tag badge.

**Display attributes** — all default to `true` unless noted:

| Attribute (`snake_case` / `camelCase`) | Default | Notes |
|---|---|---|
| `show_title` / `showTitle` | `true` | review post title |
| `show_author_name` / `showAuthorName` | `true` | |
| `show_avatar` / `showAvatar` | `true` | falls back to initials if no URL |
| `show_date` / `showDate` | `false` | |
| `show_rating` / `showRating` | `true` | |
| `show_source` / `showSource` | `true` | source logo in card header |
| `show_tag` / `showTag` | `true` | neutral pill badge |
| `show_shadow` / `showShadow` | `true` | drop shadow on cards |
| `count` | `6` | |
| `layout` | `'grid'` | `'grid'` or `'masonry'` — controls card *arrangement* |
| `card_style` / `cardStyle` | `'default'` | `'default'` or `'modern'` — controls card *visual design* |
| `min_width` / `minWidth` | `280` | minimum card width in px; drives the CSS `--riaco-card-min-width` variable |
| `orderby` | `'date'` | `'date'`, `'rating'`, or `'rand'` |
| `order` | `'DESC'` | `'ASC'` or `'DESC'` |

**Colour / typography attributes** (block only, empty = use CSS default):

| Attribute (`snake_case` / `camelCase`) | CSS variable injected |
|---|---|
| `card_bg` / `cardBg` | `--riaco-card-bg` |
| `card_text_color` / `cardTextColor` | `--riaco-card-text` |
| `card_border_color` / `cardBorderColor` | `--riaco-card-border` |
| `star_color` / `starColor` | `--riaco-star-color` |
| `tag_bg` / `tagBg` | `--riaco-tag-bg` |
| `tag_text_color` / `tagTextColor` | `--riaco-tag-text` |
| `font_size` / `fontSize` | `--riaco-font-size` (rem) |
| `line_height` / `lineHeight` | `--riaco-line-height` |

`Renderer::render()` sanitizes these values (hex colours via `sanitize_hex_color()`, typography as bounded floats, `min_width` as a positive integer) and injects them as a `style="…"` attribute on the `.riaco-reviews` wrapper. Non-default `min_width` is injected as `--riaco-card-min-width`. When `show_shadow` is false, `--riaco-card-shadow:none` is injected to suppress the drop shadow.

### Gutenberg block

- Source: `src/reviews-block/` — `index.js`, `edit.js`, `editor.scss`, `style.scss`, `block.json`
- Build output: `build/reviews-block/` (committed? no — regenerate with `npm run build`)
- The block uses **server-side rendering**: `save()` returns `null`; `Blocks::render()` is the PHP render callback registered in `register_block_type()`.
- The editor preview uses `ServerSideRender` — the inspector sidebar has panels for Display Settings, Field Visibility, Sort Order, Card Colours, and Typography.
- `style.scss` is intentionally empty — frontend styles are loaded separately from `assets/dist/reviews.css` via `wp_enqueue_block_style()` in `Blocks::register_block()`. This hook loads the CSS both on the frontend (when the block is present) and inside the editor canvas iframe, which is required for the `ServerSideRender` preview to be styled correctly.
- `block.json` `style` field points to `style-index.css` (empty build artifact) so WordPress does not double-load styles.

### Admin JS

`assets/src/js/admin.js` (copied verbatim to `assets/dist/admin.js`) handles all WordPress media-uploader interactions in the admin:
- **Avatar upload** on the review edit screen (`#riaco_author_avatar` / `#riaco_avatar_preview`)
- **Source logo upload** on the Sources taxonomy term screens (`#riaco_source_image` / `#riaco_source_image_preview`)

Each is a self-contained IIFE using `wp.media`. Enqueued by `Admin::enqueue_assets()` (review post screens) and `ReviewSource::enqueue_assets()` (taxonomy screens); both also call `wp_enqueue_media()`.

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

`riaco_review_tag` — the product or subject the review refers to. No extra term meta — just the term name. Managed in **Reviews → Tags**. Implemented in `ReviewTag.php`. Displayed on the frontend card as a neutral pill badge (`.riaco-reviews__card-tag`, shadcn-inspired: `border-radius: 9999px`, zinc-100 background, `font-weight: 500`, no uppercase); toggled via `show_tag` / `showTag`.

### CSS

Frontend card styles use BEM with the `.riaco-reviews__` prefix.

**Layouts:**

- **Grid** — `grid-template-columns: repeat(auto-fill, minmax(var(--riaco-card-min-width, 280px), 1fr))`. No fixed breakpoints; column count adjusts automatically to the container width and the configured min card width.
- **Masonry** — `column-width: var(--riaco-card-min-width, 280px)` with `break-inside: avoid` on cards. Same adaptive behaviour as grid.

**Card DOM order (both styles):**

`default`:
1. `.riaco-reviews__header` — flex row: `<h3 class="riaco-reviews__title">` (if `show_title`) + `.riaco-reviews__source` (logo, if `show_source`)
2. `.riaco-reviews__rating` — five `★` spans
3. `.riaco-reviews__card-tag` — tag badge
4. `.riaco-reviews__body` — review text
5. `<footer class="riaco-reviews__footer">` — avatar + author name + date

`modern`:
1. `<h3 class="riaco-reviews__title--modern">` (if `show_title`)
2. `.riaco-reviews__modern-header` — flex row: avatar + `.riaco-reviews__author` (name + date) + `.riaco-reviews__rating-compact` (★ + numeric value)
3. `.riaco-reviews__body` — review text
4. `.riaco-reviews__modern-footer` — flex row: tag badge (left) + source link/logo (right)

**Card style modifier classes** sit on `<article class="riaco-reviews__card riaco-reviews__card--{style}">`:

| Class | Visual treatment |
|---|---|
| `.riaco-reviews__card--default` | White card, drop shadow, 12px border-radius; header flex row for title + source logo |
| `.riaco-reviews__card--modern` | Same base card; top row collapses avatar + author + compact rating; footer splits tag and source link |

`layout` and `card_style` are orthogonal — any combination is valid.

**CSS custom properties** (with fallback defaults):

| Property | Default | Used on |
|---|---|---|
| `--riaco-card-bg` | `#ffffff` | card background |
| `--riaco-card-text` | `#444444` | review text colour |
| `--riaco-card-border` | `transparent` | card border + minimal accent |
| `--riaco-star-color` | `#f59e0b` | filled stars |
| `--riaco-tag-bg` | `#f4f4f5` | tag badge background |
| `--riaco-tag-text` | `#18181b` | tag badge text |
| `--riaco-tag-border` | `#e4e4e7` | tag badge border |
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
| `riaco_reviews_card_styles` | filter | `string[]` | Extend the allowed `card_style` values (default: `['default','modern']`). |
| `riaco_reviews_orderby_options` | filter | `string[]` | Extend the allowed `orderby` values (default: `['date','rating','rand']`). |
| `riaco_reviews_atts` | filter | `$atts` | Override any sanitised display attribute before CSS vars are built. |
| `riaco_reviews_query_args` | filter | `$query_args, $atts` | Modify the `WP_Query` args before the query runs (tax queries, `post__in`, etc.). |

### Template loop (`templates/reviews.php`)

| Hook | Type | Args | Purpose |
|---|---|---|---|
| `riaco_reviews_before_loop` | action | `$atts` | Output before the `.riaco-reviews` wrapper div. |
| `riaco_reviews_after_loop` | action | `$atts` | Output after the wrapper div. |
| `riaco_reviews_card_meta` | filter | `$meta, $post_id, $atts` | Add or modify per-review meta before the card template receives it. |
| `riaco_reviews_card_template_path` | filter | `$path, $card_style, $post_id, $meta` | Return a custom template file path for a given card style. |
| `riaco_reviews_before_card` | action | `$post_id, $meta, $atts` | Output before each `<article>`. |
| `riaco_reviews_after_card` | action | `$post_id, $meta, $atts` | Output after each `</article>`. |
| `riaco_reviews_no_reviews_html` | filter | `$html, $atts` | Replace the "No reviews found" paragraph HTML. |

### Block renderer (`Blocks.php`)

| Hook | Type | Args | Purpose |
|---|---|---|---|
| `riaco_reviews_block_render_atts` | filter | `$atts, $attributes` | Map additional block attributes (added via WP core's `register_block_type_args` + `editor.BlockEdit` JS filter) into the snake_case `$atts` array that reaches `Renderer::render()`. |

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
