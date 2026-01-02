<?php
require_once 'panel/config/config.php';

try {
    echo "Iniciando migración de cartelera...<br>";

    // 1. Agregar columnas si no existen
    $columns = ['id_hora_f1', 'id_hora_f2', 'id_hora_f3', 'id_hora_f4', 'id_hora_f5', 'id_hora_f6'];

    foreach ($columns as $col) {
        // Verificar si existe
        $check = $db->query("SHOW COLUMNS FROM tbl_cartelera LIKE '$col'");
        if ($check->rowCount() == 0) {
            echo "Agregando columna $col...<br>";
            $db->exec("ALTER TABLE tbl_cartelera ADD COLUMN $col INT NULL DEFAULT NULL");
            // Agregar FK (opcional para integridad, pero útil)
            // $db->exec("ALTER TABLE tbl_cartelera ADD CONSTRAINT fk_cartelera_$col FOREIGN KEY ($col) REFERENCES tbl_hora(id)");
        } else {
            echo "Columna $col ya existe.<br>";
        }
    }

    // 2. Intentar migrar datos antiguos (si existen en 'horarios')
    echo "Migrando datos antiguos...<br>";
    $stmt = $db->query("SELECT id, horarios FROM tbl_cartelera WHERE horarios IS NOT NULL AND horarios != ''");
    $carteleras = $stmt->fetchAll();

    foreach ($carteleras as $c) {
        $ids = explode(',', $c['horarios']);
        $updates = [];
        // Llenar F1, F2... hasta donde alcance
        for ($i = 0; $i < 6; $i++) {
            if (isset($ids[$i]) && is_numeric($ids[$i])) {
                $col_name = "id_hora_f" . ($i + 1); // f1, f2...
                $updates[$col_name] = $ids[$i];
            }
        }

        if (!empty($updates)) {
            $set_clause = [];
            $params = [];
            foreach ($updates as $col => $val) {
                $set_clause[] = "$col = ?";
                $params[] = $val;
            }
            $params[] = $c['id'];

            $sql = "UPDATE tbl_cartelera SET " . implode(', ', $set_clause) . " WHERE id = ?";
            $db->prepare($sql)->execute($params);
        }
    }

    echo "Migración completada con éxito. Ya puedes usar las nuevas columnas.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
