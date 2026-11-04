<?php
declare(strict_types=1);

function site_config(): array
{
    $path = dirname(__DIR__, 2) . '/sb-constructions-private/config.php';
    if (!is_file($path)) {
        throw new RuntimeException('Private site configuration is missing');
    }
    $config = require $path;
    if (!is_array($config)) {
        throw new RuntimeException('Private site configuration is invalid');
    }
    return $config;
}

function site_db(array $config): PDO
{
    foreach (['db_host', 'db_name', 'db_user', 'db_password'] as $key) {
        if (!isset($config[$key]) || !is_string($config[$key]) || $config[$key] === '') {
            throw new RuntimeException('Database is not configured');
        }
    }
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
    return new PDO($dsn, $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function site_json(array $body, int $status): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}
