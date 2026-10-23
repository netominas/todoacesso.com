/* Todo Acesso — JavaScript sin dependencias. */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };

  /* ---------- Fragmentos actualizables ---------- */
  function applyFragments(data) {
    if (!data) { return; }
    var map = { status: '#status-fragment', chart: '#chart-fragment', breakdown: '#breakdown-fragment' };
    Object.keys(map).forEach(function (k) {
      var el = $(map[k]);
      if (el && typeof data[k] === 'string') { el.innerHTML = data[k]; }
    });
    if (Array.isArray(data.regions)) { updateRegions(data.regions); }
  }

  function showMessage(tone, text) {
    var box = $('#report-message');
    if (!box) { return; }
    box.className = 'alert alert--' + tone;
    box.textContent = text;
    box.classList.remove('is-hidden');
  }

  function postForm(form, extra) {
    var fd = new FormData(form);
    if (extra) { Object.keys(extra).forEach(function (k) { fd.set(k, extra[k]); }); }
    return fetch(form.action, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' }
    }).then(function (r) { return r.json(); });
  }

  /* ---------- Ubicación (solo al enviar el reporte, nunca antes) ---------- */
  function getPosition() {
    return new Promise(function (resolve) {
      if (!('geolocation' in navigator)) { return resolve(null); }
      var done = false;
      function finish(v) { if (!done) { done = true; clearTimeout(timer); resolve(v); } }
      var timer = setTimeout(function () { finish(null); }, 9000);
      try {
        navigator.geolocation.getCurrentPosition(
          function (p) { finish({ lat: p.coords.latitude.toFixed(2), lon: p.coords.longitude.toFixed(2) }); },
          function () { finish(null); },
          { enableHighAccuracy: false, timeout: 8000, maximumAge: 600000 }
        );
      } catch (e) { finish(null); }
    });
  }

  /* ---------- Formulario de reportes ---------- */
  var reportForm = $('[data-report-form]');
  if (reportForm && window.fetch && window.FormData) {
    var btn = $('[data-report-btn]', reportForm);
    reportForm.addEventListener('submit', function (e) {
      var chosen = reportForm.querySelector('input[name="problem"]:checked');
      e.preventDefault();
      if (!chosen) {
        showMessage('error', 'Elegí un tipo de problema para poder enviar el reporte.');
        return;
      }
      var label = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Enviando…';
      getPosition().then(function (pos) {
        return postForm(reportForm, pos ? pos : { lat: '', lon: '' });
      }).then(function (data) {
        showMessage(data.tone || 'info', data.message || 'Listo.');
        if (data.ok) {
          applyFragments(data);
          chosen.checked = false;
        }
      }).catch(function () {
        showMessage('error', 'No pudimos enviar el reporte. Revisá tu conexión e intentá de nuevo.');
      }).then(function () {
        btn.disabled = false;
        btn.textContent = label;
      });
    });
  }

  /* ---------- Verificar de nuevo / actualización automática ---------- */
  var root = $('[data-status-root]');
  var recheckForm = $('[data-recheck-form]');
  function recheck(mode, button) {
    if (!recheckForm) { return; }
    var label = button ? button.textContent : '';
    if (button) { button.disabled = true; button.textContent = 'Verificando…'; }
    postForm(recheckForm, { mode: mode }).then(function (data) {
      if (data && data.ok) { applyFragments(data); }
    }).catch(function () { /* se mantiene el último resultado */ }).then(function () {
      if (button) { button.disabled = false; button.textContent = label; }
    });
  }
  if (recheckForm && window.fetch && window.FormData) {
    recheckForm.addEventListener('submit', function (e) {
      e.preventDefault();
      recheck('manual', $('button', recheckForm));
    });
    if (root && root.getAttribute('data-stale') === '1') {
      setTimeout(function () { recheck('auto', null); }, 1200);
    }
  }

  /* ---------- Mapa (Leaflet se carga solo cuando se acerca a la pantalla) ---------- */
  var mapEl = $('#map');
  var leafletMap = null;
  var layer = null;

  function parseRegions() {
    try { return JSON.parse(mapEl.getAttribute('data-regions') || '[]'); } catch (e) { return []; }
  }

  function updateRegions(regions) {
    if (!mapEl) { return; }
    mapEl.setAttribute('data-regions', JSON.stringify(regions));
    var empty = $('#map-empty');
    if (empty) { empty.classList.toggle('is-hidden', regions.length > 0); }
    var list = $('#regions-list');
    if (list) {
      list.innerHTML = '';
      regions.forEach(function (r) {
        var li = document.createElement('li');
        li.appendChild(document.createTextNode(r.name + ': '));
        var b = document.createElement('strong');
        b.textContent = r.count;
        li.appendChild(b);
        list.appendChild(li);
      });
      list.hidden = regions.length === 0;
    }
    drawBubbles(regions);
  }

  function drawBubbles(regions) {
    if (!leafletMap || !window.L) { return; }
    if (layer) { layer.clearLayers(); } else { layer = L.layerGroup().addTo(leafletMap); }
    var max = regions.reduce(function (m, r) { return Math.max(m, r.count); }, 1);
    regions.forEach(function (r) {
      var size = Math.round(30 + 34 * Math.sqrt(r.count / max));
      var icon = L.divIcon({
        className: 'map-bubble-wrap',
        html: '<div class="map-bubble" style="width:' + size + 'px;height:' + size + 'px;line-height:' + size + 'px">' + r.count + '</div>',
        iconSize: [size, size]
      });
      var label = r.name + ': ' + r.count + (r.count === 1 ? ' reporte' : ' reportes') + ' (zona aproximada)';
      L.marker([r.lat, r.lon], { icon: icon, title: label, alt: label, keyboard: true })
        .bindTooltip(label, { direction: 'top' })
        .addTo(layer);
    });
  }

  function startMap() {
    if (!mapEl || leafletMap || !window.L) { return; }
    leafletMap = L.map(mapEl, {
      scrollWheelZoom: false,
      minZoom: 3,
      maxZoom: 9,
      zoomSnap: 0.25,
      maxBounds: [[-62, -85], [-12, -40]],
      maxBoundsViscosity: 0.8
    });
    // Encuadra todo el país (de Jujuy a Tierra del Fuego) en cualquier tamaño de pantalla.
    leafletMap.fitBounds([[-55.2, -73.7], [-21.7, -53.4]], { padding: [8, 8] });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 9,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>'
    }).addTo(leafletMap);
    drawBubbles(parseRegions());
  }

  function loadLeaflet() {
    if (window.L) { startMap(); return; }
    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = mapEl.getAttribute('data-css') || '/assets/vendor/leaflet/leaflet.css';
    document.head.appendChild(css);
    var js = document.createElement('script');
    js.src = mapEl.getAttribute('data-js') || '/assets/vendor/leaflet/leaflet.js';
    js.onload = startMap;
    document.head.appendChild(js);
  }

  if (mapEl) {
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        if (entries.some(function (en) { return en.isIntersecting; })) {
          io.disconnect();
          loadLeaflet();
        }
      }, { rootMargin: '300px' });
      io.observe(mapEl);
    } else {
      window.addEventListener('load', loadLeaflet);
    }
  }
})();
