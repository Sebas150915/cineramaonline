<?php
require_once '../includes/front_config.php';
require_once '../includes/Niubiz.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // 1. Get Sale ID and calculate Total
    $id_venta = isset($_POST['id_venta']) ? (int)$_POST['id_venta'] : 0;
    
    if ($id_venta <= 0) {
        throw new Exception('ID Venta inválido');
    }

    // Verify Sale
    $stmtCheck = $db->prepare("SELECT * FROM tbl_ventas WHERE id = ? AND estado = 'PENDIENTE'");
    $stmtCheck->execute([$id_venta]);
    $sale = $stmtCheck->fetch();

    if (!$sale) {
        throw new Exception("La orden no es válida o ya expiró.");
    }

    // NEW: Save Customer Data & Selected Tariffs
    $cliente_nombre = isset($_POST['cliente_nombre']) ? sanitize($_POST['cliente_nombre']) : '';
    $cliente_doc = isset($_POST['cliente_doc']) ? sanitize($_POST['cliente_doc']) : '';
    $tipo_comprobante = isset($_POST['tipo_comprobante']) ? sanitize($_POST['tipo_comprobante']) : 'BOLETA';
    
    // Calculate Total based on selected tariffs
    $total = 0;
    
    // We need to parse fields like tarifa_123 = 5 (boleto_id = tarifa_id)
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'tarifa_') === 0 && !empty($value)) {
            $id_boleto = (int)str_replace('tarifa_', '', $key);
            $id_tarifa = (int)$value;
            
            // Validate tariff price
            $stmtTarifa = $db->prepare("SELECT precio FROM tbl_tarifa WHERE id = ?");
            $stmtTarifa->execute([$id_tarifa]);
            $precio = $stmtTarifa->fetchColumn();
            
            if ($precio === false) {
                 throw new Exception("Tarifa inválida ID: $id_tarifa");
            }
            
            $total += $precio;
            
            // Update Boleto with selected tariff immediately (pre-save)
            $stmtUpdateBoleto = $db->prepare("UPDATE tbl_boletos SET id_tarifa = ?, precio = ? WHERE id = ?");
            $stmtUpdateBoleto->execute([$id_tarifa, $precio, $id_boleto]);
        }
    }
    
    if ($total <= 0) {
        throw new Exception("El monto total debe ser mayor a 0");
    }

    // Update Sale with customer info and total
    $stmtUpdateVenta = $db->prepare("
        UPDATE tbl_ventas 
        SET cliente_nombre = ?, 
            cliente_doc = ?, 
            tipo_comprobante = ?,
            total = ?
        WHERE id = ?
    ");
    $stmtUpdateVenta->execute([$cliente_nombre, $cliente_doc, $tipo_comprobante, $total, $id_venta]);

    // 2. Niubiz Logic
    $niubiz = new Niubiz();
    $accessToken = $niubiz->getAccessToken();
    
    if (!$accessToken) {
        throw new Exception("Error al obtener Access Token de Niubiz");
    }

    // Session
    $session = $niubiz->createSession($accessToken, $total);
    
    if (!isset($session['sessionKey'])) {
        throw new Exception("Error al crear sesión Niubiz: " . json_encode($session));
    }
    
    // Purchase Number (Must be unique for Niubiz)
    // We use id_venta + timestamp to ensure uniqueness in retries
    // Using hyphen as separator for easy extraction later
    $purchaseNumber = $id_venta . '-' . time();

    echo json_encode([
        'success' => true,
        'sessionKey' => $session['sessionKey'],
        'merchantId' => $niubiz->getMerchantId(),
        'amount' => $total,
        'purchaseNumber' => $purchaseNumber,
        'channel' => 'web'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}