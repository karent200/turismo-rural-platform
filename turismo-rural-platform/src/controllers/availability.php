<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cors.php';
handleCorsPreflight();
setCorsHeaders();

header('Content-Type: application/json; charset=utf-8');

initSecureSession();

require_once __DIR__ . '/../connectdb.php';
require_once __DIR__ . '/../models/Availability.php';

$availabilityModel = new Availability();
$providerId = (int) ($_SESSION['user_id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($method === 'GET') {
    if ($action === 'check') {
        $serviceId = (int) ($_GET['service_id'] ?? 0);
        $date = $_GET['date'] ?? '';
        if ($serviceId <= 0 || !$date) {
            echo json_encode([]);
            exit;
        }
        $pdo = getDBConnection();
        $stmt = executeQuery($pdo, "SELECT date, slots_available FROM availability WHERE service_id = ? AND date >= ? AND slots_available > 0 ORDER BY date", [$serviceId, date('Y-m-d')]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    requireRole('prestador');
    $serviceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;
    if ($serviceId > 0) {
        if (!$availabilityModel->serviceBelongsToProvider($serviceId, $providerId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Servicio no válido']);
            exit;
        }
        echo json_encode($availabilityModel->getByService($serviceId, $providerId));
    } else {
        echo json_encode($availabilityModel->getByProvider($providerId));
    }
    exit;
}

if ($method === 'POST') {
    requireRole('prestador');
    if (!validateCsrfToken($_POST['_csrf'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF inválido']);
        exit;
    }

    if ($action === 'set') {
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $date = $_POST['date'] ?? '';
        $slots = (int) ($_POST['slots_available'] ?? 0);

        if (!$availabilityModel->serviceBelongsToProvider($serviceId, $providerId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Servicio no válido']);
            exit;
        }
        if ($date === '' || $date < date('Y-m-d')) {
            http_response_code(400);
            echo json_encode(['error' => 'Fecha inválida']);
            exit;
        }
        if ($slots < 1 || $slots > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Los cupos deben ser entre 1 y 100']);
            exit;
        }

        $availabilityModel->upsert($serviceId, $date, $slots);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        if ($availabilityModel->delete($id, $providerId)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'No se encontró la disponibilidad']);
        }
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Acción no válida']);
