<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cors.php';
handleCorsPreflight();
setCorsHeaders();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

initSecureSession();
requireRole('admin');

require_once __DIR__ . '/../connectdb.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Reservation.php';

$pdo = getDBConnection();
$userModel = new User();
$serviceModel = new Service();
$reservationModel = new Reservation();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'users':
            echo json_encode($userModel->getAll());
            break;

        case 'stats':
            $users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $services = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
            $reservations = (int) $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
            $pending = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pendiente'")->fetchColumn();
            $prestadores = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'prestador'")->fetchColumn();
            $turistas = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'turista'")->fetchColumn();
            echo json_encode([
                'users' => $users,
                'services' => $services,
                'reservations' => $reservations,
                'pending' => $pending,
                'prestadores' => $prestadores,
                'turistas' => $turistas,
            ]);
            break;

        case 'reservations':
            echo json_encode($reservationModel->getAll());
            break;

        case 'services':
            echo json_encode($serviceModel->getAll());
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!validateCsrfToken($_POST['_csrf'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF inválido']);
        exit;
    }

    switch ($action) {
        case 'update_user':
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim(strtolower($_POST['email'] ?? ''));
            $role = $_POST['role'] ?? '';

            if (!$id || !$name || !$email || !in_array($role, ['admin', 'prestador', 'turista'], true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                exit;
            }
            if (strlen($name) > 100 || strlen($email) > 255) {
                http_response_code(400);
                echo json_encode(['error' => 'Campos demasiado largos']);
                exit;
            }

            $stmt = executeQuery($pdo, "SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'El email ya está en uso']);
                exit;
            }

            executeQuery($pdo, "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?", [$name, $email, $role, $id]);
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado']);
            exit;

        case 'delete_user':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id || $id === (int) $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'No puedes eliminarte a ti mismo']);
                exit;
            }
            $userModel->delete($id);
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado']);
            exit;

        case 'delete_reservation':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID inválido']);
                exit;
            }
            executeQuery($pdo, "DELETE FROM reservations WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => 'Reserva eliminada']);
            exit;

        case 'update_reservation_status':
            $id = (int) ($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!$id || !in_array($status, ['pendiente', 'confirmada', 'completada', 'cancelada'], true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                exit;
            }
            $reservationModel->updateStatus($id, $status);
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            exit;

        case 'update_service':
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? '';
            $description = trim($_POST['description'] ?? '');
            $capacity = (int) ($_POST['capacity'] ?? 1);
            $price = floatval($_POST['price'] ?? 0);
            $location = trim($_POST['location'] ?? '');

            if (!$id || !$name || !in_array($type, ['alojamiento','recreacion','gastronomia','actividades',''], true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                exit;
            }

            executeQuery($pdo, "UPDATE services SET name=?, type=?, description=?, capacity=?, price=?, location=? WHERE id=?", [$name, $type, $description, $capacity, $price, $location, $id]);
            echo json_encode(['success' => true, 'message' => 'Servicio actualizado']);
            exit;

        case 'delete_service':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID inválido']);
                exit;
            }
            executeQuery($pdo, "DELETE FROM availability WHERE service_id = ?", [$id]);
            executeQuery($pdo, "DELETE FROM reservations WHERE service_id = ?", [$id]);
            executeQuery($pdo, "DELETE FROM services WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => 'Servicio eliminado']);
            exit;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
