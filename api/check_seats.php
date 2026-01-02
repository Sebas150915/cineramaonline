<?php
require_once '../includes/front_config.php';

header('Content-Type: application/json');

$id_funcion = isset($_GET['id_funcion']) ? (int)$_GET['id_funcion'] : 0;

if ($id_funcion <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

try {
    // 1. Clean up old pending reservations (older than 10 mins)
    // MySQL TIMESTAMPDIFF: TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 10
    $db->exec("DELETE FROM tbl_boletos WHERE id_venta IN (
        SELECT id FROM tbl_ventas WHERE estado = 'PENDIENTE' AND created_at < (NOW() - INTERVAL 10 MINUTE)
    )");
    $db->exec("DELETE FROM tbl_ventas WHERE estado = 'PENDIENTE' AND created_at < (NOW() - INTERVAL 10 MINUTE)");

    // 2. Fetch occupied seats (PAGADO or PENDIENTE)
    $stmt = $db->prepare("
        SELECT b.id_asiento, v.estado 
        FROM tbl_boletos b
        JOIN tbl_ventas v ON b.id_venta = v.id
        WHERE v.id_funcion = ? 
        AND v.estado IN ('PAGADO', 'PENDIENTE')
        AND b.estado = 'ACTIVO'
    ");
    $stmt->execute([$id_funcion]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
