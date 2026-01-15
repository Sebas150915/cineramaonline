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
<section id="cines" class="section-premium" style="background: var(--bg-primary); padding-top: 100px;">
    <div class="container">
        <div class="section-header-premium" style="text-align: left; margin-bottom: 50px;">
            <span class="section-label" style="text-align: left; margin-left: 0;">Ubicaciones</span>
            <h2 class="section-title-premium" style="text-align: left; font-size: 2.5rem; margin-top: 10px;">Nuestros Cines</h2>
            <p class="section-desc-premium" style="text-align: left; margin-left: 0; max-width: 600px;">Encuentra tu CINERAMA más cercano y disfruta de la mejor experiencia cinematográfica en Perú con la mejor tecnología.</p>
        </div>

        <div class="lovable-cinema-stack">
            <?php foreach ($cines as $index => $cine): ?>
                <div class="cinema-item-premium" onclick="window.location.href='cartelera_cine.php?id=<?php echo $cine['id']; ?>'">
                    <div class="cinema-visual-wrap">
                        <?php
                        $cine_img = !empty($cine['img']) ? UPLOADS_URL . 'cines/' . $cine['img'] : 'https://www.cinerama.com.pe/_admin/assets/images/cines/pacifico.jpg';
                        ?>
                        <img src="<?php echo $cine_img; ?>" alt="<?php echo htmlspecialchars($cine['nombre']); ?>" class="cinema-stack-img">
                        <div class="cinema-stack-overlay"></div>
                    </div>

                    <div class="cinema-stack-info">
                        <h3 class="cinema-stack-name"><?php echo htmlspecialchars($cine['nombre']); ?></h3>
                        <div class="cinema-stack-addr">
                            <i class="fas fa-map-marker-alt" style="color: var(--cinerama-red);"></i>
                            <?php echo htmlspecialchars($cine['direccion']); ?>
                        </div>
                        <a href="cartelera_cine.php?id=<?php echo $cine['id']; ?>" class="cinema-stack-link">
                            VER CARTELERA <i class="fas fa-arrow-right"></i>
                        </a>
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
    /* Specific Lovable Style for Cinema Stack */
    .lovable-cinema-stack {
        display: flex;
        flex-direction: column;
        gap: 40px;
    }

    .cinema-item-premium {
        position: relative;
        cursor: pointer;
        transition: all 0.4s ease;
    }

    .cinema-visual-wrap {
        position: relative;
        width: 100%;
        height: 480px;
        /* Large cinematic height */
        border-radius: 4px;
        overflow: hidden;
        background: #000;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
    }

    .cinema-stack-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .cinema-item-premium:hover .cinema-stack-img {
        transform: scale(1.05);
    }

    .cinema-stack-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, transparent 40%);
        z-index: 2;
    }

    .cinema-stack-info {
        margin-top: 20px;
    }

    .cinema-stack-name {
        font-family: var(--font-title);
        font-size: 1.8rem;
        color: white;
        margin-bottom: 8px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .cinema-stack-addr {
        color: var(--cinerama-red);
        font-weight: 700;
        font-size: 0.9rem;
        margin-bottom: 12px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .cinema-stack-link {
        color: #3b82f6;
        /* Modern Blue link from reference */
        font-weight: 800;
        font-size: 0.85rem;
        text-decoration: none !important;
        display: flex;
        align-items: center;
        gap: 8px;
        letter-spacing: 0.5px;
        transition: color 0.3s;
    }

    .cinema-stack-link:hover {
        color: white;
    }

    @media (max-width: 768px) {
        .cinema-visual-wrap {
            height: 300px;
        }

        .cinema-stack-name {
            font-size: 1.4rem;
        }
    }
</style>

<?php include 'includes/footer_front.php'; ?>