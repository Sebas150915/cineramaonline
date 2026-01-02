<?php
require_once 'panel/config/config.php';

echo "=== DEBUGGING POS LOCAL LISTING ===\n";

// 1. Check User Local
$user = 'cajero1';
$stmt = $db->prepare("SELECT id, usuario, id_local FROM tbl_usuarios WHERE usuario = ?");
$stmt->execute([$user]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
echo "User: {$u['usuario']} | ID Local: {$u['id_local']}\n";
$id_local = $u['id_local'];

// 2. Check Local Name
$stmt = $db->prepare("SELECT * FROM tbl_locales WHERE id = ?");
$stmt->execute([$id_local]);
$local = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Local Name: " . ($local ? $local['nombre'] : 'NOT FOUND') . "\n";

// 3. Check Sales/Rooms for this Local
$stmt = $db->prepare("SELECT id, nombre, local FROM tbl_sala WHERE local = ?");
$stmt->execute([$id_local]);
$salas = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Salas found for Local $id_local: " . count($salas) . "\n";
$sala_ids = array_column($salas, 'id');
$sala_ids_str = implode(',', $sala_ids);
echo "Sala IDs: $sala_ids_str\n";

if (empty($sala_ids)) {
    die("NO SALAS FOUND FOR THIS LOCAL. STOPPING.\n");
}

// 4. Check Functions in these Salas
echo "Checking functions for Sala IDs: $sala_ids_str\n";
// Using the same logic as get_movies.php (joining tbl_sala)
$sql = "
    SELECT *
    FROM tbl_ventas
    LIMIT 1
";
$stmt = $db->prepare($sql);
$stmt->execute([$id_local]);
$funcs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Active Functions found: " . count($funcs) . "\n";
if (!empty($funcs)) {
    echo "Dumping first function row:\n";
    print_r($funcs[0]);
}
