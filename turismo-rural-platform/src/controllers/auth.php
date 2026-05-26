<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../cors.php';
handleCorsPreflight();
setCorsHeaders();

header('Content-Type: application/json; charset=utf-8');

initSecureSession();

require_once __DIR__ . '/../connectdb.php';
require_once __DIR__ . '/../models/User.php';

$userModel = new User();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_SESSION['user_id'])) {
        $u = $userModel->findById((int) $_SESSION['user_id']);
        echo json_encode([
            'authenticated' => true,
            'csrf_token' => getCsrfToken(),
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'] ?? '',
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['user_role'] ?? '',
                'telefono' => $u ? ($u['telefono'] ?? '') : '',
                'created_at' => $u ? ($u['created_at'] ?? '') : '',
            ],
        ]);
    } else {
        echo json_encode(['authenticated' => false, 'csrf_token' => getCsrfToken()]);
    }
    exit;
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = $userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            resetRateLimit();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'telefono' => $user['telefono'] ?? '',
                    'created_at' => $user['created_at'] ?? '',
                ],
            ]);
        } else {
            checkRateLimit();
            http_response_code(401);
            echo json_encode(['error' => 'Credenciales inválidas']);
        }
        exit;
    }

    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'turista';
        $telefono = $_POST['telefono'] ?? '';

        checkRateLimit();

        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos obligatorios']);
            exit;
        }

        if (strlen($name) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre demasiado largo']);
            exit;
        }

        if (strlen($email) > 255) {
            http_response_code(400);
            echo json_encode(['error' => 'Email demasiado largo']);
            exit;
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'La contraseña debe tener al menos 8 caracteres']);
            exit;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            http_response_code(400);
            echo json_encode(['error' => 'La contraseña debe contener al menos una mayúscula']);
            exit;
        }

        if (!preg_match('/[0-9]/', $password)) {
            http_response_code(400);
            echo json_encode(['error' => 'La contraseña debe contener al menos un número']);
            exit;
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            http_response_code(400);
            echo json_encode(['error' => 'La contraseña debe contener al menos un carácter especial']);
            exit;
        }

        if (!in_array($role, ['turista', 'prestador'], true)) {
            $role = 'turista';
        }

        if ($userModel->emailExists($email)) {
            http_response_code(400);
            echo json_encode(['error' => 'El correo ya está registrado']);
            exit;
        }

        $userModel->create($name, $email, $password, $role, $telefono);
        resetRateLimit();
        echo json_encode(['success' => true, 'message' => 'Usuario registrado']);
        exit;
    }

    if ($action === 'update_profile') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $telefono = trim($_POST['telefono'] ?? '');
        if (empty($name) || empty($email)) {
            echo json_encode(['error' => 'Nombre y email requeridos']);
            exit;
        }
        $current = $userModel->findById((int) $_SESSION['user_id']);
        if ($email !== $current['email'] && $userModel->emailExists($email)) {
            echo json_encode(['error' => 'El email ya está en uso']);
            exit;
        }
        $userModel->update((int) $_SESSION['user_id'], $name, $email, $telefono);
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'change_password') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            exit;
        }
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $user = $userModel->findByEmail($_SESSION['user_email']);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            echo json_encode(['error' => 'Contraseña actual incorrecta']);
            exit;
        }
        if (strlen($newPassword) < 8) {
            echo json_encode(['error' => 'La nueva contraseña debe tener al menos 8 caracteres']);
            exit;
        }
        $userModel->changePassword((int) $_SESSION['user_id'], $newPassword);
        echo json_encode(['success' => true, 'message' => 'Contraseña actualizada']);
        exit;
    }

    if ($action === 'logout') {
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
