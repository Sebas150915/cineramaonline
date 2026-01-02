<?php
define('IS_POS', true);
require_once '../../panel/config/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

try {
    // Only fetch products marked as 'es_vendible' = 1
    // Filter out inactive
    $sql = "SELECT id, nombre, precio_venta, tipo, stock, unidad_medida, codigo_barras, imagen 
            FROM tbl_productos 
            WHERE es_vendible = 1 AND estado = '1' 
            ORDER BY nombre ASC";

    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format numbers
    foreach ($products as &$p) {
        $p['precio_venta'] = (float)$p['precio_venta'];
    }

    echo json_encode($products);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
