<?php
require_once 'includes/front_config.php';

$page_title = "Próximos Estrenos";

// LOGIC: Estrenos are movies with release date in the future
try {
    $stmt = $db->prepare("
        SELECT * FROM tbl_pelicula 
        WHERE estado = '1' 
        AND fecha_estreno > CURRENT_DATE
        ORDER BY fecha_estreno ASC
    ");
    $stmt->execute();
    $estrenos = $stmt->fetchAll();
} catch (PDOException $e) {
    $estrenos = [];
}

include 'includes/header_front.php';
?>

<div class="container" style="padding-top: 40px;">
    <h2 class="section-title">Próximos Estrenos</h2>
    <p style="text-align: center; color: #777; margin-bottom: 30px;">
        Disfruta de lo que se viene muy pronto en Cinerama.
    </p>

    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 30px;">
        <?php foreach ($estrenos as $peli): ?>
            <div class="card" style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s;">
                <?php
                $poster = !empty($peli['img']) ? UPLOADS_URL . 'peliculas/' . $peli['img'] : 'https://via.placeholder.com/400x600?text=Proximamente';
                ?>
                <div style="position: relative; overflow: hidden;">
                    <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($peli['nombre']); ?>" style="width: 100%; height: 320px; object-fit: cover;">
                    <div style="position: absolute; top: 10px; right: 10px; background: #e50914; color: #fff; padding: 4px 10px; font-size: 12px; font-weight: bold; border-radius: 4px;">
                        <?php echo date('d M', strtotime($peli['fecha_estreno'])); ?>
                    </div>
                </div>

                <div class="card-info" style="padding: 15px; text-align: center;">
                    <h3 style="font-size: 16px; margin: 0 0 10px; color: #333; font-weight: bold; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?php echo htmlspecialchars($peli['nombre']); ?>
                    </h3>

                    <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($peli['genero']); ?>
                    </p>

                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <?php if (!empty($peli['trailer'])): ?>
                            <a href="https://www.youtube.com/watch?v=<?php echo $peli['trailer']; ?>" target="_blank" class="btn" style="background: #333; color: white; padding: 8px 15px; font-size: 13px; text-decoration: none; border-radius: 4px;">
                                <i class="fas fa-play"></i> Trailer
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($estrenos)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                <p style="color: #666;">No hay próximos estrenos registrados por el momento.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer_front.php'; ?>

<style>
    .card:hover {
        transform: translateY(-5px);
    }
</style>