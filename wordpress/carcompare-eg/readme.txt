=== CarCompare EG ===
Contributors: carcompare
Tags: cars, price comparison, egypt, olx, sylndr
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT

Compare used-car prices across OLX, Contact Cars, Hatla2ee, Sylndr, and YallaMotor Egypt inside any WordPress page.

== Installation ==
1. Upload the `carcompare-eg` folder to `/wp-content/plugins/`.
2. Activate the plugin in **Plugins** menu.
3. Add the shortcode `[car_compare]` to any page or post.

== Usage ==
Place `[car_compare]` in any page. A search box with filters (price, year, source toggles) and aggregated stats (min/avg/max) will appear.

== How it works ==
Search requests hit a custom REST endpoint `/wp-json/carcompare/v1/search?q=...` which scrapes all five sources server-side via `wp_remote_get`, merges and normalizes the listings, caches the result for 10 minutes per query using the WordPress Transients API, and returns JSON to the front-end.

== Notes ==
Some sources (OLX in particular) may block non-browser HTTP requests or render prices via JavaScript. When that happens, those sources will return fewer or zero results while the others continue to work. You can extend `includes/scrapers.php` with updated selectors or wire in an external scraper API key if you need deeper coverage.
