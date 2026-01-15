<?php
require_once 'includes/front_config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: cartelera.php');
    exit;
}

// Obtener detalles de la película con género y censura
try {
    $stmt = $db->prepare("
        SELECT p.*, g.nombre as genero_nombre, c.codigo as censura_codigo
        FROM tbl_pelicula p 
        LEFT JOIN tbl_genero g ON p.genero = g.id
        LEFT JOIN tbl_censura c ON p.censura = c.id
        WHERE p.id = ? AND p.estado = '1'
    ");
    $stmt->execute([$id]);
    $pelicula = $stmt->fetch();

    if (!$pelicula) {
        die("Película no encontrada.");
    }

    // Obtener otras películas activas para el carrusel inferior
    $stmtOthers = $db->prepare("
        SELECT DISTINCT p.* 
        FROM tbl_pelicula p
        JOIN tbl_funciones f ON p.id = f.id_pelicula
        WHERE p.id != ? AND p.estado = '1' AND f.fecha >= CURRENT_DATE
        LIMIT 6
    ");
    $stmtOthers->execute([$id]);
    $otrasPeliculas = $stmtOthers->fetchAll();

    // Obtener funciones detalladas para los próximos días
    $stmtFunc = $db->prepare("
        SELECT f.id, f.fecha, f.id_sala, s.nombre as sala, l.nombre as cine, h.hora, l.direccion
        FROM tbl_funciones f
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_locales l ON s.local = l.id
        JOIN tbl_hora h ON f.id_hora = h.id
        WHERE f.id_pelicula = ? 
        AND f.estado = '1'
        AND f.fecha >= CURRENT_DATE
        ORDER BY f.fecha ASC, l.nombre ASC, h.hora ASC
    ");
    $stmtFunc->execute([$id]);
    $all_funciones = $stmtFunc->fetchAll();

    // Agrupar funciones por Fecha y luego por Cine
    $funcionesAgrupadas = [];
    $unique_dates = [];

    foreach ($all_funciones as $func) {
        $fecha = $func['fecha'];
        $cine = $func['cine'];

        $unique_dates[$fecha] = $fecha;

        if (!isset($funcionesAgrupadas[$fecha])) {
            $funcionesAgrupadas[$fecha] = [];
        }

        if (!isset($funcionesAgrupadas[$fecha][$cine])) {
            $funcionesAgrupadas[$fecha][$cine] = [
                'direccion' => $func['direccion'],
                'horarios' => []
            ];
        }

        $funcionesAgrupadas[$fecha][$cine]['horarios'][] = $func;
    }
    ksort($unique_dates);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$page_title = $pelicula['nombre'];
include 'includes/header_front.php';
?>

<div class="page-dark" style="padding: 40px 0; min-height: 80vh;">
    <div class="container">

        <!-- Top Row: Date Selection and Availability Legend -->
        <div style="display: flex; gap: 40px; margin-bottom: 40px; align-items: flex-start;">
            <!-- Left Side Empty for Alignment with Poster -->
            <div style="flex: 1; min-width: 320px;"></div>

            <!-- Right Side: Filters and Legend -->
            <div style="flex: 2; min-width: 350px;">
                <!-- Date Selector Cinemark Style -->
                <div class="date-selector cinemark-dates" style="margin-bottom: 30px; justify-content: flex-start; border: none; overflow: visible;">
                    <?php
                    $first = true;
                    foreach ($unique_dates as $date):
                        $is_today = ($date == date('Y-m-d'));
                    ?>
                        <button class="date-tab-cinemark <?php echo $first ? 'active' : ''; ?>"
                            onclick="filterDetailedDate('<?php echo $date; ?>', this)">
                            <span class="day-large"><?php echo $is_today ? 'HOY' : mb_strtoupper(strftime('%a', strtotime($date))); ?></span>
                            <?php if (!$is_today): ?>
                                <span class="day-small"><?php echo date('d/M', strtotime($date)); ?></span>
                            <?php endif; ?>
                        </button>
                    <?php
                        $first = false;
                    endforeach;
                    ?>
                </div>

                <!-- Availability Legend -->
                <div class="availability-legend" style="margin-bottom: 30px;">
                    <span class="legend-title">DISPONIBILIDAD DE ASIENTOS</span>
                    <div class="legend-items">
                        <span class="legend-item"><i class="fas fa-couch chair-alta"></i> Alta</span>
                        <span class="legend-item"><i class="fas fa-couch chair-media"></i> Media</span>
                        <span class="legend-item"><i class="fas fa-couch chair-baja"></i> Baja</span>
                        <span class="legend-item"><i class="fas fa-couch chair-lleno"></i> Lleno</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 50px; flex-wrap: wrap;">
            <!-- Left Column: Poster & Meta -->
            <div style="flex: 1; min-width: 320px;">
                <div style="position: relative; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.8); border: 1px solid rgba(255,255,255,0.05); margin-bottom: 25px;">
                    <img src="<?php echo !empty($pelicula['img']) ? UPLOADS_URL . 'peliculas/' . $pelicula['img'] : 'https://via.placeholder.com/400x600'; ?>"
                        alt="Poster" style="width: 100%; display: block;">

                    <!-- Labels on Poster -->
                    <div style="position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); color: white; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: 600;">
                        <?php echo substr($pelicula['duracion'], 0, 5); ?>
                    </div>
                    <?php if ($pelicula['censura_codigo']): ?>
                        <div class="rating-badge" style="top: 15px; right: 15px; background: #333; list-style: none; font-size: 14px;">
                            <?php echo htmlspecialchars($pelicula['censura_codigo']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Trailer Overlay Play Button -->
                    <?php if (!empty($pelicula['trailer'])): ?>
                        <div class="trailer-play-overlay" data-trailer="<?php echo $pelicula['trailer']; ?>" style="cursor: pointer;">
                            <i class="fas fa-play"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Genre Pill -->
                <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                    <span class="movie-pill"><i class="fas fa-smile"></i> <?php echo htmlspecialchars($pelicula['genero_nombre'] ?? 'General'); ?></span>
                </div>

                <!-- Synopsis -->
                <p style="color: #b3b3b3; line-height: 1.6; font-size: 0.95rem; margin-bottom: 40px;">
                    <?php echo nl2br(htmlspecialchars($pelicula['sinopsis'])); ?>
                </p>

                <!-- Detailed Specs List -->
                <div class="movie-specs-list">
                    <div class="spec-item">
                        <span class="spec-label">FORMATOS DISPONIBLES</span>
                        <span class="spec-value">2D</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">DURACIÓN</span>
                        <span class="spec-value"><?php echo substr($pelicula['duracion'], 0, 5); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">FECHA DE ESTRENO</span>
                        <span class="spec-value"><?php echo date('d F, Y', strtotime($pelicula['fecha_estreno'])); ?></span>
                    </div>
                    <?php if (!empty($pelicula['distribuidor'])): ?>
                        <div class="spec-item">
                            <span class="spec-label">DISTRIBUIDOR</span>
                            <span class="spec-value"><?php echo htmlspecialchars($pelicula['distribuidor']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Cast & Director -->
                <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px;">
                    <h3 class="spec-label" style="margin-bottom: 20px;">ACTORES Y DIRECTOR</h3>
                    <p style="color: white; margin-bottom: 5px; font-weight: 700; font-size: 0.95rem;">Director</p>
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;"><?php echo htmlspecialchars($pelicula['director']); ?></p>

                    <p style="color: white; margin-bottom: 5px; font-weight: 700; font-size: 0.95rem;">Actores</p>
                    <p style="color: #666; font-size: 0.9rem; line-height: 1.6;"><?php echo htmlspecialchars($pelicula['reparto']); ?></p>
                </div>
            </div>

            <!-- Right Column: Showtimes -->
            <div style="flex: 2; min-width: 350px;">
                <div class="schedule-container-detailed">
                    <?php if (empty($funcionesAgrupadas)): ?>
                        <div class="no-functions-box">
                            <p>No hay funciones programadas para esta película próximamente.</p>
                            <a href="cartelera.php" class="btn-premium btn-premium-red">Volver a Cartelera</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($funcionesAgrupadas as $fecha => $cines): ?>
                            <div class="date-group" data-date="<?php echo $fecha; ?>" style="display: none;">
                                <h3 class="cinema-main-title">CINE PRINCIPAL</h3>
                                <?php foreach ($cines as $nombreCine => $datos): ?>
                                    <div class="theater-section">
                                        <h4 class="theater-name-detailed">
                                            HORARIOS EN <span style="color: var(--cinerama-red);"><?php echo htmlspecialchars($nombreCine); ?></span> <i class="fas fa-chevron-down"></i>
                                        </h4>
                                        <p class="theater-meta-detailed">Dirección: <?php echo htmlspecialchars($datos['direccion']); ?></p>

                                        <div class="format-showtimes" style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                                            <span class="format-label">2D • Doblada</span>
                                            <div class="cinemark-times-grid">
                                                <?php foreach ($datos['horarios'] as $h): ?>
                                                    <a href="compra_asientos.php?id_funcion=<?php echo $h['id']; ?>" class="time-btn-cinemark">
                                                        <i class="fas fa-couch chair-alta" style="font-size: 10px; margin-right: 8px;"></i>
                                                        <?php echo date('h:i', strtotime($h['hora'])); ?>hs
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Cines más cercanos (Mocked as per reference) -->
                <div style="margin-top: 40px; background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
                    <div style="padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="color: white; font-size: 1.1rem; font-weight: 700; margin: 0; text-transform: uppercase;">Cines más cercanos</h3>
                        <i class="fas fa-chevron-down" style="color: #666;"></i>
                    </div>
                    <div style="padding: 20px 25px; color: #777; font-size: 0.9rem;">
                        Ver los cines que se encuentran cerca del cine seleccionado con horarios disponibles para esta película.
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Movies Section (Carousel/Grid) -->
        <?php if (!empty($otrasPeliculas)): ?>
            <div style="margin-top: 100px;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 40px;">
                    <h2 class="section-title" style="text-align: left; margin: 0; font-size: 1.8rem;">PELÍCULAS EN CARTELERA</h2>
                    <div style="display: flex; gap: 10px;">
                        <button class="carousel-nav-btn"><i class="fas fa-chevron-left"></i></button>
                        <button class="carousel-nav-btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>

                <div class="movie-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
                    <?php foreach ($otrasPeliculas as $otra): ?>
                        <div class="movie-card" onclick="window.location.href='pelicula.php?id=<?php echo $otra['id']; ?>'" style="min-height: auto;">
                            <div class="movie-poster-container" style="aspect-ratio: 2/3;">
                                <img src="<?php echo !empty($otra['img']) ? UPLOADS_URL . 'peliculas/' . $otra['img'] : 'https://via.placeholder.com/400x600'; ?>"
                                    alt="<?php echo htmlspecialchars($otra['nombre']); ?>" class="movie-poster">
                                <div class="duration-badge" style="bottom: 10px; right: 10px; top: auto; background: rgba(0,0,0,0.8);"><?php echo substr($otra['duracion'], 0, 5); ?></div>
                            </div>
                            <div class="movie-info-premium" style="padding: 15px 0;">
                                <h3 style="font-size: 0.95rem; color: white; margin: 0; font-weight: 700; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($otra['nombre']); ?></h3>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Cinemark Specific Styling */
    .cinemark-dates .date-tab-cinemark {
        background: #1a1a1a;
        border: 1px solid #333;
        color: #888;
        padding: 5px 25px;
        min-width: 100px;
        height: 65px;
        border-radius: 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-right: -1px;
        /* Overlap borders */
    }

    .cinemark-dates .date-tab-cinemark.active {
        background: white;
        border-color: white;
        color: black;
        z-index: 2;
    }

    .cinemark-dates .day-large {
        font-size: 15px;
        font-weight: 800;
    }

    .cinemark-dates .day-small {
        font-size: 11px;
        font-weight: 600;
        opacity: 0.7;
    }

    .availability-legend {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 20px;
    }

    .legend-title {
        display: block;
        color: white;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 15px;
        letter-spacing: 0.5px;
    }

    .legend-items {
        display: flex;
        gap: 20px;
    }

    .legend-item {
        font-size: 13px;
        color: #aaa;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chair-alta {
        color: #00cc99;
    }

    .chair-media {
        color: #ffcc00;
    }

    .chair-baja {
        color: #ff6666;
    }

    .chair-lleno {
        color: #666;
    }

    .trailer-play-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        background: var(--cinerama-red);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        box-shadow: 0 0 20px rgba(220, 20, 60, 0.6);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        text-decoration: none !important;
    }

    .trailer-play-overlay:hover {
        transform: translate(-50%, -50%) scale(1.15);
    }

    .movie-pill {
        background: #1a1a1a;
        color: white;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #333;
    }

    .movie-specs-list {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .spec-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding-bottom: 15px;
    }

    .spec-label {
        color: white;
        font-size: 0.85rem;
        font-weight: 800;
        letter-spacing: 1px;
    }

    .spec-value {
        color: #777;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .cinema-main-title {
        color: white;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 15px;
        letter-spacing: 1px;
    }

    .theater-section {
        margin-bottom: 40px;
    }

    .theater-name-detailed {
        color: white;
        font-size: 1.3rem;
        font-weight: 800;
        margin-bottom: 8px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .theater-meta-detailed {
        color: #777;
        font-size: 0.9rem;
        margin-bottom: 25px;
    }

    .format-label {
        display: block;
        color: #eee;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .cinemark-times-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .time-btn-cinemark {
        background: #252525;
        border: 1px solid #3a3a3a;
        color: #ddd;
        padding: 10px 18px;
        border-radius: 6px;
        text-decoration: none !important;
        font-size: 14px;
        font-weight: 700;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
    }

    .time-btn-cinemark:hover {
        background: #333;
        border-color: #555;
        transform: translateY(-2px);
    }

    .carousel-nav-btn {
        background: #222;
        border: 1px solid #333;
        color: #666;
        width: 40px;
        height: 40px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .carousel-nav-btn:hover {
        background: white;
        color: black;
        border-color: white;
    }

    .movie-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .movie-card {
        background: #1a1a1a;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.5);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .movie-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
    }

    .movie-poster-container {
        position: relative;
        width: 100%;
        padding-top: 150%;
        /* For 2:3 aspect ratio */
        overflow: hidden;
    }

    .movie-poster {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .duration-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        color: white;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
    }

    .movie-info-premium {
        padding: 15px;
        text-align: center;
    }

    .no-functions-box {
        text-align: center;
        padding: 100px 20px;
        background: #111;
        border-radius: 12px;
        color: #666;
    }

    @media (max-width: 768px) {
        .date-selector.cinemark-dates {
            justify-content: flex-start;
            overflow-x: auto;
            padding-bottom: 10px;
        }
    }
</style>

<script>
    function filterDetailedDate(date, button) {
        // Toggle tabs
        document.querySelectorAll('.date-tab-cinemark').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        // Toggle date groups
        document.querySelectorAll('.date-group').forEach(group => {
            group.style.display = (group.dataset.date === date) ? 'block' : 'none';
        });
    }

    // Auto-init first available date
    document.addEventListener('DOMContentLoaded', () => {
        const firstBtn = document.querySelector('.date-tab-cinemark');
        if (firstBtn) firstBtn.click();
    });
</script>

<?php include 'includes/footer_front.php'; ?>