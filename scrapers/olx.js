const cheerio = require('cheerio');
const { fetchHtml } = require('../lib/http');
const { parsePrice, parseYear, normalizeTitle, absUrl, matchesQuery } = require('../lib/normalize');

const SOURCE = 'OLX Egypt';
const BASE = 'https://www.olx.com.eg';

async function search(query) {
  const q = encodeURIComponent(query || '');
  const url = `${BASE}/en/vehicles/cars-for-sale/q-${q}/`;
  const html = await fetchHtml(url);
  const $ = cheerio.load(html);
  const results = [];

  $('[data-aut-id="itemBox"], li.ee2b0479, li[data-aut-id="itemBox"]').each((_, el) => {
    const $el = $(el);
    const title = normalizeTitle($el.find('[data-aut-id="itemTitle"]').text() || $el.find('h4, h3, span._2Gr10').text());
    const price = parsePrice($el.find('[data-aut-id="itemPrice"]').text() || $el.find('span._89yzn').text());
    const href = $el.find('a').attr('href');
    const location = normalizeTitle($el.find('[data-aut-id="item-location"]').text());
    if (title && price) {
      results.push({
        source: SOURCE,
        title,
        price,
        currency: 'EGP',
        year: parseYear(title),
        location: location || null,
        url: absUrl(BASE, href),
      });
    }
  });

  // JSON-LD fallback
  if (results.length === 0) {
    $('script[type="application/ld+json"]').each((_, el) => {
      try {
        const data = JSON.parse($(el).contents().text());
        const items = Array.isArray(data) ? data : data['@graph'] || [data];
        items.forEach((it) => {
          if (it['@type'] === 'Product' || it.offers) {
            const title = normalizeTitle(it.name);
            const price = parsePrice(it.offers && (it.offers.price || it.offers.lowPrice));
            if (title && price) {
              results.push({ source: SOURCE, title, price, currency: 'EGP', year: parseYear(title), location: null, url: it.url });
            }
          }
        });
      } catch {}
    });
  }

  return results.filter((r) => matchesQuery(r.title, query));
}

module.exports = { search, SOURCE };
