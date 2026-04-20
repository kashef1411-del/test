(function () {
  var cfg = window.CarCompareEG || {};
  var form = document.getElementById('cce-form');
  if (!form) return;

  var qInput = document.getElementById('cce-q');
  var sortSel = document.getElementById('cce-sort');
  var minPrice = document.getElementById('cce-min-price');
  var maxPrice = document.getElementById('cce-max-price');
  var minYear = document.getElementById('cce-min-year');
  var statusEl = document.getElementById('cce-status');
  var resultsEl = document.getElementById('cce-results');
  var statsEl = document.getElementById('cce-stats');
  var sourceToggles = document.getElementById('cce-sources');

  var state = { activeSources: new Set(), lastData: null };

  function fmtEGP(n) {
    try { return new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 0 }).format(n); }
    catch (e) { return 'EGP ' + (n || 0).toLocaleString(); }
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }

  function loadSources() {
    fetch(cfg.sources, { headers: { 'X-WP-Nonce': cfg.nonce } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        sourceToggles.innerHTML = '';
        (data.sources || []).forEach(function (s) {
          var name = s.name || s;
          var chip = document.createElement('span');
          chip.className = 'cce-chip active';
          chip.textContent = name;
          chip.addEventListener('click', function () {
            chip.classList.toggle('active');
            if (chip.classList.contains('active')) state.activeSources.add(name);
            else state.activeSources.delete(name);
            render();
          });
          state.activeSources.add(name);
          sourceToggles.appendChild(chip);
        });
      })
      .catch(function () { /* ignore */ });
  }

  function render() {
    if (!state.lastData) return;
    var data = state.lastData;
    var stats = data.stats || {};
    var bySource = data.bySource || {};
    var filtered = (data.results || []).filter(function (r) { return state.activeSources.has(r.source); });

    if (stats.count) {
      statsEl.classList.remove('cce-hidden');
      statsEl.innerHTML =
        '<div class="cce-card"><div class="k">Listings</div><div class="v">' + stats.count + '</div></div>' +
        '<div class="cce-card"><div class="k">Min price</div><div class="v">' + fmtEGP(stats.min) + '</div></div>' +
        '<div class="cce-card"><div class="k">Average</div><div class="v">' + fmtEGP(stats.avg) + '</div></div>' +
        '<div class="cce-card"><div class="k">Max price</div><div class="v">' + fmtEGP(stats.max) + '</div></div>';
    } else {
      statsEl.classList.add('cce-hidden');
    }

    resultsEl.innerHTML = '';
    if (!filtered.length) {
      resultsEl.innerHTML = '<p style="color:#6b7280;grid-column:1/-1;">No listings match the current filters.</p>';
    } else {
      filtered.forEach(function (item) {
        var el = document.createElement('article');
        el.className = 'cce-listing';
        el.innerHTML =
          '<span class="cce-src">' + escapeHtml(item.source) + '</span>' +
          '<div class="cce-title">' + escapeHtml(item.title) + '</div>' +
          '<div class="cce-price">' + fmtEGP(item.price) + '</div>' +
          '<div class="cce-meta"><span>' + (item.year || '') + '</span><span>' + escapeHtml(item.location || '') + '</span></div>' +
          '<a class="cce-open" href="' + escapeHtml(item.url || '#') + '" target="_blank" rel="noopener noreferrer">View listing →</a>';
        resultsEl.appendChild(el);
      });
    }

    var counts = Object.keys(bySource).map(function (k) { return k + ': ' + bySource[k]; }).join(' · ');
    statusEl.textContent = counts ? 'Sources — ' + counts : '';
  }

  function doSearch(e) {
    if (e) e.preventDefault();
    var q = qInput.value.trim();
    if (!q) { statusEl.textContent = 'Type a car make or model to search.'; return; }
    statusEl.textContent = 'Fetching listings for “' + q + '” from all sources…';
    resultsEl.innerHTML = '';

    var params = new URLSearchParams({ q: q, sort: sortSel.value });
    if (minPrice.value) params.set('minPrice', minPrice.value);
    if (maxPrice.value) params.set('maxPrice', maxPrice.value);
    if (minYear.value) params.set('minYear', minYear.value);

    fetch(cfg.endpoint + '?' + params.toString(), { headers: { 'X-WP-Nonce': cfg.nonce } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        state.lastData = data;
        render();
        if (!data.results || !data.results.length) {
          statusEl.textContent = 'No results found. Some sources may block automated requests — try a more common query like "Toyota Corolla".';
        }
      })
      .catch(function (err) { statusEl.textContent = 'Request failed: ' + err.message; });
  }

  form.addEventListener('submit', doSearch);
  [sortSel, minPrice, maxPrice, minYear].forEach(function (el) { el.addEventListener('change', doSearch); });

  loadSources();
})();
