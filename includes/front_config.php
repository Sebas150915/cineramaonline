<?php
// Configuración para el Frontend
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Lima');

// Rutas base
define('BASE_PATH', __DIR__ . '/../');
define('BASE_URL', 'http://localhost/cinerama/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

// Incluir configuración de base de datos del Panel
require_once BASE_PATH . 'panel/config/database.php';
require_once BASE_PATH . 'panel/helpers/functions.php';

$database = new Database();
$db = $database->getConnection();
