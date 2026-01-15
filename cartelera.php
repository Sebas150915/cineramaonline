<?php
require_once 'includes/front_config.php';

$page_title = "Cartelera General";

// Obtener películas que tengan funciones activas con ratings y géneros
try {
    $stmt = $db->prepare("
        SELECT DISTINCT p.*, c.codigo as censura_codigo, g.nombre as genero_nombre
        FROM tbl_pelicula p
        JOIN tbl_funciones f ON p.id = f.id_pelicula
        LEFT JOIN tbl_censura c ON p.censura = c.id
        LEFT JOIN tbl_genero g ON p.genero = g.id
        WHERE p.estado = '1' 
        AND f.estado = '1' 
        AND f.fecha >= CURRENT_DATE
        ORDER BY p.fecha_estreno DESC, p.nombre ASC
    ");
    $stmt->execute();
    $peliculas = $stmt->fetchAll();
} catch (PDOException $e) {
    $peliculas = [];
}

include 'includes/header_front.php';
include 'includes/slider_front.php';
?>

<div class="page-dark" style="padding: 60px 0; min-height: 80vh;">
    <div class="container">
        <h2 class="section-title">En Cartelera</h2>

        <div class="movie-grid">
            <?php foreach ($peliculas as $peli): ?>
                <div class="movie-card" onclick="window.location.href='pelicula.php?id=<?php echo $peli['id']; ?>'">
                    <div class="movie-poster-container">
                        <?php
                        $poster = !empty($peli['img']) ? UPLOADS_URL . 'peliculas/' . $peli['img'] : 'https://via.placeholder.com/400x600?text=Sin+Poster';
                        ?>
                        <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($peli['nombre']); ?>" class="movie-poster">

                        <?php if ($peli['censura_codigo']): ?>
                            <div class="rating-badge"><?php echo htmlspecialchars($peli['censura_codigo']); ?></div>
                        <?php endif; ?>

                        <?php if ($peli['duracion']): ?>
                            <div class="duration-badge">
                                <i class="far fa-clock"></i> <?php echo substr($peli['duracion'], 0, 5); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Label de Estreno detectando si la fecha es reciente (ej. ultimos 15 dias) -->
                        <?php
                        $fecha_estreno = strtotime($peli['fecha_estreno']);
                        $hace_15_dias = strtotime('-15 days');
                        if ($fecha_estreno >= $hace_15_dias): ?>
                            <div class="estreno-label">Estreno</div>
                        <?php endif; ?>
                    </div>

                    <div class="movie-info-premium">
                        <div>
                            <h3 class="movie-title-premium"><?php echo htmlspecialchars($peli['nombre']); ?></h3>
                            <div class="movie-meta-premium">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($peli['genero_nombre'] ?? 'General'); ?></span>
                                <span><i class="fas fa-video"></i> 2D</span>
                            </div>
                        </div>

                        <div class="movie-actions-premium" onclick="event.stopPropagation();">
                            <?php if (!empty($peli['trailer'])): ?>
                                <div class="btn-premium btn-premium-outline" data-trailer="<?php echo $peli['trailer']; ?>" style="cursor: pointer;">
                                    <i class="fab fa-youtube"></i> Trailer
                                </div>
                            <?php endif; ?>
                            <a href="pelicula.php?id=<?php echo $peli['id']; ?>" class="btn-premium btn-premium-red">
                                <i class="fas fa-ticket-alt"></i> Horarios
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($peliculas)): ?>
            <div style="text-align: center; color: #666; padding: 100px 0;">
                <i class="fas fa-film" style="font-size: 60px; margin-bottom: 20px; opacity: 0.3;"></i>
                <p style="font-size: 1.2rem;">No hay películas en cartelera por el momento.</p>
                <a href="index.php" class="btn-premium btn-premium-red" style="display: inline-flex; margin-top: 20px; width: auto; padding: 12px 30px;">
                    Ver Cines Disponibles
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer_front.php'; ?>