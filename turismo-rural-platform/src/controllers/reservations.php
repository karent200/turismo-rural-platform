<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cors.php';
handleCorsPreflight();
setCorsHeaders();

header('Content-Type: application/json; charset=utf-8');

initSecureSession();

require_once __DIR__ . '/../connectdb.php';
require_once __DIR__ . '/../models/Reservation.php';

$reservationModel = new Reservation();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$role = $_SESSION['user_role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

if ($method === 'GET') {
    if ($action === 'my_reservations' || $action === '') {
        $status = $_GET['status'] ?? null;
        echo json_encode($reservationModel->getByUser($userId, $status));
    } elseif ($action === 'all') {
        requireRole('admin');
        echo json_encode($reservationModel->getAll());
    } elseif ($action === 'provider') {
        requireRole('prestador');
        $pdo = getDBConnection();
        $stmt = $pdo->query(
            "SELECT r.*, s.name as service_name, s.provider_id, s.price as service_price, u.name as tourist_name, u.email as tourist_email, pp.business_name, u2.name as provider_name
             FROM reservations r
             JOIN services s ON r.service_id = s.id
             JOIN users u ON r.tourist_id = u.id
             JOIN users u2 ON s.provider_id = u2.id
             LEFT JOIN provider_profiles pp ON s.provider_id = pp.user_id
             ORDER BY r.reservation_date DESC"
        );
        echo json_encode($stmt->fetchAll());
    } elseif ($action === 'pending') {
        requireRole('admin', 'prestador');
        $pdo = getDBConnection();
        $stmt = $pdo->query(
            "SELECT r.*, s.name as service_name, s.provider_id, s.price as service_price, u.name as tourist_name, u.email as tourist_email, pp.business_name, u2.name as provider_name
             FROM reservations r
             JOIN services s ON r.service_id = s.id
             JOIN users u ON r.tourist_id = u.id
             JOIN users u2 ON s.provider_id = u2.id
             LEFT JOIN provider_profiles pp ON s.provider_id = pp.user_id
             WHERE r.status IN ('pendiente', 'confirmada')
             ORDER BY r.reservation_date ASC"
        );
        echo json_encode($stmt->fetchAll());
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
    }
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

        $service_id = (int) ($_POST['service_id'] ?? 0);
        $date = $_POST['date'] ?? '';
        $personas = (int) ($_POST['personas'] ?? 1);
        $telefono = trim($_POST['telefono'] ?? '');

        if ($service_id <= 0) {
            echo json_encode(['error' => 'Servicio inválido']);
            exit;
        }

        if (empty($date)) {
            echo json_encode(['error' => 'Selecciona una fecha']);
            exit;
        }

        if ($date < date('Y-m-d')) {
            echo json_encode(['error' => 'No se puede reservar en fechas pasadas']);
            exit;
        }

        if ($personas < 1 || $personas > 50) {
            echo json_encode(['error' => 'Número de personas inválido']);
            exit;
        }

        if (strlen($telefono) < 7) {
            echo json_encode(['error' => 'Teléfono inválido']);
            exit;
        }

        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT capacity FROM services WHERE id = ?');
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();

        if (!$service) {
            echo json_encode(['error' => 'Servicio no encontrado']);
            exit;
        }

        $existStmt = executeQuery($pdo, "SELECT COALESCE(SUM(personas), 0) as total FROM reservations WHERE service_id = ? AND reservation_date = ? AND status IN ('pendiente', 'confirmada')", [$service_id, $date]);
        $existing = (int) $existStmt->fetch()['total'];

        $availStmt = executeQuery($pdo, "SELECT slots_available FROM availability WHERE service_id = ? AND date = ?", [$service_id, $date]);
        $avail = $availStmt->fetch();

        $limite = (int) $service['capacity'];
        if ($avail) {
            $limite = min($limite, (int) $avail['slots_available']);
        }

        $disponible = $limite - $existing;
        if ($disponible < $personas) {
            echo json_encode(['error' => "No hay suficientes cupos para esa fecha. Disponibles: $disponible de $limite"]);
            exit;
        }

        $id = $reservationModel->create($userId, $service_id, $date, $personas, $telefono);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    if ($action === 'update_status' && isset($_POST['id'], $_POST['status'])) {
        requireRole('admin', 'prestador');
        $reservationId = (int) $_POST['id'];
        $status = $_POST['status'];

        if ($reservationId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        if (!in_array($status, ['pendiente', 'confirmada', 'completada', 'cancelada'], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Estado inválido']);
            exit;
        }

        if ($role === 'prestador' && !$reservationModel->providerOwnsReservation($reservationId, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'No puedes modificar esta reserva']);
            exit;
        }

        $reservationModel->updateStatus($reservationId, $status);
        if ($status === 'cancelada') {
            $r = $reservationModel->findById($reservationId);
            if ($r) {
                $pdo2 = getDBConnection();
                executeQuery($pdo2, "UPDATE availability SET slots_available = slots_available + ? WHERE service_id = ? AND date = ?", [(int) $r['personas'], $r['service_id'], $r['reservation_date']]);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'cancel' && isset($_POST['id'])) {
        $reservationId = (int) $_POST['id'];

        if ($reservationId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        if ($role === 'turista' && !$reservationModel->userOwnsReservation($reservationId, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'No puedes cancelar esta reserva']);
            exit;
        }

        requireRole('turista', 'admin');

        $reservationModel->updateStatus($reservationId, 'cancelada');
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Acción no válida']);
