<?php
define('IS_POS', true);
require_once '../panel/config/config.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$id_funcion = $_POST['id_funcion'] ?? 0;
$asientos = $_POST['asientos'] ?? []; // Array of [id, nombre, id_tarifa]
$cliente_nombre = strtoupper($_POST['cliente_nombre'] ?? 'CLIENTE GENERICO');
$cliente_doc = $_POST['cliente_doc'] ?? '';
$tipo_comprobante = $_POST['tipo_comprobante'] ?? 'BOLETA';
$medio_pago = $_POST['medio_pago'] ?? 'EFECTIVO';

if (!$id_funcion || empty($asientos)) {
    die("Error: Faltan datos.");
}

try {
    $db->beginTransaction();

    // 0. Verify availability (Race condition check)
    $asientos_ids = array_column($asientos, 'id');
    if (empty($asientos_ids)) {
        throw new Exception("No hay asientos seleccionados");
    }

    $placeholders = implode(',', array_fill(0, count($asientos_ids), '?'));
    $params = $asientos_ids;
    $params[] = $id_funcion;

    $stmtCheck = $db->prepare("
        SELECT Count(*) 
        FROM tbl_boletos b
        JOIN tbl_ventas v ON b.id_venta = v.id
        WHERE b.id_asiento IN ($placeholders)
        AND v.id_funcion = ?
        AND v.estado IN ('PAGADO', 'PENDIENTE', 'COMPLETADO')
        AND b.estado = 'ACTIVO'
    ");
    $stmtCheck->execute($params);
    $count = $stmtCheck->fetchColumn();

    if ($count > 0) {
        $db->rollBack();
        die("Error: Uno o más asientos ya han sido vendidos a otro cliente. Por favor intente nuevamente.");
    }

    // 1. Calculate Total and Verify
    $total = 0;
    $boletos_data = [];

    foreach ($asientos as $a) {
        $id_asiento = $a['id'];
        $id_tarifa = $a['id_tarifa'];

        // Get Price
        $stmt = $db->prepare("SELECT precio FROM tbl_tarifa WHERE id = ?");
        $stmt->execute([$id_tarifa]);
        $tarifa = $stmt->fetch();
        $precio = $tarifa['precio'] ?? 0;
        $total += $precio;

        // Get Seat Details
        $stmtS = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE id = ?");
        $stmtS->execute([$id_asiento]);
        $seat = $stmtS->fetch();

        $boletos_data[] = [
            'id_asiento' => $id_asiento,
            'fila' => $seat['fila'],
            'columna' => $seat['columna'], // Assuming match
            'letra' => $seat['fila'], // redundancy?
            'numero' => $seat['num_asiento'],
            'id_tarifa' => $id_tarifa,
            'precio' => $precio
        ];

        // Double check availability (Race condition check)
        // ... (Skipping for MVP speed, but should exist)
    }

    // 2. Insert Sale
    $codigo = 'POS-' . time() . '-' . rand(100, 999);
    $stmtVenta = $db->prepare("
        INSERT INTO tbl_ventas (codigo, id_funcion, cliente_nombre, cliente_doc, total, tipo_comprobante, medio_pago, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'PAGADO')
    ");
    $stmtVenta->execute([$codigo, $id_funcion, $cliente_nombre, $cliente_doc, $total, $tipo_comprobante, $medio_pago]);
    $id_venta = $db->lastInsertId();

    // 3. Insert Tickets
    $stmtBoleto = $db->prepare("
        INSERT INTO tbl_boletos (id_venta, id_asiento, fila, columna, letra, numero, id_tarifa, precio, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO')
    ");

    foreach ($boletos_data as $b) {
        $stmtBoleto->execute([
            $id_venta,
            $b['id_asiento'],
            $b['fila'],
            $b['columna'],
            $b['letra'],
            $b['numero'],
            $b['id_tarifa'],
            $b['precio']
        ]);
    }

    $db->commit();

    // Redirect to Ticket
    header("Location: ticket.php?id=" . $id_venta);
    exit;
} catch (Exception $e) {
    $db->rollBack();
    die("Error al procesar venta: " . $e->getMessage());
}
