<?php

declare(strict_types=1);

/**
 * Returns a shared PDO connection. Defaults match DDEV's database service,
 * environment variables override them on other hosts.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'db';
        $user = getenv('DB_USER') ?: 'db';
        $pass = getenv('DB_PASSWORD') ?: 'db';

        $pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        );
    }

    return $pdo;
}
