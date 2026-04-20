const store = new Map();
const TTL_MS = 10 * 60 * 1000;

function get(key) {
  const entry = store.get(key);
  if (!entry) return null;
  if (Date.now() - entry.ts > TTL_MS) { store.delete(key); return null; }
  return entry.value;
}

function set(key, value) {
  store.set(key, { ts: Date.now(), value });
}

module.exports = { get, set };
