<?php
require_once 'includes/front_config.php';

$page_title = "Cartelera General";

// Obtener películas que tengan funciones activas (future-proof: filtrar por fecha >= hoy)
try {
    $stmt = $db->prepare("
        SELECT DISTINCT p.* 
        FROM tbl_pelicula p
        JOIN tbl_funciones f ON p.id = f.id_pelicula
        WHERE p.estado = '1' 
        AND f.estado = '1' 
        AND f.fecha >= CURRENT_DATE
        ORDER BY p.fecha_estreno DESC, p.nombre ASC
    ");
    $stmt->execute();
    $peliculas = $stmt->fetchAll();
} catch (PDOException $e) {
    echo $e->getMessage();
    $peliculas = [];
}

include 'includes/header_front.php';
include 'includes/slider_front.php';
?>

<div class="container">
    <h2 class="section-title">En Cartelera</h2>

    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
        <?php foreach ($peliculas as $peli): ?>
            <div class="card">
                <?php
                $poster = !empty($peli['img']) ? UPLOADS_URL . 'peliculas/' . $peli['img'] : 'https://via.placeholder.com/400x600?text=Sin+Poster';
                ?>
                <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($peli['nombre']); ?>" class="card-img" style="height: 320px;">

                <div class="card-info" style="text-align: center;">
                    <h3 class="card-title" style="font-size: 16px; min-height: 40px;"><?php echo htmlspecialchars($peli['nombre']); ?></h3>

                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                        <?php if (!empty($peli['trailer'])): ?>
                            <a href="https://www.youtube.com/watch?v=<?php echo $peli['trailer']; ?>" target="_blank" class="btn" style="background: #333; font-size: 12px; padding: 8px 12px;">
                                Trailer
                            </a>
                        <?php endif; ?>
                        <!-- Link a detalle (futuro) o filtro por esta pelicula -->
                        <a href="pelicula.php?id=<?php echo $peli['id']; ?>" class="btn" style="font-size: 12px; padding: 8px 12px;">Horarios</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($peliculas)): ?>
            <p style="text-align: center; color: #666; width: 100%;">No hay películas en cartelera por el momento.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer_front.php'; ?>