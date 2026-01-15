<?php
// Obtener slides activos
try {
    $stmtSlider = $db->query("SELECT * FROM tbl_slider WHERE estado = '1' ORDER BY orden ASC");
    $slides = $stmtSlider->fetchAll();
} catch (PDOException $e) {
    $slides = [];
}

if (!empty($slides)):
?>
    <section class="premium-hero-slider">
        <div class="hero-carousel">
            <?php foreach ($slides as $index => $slide): ?>
                <?php
                $imgUrl = UPLOADS_URL . 'sliders/' . $slide['img'];
                $activeClass = ($index === 0) ? 'active' : '';
                ?>
                <div class="hero-slide <?php echo $activeClass; ?>" data-index="<?php echo $index; ?>">
                    <div class="hero-img-wrap">
                        <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($slide['titulo']); ?>" class="hero-bg-img">
                        <div class="hero-vignette"></div>
                        <div class="hero-glow-bottom"></div>
                    </div>

                    <div class="container hero-content-wrap">
                        <div class="hero-text-area">
                            <span class="hero-label-premium">EXCLUSIVO EN CINERAMA</span>
                            <h1 class="hero-title-premium"><?php echo htmlspecialchars($slide['titulo']); ?></h1>
                            <p class="hero-subtitle-premium">Vive la mejor experiencia en la pantalla gigante</p>

                            <div class="hero-actions-premium">
                                <a href="<?php echo !empty($slide['link']) ? htmlspecialchars($slide['link']) : 'cartelera.php'; ?>" class="btn-premium-red">
                                    <i class="fas fa-ticket-alt"></i> COMPRAR ENTRADAS
                                </a>
                                <button class="btn-premium-outline" data-trailer="dQw4w9WgXcQ">
                                    <i class="fas fa-play"></i> VER TRAILER
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Progress Indicators -->
        <div class="hero-indicators-premium">
            <?php foreach ($slides as $index => $slide): ?>
                <div class="indicator-bar <?php echo ($index === 0) ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)">
                    <div class="indicator-progress"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <style>
        .premium-hero-slider {
            position: relative;
            height: 90vh;
            width: 100%;
            background: #000;
            overflow: hidden;
            margin-top: -80px;
            /* Pull up under transparent header */
        }

        .hero-carousel {
            position: relative;
            height: 100%;
            width: 100%;
        }

        .hero-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            visibility: hidden;
            transition: opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
        }

        .hero-slide.active {
            opacity: 1;
            visibility: visible;
            z-index: 2;
        }

        .hero-img-wrap {
            position: absolute;
            inset: 0;
            z-index: 1;
        }

        .hero-bg-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.1);
            transition: transform 10s linear;
        }

        .hero-slide.active .hero-bg-img {
            transform: scale(1);
        }

        .hero-vignette {
            position: absolute;
            inset: 0;
            background: linear-gradient(to right,
                    rgba(10, 10, 10, 0.9) 0%,
                    rgba(10, 10, 10, 0.4) 40%,
                    transparent 70%),
                linear-gradient(to top,
                    rgba(10, 10, 10, 0.9) 0%,
                    transparent 30%);
            z-index: 2;
        }

        .hero-glow-bottom {
            position: absolute;
            bottom: -50px;
            left: 20%;
            width: 40%;
            height: 100px;
            background: rgba(220, 38, 38, 0.3);
            filter: blur(80px);
            border-radius: 50%;
            z-index: 3;
            pointer-events: none;
        }

        .hero-content-wrap {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .hero-text-area {
            max-width: 800px;
            transform: translateX(-30px);
            opacity: 0;
            transition: all 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) 0.5s;
        }

        .hero-slide.active .hero-text-area {
            transform: translateX(0);
            opacity: 1;
        }

        .hero-label-premium {
            display: inline-block;
            color: var(--cinerama-red);
            font-weight: 800;
            letter-spacing: 4px;
            font-size: 0.9rem;
            margin-bottom: 15px;
            text-shadow: 0 0 10px rgba(220, 38, 38, 0.3);
        }

        .hero-title-premium {
            font-size: 6rem;
            line-height: 0.9;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-weight: 900;
            color: white;
            text-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .hero-subtitle-premium {
            font-size: 1.5rem;
            color: var(--text-secondary);
            margin-bottom: 40px;
            max-width: 500px;
            font-weight: 400;
        }

        .hero-indicators-premium {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            z-index: 20;
        }

        .indicator-bar {
            width: 80px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            cursor: pointer;
            overflow: hidden;
            transition: background 0.3s;
        }

        .indicator-bar.active {
            background: rgba(255, 255, 255, 0.4);
        }

        .indicator-progress {
            height: 100%;
            width: 0;
            background: var(--cinerama-red);
            transition: width 5s linear;
        }

        .indicator-bar.active .indicator-progress {
            width: 100%;
        }

        @media (max-width: 992px) {
            .hero-title-premium {
                font-size: 4rem;
            }

            .hero-subtitle-premium {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .hero-title-premium {
                font-size: 3rem;
            }

            .hero-subtitle-premium {
                display: none;
            }

            .premium-hero-slider {
                height: 60vh;
            }

            .hero-text-area {
                text-align: center;
                margin: 0 auto;
                transform: translateY(30px) !important;
            }

            .hero-slide.active .hero-text-area {
                transform: translateY(0) !important;
            }

            .hero-vignette {
                background: linear-gradient(to top,
                        rgba(10, 10, 10, 0.95) 0%,
                        rgba(10, 10, 10, 0.4) 50%,
                        rgba(10, 10, 10, 0.9) 100%);
            }
        }
    </style>

    <script>
        let currentSlideIndex = 0;
        const heroSlides = document.querySelectorAll('.hero-slide');
        const indicatorBars = document.querySelectorAll('.indicator-bar');
        let slideTimer;

        function goToSlide(index) {
            heroSlides.forEach(s => s.classList.remove('active'));
            indicatorBars.forEach(b => b.classList.remove('active'));

            heroSlides[index].classList.add('active');
            indicatorBars[index].classList.add('active');
            currentSlideIndex = index;

            startSlideTimer();
        }

        function nextSlide() {
            let next = (currentSlideIndex + 1) % heroSlides.length;
            goToSlide(next);
        }

        function startSlideTimer() {
            clearInterval(slideTimer);
            slideTimer = setInterval(nextSlide, 5000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', startSlideTimer);
    </script>
<?php endif; ?>