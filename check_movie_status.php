<?php
require_once 'panel/config/config.php';

echo "Checking Movie Status for active functions...\n";

// Get movie IDs active today
$stmt = $db->query("SELECT DISTINCT id_pelicula FROM tbl_funciones WHERE fecha >= CURRENT_DATE AND estado = '1'");
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($ids)) {
    echo "No active functions found today.\n";
} else {
    $in = implode(',', $ids);
    $stmtP = $db->query("SELECT id, nombre, estado FROM tbl_pelicula WHERE id IN ($in)");
    $movies = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    foreach ($movies as $m) {
        echo "[{$m['id']}] {$m['nombre']} - Estado: {$m['estado']}\n";
    }
}
