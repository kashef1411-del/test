function parsePrice(text) {
  if (!text) return null;
  const cleaned = String(text).replace(/[,\u066C\s]/g, '');
  const match = cleaned.match(/(\d{4,10})/);
  if (!match) return null;
  const n = parseInt(match[1], 10);
  if (isNaN(n) || n < 10000) return null;
  return n;
}

function parseYear(text) {
  if (!text) return null;
  const m = String(text).match(/\b(19|20)\d{2}\b/);
  return m ? parseInt(m[0], 10) : null;
}

function normalizeTitle(s) {
  return (s || '').replace(/\s+/g, ' ').trim();
}

function absUrl(base, href) {
  if (!href) return null;
  try { return new URL(href, base).toString(); } catch { return href; }
}

function matchesQuery(title, query) {
  if (!query) return true;
  const t = (title || '').toLowerCase();
  return query.toLowerCase().split(/\s+/).filter(Boolean).every((tok) => t.includes(tok));
}

module.exports = { parsePrice, parseYear, normalizeTitle, absUrl, matchesQuery };
