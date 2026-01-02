<?php

/**
 * Configuración General - Cinerama Panel
 */

// Rutas base
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost/cineramaonline/panel/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', 'http://localhost/cineramaonline/uploads/');

// Configuración de errores (Producción)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/error.log');

// Configuración de Sesión Segura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_same_site', 'Lax');
    session_start();
}

// Cabeceras de Seguridad HTTP
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Configuración de la aplicación
define('APP_NAME', 'Cinerama Admin');
define('APP_VERSION', '1.0.0');

// Incluir archivos necesarios
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/helpers/functions.php';

// Crear instancia de base de datos
$database = new Database();
$db = $database->getConnection();

// Verificar conexión
if (!$db) {
    die("Error: No se pudo conectar a la base de datos");
}

// Authentication Middleware
if (file_exists(BASE_PATH . '/includes/auth.php')) {
    require_once BASE_PATH . '/includes/auth.php';
}
