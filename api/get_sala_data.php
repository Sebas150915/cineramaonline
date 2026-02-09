<?php
// Disable error reporting for production API to prevent JSON corruption
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL); // Log errors instead

header('Content-Type: application/json');

// Handle CORS if needed (optional, good for dev)
header("Access-Control-Allow-Origin: *");

require_once '../includes/front_config.php';

$id_funcion = isset($_GET['id_funcion']) ? (int)$_GET['id_funcion'] : 0;

if ($id_funcion <= 0) {
    echo json_encode(['error' => 'ID de función inválido']);
    exit;
}

try {
    // 1. Get Function & Room Info
    $stmt = $db->prepare("
        SELECT f.id, h.hora as hora_inicio, p.nombre as pelicula, s.nombre as sala, 
               s.filas, s.columnas, s.id as id_sala, l.nombre as cine
        FROM tbl_funciones f
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_locales l ON s.local = l.id
        JOIN tbl_hora h ON f.id_hora = h.id
        WHERE f.id = ? AND f.estado = '1'
    ");
    $stmt->execute([$id_funcion]);
    $funcion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$funcion) {
        echo json_encode(['error' => 'Función no encontrada o inactiva']);
        exit;
    }

    // 2. Get All Seats (Layout)
    $stmtAsientos = $db->prepare("SELECT id, fila, columna, num_asiento, tipo FROM tbl_sala_asiento WHERE idsala = ? ORDER BY fila, columna");
    $stmtAsientos->execute([$funcion['id_sala']]);
    $asientos = $stmtAsientos->fetchAll(PDO::FETCH_ASSOC);

    // 3. Clean up expired pending reservations
    try {
        $db->exec("DELETE FROM tbl_boletos WHERE id_venta IN (
            SELECT id FROM (SELECT id FROM tbl_ventas WHERE estado = 'PENDIENTE' AND created_at < (NOW() - INTERVAL 10 MINUTE)) as v_temp
        )");
        $db->exec("DELETE FROM tbl_ventas WHERE estado = 'PENDIENTE' AND created_at < (NOW() - INTERVAL 10 MINUTE)");
    } catch (Exception $e) {
        // Continue even if cleanup fails
        error_log("Cleanup warning: " . $e->getMessage());
    }

    // 4. Get Occupied Seats
    $stmtOcupados = $db->prepare("
        SELECT b.id_asiento 
        FROM tbl_boletos b
        JOIN tbl_ventas v ON b.id_venta = v.id
        WHERE v.id_funcion = ? 
        AND v.estado IN ('PAGADO', 'PENDIENTE')
        AND b.estado = 'ACTIVO'
    ");
    $stmtOcupados->execute([$id_funcion]);
    $ocupadosIds = $stmtOcupados->fetchAll(PDO::FETCH_COLUMN);

    // 5. Build Response
    $response = [
        'info' => [
            'pelicula' => $funcion['pelicula'],
            'cine' => $funcion['cine'],
            'sala' => $funcion['sala'],
            'hora' => $funcion['hora_inicio'],
            'cols' => (int)$funcion['columnas']
        ],
        'layout' => $asientos,
        'occupied' => $ocupadosIds
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}