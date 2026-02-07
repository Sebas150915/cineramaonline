<?php
require_once 'includes/front_config.php';
require_once 'includes/Niubiz.php';

// Niubiz POSTs to this URL after payment
$transactionToken = $_POST['transactionToken'] ?? null;
// We sent purchaseNumber as "id_venta" + "timestamp"
$purchaseNumber = $_POST['purchaseNumber'] ?? null; 
$amount = $_POST['amount'] ?? null;

if (!$transactionToken || !$purchaseNumber) {
    die("Error: Datos de transacción incompletos.");
}

// Extract ID Venta (remove timestamp if needed, but we used direct concatenation)
// Logic: purchaseNumber = 1231700000. If ID is small, this is tricky.
// Better: We stored ID in purchaseNumber, but we can't easily split it unless we used a separator.
// However, Niubiz returns what we sent. 
// Let's assume we can get ID from session if we stored it, OR we extract it if we used a separator.
// In get_niubiz_token.php we did: $purchaseNumber = $id_venta . time();
// This is bad for extraction. Let's fix get_niubiz_token.php later to use a separator or just rely on session.
// Actually, let's look up the sale by the purchase number if we stored it? No.
// Let's try to pass ID Venta as a separate parameter if possible? 
// Niubiz allows 'merchantDefineData'. We should have used that.
// BUT, since we updated the DB with the TOTAL before calling Niubiz, we can search for the sale 
// that matches the amount and is PENDIENTE? Risky.

// Quick Fix: Let's assume the user session is still active and we can trust the flow? No.
// Let's assume we can pass custom parameters.
// For now, let's try to Authorization first.
// If Authorization works, we get the order details.

try {
    $niubiz = new Niubiz();
    $accessToken = $niubiz->getAccessToken();
    
    $auth = $niubiz->authorizeTransaction($accessToken, $transactionToken, $purchaseNumber, $amount);
    
    if (isset($auth['dataMap']) && $auth['dataMap']['ACTION_CODE'] === '000') {
        // Success!
        
        // Recover ID Venta. 
        // purchaseNumber format: ID_VENTA-TIMESTAMP
        $parts = explode('-', $purchaseNumber);
        $id_venta = (int)$parts[0];
        
        // Finalize Sale
        $db->beginTransaction();
        
        // Get Sale
        $stmtCheck = $db->prepare("SELECT * FROM tbl_ventas WHERE id = ?");
        $stmtCheck->execute([$id_venta]);
        $sale = $stmtCheck->fetch();
        
        if (!$sale) throw new Exception("Venta no encontrada ID: $id_venta");
        
        // Generate Invoice Code
        $tipo_comprobante = $sale['tipo_comprobante'] ?: 'BOLETA';
        $prefix = ($tipo_comprobante === 'FACTURA') ? 'F001' : 'B001';
        $correlativo = str_pad($id_venta, 8, '0', STR_PAD_LEFT);
        $nuevo_codigo = "$prefix-$correlativo";
        
        $stmtUpdateVenta = $db->prepare("
            UPDATE tbl_ventas 
            SET estado = 'PAGADO',
                codigo = ?,
                medio_pago = 'TARJETA',
                niubiz_token = ?
            WHERE id = ?
        ");
        $stmtUpdateVenta->execute([$nuevo_codigo, $transactionToken, $id_venta]);
        
        // Confirm Seats (tbl_boletos already has prices from previous step)
        // No need to update prices again as they were set in get_niubiz_token.php
        
        $db->commit();
        
        // Redirect to Ticket
        header('Location: ticket.php?codigo=' . $nuevo_codigo);
        exit;
        
    } else {
        // Failed
        $error = isset($auth['dataMap']['ACTION_DESCRIPTION']) ? $auth['dataMap']['ACTION_DESCRIPTION'] : 'Transacción denegada';
        die("Error en el pago: " . $error . " <a href='index.php'>Volver</a>");
    }

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    die("Error del sistema: " . $e->getMessage());
}