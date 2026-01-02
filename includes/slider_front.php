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
        }

        .main-slider {
            position: relative;
            width: 100%;
            /* 16:9 Aspect Ratio approx or fixed height */
            height: 50vh;
            min-height: 400px;
        }

        .slide-item {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .slide-item.active {
            opacity: 1;
            z-index: 1;
        }

        .slide-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* Cover to fill area */
        }

        /* Navigation Buttons */
        .slider-nav {
            cursor: pointer;
            position: absolute;
            top: 50%;
            width: auto;
            padding: 16px;
            margin-top: -22px;
            color: white;
            font-weight: bold;
            font-size: 18px;
            transition: 0.6s ease;
            border-radius: 0 3px 3px 0;
            user-select: none;
            background-color: rgba(0, 0, 0, 0.3);
            border: none;
            z-index: 2;
        }

        .slider-nav.next {
            right: 0;
            border-radius: 3px 0 0 3px;
        }

        .slider-nav:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        /* Dots */
        .slider-dots {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
            z-index: 2;
        }

        .dot {
            cursor: pointer;
            height: 12px;
            width: 12px;
            margin: 0 5px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            transition: background-color 0.6s ease;
        }

        .dot.active,
        .dot:hover {
            background-color: #e50914;
            /* Cinerama Red */
        }

        @media (max-width: 768px) {
            .main-slider {
                width: 100%;
                height: auto;
                aspect-ratio: 16/9;
                /* Standard wide format, preventing crop */
                min-height: unset;
            }

            .slide-item img {
                object-fit: contain;
                /* Ensure full image is visible */
                background: #000;
                /* Fill gaps if aspect ratio doesn't match perfectly */
            }

            .slider-nav {
                padding: 10px;
                font-size: 14px;
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