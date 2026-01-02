<?php
require_once 'includes/front_config.php';

$id_sala = 16;

try {
    // 1. Get current config
    $stmt = $db->query("SELECT * FROM tbl_sala WHERE id = $id_sala");
    $sala = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Configuración Actual: Filas {$sala['filas']}, Columnas {$sala['columnas']}\n";

    // 2. Get real max column from seats
    $stmtMax = $db->query("SELECT MAX(CAST(columna AS UNSIGNED)) FROM tbl_sala_asiento WHERE idsala = $id_sala");
    $maxCol = $stmtMax->fetchColumn();

    $stmtMaxRow = $db->query("SELECT MAX(CAST(fila AS UNSIGNED)) FROM tbl_sala_asiento WHERE idsala = $id_sala"); // usually letters but check
    // Actually fila is letters (A, B, C...) usually.

    echo "Máxima Columna en Asientos: $maxCol\n";

    if ($maxCol > 1) {
        // Fix it
        $db->exec("UPDATE tbl_sala SET columnas = $maxCol WHERE id = $id_sala");
        echo "✅ Corregido: tbl_sala ahora tiene $maxCol columnas.\n";
    } else {
        echo "⚠️ No se encontraron asientos o max col es 1. (¿Quizás se borraron?)\n";

        // If 0 seats, let's look closer
        $count = $db->query("SELECT COUNT(*) FROM tbl_sala_asiento WHERE idsala = $id_sala")->fetchColumn();
        echo "Total Asientos: $count\n";
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}
