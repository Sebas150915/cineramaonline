<?php
// Script de prueba para verificar permisos de subida de imágenes
echo "<h2>Test de Subida de Imágenes - Cinerama</h2>";

// Verificar rutas
$base_path = dirname(__DIR__);
$uploads_dir = $base_path . '/uploads/cines/';

echo "<h3>Información de Rutas:</h3>";
echo "<p><strong>Base Path:</strong> " . $base_path . "</p>";
echo "<p><strong>Uploads Dir:</strong> " . $uploads_dir . "</p>";

// Verificar si existe el directorio
if (file_exists($uploads_dir)) {
    echo "<p style='color: green;'>✓ El directorio existe</p>";

    // Verificar permisos
    if (is_writable($uploads_dir)) {
        echo "<p style='color: green;'>✓ El directorio tiene permisos de escritura</p>";
    } else {
        echo "<p style='color: red;'>✗ El directorio NO tiene permisos de escritura</p>";
        echo "<p>Ejecuta: <code>chmod 777 " . $uploads_dir . "</code></p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ El directorio no existe, se creará automáticamente al subir una imagen</p>";
}

// Información de PHP
echo "<h3>Configuración PHP:</h3>";
echo "<p><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</p>";

// Test de escritura
$test_file = $uploads_dir . 'test_' . time() . '.txt';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

if (file_put_contents($test_file, 'Test de escritura')) {
    echo "<p style='color: green;'>✓ Test de escritura exitoso</p>";
    unlink($test_file); // Eliminar archivo de prueba
} else {
    echo "<p style='color: red;'>✗ Error al escribir archivo de prueba</p>";
}

echo "<hr>";
echo "<p><a href='modules/cines/create.php'>Ir a Crear Cine</a></p>";
