const cheerio = require('cheerio');
const { fetchHtml } = require('../lib/http');
const { parsePrice, parseYear, normalizeTitle, absUrl, matchesQuery } = require('../lib/normalize');

const SOURCE = 'Sylndr';
const BASE = 'https://sylndr.com';

async function search(query) {
  const url = `${BASE}/en/buy-used-cars`;
  const html = await fetchHtml(url);
  const $ = cheerio.load(html);
  const results = [];

  // Next.js data embedded
  const nextData = $('#__NEXT_DATA__').contents().text();
  if (nextData) {
    try {
      const parsed = JSON.parse(nextData);
      const stack = [parsed];
      const seen = new Set();
      while (stack.length) {
        const node = stack.pop();
        if (!node || typeof node !== 'object' || seen.has(node)) continue;
        seen.add(node);
        if (Array.isArray(node)) { stack.push(...node); continue; }
        if (node.price && (node.title || node.name || node.make)) {
          const title = normalizeTitle(
            node.title || [node.year, node.make, node.model, node.trim].filter(Boolean).join(' ')
          );
          const price = parsePrice(node.price && (node.price.amount || node.price.value || node.price));
          if (title && price) {
            results.push({
              source: SOURCE,
              title,
              price,
              currency: 'EGP',
              year: node.year || parseYear(title),
              location: node.city || node.location || null,
              url: node.url ? absUrl(BASE, node.url) : (node.slug ? `${BASE}/en/buy-used-cars/${node.slug}` : BASE),
            });
          }
        }
        for (const k of Object.keys(node)) stack.push(node[k]);
      }
    } catch {}
  }

  if (results.length === 0) {
    $('a[href*="/buy-used-cars/"]').each((_, el) => {
      const $el = $(el);
      const title = normalizeTitle($el.find('h2, h3, [class*="title"]').first().text() || $el.attr('title'));
      const price = parsePrice($el.find('[class*="price"], [class*="Price"]').first().text());
      const href = $el.attr('href');
      if (title && price) {
        results.push({
          source: SOURCE,
          title,
          price,
          currency: 'EGP',
          year: parseYear(title),
          location: null,
          url: absUrl(BASE, href),
        });
      }
    });
  }

  return results.filter((r) => matchesQuery(r.title, query));
}

module.exports = { search, SOURCE };
