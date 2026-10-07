<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const SOURCE_URL = 'https://data.wien.gv.at/daten/geo?service=WFS&request=GetFeature'
    . '&version=1.1.0&typeName=ogdwien:TRINKBRUNNENOGD&srsName=EPSG:4326&outputFormat=json';

// How long the cached copy of the open data is considered fresh.
const MAX_AGE_SECONDS = 24 * 60 * 60;

function ensure_schema(): void
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS fountains (
            id INT UNSIGNED PRIMARY KEY,
            type_id SMALLINT UNSIGNED NOT NULL,
            type_name VARCHAR(100) NOT NULL,
            lat DECIMAL(10, 8) NOT NULL,
            lng DECIMAL(11, 8) NOT NULL,
            INDEX idx_type (type_id)
        ) DEFAULT CHARSET=utf8mb4'
    );

    db()->exec(
        'CREATE TABLE IF NOT EXISTS imports (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            imported_at DATETIME NOT NULL,
            row_count INT UNSIGNED NOT NULL
        )'
    );
}

/**
 * Downloads the dataset from data.wien.gv.at and replaces the cached rows.
 * Returns the number of imported fountains.
 */
function import_fountains(): int
{
    // data.wien.gv.at never answers requests without a User-Agent header,
    // and PHP's HTTP stream sends none by default.
    $json = @file_get_contents(SOURCE_URL, false, stream_context_create([
        'http' => [
            'timeout' => 20,
            'user_agent' => 'trinkbrunnen-wien (+https://github.com/bcostaaa01/trinkbrunnen-wien)',
        ],
    ]));

    if ($json === false) {
        $reason = error_get_last()['message'] ?? 'unknown error';
        throw new RuntimeException("Could not download fountain data: {$reason}");
    }

    $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    $features = $data['features'] ?? [];

    if ($features === []) {
        throw new RuntimeException('Fountain data contained no features');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $pdo->exec('DELETE FROM fountains');
        $insert = $pdo->prepare(
            'INSERT INTO fountains (id, type_id, type_name, lat, lng) VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($features as $feature) {
            [$lng, $lat] = $feature['geometry']['coordinates'];
            $props = $feature['properties'];
            $insert->execute([
                $props['OBJECTID'],
                $props['BASIS_TYP'],
                $props['BASIS_TYP_TXT'],
                $lat,
                $lng,
            ]);
        }

        $pdo->prepare('INSERT INTO imports (imported_at, row_count) VALUES (NOW(), ?)')
            ->execute([count($features)]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return count($features);
}

function last_import(): ?array
{
    $row = db()->query('SELECT imported_at, row_count FROM imports ORDER BY id DESC LIMIT 1')->fetch();

    return $row ?: null;
}

/**
 * Makes sure the cache exists and is reasonably fresh. A fresh preview
 * environment starts with an empty database, so the first request seeds it.
 */
function ensure_fresh(): void
{
    ensure_schema();
    $last = last_import();

    if ($last !== null && time() - strtotime($last['imported_at']) < MAX_AGE_SECONDS) {
        return;
    }

    try {
        import_fountains();
    } catch (Throwable $e) {
        // Stale data beats no data: only fail when there is nothing cached yet.
        if ($last === null) {
            throw $e;
        }
        error_log('Fountain refresh failed: ' . $e->getMessage());
    }
}

function fetch_fountains(?array $typeIds = null): array
{
    $sql = 'SELECT id, type_id, type_name, lat, lng FROM fountains';
    $params = [];

    if ($typeIds) {
        $sql .= ' WHERE type_id IN (' . implode(',', array_fill(0, count($typeIds), '?')) . ')';
        $params = $typeIds;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function fetch_types(): array
{
    return db()->query(
        'SELECT type_id, type_name, COUNT(*) AS total
         FROM fountains GROUP BY type_id, type_name ORDER BY total DESC'
    )->fetchAll();
}
