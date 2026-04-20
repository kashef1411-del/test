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

## Deploy

The repo ships with configs for three popular hosts — pick one.

### Render (easiest, free tier)
1. Push this repo to GitHub.
2. Go to <https://render.com> → **New → Blueprint** → point at this repo.
3. Render reads `render.yaml` and deploys automatically. You get a `https://carcompare-eg.onrender.com` URL.

### Railway
1. <https://railway.app> → **New Project → Deploy from GitHub repo**.
2. Railway auto-detects Node via `railway.json` and runs `node server.js`.

### Fly.io (Docker)
```bash
brew install flyctl      # or: curl -L https://fly.io/install.sh | sh
fly auth login
fly launch --copy-config --no-deploy   # uses fly.toml
fly deploy
```

### Any VPS with Docker
```bash
docker build -t carcompare .
docker run -d -p 80:3000 --name carcompare carcompare
```

## Notes
The scrapers target publicly available HTML and may need selector updates when a source changes its markup. Some sources may block non-browser requests or require a headless browser for JS-rendered pages — extend with Playwright/Puppeteer if needed.

Intended for personal research and price comparison. Respect each site's robots.txt and terms of service.
