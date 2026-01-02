<?php
define('IS_POS', true);
require_once '../../panel/config/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

// Use POS User ID
$userId = $_SESSION['pos_user_id'];

// 1. Check Permissions (Already handled by auth_check, but double check Dulceria specific?)
// In POS, if we are here, we might want to check global permission or role again?
// Simplified: If you accessed this file, we assume auth_check passed. 
// However, let's verify explicit permission just in case.
$stmtUser = $db->prepare("SELECT rol, permiso_dulceria FROM tbl_usuarios WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

if (!$user['permiso_dulceria']) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permiso para vender en Dulcería.']);
    exit;
}

// 2. Check Series Assignment (Moved inside transaction for locking)
// We will validate existence inside the transaction to ensure atomicity.


// 3. Receive Data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['cart'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Carrito vacío']);
    exit;
}

$cart = $input['cart'];
$payments = $input['payments'];
$total = (float)$input['total'];
$cliente = $input['cliente'] ?? 'Público General';

try {
    $db->beginTransaction();

    // 2. Check Series (with Lock)
    $stmtSerie = $db->prepare("SELECT * FROM tbl_series WHERE id_usuario = ? AND estado = '1' AND tipo = 'B' LIMIT 1 FOR UPDATE");
    $stmtSerie->execute([$userId]);
    $serie = $stmtSerie->fetch();

    if (!$serie) {
        throw new Exception("No tiene una serie de asignada (bloqueo fallido).");
    }

    $productCache = [];

    // 4. Validate Inventory & Deduct Stock
    foreach ($cart as $item) {
        $prodId = $item['id'];
        $qty = $item['qty'];

        $stmtP = $db->prepare("SELECT tipo, stock, nombre FROM tbl_productos WHERE id = ?");
        $stmtP->execute([$prodId]);
        $prod = $stmtP->fetch();

        // Cache for later use
        $productCache[$prodId] = $prod;

        if ($prod['tipo'] === 'combo') {
            $stmtReceta = $db->prepare("SELECT id_producto_hijo, cantidad FROM tbl_recetas WHERE id_producto_padre = ?");
            $stmtReceta->execute([$prodId]);
            $ingredients = $stmtReceta->fetchAll();

            foreach ($ingredients as $ing) {
                $deductQty = $ing['cantidad'] * $qty;
                $stmtStock = $db->prepare("SELECT stock, nombre FROM tbl_productos WHERE id = ? FOR UPDATE");
                $stmtStock->execute([$ing['id_producto_hijo']]);
                $ingProd = $stmtStock->fetch();

                if ($ingProd['stock'] < $deductQty) {
                    throw new Exception("Stock insuficiente para insumo: " . $ingProd['nombre']);
                }

                $upd = $db->prepare("UPDATE tbl_productos SET stock = stock - ? WHERE id = ?");
                $upd->execute([$deductQty, $ing['id_producto_hijo']]);
            }
        } else {
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

    // 6. Insert Sale Header (Unified)
    // Note: tbl_ventas uses 'codigo' usually? we will use NULL or auto-gem if needed.
    // Structure: id, codigo, id_funcion, cliente_nombre, cliente_doc, total, tipo_comprobante, medio_pago, estado, created_at, id_usuario, serie, correlativo, origen

    // We assume 'medio_pago' is 'MIXTO' if multiple, or the name if single.
    $medio_pago = (count($payments) > 1) ? 'MIXTO' : $payments[0]['name'];

    // We assume 'tipo_comprobante' matches serie type (BOLETA).
    $tipo_comprobante = ($serie['tipo'] === 'F') ? 'FACTURA' : 'BOLETA';

    // Generate unique code (copied from finalizar_venta.php logic)
    $codigo = 'POS-' . time() . '-' . rand(100, 999);

    $stmtSale = $db->prepare("INSERT INTO tbl_ventas (
            codigo, id_funcion, cliente_nombre, total, tipo_comprobante, medio_pago, estado, 
            id_usuario, serie, correlativo, origen
        ) VALUES (
            ?, NULL, ?, ?, ?, ?, 'PAGADO', 
            ?, ?, ?, 'DULCERIA'
        )");

    $stmtSale->execute([$codigo, $cliente, $total, $tipo_comprobante, $medio_pago, $userId, $serieNum, $newCorrelativo]);
    $ventaId = $db->lastInsertId();

    // 7. Insert Sale Details (Unified)
    $stmtDet = $db->prepare("INSERT INTO tbl_ventas_det (id_venta, tipo_item, id_item, descripcion, cantidad, precio_unitario, importe) VALUES (?, 'PRODUCTO', ?, ?, ?, ?, ?)");
    foreach ($cart as $item) {
        $importe = $item['price'] * $item['qty'];

        // Use cached name if available (it should be from validation step)
        $prodName = $productCache[$item['id']]['nombre'] ?? 'Producto Desconocido';
        // Fallback fetch if somehow missed (unlikely)
        if ($prodName === 'Producto Desconocido') {
            $stmtName = $db->prepare("SELECT nombre FROM tbl_productos WHERE id = ?");
            $stmtName->execute([$item['id']]);
            $prodName = $stmtName->fetchColumn();
        }

        $stmtDet->execute([$ventaId, $item['id'], $prodName, $item['qty'], $item['price'], $importe]);
    }

    // 8. Insert Payments (Unified)
    $stmtPay = $db->prepare("INSERT INTO tbl_ventas_pag (id_venta, id_forma_pago, monto, referencia) VALUES (?, ?, ?, ?)");
    foreach ($payments as $pay) {
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
