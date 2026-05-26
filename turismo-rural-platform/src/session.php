<?php
require_once __DIR__ . '/config.php';

const SESSION_LIFETIME = 7200;
const IDLE_TIMEOUT = 1800;
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW = 900;

function initSecureSession(): void {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => $cookieParams['path'],
        'domain'   => $cookieParams['domain'] ?? '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['_init_time'])) {
        $_SESSION['_init_time'] = time();
    }

    if (!empty($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > IDLE_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }

    $_SESSION['_last_activity'] = time();
}

function getCsrfToken(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

function checkRateLimit(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = '_login_attempts_' . str_replace('.', '_', $ip);

    $attempts = $_SESSION[$key] ?? ['count' => 0, 'first' => time()];

    if (time() - $attempts['first'] > LOGIN_WINDOW) {
        $attempts = ['count' => 0, 'first' => time()];
    }

    $attempts['count']++;
    $_SESSION[$key] = $attempts;

    if ($attempts['count'] > LOGIN_MAX_ATTEMPTS) {
        http_response_code(429);
        echo json_encode(['error' => 'Demasiados intentos. Intenta en 15 minutos.']);
        exit;
    }
}

function resetRateLimit(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = '_login_attempts_' . str_replace('.', '_', $ip);
    unset($_SESSION[$key]);
}

function requireRole(string ...$roles): void {
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
}
