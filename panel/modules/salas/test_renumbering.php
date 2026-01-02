<?php
require_once '../../config/config.php';

echo "<h2>Iniciando pruebas de reubicación de asientos...</h2>";

// 1. Crear Sala de Prueba
$nombre = "Sala Test " . rand(1000, 9999);
$stmt = $db->prepare("INSERT INTO tbl_sala (nombre, local, capacidad, filas, columnas) VALUES (?, 1, 10, 5, 5)");
$stmt->execute([$nombre]);
$sala_id = $db->lastInsertId();
echo "Sala de prueba creada (ID: $sala_id)<br>";

// 2. Insertar Asientos Iniciales
// A-1: NORMAL
// A-2: NORMAL
// A-3: (Vacio)
$db->prepare("INSERT INTO tbl_sala_asiento (idsala, local, fila, columna, num_asiento, tipo) VALUES (?, 1, 'A', 1, '1', 'NORMAL')")->execute([$sala_id]);
$db->prepare("INSERT INTO tbl_sala_asiento (idsala, local, fila, columna, num_asiento, tipo) VALUES (?, 1, 'A', 2, '2', 'NORMAL')")->execute([$sala_id]);

echo "Asientos A-1 y A-2 creados.<br>";

// Función helper para llamar a la logica (simulada)
function test_renumbering($db, $sala_id, $f_orig, $n_orig, $f_dest, $n_dest)
{
    echo "Intento mover $f_orig-$n_orig a $f_dest-$n_dest... ";

    // Logica copiada/adaptada de asientos.php para test unitario rápido sin hacer requests HTTP completos
    // En un test real haríamos curl, pero esto valida la logica db

    // Verificar destino
    $stmtC = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE idsala = ? AND fila = ? AND columna = ?");
    $stmtC->execute([$sala_id, $f_dest, $n_dest]);
    $destino = $stmtC->fetch();

    if ($destino) {
        if ($destino['tipo'] == 'PASILLO') {
            $db->prepare("DELETE FROM tbl_sala_asiento WHERE id = ?")->execute([$destino['id']]);
            echo "[OK: Pasillo eliminado] ";
        } else {
            echo "[ERROR: Ocupado por {$destino['tipo']}] -> ";
            return false;
        }
    }

    // Update
    $stmt = $db->prepare("UPDATE tbl_sala_asiento SET fila = ?, columna = ?, num_asiento = ? WHERE idsala = ? AND fila = ? AND columna = ?");
    $result = $stmt->execute([$f_dest, $n_dest, (string)$n_dest, $sala_id, $f_orig, $n_orig]);

    if ($result && $stmt->rowCount() > 0) {
        echo "[SUCCESS]<br>";
        return true;
    } else {
        echo "[FAIL: No update]<br>";
        return false;
    }
}

// TEST 1: Mover A-1 a A-3 (Libre)
if (test_renumbering($db, $sala_id, 'A', 1, 'A', 3)) {
    echo "Test 1 Passed<br>";
} else {
    echo "Test 1 Failed<br>";
}

// TEST 2: Mover A-3 (antiguo A-1) a A-2 (Ocupado NORMAL)
if (!test_renumbering($db, $sala_id, 'A', 3, 'A', 2)) {
    echo "Test 2 Passed (Correctamente bloqueado)<br>";
} else {
    echo "Test 2 Failed (Debería haber fallado)<br>";
}

// TEST 3: Crear Pasillo en A-4 y mover A-2 alli
$db->prepare("INSERT INTO tbl_sala_asiento (idsala, local, fila, columna, num_asiento, tipo) VALUES (?, 1, 'A', 4, '4', 'PASILLO')")->execute([$sala_id]);
echo "Pasillo creado en A-4<br>";

if (test_renumbering($db, $sala_id, 'A', 2, 'A', 4)) {
    echo "Test 3 Passed (Pasillo sobrescrito)<br>";
} else {
    echo "Test 3 Failed<br>";
}

// Limpieza
$db->prepare("DELETE FROM tbl_sala_asiento WHERE idsala = ?")->execute([$sala_id]);
$db->prepare("DELETE FROM tbl_sala WHERE id = ?")->execute([$sala_id]);
echo "Limpieza completada.";
