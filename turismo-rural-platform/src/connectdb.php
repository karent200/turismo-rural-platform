<?php
// connectdb.php - Conexión segura PDO MySQL

require_once __DIR__ . '/config.php';

function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4;collation=utf8mb4_unicode_ci";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Error conexión DB: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
        exit;
    }
}

function executeQuery($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
?>