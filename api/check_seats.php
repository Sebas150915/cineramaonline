<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/front_config.php';

header('Content-Type: application/json');

// Log request for debugging
error_log("check_seats.php called with id_funcion: " . ($_GET['id_funcion'] ?? 'null'));

$id_funcion = isset($_GET['id_funcion']) ? (int)$_GET['id_funcion'] : 0;

if ($id_funcion <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

try {
    // 1. Clean up old pending reservations (older than 10 mins)
    // Use try-catch specifically for cleanup to avoid blocking the main read
    try {
        // Fix for potential MySQL error 1093 (You can't specify target table 'x' for update in FROM clause)
        // Although here tables are different (delete from boletos using ventas), but let's be safe.
        // Actually the original query was deleting from boletos where id_venta in (select from ventas). This is usually fine in MySQL.
        
        $db->exec("DELETE FROM tbl_boletos WHERE id_venta IN (
            SELECT id FROM (SELECT id FROM tbl_ventas WHERE estado = 'PENDIENTE' AND created_at < (NOW() - INTERVAL 10 MINUTE)) as v_temp
        )");
        $db->exec("DELETE FROM tbl_ventas WHERE estado = 'PENDIENTE' AND created_at < (NOW() - INTERVAL 10 MINUTE)");
    } catch (PDOException $e) {
        // Log cleanup error but continue
        error_log("Cleanup error: " . $e->getMessage());
    }

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
