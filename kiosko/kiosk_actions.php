<?php
require_once '../panel/config/config.php';

// Disable default HTML error display for JSON response
ini_set('display_errors', 0);
header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'pre_booking') {
    $id_funcion = (int)$_POST['id_funcion'];
    $seats_str = $_POST['seats'];
    $seats_ids = explode(',', $seats_str);

    if (empty($id_funcion) || empty($seats_ids)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Verificar disponibilidad (Race condition)
        $placeholders = implode(',', array_fill(0, count($seats_ids), '?'));
        // Params for checking: IDs of seats... + id_funcion
        $params = $seats_ids;
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
            echo json_encode(['success' => false, 'message' => 'Uno o más asientos ya fueron ocupados.']);
            exit;
        }

        // 2. Crear Venta PENDIENTE
        $codigo = 'KIOSK-' . time() . '-' . rand(100, 999);
        $stmtVenta = $db->prepare("INSERT INTO tbl_ventas (codigo, id_funcion, total, estado) VALUES (?, ?, 0, 'PENDIENTE')");
        $stmtVenta->execute([$codigo, $id_funcion]);
        $id_venta = $db->lastInsertId();

        // 3. Crear Boletos
        foreach ($seats_ids as $sid) {
            // Get data for layout
            $stmtS = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE id = ?");
            $stmtS->execute([$sid]);
            $sData = $stmtS->fetch();

            if (!$sData) continue;

            $stmtB = $db->prepare("
                INSERT INTO tbl_boletos (id_venta, id_asiento, fila, columna, numero, precio, estado) 
                VALUES (?, ?, ?, ?, ?, 0, 'ACTIVO')
            ");
            // num_asiento is mapped to 'numero' in boletos based on legacy logic (compra_pre_booking.php)
            $stmtB->execute([$id_venta, $sid, $sData['fila'], $sData['columna'], $sData['num_asiento']]);
        }

        $db->commit();
        echo json_encode(['success' => true, 'id_venta' => $id_venta]);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error DB: ' . $e->getMessage()]);
    }
    exit;
}


if ($action === 'pagar') {
    $id_venta = (int)$_POST['id_venta'];
    $metodo = isset($_POST['metodo']) ? $_POST['metodo'] : 'EFECTIVO';

    if (!$id_venta) {
        echo json_encode(['success' => false, 'message' => 'ID Venta Inválido']);
        exit;
    }

    try {
        // Actualizar estado de venta y boletos
        // En un sistema real, aquí iría la integración con pasarela (Niubiz/Izipay)

        $stmtUpdated = $db->prepare("
            UPDATE tbl_ventas 
            SET estado = 'PAGADO', medio_pago = ?, tipo_comprobante = 'BOLETA' 
            WHERE id = ?
        ");
        $stmtUpdated->execute([$metodo, $id_venta]);

        // Si quisieramos re-confirmar boletos:
        // $db->prepare("UPDATE tbl_boletos SET estado = 'ACTIVO' WHERE id_venta = ?")->execute([$id_venta]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error DB: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
