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

**Frontend CSS** — plain CSS, no build step. Edit `assets/src/css/reviews.css`, then copy to dist:
```bash
cp assets/src/css/reviews.css assets/dist/reviews.css
cp assets/src/css/admin.css   assets/dist/admin.css
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

### Gutenberg block

- Source: `src/reviews-block/` — `index.js`, `edit.js`, `editor.scss`, `style.scss`, `block.json`
- Build output: `build/reviews-block/` (committed? no — regenerate with `npm run build`)
- The block uses **server-side rendering**: `save()` returns `null`; `Blocks::render()` is the PHP render callback registered in `register_block_type()`.
- `style.scss` is intentionally empty — frontend styles are loaded separately from `assets/dist/reviews.css` by `Blocks::enqueue_frontend_assets()` (only when the block is present on the page).
- `block.json` `style` field points to `style-index.css` (empty build artifact) so WordPress does not double-load styles.

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

Both taxonomies are flat (non-hierarchical), registered on `riaco_review`, with a custom `meta_box_cb` that renders a single-select dropdown in the review editor sidebar.

`riaco_review_source` — source/platform (e.g. "WordPress.org", "G2"). Term meta `_riaco_source_image` stores the logo URL. Managed in **Reviews → Sources**. Implemented in `ReviewSource.php`.

`riaco_review_tag` — the product or subject the review refers to. No extra term meta — just the term name. Managed in **Reviews → Tags**. Implemented in `ReviewTag.php`. Displayed on the frontend card as a cream pill badge (`.riaco-reviews__card-tag`); toggled via `show_tag` att / `showTag` block attribute.

### CSS

Frontend card styles use BEM with the `.riaco-reviews__` prefix. Layout modifier classes are `.riaco-reviews--grid` and `.riaco-reviews--masonry`. Grid uses CSS Grid; masonry uses CSS `columns` with `break-inside: avoid` on cards. Colours: amber stars `#f59e0b`, cream quote mark `#f5ece0`, footer rule `#f0ebe3`.

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
