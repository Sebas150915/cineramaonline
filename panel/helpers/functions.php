<?php

/**
 * Funciones Helper - Cinerama Panel
 * Funciones auxiliares para uso general
 */

/**
 * Sanitizar entrada de datos
 */
function sanitize($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Redireccionar a una URL
 */
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

/**
 * Mostrar alerta con SweetAlert2
 */
function showAlert($type, $title, $message)
{
    $_SESSION['alert'] = [
        'type' => $type,
        'title' => $title,
        'message' => $message
    ];
}

/**
 * Verificar si el usuario está autenticado
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Formatear fecha
 */
function formatDate($date, $format = 'd/m/Y')
{
    return date($format, strtotime($date));
}

/**
 * Formatear hora
 */
function formatTime($time)
{
    return date('H:i', strtotime($time));
}

/**
 * Generar token CSRF
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Subir imagen
 */
function uploadImage($file, $directory = 'uploads/')
{
    // Usar ruta absoluta desde la raíz del proyecto
    $base_path = dirname(dirname(__DIR__)); // Sube dos niveles desde panel/helpers/
    $target_dir = $base_path . '/' . $directory;

    // Crear directorio si no existe
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $newFileName = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $newFileName;

    // Verificar si es una imagen real
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'El archivo no es una imagen'];
    }

    // Verificar tamaño del archivo (5MB máximo)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande'];
    }

    // Permitir ciertos formatos
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return ['success' => false, 'message' => 'Solo se permiten archivos JPG, JPEG, PNG, GIF y WEBP'];
    }

    // Intentar subir el archivo
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $newFileName];
    } else {
        return ['success' => false, 'message' => 'Error al subir el archivo. Verifica permisos de escritura.'];
    }
}

/**
 * Eliminar imagen
 */
function deleteImage($filename, $directory = 'uploads/')
{
    $base_path = dirname(dirname(__DIR__)); // Sube dos niveles desde panel/helpers/
    $file_path = $base_path . '/' . $directory . $filename;
    if (file_exists($file_path)) {
        unlink($file_path);
        return true;
    }
    return false;
}

/**
 * Generar slug
 */
function generateSlug($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text;
}

/**
 * Validar email
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Debug - Solo para desarrollo
 */
function dd($data)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}
