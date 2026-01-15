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
 * Redimensionar y subir imagen para slider (1920x560 con crop centrado)
 */
function resizeSliderImage($file, $directory = 'uploads/sliders/')
{
    // Dimensiones fijas para el slider
    $target_width = 1920;
    $target_height = 560;

    // Usar ruta absoluta desde la raíz del proyecto
    $base_path = dirname(dirname(__DIR__)); // Sube dos niveles desde panel/helpers/
    $target_dir = $base_path . '/' . $directory;

    // Crear directorio si no existe
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Verificar si es una imagen real
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'El archivo no es una imagen'];
    }

    // Verificar tamaño del archivo (5MB máximo)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande'];
    }

    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    // Permitir ciertos formatos
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return ['success' => false, 'message' => 'Solo se permiten archivos JPG, JPEG, PNG, GIF y WEBP'];
    }

    // Crear imagen desde el archivo temporal
    switch ($imageFileType) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($file["tmp_name"]);
            break;
        case 'png':
            $source = imagecreatefrompng($file["tmp_name"]);
            break;
        case 'gif':
            $source = imagecreatefromgif($file["tmp_name"]);
            break;
        case 'webp':
            $source = imagecreatefromwebp($file["tmp_name"]);
            break;
        default:
            return ['success' => false, 'message' => 'Formato de imagen no soportado'];
    }

    if (!$source) {
        return ['success' => false, 'message' => 'Error al procesar la imagen'];
    }

    // Obtener dimensiones originales
    $source_width = imagesx($source);
    $source_height = imagesy($source);

    // Calcular ratios
    $source_ratio = $source_width / $source_height;
    $target_ratio = $target_width / $target_height;

    // Crear imagen de destino
    $destination = imagecreatetruecolor($target_width, $target_height);

    // Calcular dimensiones y posición para crop centrado
    if ($source_ratio > $target_ratio) {
        // Imagen más ancha - ajustar a la altura y recortar los lados
        $temp_height = $target_height;
        $temp_width = (int)($target_height * $source_ratio);
        $src_x = (int)(($source_width - ($source_height * $target_ratio)) / 2);
        $src_y = 0;
        $src_width = (int)($source_height * $target_ratio);
        $src_height = $source_height;
    } else {
        // Imagen más alta - ajustar al ancho y recortar arriba/abajo
        $temp_width = $target_width;
        $temp_height = (int)($target_width / $source_ratio);
        $src_x = 0;
        $src_y = (int)(($source_height - ($source_width / $target_ratio)) / 2);
        $src_width = $source_width;
        $src_height = (int)($source_width / $target_ratio);
    }

    // Redimensionar con crop centrado
    imagecopyresampled(
        $destination,
        $source,
        0,
        0,
        $src_x,
        $src_y,
        $target_width,
        $target_height,
        $src_width,
        $src_height
    );

    // Generar nombre de archivo único
    $newFileName = uniqid() . '.jpg';
    $target_file = $target_dir . $newFileName;

    // Guardar la imagen redimensionada
    $saved = imagejpeg($destination, $target_file, 90);

    // Liberar memoria
    imagedestroy($source);
    imagedestroy($destination);

    if ($saved) {
        return ['success' => true, 'filename' => $newFileName];
    } else {
        return ['success' => false, 'message' => 'Error al guardar la imagen redimensionada'];
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
