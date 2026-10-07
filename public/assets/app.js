(function () {
    'use strict';

    var VIENNA = [48.2082, 16.3738];

    // Fixed colour per type id so the legend and the markers always match.
    var PALETTE = [
        '#2f6fed', '#13a3a3', '#e0820f', '#8a4fd8', '#d64545',
        '#2e9d57', '#c1499b', '#6b7a8f', '#b38b00', '#0b8fd6',
        '#7f5539', '#e05d8a', '#4a7c59', '#9c6ade', '#3c4fa0',
    ];

    var typeList = document.getElementById('type-list');
    var statusEl = document.getElementById('status');
    var toggleAllBtn = document.querySelector('[data-action="toggle-all"]');

    var colors = {};
    var layersByType = {};

    var map = L.map('map', { preferCanvas: true }).setView(VIENNA, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    function setStatus(text) {
        statusEl.textContent = text;
    }

    function getJSON(url) {
        return fetch(url).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        });
    }

    function renderTypes(types) {
        typeList.innerHTML = '';
        types.forEach(function (type, i) {
            colors[type.id] = PALETTE[i % PALETTE.length];

            var li = document.createElement('li');
            li.innerHTML =
                '<label class="type-item">' +
                '<input type="checkbox" checked value="' + type.id + '">' +
                '<span class="swatch" style="background:' + colors[type.id] + '"></span>' +
                '<span class="type-name"></span>' +
                '<span class="type-count">' + type.total + '</span>' +
                '</label>';
            li.querySelector('.type-name').textContent = type.name;
            typeList.appendChild(li);
        });
    }

    function renderFountains(geojson) {
        geojson.features.forEach(function (feature) {
            var p = feature.properties;
            var c = feature.geometry.coordinates;

            if (!layersByType[p.typeId]) {
                layersByType[p.typeId] = L.layerGroup().addTo(map);
            }

            L.circleMarker([c[1], c[0]], {
                radius: 6,
                color: '#fff',
                weight: 1.5,
                fillColor: colors[p.typeId] || '#2f6fed',
                fillOpacity: 0.9,
            })
                .bindPopup(
                    '<strong>' + escapeHtml(p.typeName) + '</strong><br>' +
                    '<a href="https://www.google.com/maps/dir/?api=1&destination=' + c[1] + ',' + c[0] +
                    '" target="_blank" rel="noopener">Route planen</a>'
                )
                .addTo(layersByType[p.typeId]);
        });
    }

    function syncLayers() {
        var boxes = typeList.querySelectorAll('input[type="checkbox"]');
        var anyChecked = false;

        boxes.forEach(function (box) {
            var layer = layersByType[box.value];
            if (!layer) return;
            if (box.checked) {
                anyChecked = true;
                map.addLayer(layer);
            } else {
                map.removeLayer(layer);
            }
        });

        toggleAllBtn.textContent = anyChecked ? 'Alle abwählen' : 'Alle auswählen';
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    typeList.addEventListener('change', syncLayers);

    toggleAllBtn.addEventListener('click', function () {
        var boxes = typeList.querySelectorAll('input[type="checkbox"]');
        var anyChecked = Array.prototype.some.call(boxes, function (b) { return b.checked; });
        boxes.forEach(function (b) { b.checked = !anyChecked; });
        syncLayers();
    });

    setStatus('Lade Brunnen…');

    getJSON('api/fountains.php?meta=1')
        .then(function (meta) {
            renderTypes(meta.types);
            return getJSON('api/fountains.php').then(function (geojson) {
                renderFountains(geojson);
                var when = meta.lastImport
                    ? new Date(meta.lastImport.importedAt).toLocaleString('de-AT')
                    : 'unbekannt';
                setStatus(geojson.features.length + ' Brunnen · Stand: ' + when);
            });
        })
        .catch(function () {
            typeList.innerHTML = '<li class="muted">Daten konnten nicht geladen werden.</li>';
            setStatus('Fehler beim Laden der Daten.');
        });

    // Theme toggle, same approach as the knecht test page.
    var root = document.documentElement;
    try {
        var saved = localStorage.getItem('theme');
        if (saved) root.dataset.theme = saved;
    } catch (e) {}
    document.querySelector('.theme-toggle').addEventListener('click', function () {
        var dark = root.dataset.theme
            ? root.dataset.theme === 'dark'
            : window.matchMedia('(prefers-color-scheme: dark)').matches;
        root.dataset.theme = dark ? 'light' : 'dark';
        try { localStorage.setItem('theme', root.dataset.theme); } catch (e) {}
    });
})();
