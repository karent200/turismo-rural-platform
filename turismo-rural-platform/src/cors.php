<?php
/**
 * Cabeceras CORS y preflight para la API.
 */
require_once __DIR__ . '/config.php';

function setCorsHeaders(): void
{
    $allowed = array_filter(array_unique([
        rtrim(APP_URL, '/'),
        'http://localhost:8080',
        'http://localhost:4200',
        'http://localhost:8000',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:4200',
        'http://127.0.0.1:8000',
    ]));

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } elseif (APP_ENV === 'development' && $origin !== '' && preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        header('Access-Control-Allow-Origin: ' . rtrim(APP_URL, '/'));
    }

    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

function handleCorsPreflight(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        setCorsHeaders();
        http_response_code(204);
        exit;
    }
}
