<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// 1. Check Permissions
$stmtUser = $db->prepare("SELECT rol, permiso_dulceria FROM tbl_usuarios WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$user = $stmtUser->fetch();

$isSuper = ($user['rol'] === 'superadmin' || $user['rol'] === 'admin');
if (!$isSuper && !$user['permiso_dulceria']) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permiso para vender en Dulcería.']);
    exit;
}

// 2. Check Series Assignment
$stmtSerie = $db->prepare("SELECT * FROM tbl_series WHERE id_usuario = ? AND estado = '1' AND tipo = 'B' LIMIT 1"); // Defaulting to Boleta for now or we pass type
$stmtSerie->execute([$_SESSION['user_id']]);
$serie = $stmtSerie->fetch();

if (!$serie) {
    http_response_code(400);
    echo json_encode(['error' => 'No tiene una serie de BOLETA asignada. Contacte al administrador.']);
    exit;
}

// 3. Receive Data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['cart'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Carrito vacío']);
    exit;
}

$cart = $input['cart'];
$payments = $input['payments']; // [{method_id: 1, amount: 50}, ...]
$total = (float)$input['total'];
$cliente = $input['cliente'] ?? 'Público General';

try {
    $db->beginTransaction();

    // 4. Validate Inventory & Deduct Stock
    foreach ($cart as $item) {
        $prodId = $item['id'];
        $qty = $item['qty'];

        // Get Product Type
        $stmtP = $db->prepare("SELECT tipo, stock, nombre FROM tbl_productos WHERE id = ?");
        $stmtP->execute([$prodId]);
        $prod = $stmtP->fetch();

        if ($prod['tipo'] === 'combo') {
            // Deduct from Ingredients
            $stmtReceta = $db->prepare("SELECT id_producto_hijo, cantidad FROM tbl_recetas WHERE id_producto_padre = ?");
            $stmtReceta->execute([$prodId]);
            $ingredients = $stmtReceta->fetchAll();

            foreach ($ingredients as $ing) {
                $deductQty = $ing['cantidad'] * $qty;
                // Check Stock
                $stmtStock = $db->prepare("SELECT stock, nombre FROM tbl_productos WHERE id = ? FOR UPDATE");
                $stmtStock->execute([$ing['id_producto_hijo']]);
                $ingProd = $stmtStock->fetch();

                if ($ingProd['stock'] < $deductQty) {
                    throw new Exception("Stock insuficiente para insumo: " . $ingProd['nombre'] . " (Req: $deductQty, Disp: " . $ingProd['stock'] . ")");
                }

                // Update Stock
                $upd = $db->prepare("UPDATE tbl_productos SET stock = stock - ? WHERE id = ?");
                $upd->execute([$deductQty, $ing['id_producto_hijo']]);
            }
        } else {
            // Simple Product: Deduct directly
            // Check Stock
            $stmtStock = $db->prepare("SELECT stock FROM tbl_productos WHERE id = ? FOR UPDATE");
            $stmtStock->execute([$prodId]);
            $currentStart = $stmtStock->fetchColumn();

            if ($currentStart < $qty) {
                throw new Exception("Stock insuficiente para: " . $prod['nombre']);
            }

            $upd = $db->prepare("UPDATE tbl_productos SET stock = stock - ? WHERE id = ?");
            $upd->execute([$qty, $prodId]);
        }
    }

    // 5. Generate Correlativo
    $newCorrelativo = $serie['correlativo'] + 1;
    $serieNum = $serie['serie'];

    // 6. Insert Sale Header
    $stmtSale = $db->prepare("INSERT INTO tbl_ventas_dulceria (id_usuario, cliente_nombre, total, serie, correlativo) VALUES (?, ?, ?, ?, ?)");
    $stmtSale->execute([$_SESSION['user_id'], $cliente, $total, $serieNum, $newCorrelativo]);
    $ventaId = $db->lastInsertId();

    // 7. Insert Sale Details
    $stmtDet = $db->prepare("INSERT INTO tbl_ventas_dulceria_detalle (id_venta, id_producto, cantidad, precio_unitario, importe) VALUES (?, ?, ?, ?, ?)");
    foreach ($cart as $item) {
        $importe = $item['price'] * $item['qty'];
        $stmtDet->execute([$ventaId, $item['id'], $item['qty'], $item['price'], $importe]);
    }

    // 8. Insert Payments
    $stmtPay = $db->prepare("INSERT INTO tbl_ventas_pagos (id_venta, id_forma_pago, monto, referencia) VALUES (?, ?, ?, ?)");
    foreach ($payments as $pay) {
        // pay: { id: 1, amount: 50, ref: '123' }
        $stmtPay->execute([$ventaId, $pay['id'], $pay['amount'], $pay['ref'] ?? null]);
    }

    // 9. Update Series Correlativo
    $stmtUpdSerie = $db->prepare("UPDATE tbl_series SET correlativo = ? WHERE id = ?");
    $stmtUpdSerie->execute([$newCorrelativo, $serie['id']]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Venta registrada',
        'venta_id' => $ventaId,
        'ticket_number' => $serieNum . '-' . str_pad($newCorrelativo, 8, '0', STR_PAD_LEFT)
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
