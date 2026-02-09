<?php
require_once 'includes/front_config.php';

// Allow GET for debugging/fallback
// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//    header('Location: index.php');
//    exit;
// }

$id_funcion = isset($_REQUEST['id_funcion']) ? (int)$_REQUEST['id_funcion'] : 0;

// Try to get seats from various possible inputs
$seats_ids = [];

if (!empty($_REQUEST['selected_seats'])) {
    // 1. Comma separated string
    $seats_ids = explode(',', $_REQUEST['selected_seats']);
} elseif (!empty($_REQUEST['asientos']) && is_array($_REQUEST['asientos'])) {
    // 2. Array 'asientos'
    $seats_ids = $_REQUEST['asientos'];
} elseif (!empty($_REQUEST['id_asiento']) && is_array($_REQUEST['id_asiento'])) {
    // 3. Array 'id_asiento'
    $seats_ids = $_REQUEST['id_asiento'];
}

// Sanitize IDs: Remove empty/null values and ensure integers
$seats_ids = array_filter($seats_ids, function($v) { 
    return $v !== '' && $v !== null; 
});
$seats_ids = array_map('intval', $seats_ids);
$seats_ids = array_values($seats_ids); // Re-index array

if (empty($seats_ids)) {
    echo "<h3>Error: No se recibieron asientos seleccionados.</h3>";
    echo "<p>Por favor toma una captura de esta pantalla y envíala al soporte.</p>";
    echo "<hr>";
    echo "<strong>DEBUG INFO:</strong><br>";
    echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
    echo "Query String: " . $_SERVER['QUERY_STRING'] . "<br>";
    echo "<pre>";
    echo "GET Data:\n";
    print_r($_GET);
    echo "\nPOST Data:\n";
    print_r($_POST);
    echo "\nREQUEST Data:\n";
    print_r($_REQUEST);
    echo "</pre>";
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
        if ($sid <= 0) continue; // Skip invalid IDs

        // Get visual data for the seat record
        $stmtS = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE id = ?");
        $stmtS->execute([$sid]);
        $sData = $stmtS->fetch(PDO::FETCH_ASSOC);

        if (!$sData) {
            // If seat not found, rollback and error
            throw new Exception("El asiento con ID $sid no existe en la base de datos.");
        }

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
