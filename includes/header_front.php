<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinerama - <?php echo isset($page_title) ? $page_title : 'Bienvenido'; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Extra Head Content -->
    <?php if (isset($extra_head)) echo $extra_head; ?>

    <style>
        /* ========================================
           CSS VARIABLES - DESIGN SYSTEM
        ======================================== */
        :root {
            /* Colors */
            --cinerama-red: #DC143C;
            --cinerama-red-dark: #8B0000;
            --cinerama-dark: #0a0a0a;
            --cinerama-gold: #FFD700;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --text-dark: #1a1a1a;
            --bg-primary: #000000;
            --bg-secondary: #1a1a1a;
            --bg-light: #f8f9fa;

            /* Gradients */
            --gradient-primary: linear-gradient(135deg, #DC143C 0%, #8B0000 100%);
            --gradient-overlay: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.8) 100%);
            --gradient-text: linear-gradient(135deg, #DC143C 0%, #FF6B6B 100%);

            /* Spacing */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 4rem;

            /* Shadows */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.2);
            --shadow-xl: 0 12px 48px rgba(0, 0, 0, 0.3);
            --shadow-glow: 0 0 30px rgba(220, 20, 60, 0.4);
            --shadow-glow-hover: 0 0 40px rgba(220, 20, 60, 0.6);

            /* Transitions */
            --transition-fast: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 0.5s cubic-bezier(0.4, 0, 0.2, 1);

            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        /* ========================================
           GLOBAL STYLES
        ======================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--bg-light);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ========================================
           HEADER - STICKY NAVIGATION
        ======================================== */
        header {
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1.2rem 3rem;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all var(--transition-normal);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        header.scrolled {
            padding: 0.8rem 3rem;
            background: rgba(0, 0, 0, 0.98);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }

        header .logo {
            font-size: 2rem;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            background: var(--gradient-text);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            letter-spacing: 1px;
            transition: all var(--transition-normal);
            position: relative;
        }

        header .logo::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--gradient-primary);
            transition: width var(--transition-normal);
        }

        header .logo:hover::after {
            width: 100%;
        }

        /* Navigation */
        nav {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
        }

        nav a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
            position: relative;
        }

        nav a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--cinerama-red);
            transition: width var(--transition-normal);
        }

        nav a:hover {
            color: var(--cinerama-red);
        }

        nav a:hover::before {
            width: 80%;
        }

        /* ========================================
           CONTAINER & LAYOUT
        ======================================== */
        .container {
            padding: var(--spacing-2xl) var(--spacing-lg);
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 3rem;
            text-align: center;
            margin-bottom: var(--spacing-xl);
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            background: var(--gradient-text);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            padding-bottom: var(--spacing-md);
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        /* ========================================
           CINEMA CARDS - PREMIUM DESIGN
        ======================================== */
        .cinema-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xl);
            max-width: 1200px;
            margin: 0 auto;
        }

        .cinema-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all var(--transition-normal);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .cinema-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .cinema-header {
            background: var(--gradient-primary);
            color: white;
            padding: 1.2rem 2rem;
            font-size: 1.6rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .cinema-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .cinema-card:hover .cinema-header::before {
            left: 100%;
        }

        .cinema-body {
            display: flex;
            flex-direction: row;
            min-height: 280px;
            background: #ffffff;
        }

        .cinema-img-container {
            flex: 0 0 55%;
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            position: relative;
            overflow: hidden;
        }

        .cinema-img-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(220, 20, 60, 0.1) 0%, transparent 100%);
            pointer-events: none;
        }

        .cinema-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform var(--transition-slow);
        }

        .cinema-card:hover .cinema-img {
            transform: scale(1.05);
        }

        .cinema-info {
            flex: 1;
            padding: var(--spacing-xl);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            background: #ffffff;
            position: relative;
        }

        .cinema-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 0;
            background: var(--gradient-primary);
            transition: height var(--transition-normal);
        }

        .cinema-card:hover .cinema-info::before {
            height: 100%;
        }

        .cinema-address {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: var(--spacing-lg);
            font-weight: 500;
            line-height: 1.6;
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
        }

        .cinema-address::before {
            content: '\f3c5';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: var(--cinerama-red);
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .btn-view-cartelera {
            background: var(--gradient-primary);
            color: white;
            padding: 1rem 2rem;
            text-decoration: none;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            font-size: 0.95rem;
            border-radius: var(--radius-sm);
            transition: all var(--transition-normal);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .btn-view-cartelera::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-view-cartelera:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-view-cartelera:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow-hover);
        }

        .btn-view-cartelera i {
            margin-left: var(--spacing-xs);
            transition: transform var(--transition-fast);
        }

        .btn-view-cartelera:hover i {
            transform: translateX(5px);
        }

        /* ========================================
           RESPONSIVE MENU
        ======================================== */
        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 6px;
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
        }

        .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .menu-toggle span {
            width: 28px;
            height: 3px;
            background: var(--text-primary);
            border-radius: 2px;
            transition: all var(--transition-normal);
        }

        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(8px, -8px);
        }

        /* ========================================
           RESPONSIVE DESIGN
        ======================================== */
        @media (max-width: 768px) {
            header {
                padding: 1rem 1.5rem;
                flex-wrap: wrap;
            }

            header .logo {
                font-size: 1.5rem;
            }

            .menu-toggle {
                display: flex;
            }

            nav {
                display: none;
                width: 100%;
                flex-direction: column;
                background: rgba(26, 26, 26, 0.98);
                backdrop-filter: blur(10px);
                margin-top: var(--spacing-md);
                padding: var(--spacing-md) 0;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: var(--radius-sm);
                gap: 0;
            }

            nav.active {
                display: flex;
                animation: slideDown 0.3s ease;
            }

            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            nav a {
                padding: var(--spacing-md);
                text-align: center;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }

            nav a:last-child {
                border-bottom: none;
            }

            .container {
                padding: var(--spacing-lg) var(--spacing-md);
            }

            .section-title {
                font-size: 2rem;
                margin-bottom: var(--spacing-lg);
            }

            .cinema-body {
                flex-direction: column;
                min-height: auto;
            }

            .cinema-img-container {
                flex: 0 0 auto;
                height: 220px;
            }

            .cinema-info {
                padding: var(--spacing-lg);
                align-items: center;
                text-align: center;
            }

            .cinema-address {
                justify-content: center;
                text-align: center;
            }

            .cinema-header {
                text-align: center;
                font-size: 1.3rem;
                padding: 1rem 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .section-title {
                font-size: 1.75rem;
            }

            .cinema-header {
                font-size: 1.1rem;
            }

            .btn-view-cartelera {
                padding: 0.875rem 1.5rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>
    <header id="main-header">
        <a href="<?php echo BASE_URL; ?>" class="logo">CINERAMA</a>

        <div class="menu-toggle" onclick="toggleMenu()" id="menu-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <nav id="main-nav">
            <a href="<?php echo BASE_URL; ?>index.php"><i class="fas fa-film"></i> Cines</a>
            <a href="<?php echo BASE_URL; ?>cartelera.php"><i class="fas fa-ticket-alt"></i> Cartelera</a>
            <a href="<?php echo BASE_URL; ?>estrenos.php"><i class="fas fa-star"></i> Próximos Estrenos</a>
            <a href="<?php echo BASE_URL; ?>contacto.php"><i class="fas fa-envelope"></i> Contacto</a>
        </nav>
    </header>

    <script>
        // Toggle mobile menu
        function toggleMenu() {
            const nav = document.getElementById('main-nav');
            const toggle = document.getElementById('menu-toggle');
            nav.classList.toggle('active');
            toggle.classList.toggle('active');
        }

        // Sticky header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('main-header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('main-nav');
            const toggle = document.getElementById('menu-toggle');
            const isClickInsideNav = nav.contains(event.target);
            const isClickOnToggle = toggle.contains(event.target);

            if (!isClickInsideNav && !isClickOnToggle && nav.classList.contains('active')) {
                nav.classList.remove('active');
                toggle.classList.remove('active');
            }
        });
    </script>