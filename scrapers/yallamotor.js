const cheerio = require('cheerio');
const { fetchHtml } = require('../lib/http');
const { parsePrice, parseYear, normalizeTitle, absUrl, matchesQuery } = require('../lib/normalize');

const SOURCE = 'YallaMotor Egypt';
const BASE = 'https://egypt.yallamotor.com';

async function search(query) {
  const q = encodeURIComponent(query || '');
  const url = `${BASE}/used-cars/search?q=${q}`;
  const html = await fetchHtml(url);
  const $ = cheerio.load(html);
  const results = [];

  $('.used-car-card, .car-card, .listing-card, article, [class*="car-card"], [class*="listing"]').each((_, el) => {
    const $el = $(el);
    const title = normalizeTitle($el.find('h2, h3, .car-title, [class*="title"]').first().text() || $el.find('a').first().attr('title'));
    const price = parsePrice($el.find('.price, [class*="price"], [class*="Price"]').first().text());
    const href = $el.find('a').first().attr('href');
    const year = parseYear($el.text()) || parseYear(title);
    if (title && price) {
      results.push({
        source: SOURCE,
        title,
        price,
        currency: 'EGP',
        year,
        location: null,
        url: absUrl(BASE, href),
      });
    }
  });

  return results.filter((r) => matchesQuery(r.title, query));
}

module.exports = { search, SOURCE };
