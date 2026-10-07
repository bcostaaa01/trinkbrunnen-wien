<?php

declare(strict_types=1);

// GET /api/fountains.php            all fountains as GeoJSON
// GET /api/fountains.php?type=4,10  only the given type ids
// GET /api/fountains.php?meta=1     available types and import info

require_once __DIR__ . '/../../src/fountains.php';

header('Content-Type: application/json; charset=utf-8');

try {
    ensure_fresh();

    if (isset($_GET['meta'])) {
        $last = last_import();
        echo json_encode([
            'types' => array_map(fn (array $t) => [
                'id' => (int) $t['type_id'],
                'name' => $t['type_name'],
                'total' => (int) $t['total'],
            ], fetch_types()),
            'lastImport' => $last === null ? null : [
                // ISO 8601 with offset, so browsers in any timezone read it right.
                'importedAt' => date('c', strtotime($last['imported_at'])),
                'count' => (int) $last['row_count'],
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $typeIds = null;
    if (!empty($_GET['type'])) {
        $typeIds = array_values(array_filter(
            array_map('intval', explode(',', (string) $_GET['type'])),
            fn (int $id) => $id > 0,
        ));
    }

    header('Cache-Control: public, max-age=300');
    echo json_encode([
        'type' => 'FeatureCollection',
        'features' => array_map(fn (array $f) => [
            'type' => 'Feature',
            'id' => (int) $f['id'],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [(float) $f['lng'], (float) $f['lat']],
            ],
            'properties' => [
                'typeId' => (int) $f['type_id'],
                'typeName' => $f['type_name'],
            ],
        ], fetch_fountains($typeIds)),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(503);
    error_log($e->getMessage());
    echo json_encode(['error' => 'Fountain data is currently unavailable.']);
}
