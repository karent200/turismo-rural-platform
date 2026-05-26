<?php
// api.php - Router API central

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/session.php';
initSecureSession();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace(['/src/', '/api.php', '/api/'], '', $uri);
$parts = array_filter(explode('/', $path));
$resource = reset($parts) ?: '';
$id = $_GET['id'] ?? $_POST['id'] ?? null;

try {
    switch ($resource) {
        case 'auth':
            require_once __DIR__ . '/controllers/auth.php';
            break;

        case 'services':
            require_once __DIR__ . '/controllers/services.php';
            break;

        case 'reservations':
            require_once __DIR__ . '/controllers/reservations.php';
            break;

        case 'health':
            $pdo = getDBConnection();
            echo json_encode(['status' => 'ok', 'database' => 'connected']);
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>