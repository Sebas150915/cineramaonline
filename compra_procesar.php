<?php
require_once 'includes/front_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 1. Recibir Datos
$id_venta = (int)$_POST['id_venta'];
$cliente_nombre = sanitize($_POST['cliente_nombre']);
$cliente_doc = sanitize($_POST['cliente_doc']);
$tipo_comprobante = sanitize($_POST['tipo_comprobante']);
$medio_pago = sanitize($_POST['medio_pago']);

if ($id_venta <= 0) die("ID Venta inválido");

$total = 0;

try {
    $db->beginTransaction();

    // Verify it's still PENDIENTE (and not expired)
    $stmtCheck = $db->prepare("SELECT * FROM tbl_ventas WHERE id = ? AND estado = 'PENDIENTE'");
    $stmtCheck->execute([$id_venta]);
    $sale = $stmtCheck->fetch();

    if (!$sale) {
        throw new Exception("La orden no es válida o ya fue procesada.");
    }

    // Get Pending Boletos associated with this sale
    $stmtSeats = $db->prepare("SELECT * FROM tbl_boletos WHERE id_venta = ?");
    $stmtSeats->execute([$id_venta]);
    $seats = $stmtSeats->fetchAll();

    foreach ($seats as $seat) {
        // Form field name: tarifa_{id_boleto}
        $key = 'tarifa_' . $seat['id'];
        if (!isset($_POST[$key])) throw new Exception("Falta tarifa para un asiento");

        $id_tarifa = (int)$_POST[$key];

        // Get Price
        $stmtTarifa = $db->prepare("SELECT precio FROM tbl_tarifa WHERE id = ?");
        $stmtTarifa->execute([$id_tarifa]);
        $precio = $stmtTarifa->fetchColumn();

        if ($precio === false) throw new Exception("Tarifa inválida ID: $id_tarifa");

        $total += $precio;

        // Update Boleto Record
        $stmtUpdateBoleto = $db->prepare("UPDATE tbl_boletos SET id_tarifa = ?, precio = ? WHERE id = ?");
        $stmtUpdateBoleto->execute([$id_tarifa, $precio, $seat['id']]);
    }

    // Generate Formal Electronic Invoice Code (Correlative)
    // Format: B001-0000XXXX (Boleta) or F001-0000XXXX (Factura)
    $prefix = ($tipo_comprobante === 'FACTURA') ? 'F001' : 'B001';
    $correlativo = str_pad($id_venta, 8, '0', STR_PAD_LEFT);
    $nuevo_codigo = "$prefix-$correlativo";

    // Update Sale Header with new Code
    $stmtUpdateVenta = $db->prepare("
        UPDATE tbl_ventas 
        SET cliente_nombre = ?, 
            cliente_doc = ?, 
            total = ?, 
            tipo_comprobante = ?, 
            medio_pago = ?, 
            estado = 'PAGADO',
            codigo = ?
        WHERE id = ?
    ");
    $stmtUpdateVenta->execute([$cliente_nombre, $cliente_doc, $total, $tipo_comprobante, $medio_pago, $nuevo_codigo, $id_venta]);

    $db->commit();

    // Redirect to Ticket using the NEW code
    header('Location: ticket.php?codigo=' . $nuevo_codigo);
    exit;
} catch (Exception $e) {
    $db->rollBack();
    die("Error al procesar la compra: " . $e->getMessage());
}
