<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cors.php';
handleCorsPreflight();
setCorsHeaders();

header('Content-Type: application/json; charset=utf-8');

initSecureSession();
requireRole('prestador');

require_once __DIR__ . '/../connectdb.php';
require_once __DIR__ . '/../models/ProviderProfile.php';

$profileModel = new ProviderProfile();
$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'profile';

if ($method === 'GET' && $action === 'profile') {
    echo json_encode($profileModel->getByUserId($userId));
    exit;
}

if ($method === 'POST' && $action === 'save_profile') {
    if (!validateCsrfToken($_POST['_csrf'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF inválido']);
        exit;
    }

    $business_name = trim($_POST['business_name'] ?? '');
    $municipio = trim($_POST['municipio'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $telefono = trim($_POST['telefono_contacto'] ?? '');

    if ($business_name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'El nombre del negocio es obligatorio']);
        exit;
    }

    if (strlen($business_name) > 200 || strlen($municipio) > 100 || strlen($descripcion) > 2000) {
        http_response_code(400);
        echo json_encode(['error' => 'Campos demasiado largos']);
        exit;
    }

    $profileModel->upsert($userId, $business_name, $municipio, $descripcion, $telefono);
    echo json_encode(['success' => true, 'profile' => $profileModel->getByUserId($userId)]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción no válida']);
