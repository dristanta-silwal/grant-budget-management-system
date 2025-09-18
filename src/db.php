<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    // Ensure DATABASE_URL is available via both $_ENV and $_SERVER
    if (isset($_ENV['DATABASE_URL']) && !isset($_SERVER['DATABASE_URL'])) {
        $_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'];
    }
}

$databaseUrl = $_ENV['DATABASE_URL']
    ?? $_SERVER['DATABASE_URL']
    ?? getenv('DATABASE_URL')
    ?? null;

if ($databaseUrl === null) {
    die("DATABASE_URL environment variable is not set.");
}

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    // Parse the URL to get connection parts
    $db = parse_url($databaseUrl);

    $host = $db['host'] ?? '';
    $port = $db['port'] ?? 5432;
    $user = isset($db['user']) ? urldecode($db['user']) : '';
    $pass = isset($db['pass']) ? urldecode($db['pass']) : '';
    $path = $db['path'] ?? '';
    $dbname = ltrim($path, '/');

    if ($port == 6543) {
        $options[PDO::ATTR_EMULATE_PREPARES] = true;
    }

    $sslmode = 'require';
    if (isset($db['query'])) {
        parse_str($db['query'], $queryParams);
        if (isset($queryParams['sslmode'])) {
            $sslmode = $queryParams['sslmode'];
        }
    }

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";

    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
