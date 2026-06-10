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

### Rendering pipeline

Both the block and the shortcode converge on `Renderer::render(array $atts): string`, which runs a `WP_Query` and includes the PHP templates via output buffering. The two entry points differ only in how they normalise their attribute keys:

- **Shortcode** (`Shortcodes.php`) uses `snake_case` atts directly from `shortcode_atts()`.
- **Block** (`Blocks.php::render()`) receives `camelCase` block attributes and maps them to `snake_case` before calling `Renderer::render()`.

Templates live in `templates/` — `reviews.php` is the loop wrapper, `templates/partials/card.php` renders a single card. Both receive `$atts` (display options) and `$reviews` (the `WP_Query` object) as local variables via `include`.

`reviews.php` builds a `$meta` array per post that includes post-meta fields **and** taxonomy data resolved in the loop: it resolves the `riaco_review_source` term to get `source_image` and `source_name` (via `get_term_meta`), fetches `source_url` from post meta, and collects `riaco_review_tag` terms for the tag badge.

**Display attributes** — all default to `true` unless noted:

| Attribute (`snake_case` / `camelCase`) | Default | Notes |
|---|---|---|
| `show_author_name` / `showAuthorName` | `true` | |
| `show_avatar` / `showAvatar` | `true` | falls back to initials if no URL |
| `show_date` / `showDate` | `false` | |
| `show_rating` / `showRating` | `true` | |
| `show_source` / `showSource` | `true` | source logo, top-right of card |
| `show_tag` / `showTag` | `true` | cream pill badge |
| `count` | `6` | |
| `layout` | `'grid'` | `'grid'` or `'masonry'` — controls card *arrangement* |
| `card_style` / `cardStyle` | `'default'` | `'default'`, `'quote'`, or `'minimal'` — controls card *visual design* |
| `orderby` | `'date'` | `'date'`, `'rating'`, or `'rand'` |
| `order` | `'DESC'` | `'ASC'` or `'DESC'` |

### Gutenberg block

- Source: `src/reviews-block/` — `index.js`, `edit.js`, `editor.scss`, `style.scss`, `block.json`
- Build output: `build/reviews-block/` (committed? no — regenerate with `npm run build`)
- The block uses **server-side rendering**: `save()` returns `null`; `Blocks::render()` is the PHP render callback registered in `register_block_type()`.
- `style.scss` is intentionally empty — frontend styles are loaded separately from `assets/dist/reviews.css` by `Blocks::enqueue_frontend_assets()` (only when the block is present on the page).
- `block.json` `style` field points to `style-index.css` (empty build artifact) so WordPress does not double-load styles.

### Admin JS

`assets/src/js/admin.js` (copied verbatim to `assets/dist/admin.js`) handles all WordPress media-uploader interactions in the admin:
- **Avatar upload** on the review edit screen (`#riaco_author_avatar` / `#riaco_avatar_preview`)
- **Source logo upload** on the Sources taxonomy term screens (`#riaco_source_image` / `#riaco_source_image_preview`)

Each is a self-contained IIFE using `wp.media`. Enqueued by `Admin::enqueue_assets()` (review post screens) and `ReviewSource::enqueue_assets()` (taxonomy screens); both also call `wp_enqueue_media()`.

### CPT & meta

Post type: `riaco_review`. Review body = `post_content`; headline = `post_title`.

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

`riaco_review_source` — source/platform (e.g. "WordPress.org", "G2"). Term meta `_riaco_source_image` stores the logo URL (supports SVG — unlocked for `manage_options` users via `upload_mimes` + `wp_check_filetype_and_ext` filters in `ReviewSource.php`). Managed in **Reviews → Sources**. The logo is displayed in the top-right corner of each frontend card (`.riaco-reviews__source`, `position: absolute`), optionally linked to `_riaco_review_source_url`.

`riaco_review_tag` — the product or subject the review refers to. No extra term meta — just the term name. Managed in **Reviews → Tags**. Implemented in `ReviewTag.php`. Displayed on the frontend card as a cream pill badge (`.riaco-reviews__card-tag`); toggled via `show_tag` / `showTag`.

### CSS

Frontend card styles use BEM with the `.riaco-reviews__` prefix. Layout modifier classes are `.riaco-reviews--grid` and `.riaco-reviews--masonry`. Grid uses CSS Grid; masonry uses CSS `columns` with `break-inside: avoid` on cards. The card has `position: relative` — the source logo uses `position: absolute; top: 1.25rem; right: 1.25rem` to sit in the top-right corner. Colours: amber stars `#f59e0b`, cream quote mark `#f5ece0`, footer rule `#f0ebe3`, tag badge background `#f5ece0`.

Card style modifier classes sit on `<article class="riaco-reviews__card riaco-reviews__card--{style}">` and override base card defaults:

| Class | Visual treatment |
|---|---|
| `.riaco-reviews__card--default` | White card, drop shadow, cream quote mark top-left (base styles, no overrides needed) |
| `.riaco-reviews__card--quote` | No shadow, subtle border (`#ede8e2`), centered text, larger quote mark (`#d4b896`), centered footer |
| `.riaco-reviews__card--minimal` | Transparent background, no shadow, no border-radius, 4px amber left border, no quote mark |

`layout` and `card_style` are orthogonal — any combination is valid.

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
