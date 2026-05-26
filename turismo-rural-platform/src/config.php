<?php
// config.php - Configuración de la aplicación

/**
 * Carga variables de entorno desde .env
 * Nunca expongas valores reales en el código
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("Archivo .env no encontrado en: {$path}");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios y líneas vacías
        if (trim($line) === '' || strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parsear KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Solo establecer si no existe ya (prioridad a variables del sistema)
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Cargar .env
loadEnv(__DIR__ . '/../.env');

// Definir constantes de base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'mysql');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'turismo_db');
define('DB_USER', getenv('DB_USER') ?: 'turismo_user');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');

// Configuración de la aplicación
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('SESSION_SECRET', getenv('SESSION_SECRET') ?: 'CHANGE_ME_IN_PRODUCTION');

// Desactivar display_errors en producción
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
?>
