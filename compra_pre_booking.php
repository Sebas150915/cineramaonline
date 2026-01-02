<?php
require_once 'includes/front_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_funcion = (int)$_POST['id_funcion'];
$seats_str = $_POST['selected_seats'];
$seats_ids = explode(',', $seats_str);

if (empty($seats_ids)) {
    header('Location: compra_asientos.php?id_funcion=' . $id_funcion);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Verify availability AGAIN (Race condition check)
    $placeholders = implode(',', array_fill(0, count($seats_ids), '?'));
    $params = $seats_ids;
    $params[] = $id_funcion; // Add ID funcion at the end

    // Check if any of these seats are already sold/pending for this function
    $stmtCheck = $db->prepare("
        SELECT Count(*) 
        FROM tbl_boletos b
        JOIN tbl_ventas v ON b.id_venta = v.id
        WHERE b.id_asiento IN ($placeholders)
        AND v.id_funcion = ?
        AND v.estado IN ('PAGADO', 'PENDIENTE')
        AND b.estado = 'ACTIVO'
    ");
    $stmtCheck->execute($params);
    $count = $stmtCheck->fetchColumn();

    if ($count > 0) {
        $db->rollBack();
        echo "<script>alert('Uno o más asientos acaban de ser ocupados. Por favor selecciona otros.'); window.location.href='compra_asientos.php?id_funcion=$id_funcion';</script>";
        exit;
    }

    // 2. Create Pending Transaction
    $codigo = 'PEND-' . time() . '-' . rand(100, 999);
    $stmtVenta = $db->prepare("INSERT INTO tbl_ventas (codigo, id_funcion, total, estado) VALUES (?, ?, 0, 'PENDIENTE')");
    $stmtVenta->execute([$codigo, $id_funcion]);
    $id_venta = $db->lastInsertId();

    // 3. Create Pending Seats (Lock them)
    foreach ($seats_ids as $sid) {
        // Get visual data for the seat record
        $stmtS = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE id = ?");
        $stmtS->execute([$sid]);
        $sData = $stmtS->fetch();

        $stmtB = $db->prepare("INSERT INTO tbl_boletos (id_venta, id_asiento, fila, columna, numero, precio, estado) VALUES (?, ?, ?, ?, ?, 0, 'ACTIVO')");
        // Note: id_tarifa is NULL initially, will be updated in payment
        $stmtB->execute([$id_venta, $sid, $sData['fila'], $sData['columna'], $sData['num_asiento']]);
    }

    $db->commit();

    // 4. Redirect to Payment (with Venta ID)
    header("Location: compra_pago.php?id_venta=$id_venta");
    exit;
} catch (PDOException $e) {
    $db->rollBack();
    die("Error: " . $e->getMessage());
}
