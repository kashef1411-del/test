# CarCompare EG

A car price comparison web app that aggregates used-car listings from five Egyptian sources into one searchable view.

## Sources
- OLX Egypt (`olx.com.eg`)
- Contact Cars (`contactcars.com`)
- Hatla2ee (`eg.hatla2ee.com`)
- Sylndr (`sylndr.com`)
- YallaMotor Egypt (`egypt.yallamotor.com`)

## Run
```bash
npm install
npm start
```
Open http://localhost:3000

## API
- `GET /api/sources` — list of configured sources.
- `GET /api/search?q=<query>&sort=price_asc&minPrice=&maxPrice=&minYear=` — aggregated results with stats.

## Architecture
- `server.js` — Express server, serves `/public` and the `/api` endpoints.
- `scrapers/*.js` — one module per source. Each exports `search(query)` that returns normalized listing objects `{ source, title, price, currency, year, location, url }`.
- `lib/http.js` — shared axios client with browser-like UA.
- `lib/normalize.js` — price/year parsing and URL helpers.
- `lib/cache.js` — in-memory 10-minute cache per query.
- `public/` — static frontend (vanilla JS, no framework).

## Notes
The scrapers target publicly available HTML and may need selector updates when a source changes its markup. Some sources may block non-browser requests or require a headless browser for JS-rendered pages — extend with Playwright/Puppeteer if needed.

Intended for personal research and price comparison. Respect each site's robots.txt and terms of service.
