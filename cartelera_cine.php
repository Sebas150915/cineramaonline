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
            p.duracion, p.trailer as peli_trailer, g.nombre as genero_nombre,
            c.codigo as censura_codigo,
            f.id as funcion_id, f.fecha, f.id_sala, 
            h.hora, s.nombre as sala_nombre
        FROM tbl_funciones f
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        LEFT JOIN tbl_censura c ON p.censura = c.id
        LEFT JOIN tbl_genero g ON p.genero = g.id
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
                    'trailer' => $row['peli_trailer'] ?? '', // Added alias check
                    'genero' => $row['genero_nombre'],
                    'censura' => $row['censura_codigo']
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

<div class="page-dark" style="padding: 60px 0; min-height: 80vh;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <h1 class="section-title" style="margin-bottom: 15px;"><?php echo htmlspecialchars($cine['nombre']); ?></h1>
            <p style="color: var(--text-secondary); font-size: 1.1rem;">
                <i class="fas fa-map-marker-alt" style="color: var(--cinerama-red);"></i>
                <?php echo htmlspecialchars($cine['direccion']); ?>
            </p>
        </div>

        <?php
        $unique_dates = [];
        foreach ($cartelera as $p) {
            foreach (array_keys($p['fechas']) as $f) {
                $unique_dates[$f] = $f;
            }
        }
        ksort($unique_dates);
        ?>

        <?php if (empty($cartelera)): ?>
            <div style="text-align: center; padding: 100px 0; background: rgba(255,255,255,0.05); border-radius: 20px;">
                <i class="fas fa-calendar-times" style="font-size: 60px; color: #444; margin-bottom: 20px;"></i>
                <h3 style="color: white;">Lo sentimos</h3>
                <p style="color: #888;">No hay funciones programadas en este cine por el momento.</p>
                <a href="index.php" class="btn-premium btn-premium-red" style="display: inline-flex; width: auto; margin-top: 20px; padding: 12px 30px;">
                    Ver otros cines
                </a>
            </div>
        <?php else: ?>

            <!-- Date Selector Premium -->
            <div class="date-selector" style="justify-content: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 50px;">
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
                    <div class="schedule-card-premium" data-fechas="<?php echo implode(',', array_keys($peli['fechas'])); ?>">
                        <div class="schedule-card-inner">
                            <div class="schedule-poster-premium">
                                <?php
                                $poster = !empty($peli['info']['img']) ? UPLOADS_URL . 'peliculas/' . $peli['info']['img'] : 'https://via.placeholder.com/400x600?text=Sin+Poster';
                                ?>
                                <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($peli['info']['nombre']); ?>">
                                <?php if ($peli['info']['censura']): ?>
                                    <div class="rating-badge"><?php echo htmlspecialchars($peli['info']['censura']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="schedule-content-premium">
                                <h2 class="movie-title-premium" style="font-size: 1.8rem; margin-bottom: 10px;"><?php echo htmlspecialchars($peli['info']['nombre']); ?></h2>
                                <div class="movie-meta-premium" style="margin-bottom: 25px;">
                                    <span><i class="far fa-clock"></i> <?php echo substr($peli['info']['duracion'], 0, 5); ?></span>
                                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($peli['info']['genero']); ?></span>
                                    <span><i class="fas fa-video"></i> 2D</span>
                                </div>

                                <div class="dates-container">
                                    <?php foreach ($peli['fechas'] as $fecha => $funciones): ?>
                                        <div class="date-block-premium" data-date="<?php echo $fecha; ?>">
                                            <div class="times-premium-grid">
                                                <?php foreach ($funciones as $func): ?>
                                                    <a href="compra_asientos.php?id_funcion=<?php echo $func['id']; ?>" class="time-btn-premium">
                                                        <span class="time"><?php echo $func['hora']; ?></span>
                                                        <small class="sala-name"><?php echo htmlspecialchars($func['sala']); ?></small>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div style="margin-top: auto; padding-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                                    <?php if (!empty($peli['info']['trailer'])): ?>
                                        <div class="btn-premium btn-premium-outline" data-trailer="<?php echo $peli['info']['trailer']; ?>" style="width: auto; padding: 8px 20px; font-size: 0.85rem; cursor: pointer;">
                                            <i class="fab fa-youtube"></i> TRAILER
                                        </div>
                                    <?php endif; ?>
                                    <a href="pelicula.php?id=<?php echo $peli['info']['id']; ?>" style="color: var(--cinerama-red); text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                                        MÁS INFORMACIÓN <i class="fas fa-chevron-right" style="font-size: 0.8rem;"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<style>
    .date-selector {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        padding: 10px 0 25px;
        scrollbar-width: thin;
        scrollbar-color: var(--cinerama-red) transparent;
    }

    .date-tab {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #888;
        padding: 12px 20px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 90px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .date-tab.active {
        background: var(--gradient-primary);
        border-color: transparent;
        color: white;
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(220, 20, 60, 0.3);
    }

    .date-tab:hover:not(.active) {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
        color: white;
    }

    .date-tab .day-name {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .date-tab .day-num {
        font-size: 24px;
        font-weight: 800;
        font-family: 'Poppins', sans-serif;
    }

    .schedule-list {
        display: flex;
        flex-direction: column;
        gap: 40px;
    }

    .schedule-card-premium {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.3s ease;
    }

    .schedule-card-premium:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(220, 20, 60, 0.3);
        transform: translateX(5px);
    }

    .schedule-card-inner {
        display: flex;
    }

    .schedule-poster-premium {
        width: 200px;
        aspect-ratio: 2/3;
        flex-shrink: 0;
        position: relative;
    }

    .schedule-poster-premium img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .schedule-content-premium {
        padding: 35px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .times-premium-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .time-btn-premium {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none !important;
        text-align: center;
        min-width: 100px;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
    }

    .time-btn-premium:hover {
        background: var(--cinerama-red);
        border-color: var(--cinerama-red);
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(220, 20, 60, 0.4);
    }

    .time-btn-premium .time {
        font-size: 16px;
        font-weight: 700;
    }

    .time-btn-premium .sala-name {
        font-size: 10px;
        opacity: 0.6;
        margin-top: 2px;
        text-transform: uppercase;
    }

    @media (max-width: 768px) {
        .schedule-card-inner {
            flex-direction: column;
        }

        .schedule-poster-premium {
            width: 100%;
            height: 350px;
        }

        .schedule-content-premium {
            padding: 25px;
        }
    }
</style>

<script>
    function filterDate(date, tabElement) {
        document.querySelectorAll('.date-tab').forEach(el => el.classList.remove('active'));
        if (tabElement) tabElement.classList.add('active');

        document.querySelectorAll('.schedule-card-premium').forEach(card => {
            const movieDates = card.dataset.fechas.split(',');
            const dateBlocks = card.querySelectorAll('.date-block-premium');

            if (movieDates.includes(date)) {
                card.style.display = 'block';
                dateBlocks.forEach(block => {
                    block.style.display = (block.dataset.date === date) ? 'block' : 'none';
                });
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const firstTab = document.querySelector('.date-tab');
        if (firstTab) firstTab.click();
    });
</script>

<?php include 'includes/footer_front.php'; ?>