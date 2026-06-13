=== RIACO Reviews – Customer Reviews & Testimonials ===
Contributors: prototipo88
Tags: reviews, testimonials, star rating, review block, customer reviews
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display beautiful customer reviews and testimonials in Grid or Masonry layouts with a Gutenberg block, shortcode, and colour control.

== Description ==

**RIACO Reviews** lets you manual collect, manage, and display customer reviews and testimonials anywhere on your WordPress site — no page builder required. Add the Reviews block in the Gutenberg editor and get a live preview instantly, or drop in the `[riaco_reviews]` shortcode on any page or widget area.

Every review is a native WordPress post with its own star rating, author, avatar, review date, source platform, and product tag. Display options, layout, card style, and colours are all configurable directly from the block inspector panel — no CSS editing required.

= Why RIACO Reviews? =

* **Works with any theme** — lightweight, BEM-structured CSS, no opinionated framework dependencies.
* **Three card styles** — Default, Modern, and Minimal — each designed to suit a different aesthetic without installing extra plugins.
* **Two responsive layouts** — Grid and Masonry — that adapt to any container width automatically. No fixed breakpoints.
* **Colour control** — customise card background, text, borders, star colour, tag badge colours, font size, and line height directly from the block editor.
* **Built for WordPress** — uses a custom post type, standard taxonomies, native media uploader, and WordPress hooks throughout.

= Features =

**Display & Layout**

* **Gutenberg block** with server-side rendering and a live preview via `ServerSideRender`. All settings in the Inspector sidebar.
* **Shortcode** `[riaco_reviews]` — use it in any page, post, widget area, or theme template.
* **Grid layout** — CSS `auto-fill` grid; column count adapts automatically to available width.
* **Masonry layout** — CSS columns with `break-inside: avoid`; same adaptive behaviour.
* **Three card styles** — Default (header with title and source logo), Modern (compact header with avatar, author, and inline star rating), Minimal (large title, no avatar, source name as text).
* **Configurable minimum card width** — sets the `--riaco-card-min-width` CSS custom property that drives both layouts.

**Per-Review Data**

* Review headline (post title)
* Review body (post content)
* Author name and avatar (falls back to a generated initials badge when no photo is provided)
* Star rating — 1 to 5 stars
* Review date (separate from the publish date — enter the date the customer left the review)
* Source platform — assign a platform like "Google", "Trustpilot", or "WordPress.org" with its logo
* Source URL — link to the original review on the external platform
* Product / subject tag — label each review with what it refers to

**Visibility Toggles**

Show or hide each element independently:

* Review title
* Author name
* Author avatar / initials
* Star rating
* Review date
* Source platform logo
* Tag badge
* Card drop shadow

**Filtering**

* Filter displayed reviews by tag — show only reviews tagged with a specific product or subject.
* Block editor: choose a tag from the "Filter by Tag" dropdown in the Display Settings panel.
* Shortcode: pass `tag="my-product-slug"` (comma-separated slugs for multiple tags).

**Sorting**

* By date (newest or oldest first)
* By star rating (highest or lowest first)
* Random (shuffle on every page load)

**Colour & Typography Overrides**

Override directly from the block editor or shortcode attributes:

* Card background colour
* Card text colour
* Card border colour
* Star colour
* Tag badge background and text colour
* Font size (rem)
* Line height

**Admin**

* Dedicated **Reviews** menu in the WordPress admin
* Custom admin list table with columns for author, rating, source logo, tag, and review date
* Media-uploader integration for author avatars and source logos

**Developer-Friendly**

* `riaco_reviews_atts` — filter display attributes before rendering
* `riaco_reviews_query_args` — modify the `WP_Query` before it runs (add tax queries, `post__in`, etc.)
* `riaco_reviews_card_meta` — add or modify per-review metadata before the card template receives it
* `riaco_reviews_card_template_path` — swap in a custom card template per style or per post
* `riaco_reviews_before_card` / `riaco_reviews_after_card` — inject markup around individual cards
* `riaco_reviews_before_loop` / `riaco_reviews_after_loop` — inject markup around the whole reviews wrapper
* `riaco_reviews_no_reviews_html` — replace the empty-state message
* `riaco_reviews_layouts` / `riaco_reviews_card_styles` / `riaco_reviews_orderby_options` — register custom values for layout, card style, and sort order
* `riaco_reviews_init` / `riaco_reviews_loaded` — plugin lifecycle actions for third-party extensions
* `riaco_reviews_block_render_atts` — map additional Gutenberg block attributes into the render pipeline

= Perfect For =

* **SaaS companies** — showcase user testimonials on landing pages with star ratings and platform logos (G2, Capterra, Product Hunt).
* **Freelancers and agencies** — display client testimonials with a clean Minimal card style.
* **eCommerce stores** — highlight product reviews tagged by product name in a Grid layout.
* **WordPress plugin and theme authors** — show WordPress.org reviews with source logo and link to the original listing.
* **Local businesses** — display Google or Yelp reviews with the platform logo and a link to the source.
* **Course creators and coaches** — collect student testimonials and display them in a Masonry layout with avatars.

