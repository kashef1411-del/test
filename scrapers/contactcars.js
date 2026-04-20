const cheerio = require('cheerio');
const { fetchHtml } = require('../lib/http');
const { parsePrice, parseYear, normalizeTitle, absUrl, matchesQuery } = require('../lib/normalize');

const SOURCE = 'Contact Cars';
const BASE = 'https://www.contactcars.com';

async function search(query) {
  const q = encodeURIComponent(query || '');
  const url = `${BASE}/en/used-cars/search?query=${q}`;
  const html = await fetchHtml(url);
  const $ = cheerio.load(html);
  const results = [];

  $('.car-item, .listing-item, article, .car-card, [class*="CarCard"]').each((_, el) => {
    const $el = $(el);
    const title = normalizeTitle($el.find('h2, h3, .car-title, [class*="title"]').first().text());
    const price = parsePrice($el.find('.price, [class*="price"], [class*="Price"]').first().text());
    const href = $el.find('a').attr('href');
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
