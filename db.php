<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (getenv('JAWSDB_URL')) {
    $db = parse_url(getenv('JAWSDB_URL'));

    $host = $db['host'];
    $username = $db['user'];
    $password = $db['pass'];
    $database = ltrim($db['path'], '/');
    $port = isset($db['port']) ? $db['port'] : 3306;
} else {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $host = $_ENV['DB_HOST'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $database = $_ENV['DB_DATABASE'];
    $port = $_ENV['DB_PORT'];
}

// Create a new MySQLi connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
