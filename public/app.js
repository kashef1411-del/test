const form = document.getElementById('search-form');
const qInput = document.getElementById('q');
const sortSel = document.getElementById('sort');
const minPrice = document.getElementById('minPrice');
const maxPrice = document.getElementById('maxPrice');
const minYear = document.getElementById('minYear');
const statusEl = document.getElementById('status');
const resultsEl = document.getElementById('results');
const statsEl = document.getElementById('stats');
const sourceToggles = document.getElementById('source-toggles');

const state = { activeSources: new Set(), lastData: null };

function fmtEGP(n) {
  return new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 0 }).format(n);
}

async function loadSources() {
  const res = await fetch('/api/sources');
  const data = await res.json();
  sourceToggles.innerHTML = '';
  data.sources.forEach((s) => {
    const chip = document.createElement('span');
    chip.className = 'chip active';
    chip.textContent = s;
    chip.dataset.source = s;
    chip.addEventListener('click', () => {
      chip.classList.toggle('active');
      if (chip.classList.contains('active')) state.activeSources.add(s);
      else state.activeSources.delete(s);
      render();
    });
    state.activeSources.add(s);
    sourceToggles.appendChild(chip);
  });
}

function render() {
  if (!state.lastData) return;
  const { results, stats, bySource } = state.lastData;
  const filtered = results.filter((r) => state.activeSources.has(r.source));

  if (stats && stats.count) {
    statsEl.classList.remove('hidden');
    statsEl.innerHTML = `
      <div class="card"><div class="k">Listings</div><div class="v">${stats.count}</div></div>
      <div class="card"><div class="k">Min price</div><div class="v">${fmtEGP(stats.min)}</div></div>
      <div class="card"><div class="k">Average</div><div class="v">${fmtEGP(stats.avg)}</div></div>
      <div class="card"><div class="k">Max price</div><div class="v">${fmtEGP(stats.max)}</div></div>
    `;
  } else {
    statsEl.classList.add('hidden');
  }

  resultsEl.innerHTML = '';
  if (!filtered.length) {
    resultsEl.innerHTML = '<p style="color:#6b7280;grid-column:1/-1;">No listings match the current filters.</p>';
    return;
  }

  for (const item of filtered) {
    const card = document.createElement('article');
    card.className = 'card-listing';
    card.innerHTML = `
      <span class="src">${escapeHtml(item.source)}</span>
      <div class="title">${escapeHtml(item.title)}</div>
      <div class="price">${fmtEGP(item.price)}</div>
      <div class="meta">
        <span>${item.year ? item.year : ''}</span>
        <span>${item.location ? escapeHtml(item.location) : ''}</span>
      </div>
      <a class="open" href="${item.url || '#'}" target="_blank" rel="noopener noreferrer">View listing →</a>
    `;
    resultsEl.appendChild(card);
  }

  const counts = Object.entries(bySource || {}).map(([k, v]) => `${k}: ${v}`).join(' · ');
  statusEl.textContent = counts ? `Sources — ${counts}` : '';
}

function escapeHtml(s) {
  return String(s || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
}

async function doSearch(e) {
  if (e) e.preventDefault();
  const q = qInput.value.trim();
  if (!q) { statusEl.textContent = 'Type a car make or model to search.'; return; }

  statusEl.textContent = `Fetching listings for “${q}” from all sources…`;
  resultsEl.innerHTML = '';

  const params = new URLSearchParams({ q, sort: sortSel.value });
  if (minPrice.value) params.set('minPrice', minPrice.value);
  if (maxPrice.value) params.set('maxPrice', maxPrice.value);
  if (minYear.value) params.set('minYear', minYear.value);

  try {
    const res = await fetch('/api/search?' + params.toString());
    const data = await res.json();
    state.lastData = data;
    if (data.errors && data.errors.length) {
      console.warn('Scraper issues', data.errors);
    }
    render();
    if (!data.results.length) {
      statusEl.textContent = `No results found. Some sources may block automated requests — try a more common query like "Toyota Corolla".`;
    }
  } catch (err) {
    statusEl.textContent = 'Request failed: ' + err.message;
  }
}

form.addEventListener('submit', doSearch);
[sortSel, minPrice, maxPrice, minYear].forEach((el) => el.addEventListener('change', doSearch));

loadSources();
