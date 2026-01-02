<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    // Only fetch products marked as 'es_vendible' = 1
    // Filter out inactive
    $sql = "SELECT id, nombre, precio_venta, tipo, stock, unidad_medida, codigo_barras 
            FROM tbl_productos 
            WHERE es_vendible = 1 AND estado = '1' 
            ORDER BY nombre ASC";

    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format numbers
    foreach ($products as &$p) {
        $p['precio_venta'] = (float)$p['precio_venta'];
        // For combos, stock is virtual (calculated from ingredients), but frontend doesn't strictly need it for display if we handle check at checkout.
        // Or we could calculate max possible combos. For now, let's just return what we have.
    }

    echo json_encode($products);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