= Shortcode Reference =

Basic usage:

`[riaco_reviews]`

All parameters:

`[riaco_reviews count="6" layout="grid" card_style="default" orderby="date" order="DESC" tag="" show_title="1" show_author_name="1" show_avatar="1" show_date="0" show_rating="1" show_source="1" show_tag="1" show_shadow="1" min_width="280"]`

Colour and typography:

`[riaco_reviews card_bg="#ffffff" card_text_color="#444444" card_border_color="#e4e4e7" star_color="#f59e0b" tag_bg="#f4f4f5" tag_text_color="#18181b" font_size="0.9375" line_height="1.7"]`

== Installation ==

1. Upload the `riaco-reviews` folder to the `/wp-content/plugins/` directory, or install the plugin through the **Plugins > Add New** screen in WordPress.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Reviews > Add New** to create your first review — enter the headline as the post title, the review body as the post content, and fill in the Review Details meta box (author, rating, date, source URL).
4. Go to **Reviews > Sources** to add platform sources (Google, Trustpilot, etc.) and upload their logos.
5. Go to **Reviews > Tags** to add product or subject tags.
6. In the block editor, search for **Reviews** and insert the block. Configure display options in the Inspector sidebar.
7. Alternatively, add `[riaco_reviews]` to any post, page, or widget area.

== Frequently Asked Questions ==

= Is this plugin free? =

Yes, RIACO Reviews is completely free and open source under the GPLv2 or later licence.

= Does this work with the Gutenberg (block) editor? =

Yes. The plugin registers a native Gutenberg block (`riaco-reviews/reviews-block`) with a live server-side preview in the editor. All display settings are in the Inspector sidebar — no shortcode needed when using the block editor.

= Can I also use a shortcode instead of the block? =

Yes. Use `[riaco_reviews]` in any post, page, widget area, or PHP template. All the same options available in the block are available as shortcode attributes.

= How do I add a new review? =

Go to **Reviews > Add New** in the WordPress admin. Enter the review headline as the post title and the review text as the main content. Use the **Review Details** meta box to set the author name, avatar, star rating, review date, and a link to the original source. Assign a Source (platform) and a Tag from the sidebar dropdowns.

= How do I add a platform logo (Google, Trustpilot, etc.)? =

Go to **Reviews > Sources** and add a new source. Upload a logo image using the **Logo / Image** field — the WordPress media uploader is integrated. SVG files are supported for source logos. The logo will appear in the card header (Default and Modern styles) or as text in the Minimal style.

= What card styles are available? =

Three styles are available:

* **Default** — white card with a drop shadow; header row shows the review title and source logo side by side.
* **Modern** — compact header combines the author avatar, name, date, and a compact star rating in one row; the footer splits the tag badge and source link.
* **Minimal** — large bold title, no avatar or source logo, stars use the text colour, author name links to the source URL.

= Can I customise the colours to match my brand? =

Yes. From the block editor Inspector sidebar, open the **Card Colours** or **Typography** panel to set card background, text, border, star, and tag colours, as well as font size and line height. The same options are available as shortcode attributes. All values are injected as CSS custom properties on the wrapper element so they scope to that specific block instance.

= Does the layout respond to different screen sizes? =

Yes. Both the Grid and Masonry layouts use CSS `auto-fill` / `column-width` with a configurable minimum card width (default 280 px). Columns are added or removed automatically based on the available container width — no fixed breakpoints are used.

= Can I show or hide specific fields (avatar, date, rating, etc.)? =

Yes. Every field has an individual show/hide toggle — available both in the block editor Inspector sidebar and as shortcode attributes (`show_avatar`, `show_date`, `show_rating`, etc.). For example, to show the review date: `[riaco_reviews show_date="1"]`.

= Can I sort reviews by star rating? =

Yes. Set `orderby="rating"` in the shortcode or choose **By rating** in the block editor Sort Order panel. You can also sort by date (newest or oldest first) or random.

= How do I tag reviews by product? =

Go to **Reviews > Tags** and create a tag for each product or subject. When editing a review, select the appropriate tag from the **Tag** dropdown in the sidebar. The tag appears as a pill badge on the card.

= Can I show only reviews for a specific product or tag? =

Yes. Use the `tag` attribute in the shortcode — pass the tag slug (the URL-friendly version of the tag name):

`[riaco_reviews tag="my-plugin"]`

For multiple tags (show reviews matching any of them), pass a comma-separated list:

`[riaco_reviews tag="plugin-one,plugin-two"]`

In the block editor, open the **Display Settings** panel and choose a tag from the **Filter by Tag** dropdown. Selecting "— All Tags —" removes the filter.

= Can I filter the reviews query to show only certain reviews? =

Yes, using the `riaco_reviews_query_args` filter. This gives you access to the full `WP_Query` args array before the query runs — add taxonomy queries, `post__in`, meta queries, or any other `WP_Query` parameter. Example:

