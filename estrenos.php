<?php
require_once 'includes/front_config.php';

$page_title = "Próximos Estrenos";

// LOGIC: Estrenos are movies with release date in the future
try {
    $stmt = $db->prepare("
        SELECT p.*, g.nombre as genero_nombre, c.codigo as censura_codigo
        FROM tbl_pelicula p 
        LEFT JOIN tbl_genero g ON p.genero = g.id
        LEFT JOIN tbl_censura c ON p.censura = c.id
        WHERE p.estado = '1' 
        AND p.fecha_estreno > CURRENT_DATE
        ORDER BY p.fecha_estreno ASC
    ");
    $stmt->execute();
    $estrenos = $stmt->fetchAll();
} catch (PDOException $e) {
    $estrenos = [];
}

include 'includes/header_front.php';
include 'includes/slider_front.php';
?>

<div class="page-dark" style="padding: 60px 0; min-height: 80vh;">
    <div class="container">
        <h2 class="section-title">Próximos Estrenos</h2>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 50px; font-size: 1.1rem;">
            Disfruta de lo que se viene muy pronto en Cinerama.
        </p>

        <div class="movie-grid">
            <?php foreach ($estrenos as $peli): ?>
                <div class="movie-card">
                    <div class="movie-poster-container">
                        <?php
                        $poster = !empty($peli['img']) ? UPLOADS_URL . 'peliculas/' . $peli['img'] : 'https://via.placeholder.com/400x600?text=Proximamente';
                        ?>
                        <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($peli['nombre']); ?>" class="movie-poster">

                        <div class="rating-badge" style="background: var(--cinerama-red);">
                            <?php echo date('d M', strtotime($peli['fecha_estreno'])); ?>
                        </div>

                        <?php if ($peli['censura_codigo']): ?>
                            <div class="duration-badge" style="top: auto; bottom: 15px; right: 15px; left: auto; background: rgba(0,0,0,0.8);">
                                <?php echo htmlspecialchars($peli['censura_codigo']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="movie-info-premium">
                        <div>
                            <h3 class="movie-title-premium"><?php echo htmlspecialchars($peli['nombre']); ?></h3>
                            <div class="movie-meta-premium">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($peli['genero_nombre'] ?? 'General'); ?></span>
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($peli['fecha_estreno'])); ?></span>
                            </div>
                        </div>

                        <div class="movie-actions-premium">
                            <?php if (!empty($peli['trailer'])): ?>
                                <div class="btn-premium btn-premium-outline" data-trailer="<?php echo $peli['trailer']; ?>" style="width: 100%; cursor: pointer;">
                                    <i class="fab fa-youtube"></i> Ver Trailer
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($estrenos)): ?>
            <div style="text-align: center; color: #666; padding: 100px 0;">
                <i class="fas fa-calendar-alt" style="font-size: 60px; margin-bottom: 20px; opacity: 0.3;"></i>
                <p style="font-size: 1.2rem;">No hay próximos estrenos registrados por el momento.</p>
                <a href="cartelera.php" class="btn-premium btn-premium-red" style="display: inline-flex; margin-top: 20px; width: auto; padding: 12px 30px;">
                    Ver Películas en Cartelera
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer_front.php'; ?>