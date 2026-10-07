<?php

declare(strict_types=1);

// Usage: ddev exec php bin/import.php

require_once __DIR__ . '/../src/fountains.php';

ensure_schema();
$count = import_fountains();

echo "Imported {$count} fountains.\n";
