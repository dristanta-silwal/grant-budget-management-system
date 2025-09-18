<?php
declare(strict_types=1);
header('Content-Type: application/json');

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(dirname(__DIR__) . '/.env')) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

$databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);

try {
    if (!$databaseUrl) {
        throw new RuntimeException('Missing DATABASE_URL');
    }
    $parts = parse_url($databaseUrl);
    parse_str($parts['query'] ?? '', $q);

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        $parts['host'],
        $parts['port'] ?? '5432',
        ltrim($parts['path'] ?? '/postgres', '/'),
        $q['sslmode'] ?? 'require'
    );
    $user = urldecode($parts['user'] ?? 'postgres');
    $pass = urldecode($parts['pass'] ?? '');

    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    if (($parts['port'] ?? null) == '6543') {
        $pdoOptions[PDO::ATTR_EMULATE_PREPARES] = true;
    }
    $pdo = new PDO($dsn, $user, $pass, $pdoOptions);

    $t   = $pdo->query('SELECT now() AS db_time')->fetch(PDO::FETCH_ASSOC)['db_time'] ?? null;
    $ver = $pdo->query('SELECT version()')->fetch(PDO::FETCH_COLUMN);

    echo json_encode([
        'ok' => true,
        'db_time' => $t,
        'version' => $ver,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}