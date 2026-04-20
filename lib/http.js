const axios = require('axios');

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

const client = axios.create({
  timeout: 15000,
  headers: {
    'User-Agent': UA,
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language': 'en-US,en;q=0.9,ar;q=0.8',
  },
  validateStatus: (s) => s >= 200 && s < 400,
});

async function fetchHtml(url) {
  const { data } = await client.get(url);
  return data;
}

module.exports = { fetchHtml, client };
