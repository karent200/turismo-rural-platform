<?php
require_once __DIR__ . '/../cors.php';
handleCorsPreflight();
setCorsHeaders();

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../connectdb.php';
require_once __DIR__ . '/../models/User.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'forgot') {
        $email = trim(strtolower($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email inválido']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => true, 'message' => 'Si el correo existe, recibirás instrucciones.']);
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $stmt = $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$email, $token, $expires]);

        $resetLink = rtrim((defined('APP_URL') ? APP_URL : 'http://localhost:8080'), '/') . '/reset-password.html?token=' . $token;

        echo json_encode([
            'success' => true,
            'message' => 'Revisa tu correo para restablecer la contraseña.',
            'reset_link' => $resetLink,
            'token' => $token,
        ]);
        exit;
    }

    if ($action === 'reset') {
        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';

        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            http_response_code(400);
            echo json_encode(['error' => 'La contraseña debe tener 8+ caracteres, mayúscula, número y especial']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT email FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()');
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(400);
            echo json_encode(['error' => 'Token inválido o expirado']);
            exit;
        }

        $email = $row['email'];
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $pdo->prepare('UPDATE users SET password = ? WHERE email = ?')->execute([$hashed, $email]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')->execute([$token]);

        echo json_encode(['success' => true, 'message' => 'Contraseña actualizada. Inicia sesión.']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
