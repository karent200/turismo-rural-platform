<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cors.php';
handleCorsPreflight();
setCorsHeaders();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

initSecureSession();

require_once __DIR__ . '/../connectdb.php';
require_once __DIR__ . '/../models/Service.php';

$serviceModel = new Service();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    $type = $_GET['type'] ?? '';
    $location = $_GET['location'] ?? '';

    if ($action === 'locations') {
        $pdo = getDBConnection();
        $stmt = executeQuery($pdo, "SELECT DISTINCT location FROM services WHERE location IS NOT NULL AND location != '' ORDER BY location");
        $rows = $stmt->fetchAll();
        echo json_encode($rows);
        exit;
    }

    if ($action === 'my') {
        requireRole('prestador');
        $services = $serviceModel->getAll($type ?: null, $location ?: null, (int) $_SESSION['user_id']);
    } else {
        $pid = isset($_GET['provider_id']) ? (int) $_GET['provider_id'] : null;
        $services = $serviceModel->getAll($type ?: null, $location ?: null, $pid);
    }
    echo json_encode($services);
    exit;
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!validateCsrfToken($_POST['_csrf'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF inválido']);
        exit;
    }
    
    if ($action === 'create') {
        requireRole('prestador');
        
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 1);
        $price = floatval($_POST['price'] ?? 0);
        $location = trim($_POST['location'] ?? '');

        if (strlen($name) > 200 || strlen($location) > 200 || strlen($description) > 2000) {
            http_response_code(400);
            echo json_encode(['error' => 'Campos demasiado largos']);
            exit;
        }

        if ($capacity < 1 || $capacity > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Capacidad inválida (1-100)']);
            exit;
        }

        if ($price < 0 || $price > 999999999) {
            http_response_code(400);
            echo json_encode(['error' => 'Precio inválido']);
            exit;
        }
        
        if ($serviceModel->create((int) $_SESSION['user_id'], $name, $type, $description, $capacity, $price, $location)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al crear servicio']);
        }
        exit;
    }
    
    if ($action === 'update') {
        requireRole('prestador');
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['error' => 'Datos inválidos']);
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $description = $_POST['description'] ?? '';
        $capacity = (int)($_POST['capacity'] ?? 1);
        $price = floatval($_POST['price'] ?? 0);
        $location = $_POST['location'] ?? '';
        
        if ($serviceModel->update($id, $name, $type, $description, $capacity, $price, $location)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al actualizar']);
        }
        exit;
    }
    
    if ($action === 'delete') {
        requireRole('prestador');
        $id = (int)($_POST['id'] ?? 0);
        
        if ($serviceModel->delete($id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al eliminar']);
        }
        exit;
    }
    
    if ($action === 'list') {
        $type = $_POST['type'] ?? '';
        $location = $_POST['location'] ?? '';
        $services = $serviceModel->getAll($type ?: null, $location ?: null, null);
        echo json_encode($services);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
?>