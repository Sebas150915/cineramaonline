<?php
require_once 'includes/front_config.php';

$cine_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($cine_id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener info del cine
try {
    $stmt = $db->prepare("SELECT * FROM tbl_locales WHERE id = ? AND estado = '1'");
    $stmt->execute([$cine_id]);
    $cine = $stmt->fetch();

    if (!$cine) {
        die("Cine no encontrado");
    }

    // Obtener funciones detalladas para este cine (Película + Función + Hora)
    $stmtFun = $db->prepare("
        SELECT 
            p.id as peli_id, p.nombre as peli_nombre, p.img as peli_img, 
            p.duracion, p.genero,
            f.id as funcion_id, f.fecha, f.id_sala, 
            h.hora, s.nombre as sala_nombre
        FROM tbl_funciones f
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        JOIN tbl_hora h ON f.id_hora = h.id
        JOIN tbl_sala s ON f.id_sala = s.id
        WHERE s.local = ?
        AND f.fecha >= CURRENT_DATE
        AND f.estado = '1'
        AND p.estado = '1'
        ORDER BY p.nombre ASC, f.fecha ASC, h.hora ASC
    ");
    $stmtFun->execute([$cine_id]);
    $rows = $stmtFun->fetchAll();

    // Agrupar resultados
    $cartelera = [];
    foreach ($rows as $row) {
        $pId = $row['peli_id'];

        if (!isset($cartelera[$pId])) {
            $cartelera[$pId] = [
                'info' => [
                    'id' => $row['peli_id'],
                    'nombre' => $row['peli_nombre'],
                    'img' => $row['peli_img'],
                    'duracion' => $row['duracion'],
                    'genero' => $row['genero']
                ],
                'fechas' => []
            ];
        }

        $fecha = $row['fecha'];
        if (!isset($cartelera[$pId]['fechas'][$fecha])) {
            $cartelera[$pId]['fechas'][$fecha] = [];
        }

        $cartelera[$pId]['fechas'][$fecha][] = [
            'id' => $row['funcion_id'],
            'hora' => date('h:i A', strtotime($row['hora'])),
            'sala' => $row['sala_nombre']
        ];
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$page_title = "Cartelera - " . $cine['nombre'];
include 'includes/header_front.php';
include 'includes/slider_front.php';
?>

<div class="container">
    <div style="text-align: center; margin-bottom: 40px;">
        <h2 class="section-title" style="margin-bottom: 10px;"><?php echo htmlspecialchars($cine['nombre']); ?></h2>
        <p style="color: #aaa;"><?php echo htmlspecialchars($cine['direccion']); ?></p>
    </div>

    <?php
    // Extract unique dates for the filter
    $unique_dates = [];
    foreach ($cartelera as $p) {
        foreach (array_keys($p['fechas']) as $f) {
            $unique_dates[$f] = $f;
        }
    }
    ksort($unique_dates);
    ?>

    <?php if (empty($cartelera)): ?>
        <div style="text-align: center; padding: 50px; background: #111; border-radius: 10px;">
            <h3>Lo sentimos</h3>
            <p>No hay funciones programadas en este cine próximamente.</p>
            <a href="index.php" class="btn" style="margin-top: 20px;">Ver otros cines</a>
        </div>
    <?php else: ?>

        <!-- Date Filter -->
        <div class="date-selector">
            <?php $first = true; ?>
            <?php foreach ($unique_dates as $date): ?>
                <button class="date-tab <?php echo $first ? 'active' : ''; ?>"
                    onclick="filterDate('<?php echo $date; ?>', this)">
                    <span class="day-name">
                        <?php echo mb_strtoupper(strftime('%a', strtotime($date))); ?>
                    </span>
                    <span class="day-num">
                        <?php echo date('d', strtotime($date)); ?>
                    </span>
                </button>
                <?php $first = false; ?>
            <?php endforeach; ?>
        </div>

        <div class="schedule-list">
            <?php foreach ($cartelera as $peli): ?>
                <!-- Wrapper for filtering -->
                <div class="schedule-card movie-card" data-fechas="<?php echo implode(',', array_keys($peli['fechas'])); ?>">

                    <!-- Imagen -->
                    <div class="schedule-poster">
                        <?php
                        $poster = !empty($peli['info']['img']) ? UPLOADS_URL . 'peliculas/' . $peli['info']['img'] : 'https://via.placeholder.com/400x600?text=Sin+Poster';
                        ?>
                        <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($peli['info']['nombre']); ?>">
                    </div>

                    <!-- Info y Horarios -->
                    <div class="schedule-content">
                        <h2 class="movie-title"><?php echo htmlspecialchars($peli['info']['nombre']); ?></h2>
                        <div class="movie-meta">
                            <span><?php echo htmlspecialchars($peli['info']['duracion']); ?></span> |
                            <span><?php echo htmlspecialchars($peli['info']['genero']); ?></span>
                        </div>

                        <div class="dates-container">
                            <?php foreach ($peli['fechas'] as $fecha => $funciones): ?>
                                <div class="date-block" data-date="<?php echo $fecha; ?>">
                                    <!-- Header hidden in day view for cleaner look, or optional -->
                                    <!-- 
                                    <div class="date-header">
                                        <?php echo mb_strtoupper(strftime('%A %d %b', strtotime($fecha))); ?>
                                    </div> 
                                    -->
                                    <div class="times-grid">
                                        <?php foreach ($funciones as $func): ?>
                                            <a href="compra_asientos.php?id_funcion=<?php echo $func['id']; ?>" class="time-btn">
                                                <span class="time"><?php echo $func['hora']; ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<style>
    /* Date Selector Params */
    .date-selector {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        padding-bottom: 20px;
        margin-bottom: 30px;
        border-bottom: 1px solid #333;
    }

    .date-tab {
        background: transparent;
        border: 2px solid #555;
        color: #aaa;
        padding: 10px 20px;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 80px;
        transition: 0.3s;
    }

    .date-tab.active,
    .date-tab:hover {
        border-color: #e50914;
        color: #fff;
        background: #e5091420;
    }

    .date-tab .day-name {
        font-size: 12px;
        font-weight: bold;
    }

    .date-tab .day-num {
        font-size: 24px;
        font-weight: bold;
    }

    /* List Styles */
    .schedule-list {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    .schedule-card {
        background: #fff;
        /* Using white bg as per previous preference, or distinct card style */
        /* Actually previous was #fff, let's keep it but improve spacing */
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        /* Default flex row for desktop */
        flex-direction: row;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    @media (max-width: 768px) {
        .schedule-card {
            flex-direction: column;
        }
    }

    .times-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }

    .time-btn {
        display: inline-block;
        padding: 8px 16px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #fff;
        color: #333;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .time-btn:hover {
        background-color: #e50914;
        /* Brand red */
        color: #fff;
        border-color: #e50914;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
</style>

<script>
    function filterDate(date, tabElement) {
        // 1. Update Tabs
        document.querySelectorAll('.date-tab').forEach(el => el.classList.remove('active'));
        if (tabElement) tabElement.classList.add('active');

        // 2. Filter Movies
        document.querySelectorAll('.movie-card').forEach(card => {
            const movieDates = card.dataset.fechas.split(',');
            const dateBlocks = card.querySelectorAll('.date-block');

            // Check if movie has the selected date
            if (movieDates.includes(date)) {
                card.style.display = 'flex'; // Show movie

                // Filter inner date blocks
                dateBlocks.forEach(block => {
                    if (block.dataset.date === date) {
                        block.style.display = 'block';
                    } else {
                        block.style.display = 'none';
                    }
                });
            } else {
                card.style.display = 'none'; // Hide movie completely
            }
        });
    }

    // Init with first date
    document.addEventListener('DOMContentLoaded', () => {
        const firstTab = document.querySelector('.date-tab');
        if (firstTab) {
            // Manually trigger click or call logic. 
            // We need to parse the onclick attribute or just extract date.
            // Simpler: Just click it.
            firstTab.click();
        }
    });
</script>
border-radius: 8px;
overflow: hidden;
display: flex;
box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
color: #333;
}

.schedule-poster {
width: 150px;
flex-shrink: 0;
}

.schedule-poster img {
width: 100%;
height: 100%;
object-fit: cover;
display: block;
}

.schedule-content {
padding: 20px;
flex-grow: 1;
}

.movie-title {
margin: 0 0 5px 0;
font-size: 22px;
color: #000;
}

.movie-meta {
color: #666;
font-size: 14px;
margin-bottom: 20px;
}

.dates-container {
display: flex;
flex-direction: column;
gap: 15px;
}

.date-block {
border-bottom: 1px solid #eee;
padding-bottom: 15px;
}

.date-block:last-child {
border-bottom: none;
}

.date-header {
font-weight: bold;
color: #e50914;
margin-bottom: 10px;
font-size: 14px;
text-transform: uppercase;
}

.times-grid {
display: flex;
flex-wrap: wrap;
gap: 10px;
}

.time-btn {
display: inline-block;
padding: 8px 16px;
background: #fff;
border: 1px solid #ccc;
border-radius: 4px;
text-decoration: none;
color: #333;
font-size: 14px;
transition: 0.2s;
text-align: center;
}

.time-btn:hover {
background: #e50914;
color: #fff;
border-color: #e50914;
}

.time-btn .time {
font-weight: bold;
}

@media (max-width: 600px) {
.schedule-card {
flex-direction: column;
}

.schedule-poster {
width: 100%;
height: 200px;
}

.schedule-poster img {
object-fit: cover;
}
}
</style>

<?php include 'includes/footer_front.php'; ?>