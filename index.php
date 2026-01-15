<?php
require_once 'includes/front_config.php';

$page_title = "Nuestros Cines";

// Obtener cines activos
try {
    $cines = $db->query("SELECT * FROM tbl_locales WHERE estado = '1' ORDER BY orden ASC, nombre ASC")->fetchAll();
} catch (PDOException $e) {
    $cines = [];
}

include 'includes/header_front.php';
include 'includes/slider_front.php';
?>

<div class="container">
    <h2 class="section-title" style="text-align: center; margin-bottom: 40px;">Nuestros Cines</h2>

    <div class="cinema-list">
        <?php foreach ($cines as $cine): ?>
            <div class="cinema-card">
                <div class="cinema-header">
                    <?php echo htmlspecialchars($cine['nombre']); ?>
                </div>

                <div class="cinema-body">
                    <?php
                    $img_path = !empty($cine['img']) ? UPLOADS_URL . 'cines/' . $cine['img'] : 'https://via.placeholder.com/800x500/000000/333333?text=Cinerama+Movie';
                    ?>
                    <div class="cinema-img-container">
                        <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($cine['nombre']); ?>" class="cinema-img">
                    </div>

                    <div class="cinema-info">
                        <div class="cinema-address">
                            <?php echo htmlspecialchars($cine['direccion']); ?>
                        </div>
                        <a href="cartelera_cine.php?id=<?php echo $cine['id']; ?>" class="btn-view-cartelera">
                            VER CARTELERA <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($cines)): ?>
            <div style="text-align: center; padding: 60px; color: var(--text-muted);">
                <i class="fas fa-film" style="font-size: 50px; margin-bottom: 20px; opacity: 0.5;"></i>
                <p>No hay cines disponibles en este momento.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer_front.php'; ?>