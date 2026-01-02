<?php
require_once '../../config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php?error=No ID');
}

$id_cartelera = (int)$_GET['id'];

try {
    // 1. Obtener datos de la cartelera
    $stmt = $db->prepare("SELECT * FROM tbl_cartelera WHERE id = ?");
    $stmt->execute([$id_cartelera]);
    $cartelera = $stmt->fetch();

    if (!$cartelera) {
        redirect("index.php?error=Cartelera no encontrada");
    }

    $pelicula = $cartelera['pelicula'];
    $sala = $cartelera['sala'];
    $fecha_inicio = new DateTime($cartelera['fecha_inicio']);
    $fecha_fin = new DateTime($cartelera['fecha_fin']);
    $horarios = explode(',', $cartelera['horarios']); // IDs de horas

    // Si la sala está vacía (posiblemente un error de asignación), no podemos crear funciones
    if (empty($sala)) {
        redirect("index.php?error=Esta cartelera no tiene Sala asignada.");
    }

    $funciones_creadas = 0;
    $funciones_omitidas = 0;

    // 2. Iterar por cada día del rango (incluyendo el fin)
    // Clonamos fecha_fin para la comparación correcta en el bucle
    $fin = clone $fecha_fin;
    $fin->modify('+1 day');

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($fecha_inicio, $interval, $fin);


    foreach ($period as $dt) {
        $fecha_actual = $dt->format('Y-m-d');

        // 3. Iterar por cada hora seleccionada
        foreach ($horarios as $id_hora) {
            if (empty($id_hora)) continue;

            // 4. Verificar existencia (Evitar duplicados)
            $check_sql = "SELECT COUNT(*) FROM tbl_funciones 
                          WHERE id_sala = ? AND fecha = ? AND id_hora = ?";
            $check_stmt = $db->prepare($check_sql);
            $check_stmt->execute([$sala, $fecha_actual, $id_hora]);

            if ($check_stmt->fetchColumn() > 0) {
                $funciones_omitidas++;
                continue; // Ya existe, saltamos
            }

            // 5. Insertar función
            $insert_sql = "INSERT INTO tbl_funciones (id_pelicula, id_sala, id_hora, fecha, estado) 
                           VALUES (?, ?, ?, ?, '1')";
            $insert_stmt = $db->prepare($insert_sql);
            $insert_stmt->execute([$pelicula, $sala, $id_hora, $fecha_actual]);

            $funciones_creadas++;
        }
    }

    $msg_type = ($funciones_creadas > 0) ? 'success' : 'warning';
    $msg = "Proceso completado. Creadas: $funciones_creadas. Omitidas (ya existían): $funciones_omitidas.";

    redirect("index.php?msg=" . urlencode($msg) . "&type=$msg_type");
} catch (PDOException $e) {
    redirect("index.php?error=" . urlencode($e->getMessage()));
}
