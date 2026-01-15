<?php
require_once 'includes/front_config.php';

$page_title = "Cinerama - La Mejor Experiencia de Cine";
include 'includes/header_front.php';
include 'includes/slider_front.php';

// Obtener locales/cines
try {
    $stmt = $db->prepare("SELECT * FROM tbl_locales WHERE estado = '1' ORDER BY orden ASC, nombre ASC");
    $stmt->execute();
    $cines = $stmt->fetchAll();
} catch (PDOException $e) {
    $cines = [];
}
?>

<!-- Ubicaciones / Nuestros Cines Section -->
<section id="cines" class="section-premium" style="background: var(--bg-primary); padding: 100px 0;">
    <div class="container">
        <div class="section-header-premium" style="text-align: center; margin-bottom: 60px;">
            <span class="section-label" style="text-align: center; color: var(--cinerama-red); font-weight: 800; letter-spacing: 2px;">UBICACIONES</span>
            <h2 class="section-title-premium" style="text-align: center; font-size: 3.5rem; margin-top: 10px; font-family: 'Bebas Neue', sans-serif;">NUESTROS CINES</h2>
            <p class="section-desc-premium" style="text-align: center; margin: 20px auto 0; max-width: 700px; color: #b3b3b3; font-size: 1.1rem;">
                Encuentra tu CINERAMA más cercano y disfruta de la mejor experiencia cinematográfica en Perú.
            </p>
        </div>

        <div class="cinema-grid-premium">
            <?php foreach ($cines as $index => $cine): ?>
                <div class="cinema-card-vertical" onclick="window.location.href='cartelera_cine.php?id=<?php echo $cine['id']; ?>'">
                    <div class="cinema-card-img-wrap">
                        <?php
                        $cine_img = !empty($cine['img']) ? UPLOADS_URL . 'cines/' . $cine['img'] : 'https://www.cinerama.com.pe/_admin/assets/images/cines/pacifico.jpg';
                        ?>
                        <img src="<?php echo $cine_img; ?>" alt="<?php echo htmlspecialchars($cine['nombre']); ?>" class="cinema-v-img">
                        <div class="cinema-v-overlay"></div>
                        <?php if ($index % 3 == 0): ?>
                            <span class="featured-badge-new">
                                <i class="fas fa-star"></i> Destacado
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="cinema-card-body">
                        <h3 class="cinema-v-name"><?php echo htmlspecialchars($cine['nombre']); ?></h3>
                        <div class="cinema-v-addr">
                            <i class="fas fa-map-marker-alt" style="color: var(--cinerama-red);"></i>
                            <span><?php echo htmlspecialchars($cine['direccion']); ?></span>
                        </div>
                        <div class="cinema-v-actions">
                            <a href="cartelera_cine.php?id=<?php echo $cine['id']; ?>" class="btn-ver-cartelera">
                                Ver Cartelera <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($cines)): ?>
            <div style="text-align: center; padding: 100px 20px; color: #444;">
                <p>No hay cines disponibles en este momento.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    /* 3-Column Grid Layout */
    .cinema-grid-premium {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
    }

    .cinema-card-vertical {
        background: #121212;
        border-radius: 20px;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.4s ease;
        border: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        flex-direction: column;
    }

    .cinema-card-vertical:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
        border-color: rgba(220, 20, 60, 0.3);
    }

    .cinema-card-img-wrap {
        position: relative;
        width: 100%;
        height: 240px;
        overflow: hidden;
    }

    .cinema-v-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.8s ease;
    }

    .cinema-card-vertical:hover .cinema-v-img {
        transform: scale(1.1);
    }

    .cinema-v-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.4) 0%, transparent 100%);
        z-index: 1;
    }

    .featured-badge-new {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #f1c40f;
        color: #000;
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    .cinema-card-body {
        padding: 25px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .cinema-v-name {
        font-family: var(--font-title);
        font-size: 1.5rem;
        color: white;
        margin-bottom: 12px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .cinema-v-addr {
        color: #888;
        font-size: 0.9rem;
        margin-bottom: 25px;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        gap: 8px;
        line-height: 1.4;
        max-width: 90%;
    }

    .cinema-v-actions {
        width: 100%;
        margin-top: auto;
    }

    .btn-ver-cartelera {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 14px;
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        text-decoration: none !important;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .cinema-card-vertical:hover .btn-ver-cartelera {
        background: white;
        color: black;
        border-color: white;
    }

    @media (max-width: 768px) {
        .cinema-grid-premium {
            grid-template-columns: 1fr;
        }

        .section-title-premium {
            font-size: 2.5rem;
        }
    }
</style>

<?php include 'includes/footer_front.php'; ?>