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
    <div class="main-slider-container">
        <div class="main-slider">
            <?php foreach ($slides as $index => $slide): ?>
                <?php
                $imgUrl = UPLOADS_URL . 'sliders/' . $slide['img'];
                // Fallback if needed or just use as is. 
                // Assuming UPLOADS_URL is defined in front_config.php

                $activeClass = ($index === 0) ? 'active' : '';
                ?>
                <div class="slide-item <?php echo $activeClass; ?>">
                    <?php if (!empty($slide['link'])): ?>
                        <a href="<?php echo htmlspecialchars($slide['link']); ?>">
                            <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($slide['titulo']); ?>">
                        </a>
                    <?php else: ?>
                        <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($slide['titulo']); ?>">
                    <?php endif; ?>

                    <?php if (!empty($slide['titulo'])): ?>
                        <!-- Optional caption if needed -->
                        <!-- <div class="slide-caption"><?php echo htmlspecialchars($slide['titulo']); ?></div> -->
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Controls (Optional) -->
        <button class="slider-nav prev" onclick="moveSlide(-1)">&#10094;</button>
        <button class="slider-nav next" onclick="moveSlide(1)">&#10095;</button>

        <!-- Dots -->
        <div class="slider-dots">
            <?php foreach ($slides as $index => $slide): ?>
                <span class="dot <?php echo ($index === 0) ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $index; ?>)"></span>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
        .main-slider-container {
            position: relative;
            max-width: 100%;
            margin: 0 auto;
            overflow: hidden;
            background: #000;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .main-slider {
            position: relative;
            width: 100%;
            height: 600px;
        }

        .slide-item {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .slide-item.active {
            opacity: 1;
            z-index: 1;
        }

        .slide-item::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg,
                    transparent 0%,
                    transparent 40%,
                    rgba(0, 0, 0, 0.3) 70%,
                    rgba(0, 0, 0, 0.7) 100%);
            pointer-events: none;
            z-index: 1;
        }

        .slide-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            animation: kenBurns 20s ease-out infinite alternate;
        }

        @keyframes kenBurns {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.1);
            }
        }

        .slide-item.active img {
            animation: kenBurns 20s ease-out infinite alternate;
        }

        /* Navigation Buttons */
        .slider-nav {
            cursor: pointer;
            position: absolute;
            top: 50%;
            width: auto;
            padding: 1.2rem 1rem;
            margin-top: -30px;
            color: white;
            font-weight: bold;
            font-size: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 8px;
            user-select: none;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 10;
            opacity: 0.7;
        }

        .slider-nav.prev {
            left: 20px;
        }

        .slider-nav.next {
            right: 20px;
        }

        .slider-nav:hover {
            background: rgba(220, 20, 60, 0.8);
            opacity: 1;
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(220, 20, 60, 0.4);
        }

        /* Dots */
        .slider-dots {
            position: absolute;
            bottom: 30px;
            width: 100%;
            text-align: center;
            z-index: 10;
        }

        .dot {
            cursor: pointer;
            height: 12px;
            width: 12px;
            margin: 0 6px;
            background-color: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            display: inline-block;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .dot.active {
            background: linear-gradient(135deg, #DC143C 0%, #FF6B6B 100%);
            width: 40px;
            border-radius: 6px;
            border-color: rgba(220, 20, 60, 0.8);
            box-shadow: 0 0 15px rgba(220, 20, 60, 0.6);
        }

        .dot:hover {
            background-color: rgba(255, 255, 255, 0.7);
            transform: scale(1.2);
        }

        @media (max-width: 768px) {
            .main-slider {
                height: 400px;
            }

            .slider-nav {
                padding: 0.8rem 0.7rem;
                font-size: 18px;
                opacity: 0.9;
            }

            .slider-nav.prev {
                left: 10px;
            }

            .slider-nav.next {
                right: 10px;
            }

            .slider-dots {
                bottom: 20px;
            }

            .dot {
                height: 10px;
                width: 10px;
                margin: 0 4px;
            }

            .dot.active {
                width: 30px;
            }
        }

        @media (max-width: 480px) {
            .main-slider {
                height: 300px;
            }

            .slider-nav {
                padding: 0.6rem 0.5rem;
                font-size: 16px;
            }
        }
    </style>

    <script>
        let slideIndex = 0;
        const slides = document.querySelectorAll(".slide-item");
        const dots = document.querySelectorAll(".dot");
        let slideInterval;

        function showSlides(n) {
            if (n >= slides.length) {
                slideIndex = 0
            }
            if (n < 0) {
                slideIndex = slides.length - 1
            }

            // Hide all
            slides.forEach(slide => slide.classList.remove("active"));
            dots.forEach(dot => dot.classList.remove("active"));

            // Show new
            slides[slideIndex].classList.add("active");
            dots[slideIndex].classList.add("active");
        }

        function moveSlide(n) {
            slideIndex += n;
            showSlides(slideIndex);
            resetTimer();
        }

        function currentSlide(n) {
            slideIndex = n;
            showSlides(slideIndex);
            resetTimer();
        }

        function startTimer() {
            slideInterval = setInterval(() => {
                slideIndex++;
                showSlides(slideIndex);
            }, 5000); // 5 seconds
        }

        function resetTimer() {
            clearInterval(slideInterval);
            startTimer();
        }

        // Initialize
        if (slides.length > 0) {
            startTimer();
        }
    </script>
<?php endif; ?>