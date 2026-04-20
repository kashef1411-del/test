const cheerio = require('cheerio');
const { fetchHtml } = require('../lib/http');
const { parsePrice, parseYear, normalizeTitle, absUrl, matchesQuery } = require('../lib/normalize');

const SOURCE = 'Hatla2ee';
const BASE = 'https://eg.hatla2ee.com';

async function search(query) {
  const q = encodeURIComponent(query || '');
  const url = `${BASE}/en/car?keyword=${q}`;
  const html = await fetchHtml(url);
  const $ = cheerio.load(html);
  const results = [];

  $('.newCarListUnit_unit, .usedCarListUnit, .carListUnit, li.newCarListUnit_info, div.newCarListUnit').each((_, el) => {
    const $el = $(el);
    const title = normalizeTitle($el.find('.newCarListUnit_header a, .newCarListUnit_title, h3 a, h2 a').first().text() || $el.find('a').first().attr('title'));
    const price = parsePrice($el.find('.main_price, .newCarListUnit_price, [class*="price"]').first().text());
    const href = $el.find('a').first().attr('href');
    const year = parseYear($el.text()) || parseYear(title);
    const location = normalizeTitle($el.find('.newCarListUnit_address, [class*="location"]').first().text());
    if (title && price) {
      results.push({
        source: SOURCE,
        title,
        price,
        currency: 'EGP',
        year,
        location: location || null,
        url: absUrl(BASE, href),
      });
    }
  });

  return results.filter((r) => matchesQuery(r.title, query));
}

module.exports = { search, SOURCE };
