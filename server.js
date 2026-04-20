const path = require('path');
const express = require('express');
const cache = require('./lib/cache');

const scrapers = [
  require('./scrapers/olx'),
  require('./scrapers/contactcars'),
  require('./scrapers/hatla2ee'),
  require('./scrapers/sylndr'),
  require('./scrapers/yallamotor'),
];

const app = express();
app.use(express.static(path.join(__dirname, 'public')));

app.get('/api/sources', (_req, res) => {
  res.json({ sources: scrapers.map((s) => s.SOURCE) });
});

app.get('/api/search', async (req, res) => {
  const query = (req.query.q || '').toString().trim();
  const minPrice = req.query.minPrice ? parseInt(req.query.minPrice, 10) : null;
  const maxPrice = req.query.maxPrice ? parseInt(req.query.maxPrice, 10) : null;
  const minYear = req.query.minYear ? parseInt(req.query.minYear, 10) : null;
  const sort = (req.query.sort || 'price_asc').toString();

  const cacheKey = `q=${query}`;
  let all = cache.get(cacheKey);
  const errors = [];

  if (!all) {
    const settled = await Promise.allSettled(scrapers.map((s) => s.search(query)));
    all = [];
    settled.forEach((r, i) => {
      if (r.status === 'fulfilled') {
        all.push(...r.value);
      } else {
        errors.push({ source: scrapers[i].SOURCE, error: r.reason && r.reason.message });
      }
    });
    cache.set(cacheKey, all);
  }

  let filtered = all.slice();
  if (minPrice) filtered = filtered.filter((x) => x.price >= minPrice);
  if (maxPrice) filtered = filtered.filter((x) => x.price <= maxPrice);
  if (minYear) filtered = filtered.filter((x) => !x.year || x.year >= minYear);

  const sorters = {
    price_asc: (a, b) => a.price - b.price,
    price_desc: (a, b) => b.price - a.price,
    year_desc: (a, b) => (b.year || 0) - (a.year || 0),
    source: (a, b) => a.source.localeCompare(b.source),
  };
  filtered.sort(sorters[sort] || sorters.price_asc);

  const bySource = {};
  for (const item of all) {
    bySource[item.source] = (bySource[item.source] || 0) + 1;
  }

  const stats = all.length ? {
    count: all.length,
    min: Math.min(...all.map((x) => x.price)),
    max: Math.max(...all.map((x) => x.price)),
    avg: Math.round(all.reduce((s, x) => s + x.price, 0) / all.length),
  } : { count: 0, min: null, max: null, avg: null };

  res.json({ query, stats, bySource, errors, results: filtered });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Car price compare running on http://localhost:${PORT}`));
