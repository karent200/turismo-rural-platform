<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cors.php';
handleCorsPreflight();
setCorsHeaders();

header('Content-Type: application/json; charset=utf-8');

initSecureSession();

require_once __DIR__ . '/../connectdb.php';
require_once __DIR__ . '/../models/Review.php';

$reviewModel = new Review();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($method === 'GET' && $action === 'list') {
    $service_id = (int) ($_GET['service_id'] ?? 0);
    if ($service_id <= 0) {
        echo json_encode(['error' => 'Servicio inválido']);
        exit;
    }
    $reviews = $reviewModel->getByService($service_id);
    $stats = $reviewModel->getAverageByService($service_id);
    echo json_encode(['reviews' => $reviews, 'stats' => $stats]);
    exit;
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

if ($method === 'GET') {
    if ($action === 'list_by_provider') {
        requireRole('prestador');
        $reviews = $reviewModel->getByProvider($userId);
        $stats = $reviewModel->getAverageByProvider($userId);
        echo json_encode(['reviews' => $reviews, 'stats' => $stats]);
        exit;
    }
    if ($action === 'all') {
        requireRole('admin');
        $reviews = $reviewModel->getAll();
        echo json_encode(['reviews' => $reviews]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Acción no válida']);
    exit;
}

if ($method === 'POST') {
    if (!validateCsrfToken($_POST['_csrf'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF inválido']);
        exit;
    }

    if ($action === 'create') {
        requireRole('turista');
        $reservation_id = (int) ($_POST['reservation_id'] ?? 0);
        $service_id = (int) ($_POST['service_id'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($reservation_id <= 0 || $service_id <= 0) {
            echo json_encode(['error' => 'Datos inválidos']);
            exit;
        }

        if ($rating < 1 || $rating > 5) {
            echo json_encode(['error' => 'Calificación debe ser entre 1 y 5']);
            exit;
        }

        if (strlen($comment) > 1000) {
            echo json_encode(['error' => 'Comentario demasiado largo']);
            exit;
        }

        // Verify the reservation belongs to this user and is completed
        $pdo = getDBConnection();
        $stmt = executeQuery($pdo, "SELECT id, status FROM reservations WHERE id = ? AND tourist_id = ?", [$reservation_id, $userId]);
        $res = $stmt->fetch();
        if (!$res || $res['status'] !== 'completada') {
            echo json_encode(['error' => 'Solo puedes calificar reservas completadas']);
            exit;
        }

        if ($reviewModel->hasReviewed($userId, $reservation_id)) {
            echo json_encode(['error' => 'Ya calificaste esta reserva']);
            exit;
        }

        $reviewModel->create($service_id, $userId, $reservation_id, $rating, $comment);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        requireRole('admin');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }
        $reviewModel->delete($id);
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Acción no válida']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
