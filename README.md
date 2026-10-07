# Trinkbrunnen Wien

An interactive map of every public drinking fountain, mist shower and water
play spot in Vienna, built on the city's open data.

## Features

- 2,300+ fountains from [data.wien.gv.at](https://www.data.gv.at/katalog/dataset/trinkbrunnen-standorte-wien), shown on a Leaflet map
- Filter by type (drinking fountain, drinking hydrant, mist shower, ...)
- One-click route planning to any fountain
- Light and dark mode, responsive down to phone width

## How it works

```
data.wien.gv.at (WFS, GeoJSON)
        │  import (bin/import.php or on first request)
        ▼
MariaDB cache  ──►  /api/fountains.php  ──►  Leaflet frontend
```

- **Backend:** plain PHP 8.4, no framework. The open data is cached in MariaDB
  and refreshed at most once a day. If the city's API is down, the last cached
  copy keeps being served.
- **Self-seeding:** a fresh environment starts with an empty database, so the
  first API request imports the data automatically. Every preview deploy works
  without a manual setup step.
- **Frontend:** vanilla JS and Leaflet with canvas rendering, so thousands of
  markers stay smooth.

## API

| Request | Response |
| --- | --- |
| `GET /api/fountains.php` | All fountains as GeoJSON |
| `GET /api/fountains.php?type=4,10` | Only the given type ids |
| `GET /api/fountains.php?meta=1` | Available types with counts, last import |

## Local development

Requires [DDEV](https://ddev.com).

```bash
ddev start
ddev exec php bin/import.php   # optional, the first request does this too
ddev launch
```

## Deployment

Runs on knecht: every push updates the preview environment from the same
`.ddev/config.yaml` used locally.

## Data license

Fountain data: Stadt Wien, [data.wien.gv.at](https://data.wien.gv.at), CC BY 4.0.
Map tiles: © OpenStreetMap contributors.
