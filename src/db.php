<?php

declare(strict_types=1);

/**
 * Returns a shared PDO connection.
 *
 * DB_DRIVER=sqlite stores the cache in a single file (used in production,
 * where the data re-imports itself if the file is lost). Anything else
 * connects to MySQL/MariaDB, with defaults matching DDEV's database service.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if (db_driver() === 'sqlite') {
        $path = getenv('DB_PATH') ?: __DIR__ . '/../storage/fountains.sqlite';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        $pdo = new PDO("sqlite:{$path}", null, null, $options);
        $pdo->exec('PRAGMA journal_mode = WAL');

        return $pdo;
    }

    $host = getenv('DB_HOST') ?: 'db';
    $name = getenv('DB_NAME') ?: 'db';
    $user = getenv('DB_USER') ?: 'db';
    $pass = getenv('DB_PASSWORD') ?: 'db';

    $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, $options);

    return $pdo;
}

function db_driver(): string
{
    return getenv('DB_DRIVER') === 'sqlite' ? 'sqlite' : 'mysql';
}
