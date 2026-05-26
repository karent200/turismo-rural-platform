<?php
require __DIR__ . '/src/config.php';
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASS: " . DB_PASS . "\n";
echo "DB_NAME: " . DB_NAME . "\n";

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Connection OK\n";
    $stmt = $pdo->query('SELECT COUNT(*) as c FROM users');
    $r = $stmt->fetch();
    echo "Users: " . $r['c'] . "\n";
} catch (Exception $e) {
    echo "Connection FAILED: " . $e->getMessage() . "\n";
}