`add_filter( 'riaco_reviews_query_args', function( $args, $atts ) {
    $args['tax_query'] = [ [ 'taxonomy' => 'riaco_review_tag', 'field' => 'slug', 'terms' => 'my-product' ] ];
    return $args;
}, 10, 2 );`

= Can I use a custom card template? =

Yes. Use the `riaco_reviews_card_template_path` filter to return the path to your own PHP template file. The filter receives the default path, the card style slug, the post ID, and the meta array, so you can switch templates per style or per review.

= Does this plugin add anything to my database on activation? =

No. Activation flushes the rewrite rules so the custom post type and taxonomies register correctly; deactivation flushes them again so the CPT slug is removed cleanly. No custom database tables are created. All review data is stored as standard WordPress post meta.

= Will this slow down my site? =

The plugin loads its CSS only on pages where the block or shortcode is present (`wp_enqueue_block_style` and on-demand shortcode enqueueing). The block uses server-side rendering, so no JavaScript is loaded on the frontend. All images use the `loading="lazy"` attribute.

= Is the plugin translation-ready? =

Yes. All user-facing strings are wrapped in WordPress i18n functions and the text domain is `riaco-reviews`. A `.pot` file is included in the `languages/` directory.

== Screenshots ==

1. **Review list admin screen** — Custom admin list table showing Author, Rating (stars), Source logo, Tag, and Review Date columns for quick management.
2. **Add / Edit review screen** — Post editor with the Review Details meta box (author name, avatar upload, star rating, review date, source URL) and Source / Tag dropdowns in the sidebar.
3. **Sources admin screen** — Taxonomy screen for managing review sources with logo upload and preview.
4. **Block editor — Inspector sidebar** — The Reviews block selected, showing the Inspector panels: Display Settings, Field Visibility, Sort Order, Card Colours, and Typography.
5. **Frontend — Grid layout, Default card style** — Review cards in an auto-fill grid with title, star rating, tag badge, review text, avatar, and source logo.
6. **Frontend — Masonry layout, Modern card style** — Masonry column layout with compact author + rating header row and source link in the footer.
7. **Frontend — Grid layout, Minimal card style** — Minimal cards with large title, text-coloured stars, no avatars, and author name linked to the source URL.
8. **Frontend — Mobile view** — Single-column layout on a narrow screen, demonstrating the responsive auto-fill column behaviour.

== Changelog ==

= 1.1.0 =
* **Accessibility:** added `:focus-visible` outlines to all interactive links; improved colour contrast on muted text (date, author handle, source name) to meet WCAG 2.1 AA.
* **Dark mode:** CSS custom properties now have sensible dark-mode defaults via `prefers-color-scheme: dark` (overrideable via block/shortcode colour controls).
* **Mobile:** reduced card padding at &lt;480 px; modern card footer now wraps to avoid overflow on narrow screens.
* **Card hover:** subtle lift effect with `prefers-reduced-motion` guard.
* **Empty state:** improved padding and alignment for the "No reviews found" message.
* **Block editor:** added Wide / Full alignment support; new "Title Heading Level" control (H2–H6); help text on the Min Card Width slider; "Reset all settings to defaults" button.
* **Heading level:** `heading_level` attribute/parameter (default `3`) lets you control the HTML heading tag for review titles across block and shortcode.
* **Dashboard widget:** new "Reviews Overview" widget on the WP admin dashboard showing published review count and average rating.
* **Shortcode reference:** contextual help tab on the Reviews list screen listing all `[riaco_reviews]` parameters and defaults.
* **i18n:** admin media-uploader strings ("Select Avatar", "Use this image", "Select Logo") are now translatable via PHP localisation.
* **Bug fix:** `strtotime()` truthiness check corrected to `false !== $ts` to handle edge-case epoch dates.
* **Security:** source logo save capability tightened from `manage_categories` to `manage_options`.
* **Tooltip:** `title` attribute added to truncated author names and tag badges so full text is accessible on hover.

= 1.0.2 =
* Added filter-by-tag support: `tag` shortcode attribute and `tagFilter` block attribute let you show only reviews assigned to a specific tag (comma-separated slugs for multiple tags).
* Block editor: new "Filter by Tag" dropdown in the Display Settings panel, populated from existing tags without requiring REST API access.

= 1.0.0 =
* Initial release.
* Custom post type `riaco_review` with dedicated admin list table (Author, Rating, Source, Tag, Review Date columns).
* Gutenberg block with server-side rendering and live editor preview.
* Shortcode `[riaco_reviews]` with full attribute support.
* Grid and Masonry responsive layouts.
* Default, Modern, and Minimal card styles.
* Review Sources taxonomy with media-uploader logo field (SVG supported).
* Review Tags taxonomy with pill badge display.
* Per-review fields: author name, avatar (with initials fallback), star rating (1–5), review date, source URL.
* Show/hide toggles for all card elements.
* Sort by date, rating, or random.
* Colour and typography overrides via CSS custom properties.
* Configurable minimum card width.
* Developer hooks: filters for attributes, query args, card meta, card template path, empty state HTML, allowed layouts/styles/orderby; actions for card and loop wrapper injection, plugin lifecycle.
