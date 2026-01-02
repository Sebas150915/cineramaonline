<?php
require_once '../panel/config/config.php';

$id_pelicula = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_pelicula) {
    header('Location: index.php');
    exit;
}

try {
    // 1. Obtener detalles de la película
    $stmt = $db->prepare("
        SELECT p.*, g.nombre as genero_nombre, ce.nombre as clasificacion 
        FROM tbl_pelicula p 
        LEFT JOIN tbl_genero g ON p.genero = g.id 
        LEFT JOIN tbl_censura ce ON p.censura = ce.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id_pelicula]);
    $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pelicula) {
        die("Película no encontrada.");
    }

    // 2. Obtener horarios activos (Cartelera)
    // Se une con tbl_hora 6 veces para obtener las horas de las columnas F1-F6
    $sql = "
        SELECT 
            c.id as cartelera_id,
            c.formato,
            c.idioma,
            l.nombre as local,
            s.nombre as sala,
            h1.hora as h1, h1.id as h1_id,
            h2.hora as h2, h2.id as h2_id,
            h3.hora as h3, h3.id as h3_id,
            h4.hora as h4, h4.id as h4_id,
            h5.hora as h5, h5.id as h5_id,
            h6.hora as h6, h6.id as h6_id
        FROM tbl_cartelera c
        JOIN tbl_locales l ON c.local = l.id
        JOIN tbl_sala s ON c.sala = s.id
        LEFT JOIN tbl_hora h1 ON c.id_hora_f1 = h1.id
        LEFT JOIN tbl_hora h2 ON c.id_hora_f2 = h2.id
        LEFT JOIN tbl_hora h3 ON c.id_hora_f3 = h3.id
        LEFT JOIN tbl_hora h4 ON c.id_hora_f4 = h4.id
        LEFT JOIN tbl_hora h5 ON c.id_hora_f5 = h5.id
        LEFT JOIN tbl_hora h6 ON c.id_hora_f6 = h6.id
        WHERE c.pelicula = ? 
        AND c.estado = '1' 
        AND c.fecha_fin >= CURDATE()
        ORDER BY l.nombre ASC, c.formato ASC, c.idioma ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_pelicula]);
    $funciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Horarios - <?php echo htmlspecialchars($pelicula['nombre']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/kiosk.css">
</head>

<body>

    <!-- Header Compacto -->
    <header class="kiosk-header glass-panel" style="border-radius: 0 0 16px 16px; border-top: none;">
        <a href="index.php" class="back-link">
            <i class="fas fa-chevron-left"></i> Volver
        </a>
        <div class="brand-logo" style="font-size: 1.5rem;">HORARIOS</div>
        <div class="clock" style="font-weight: 600;">
            <?php echo date('g:i A'); ?>
        </div>
    </header>

    <main class="kiosk-main">

        <!-- Info Película -->
        <div class="movie-backdrop-container">
            <div class="movie-info-header glass-panel">
                <img src="../uploads/peliculas/<?php echo htmlspecialchars($pelicula['img']); ?>"
                    alt="Poster"
                    class="mini-poster"
                    onerror="this.src='../assets/img/no-poster.jpg'">
                <div class="movie-details">
                    <h1 class="movie-title-large"><?php echo htmlspecialchars($pelicula['nombre']); ?></h1>
                    <div class="movie-meta-tags">
                        <span class="tag"><?php echo htmlspecialchars($pelicula['genero_nombre']); ?></span>
                        <span class="tag"><?php echo $pelicula['duracion']; ?> min</span>
                        <span class="tag rating"><?php echo htmlspecialchars($pelicula['clasificacion']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Funciones -->
        <div class="functions-list">
            <?php if (empty($funciones)): ?>
                <div class="no-shows glass-panel">
                    <i class="far fa-calendar-times"></i>
                    <h3>No hay funciones programadas para hoy.</h3>
                </div>
            <?php else: ?>
                <?php foreach ($funciones as $func): ?>
                    <div class="function-card glass-panel">
                        <div class="function-header">
                            <h3 class="cinema-name"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($func['local']); ?></h3>
                            <div class="function-specs">
                                <span class="spec-badge format"><?php echo $func['formato']; ?></span>
                                <span class="spec-badge lang"><?php echo $func['idioma']; ?></span>
                                <span class="spec-badge room"><?php echo $func['sala']; ?></span>
                            </div>
                        </div>

                        <div class="time-grid">
                            <?php
                            // Iterar F1 a F6
                            for ($i = 1; $i <= 6; $i++):
                                $hora = $func["h$i"];
                                $hora_id = $func["h{$i}_id"];

                                if ($hora):
                                    // Formato AM/PM
                                    $timeObj = new DateTime($hora);
                                    $timeStr = $timeObj->format('g:i A');

                                    // TODO: Lógica para bloquear horas pasadas
                                    // $isPast = ...
                            ?>
                                    <a href="sala.php?cartelera_id=<?php echo $func['cartelera_id']; ?>&hora_id=<?php echo $hora_id; ?>&num_funcion=<?php echo $i; ?>"
                                        class="time-btn">
                                        <?php echo $timeStr; ?>
                                    </a>
                            <?php
                                endif;
                            endfor;
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <script src="assets/js/kiosk.js"></script>
    <style>
        /* Estilos específicos para esta página */
        .back-link {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
        }

        .movie-backdrop-container {
            margin-bottom: 30px;
        }

        .movie-info-header {
            display: flex;
            gap: 20px;
            padding: 20px;
            align-items: center;
        }

        .mini-poster {
            width: 80px;
            /* Reduced specific size logic */
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        .movie-title-large {
            font-size: 1.8rem;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .movie-meta-tags {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tag {
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .tag.rating {
            border: 1px solid var(--primary);
            color: var(--primary);
            font-weight: bold;
        }

        .functions-list {
            display: grid;
            gap: 20px;
        }

        .function-card {
            padding: 20px;
        }

        .function-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 15px;
        }

        .cinema-name {
            font-size: 1.2rem;
            color: var(--text-main);
        }

        .function-specs {
            display: flex;
            gap: 10px;
        }

        .spec-badge {
            background: rgba(0, 242, 234, 0.1);
            color: var(--accent);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .spec-badge.format {
            color: #fff;
            background: var(--primary);
        }

        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
        }

        .time-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            padding: 15px 10px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.2s;
        }

        .time-btn:active {
            transform: scale(0.95);
        }

        /* Simula hover para desktop, pero en kiosko es mejor active */
        .time-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .no-shows {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .no-shows i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</body>

</html>