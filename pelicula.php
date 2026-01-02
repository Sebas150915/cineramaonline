<?php
require_once 'includes/front_config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: cartelera.php');
    exit;
}

// Obtener detalles de la película
try {
    $stmt = $db->prepare("SELECT * FROM tbl_pelicula WHERE id = ? AND estado = '1'");
    $stmt->execute([$id]);
    $pelicula = $stmt->fetch();

    if (!$pelicula) {
        die("Película no encontrada.");
    }

    // Obtener funciones SOLO DE HOY (O la próxima fecha disponible si fuera la lógica, pero usuario pidió "Solo del día")
    // Asumiremos "Solo del día actual"
    $stmtFunc = $db->prepare("
        SELECT f.id, f.fecha, f.id_sala, s.nombre as sala, l.nombre as cine, h.hora, l.direccion
        FROM tbl_funciones f
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_locales l ON s.local = l.id
        JOIN tbl_hora h ON f.id_hora = h.id
        WHERE f.id_pelicula = ? 
        AND f.estado = '1'
        AND f.fecha = CURRENT_DATE
        ORDER BY l.nombre ASC, h.hora ASC
    ");
    $stmtFunc->execute([$id]);
    $funciones = $stmtFunc->fetchAll();

    // Agrupar funciones por Cine
    $funcionesPorCine = [];
    $fechaHoy = date('Y-m-d'); // Fallback if no functions

    if (!empty($funciones)) {
        $fechaHoy = $funciones[0]['fecha']; // Use DB date for accuracy
    }

    foreach ($funciones as $func) {
        $cine = $func['cine'];
        $funcionesPorCine[$cine]['direccion'] = $func['direccion'];
        $funcionesPorCine[$cine]['horarios'][] = $func;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$page_title = $pelicula['nombre'];
include 'includes/header_front.php';
?>

<div class="container" style="display: flex; gap: 40px; flex-wrap: wrap;">
    <!-- Detalles Película -->
    <div style="flex: 1; min-width: 300px;">
        <?php
        $poster = !empty($pelicula['img']) ? UPLOADS_URL . 'peliculas/' . $pelicula['img'] : 'https://via.placeholder.com/400x600';
        ?>
        <img src="<?php echo $poster; ?>" alt="Poster" style="width: 100%; border-radius: 10px; box-shadow: 0 0 20px rgba(255,0,0,0.2);">

        <div style="margin-top: 20px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h1 style="color: #e50914; margin-top: 0; font-size: 24px;"><?php echo htmlspecialchars($pelicula['nombre']); ?></h1>
            <p style="color: #333;"><strong>Director:</strong> <?php echo htmlspecialchars($pelicula['director']); ?></p>
            <p style="color: #333;"><strong>Reparto:</strong> <?php echo htmlspecialchars($pelicula['reparto']); ?></p>
            <p style="color: #333;"><strong>Sinopsis:</strong><br><?php echo nl2br(htmlspecialchars($pelicula['sinopsis'])); ?></p>

            <?php if (!empty($pelicula['trailer'])): ?>
                <div style="margin-top: 20px;">
                    <iframe width="100%" height="200" src="https://www.youtube.com/embed/<?php echo $pelicula['trailer']; ?>" frameborder="0" allowfullscreen></iframe>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Funciones -->
    <div style="flex: 2; min-width: 300px;">
        <h2 class="section-title" style="text-align: left; border-bottom: 2px solid #e50914; padding-bottom: 10px; margin-bottom: 20px;">
            Funciones para Hoy
            <span style="font-size: 0.6em; color: #777; font-weight: normal;">
                (<?php setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');
                    echo mb_strtoupper(strftime('%A %d DE %B', strtotime($fechaHoy))); ?>)
            </span>
        </h2>

        <?php if (empty($funcionesPorCine)): ?>
            <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                <p style="color: #555; font-size: 1.1em;">No hay funciones programadas para hoy.</p>
                <a href="cartelera.php" class="btn" style="margin-top: 10px; background: #e50914; color: white;">Ver Cartelera Completa</a>
            </div>
        <?php else: ?>

            <div class="cinema-list">
                <?php foreach ($funcionesPorCine as $nombreCine => $datos): ?>
                    <div class="cinema-block" style="background: #fff; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden;">
                        <div class="cinema-header" style="background: #f4f4f4; padding: 15px 20px; border-left: 5px solid #e50914;">
                            <h3 style="margin: 0; color: #333; font-size: 1.2em;"><?php echo htmlspecialchars($nombreCine); ?></h3>
                            <small style="color: #777; display: block; margin-top: 5px;"><?php echo htmlspecialchars($datos['direccion']); ?></small>
                        </div>

                        <div class="cinema-times" style="padding: 20px;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php foreach ($datos['horarios'] as $h): ?>
                                    <a href="compra_asientos.php?id_funcion=<?php echo $h['id']; ?>" class="time-btn">
                                        <?php echo date('h:i A', strtotime($h['hora'])); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<style>
    .time-btn {
        display: inline-block;
        padding: 10px 20px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background-color: #fff;
        color: #333;
        text-decoration: none;
        font-weight: 700;
        font-size: 15px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .time-btn:hover {
        background-color: #e50914;
        color: #fff;
        border-color: #e50914;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(229, 9, 20, 0.3);
    }
</style>

<?php include 'includes/footer_front.php'; ?>