<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trinkbrunnen Wien</title>
    <meta name="description" content="Alle öffentlichen Trinkbrunnen, Sprühnebelduschen und Wasserspiele in Wien auf einer Karte.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <header class="sidebar-header">
                <h1><span class="drop" aria-hidden="true">💧</span> Trinkbrunnen Wien</h1>
                <button class="theme-toggle" type="button" aria-label="Dunkelmodus umschalten">◐</button>
            </header>
            <p class="lead">Wo gibt's in der Nähe Wasser? Alle öffentlichen Brunnen der Stadt Wien auf einer Karte.</p>

            <section class="filters" aria-labelledby="filter-heading">
                <div class="filters-head">
                    <h2 id="filter-heading">Typ</h2>
                    <button class="link-btn" type="button" data-action="toggle-all">Alle abwählen</button>
                </div>
                <ul class="type-list" id="type-list">
                    <li class="muted">Lade Daten…</li>
                </ul>
            </section>

            <footer class="sidebar-footer">
                <p id="status" class="muted" aria-live="polite"></p>
                <p class="muted small">
                    Daten: <a href="https://www.data.gv.at/katalog/dataset/trinkbrunnen-standorte-wien" target="_blank" rel="noopener">Stadt Wien, data.wien.gv.at</a> (CC BY 4.0)
                </p>
            </footer>
        </aside>

        <main class="map-wrap">
            <div id="map" role="region" aria-label="Karte der Trinkbrunnen"></div>
        </main>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="assets/app.js"></script>
</body>
</html>
