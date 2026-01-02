<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinerama - <?php echo isset($page_title) ? $page_title : 'Bienvenido'; ?></title>
    <!-- Extra Head Content -->
    <?php if (isset($extra_head)) echo $extra_head; ?>

    <style>
        body {
            margin: 0;
            background: #fff;
            font-family: Arial, sans-serif;
            color: #fff;
        }

        header {
            padding: 20px 40px;
            background: #000;
            border-bottom: 1px solid #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        header .logo {
            font-size: 24px;
            font-weight: bold;
            color: #e50914;
            text-decoration: none;
        }

        .container {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 32px;
            text-align: center;
            margin-bottom: 40px;
            font-weight: 700;
            color: #fff;
        }

        /* Grid de Cines y Películas */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .card {
            background: #eeececff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            transition: transform .3s ease;
        }

        .card:hover {
            transform: scale(1.03);
            box-shadow: 0 0 30px rgba(229, 9, 20, 0.3);
        }

        /* --- Cinema Horizontal Cards (Reference Style) --- */
        .cinema-list {
            display: flex;
            flex-direction: column;
            gap: 40px;
            max-width: 1100px;
            /* Increased from 1000px */
            margin: 0 auto;
        }

        .cinema-card {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid #e0e0e0;
        }

        .cinema-header {
            background-color: #dc3545;
            /* Cinema Red */
            color: white;
            padding: 12px 20px;
            /* Slight reduction */
            font-size: 1.4rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cinema-body {
            display: flex;
            flex-direction: row;
            height: 230px;
            /* Reduced from 350px to minimize black space */
        }

        .cinema-img-container {
            flex: 0 0 60%;
            /* Image takes 60% */
            background: #000;
            position: relative;
            overflow: hidden;
            border-right: 1px solid #eee;
        }

        .cinema-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            /* Request: 100% visible */
            background: #000;
        }

        .cinema-info {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            background: #fff;
        }

        .cinema-address {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 30px;
            text-transform: uppercase;
            line-height: 1.4;
        }

        .btn-view-cartelera {
            background-color: #dc3545;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
            border-radius: 4px;
            transition: background 0.3s;
            border: none;
        }

        .btn-view-cartelera:hover {
            background-color: #c82333;
        }

        /* Responsive Mobile */
        @media (max-width: 768px) {
            .cinema-body {
                flex-direction: column;
                height: auto;
            }

            .cinema-img-container {
                height: 250px;
                border-right: none;
                border-bottom: 1px solid #eee;
            }

            .cinema-info {
                padding: 25px;
                align-items: center;
                text-align: center;
            }

            .cinema-header {
                text-align: center;
                font-size: 1.3rem;
            }
        }

        /* Responsive Menu Styles */
        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 5px;
        }

        .menu-toggle span {
            width: 25px;
            height: 3px;
            background-color: #fff;
            transition: 0.3s;
        }

        @media (max-width: 768px) {
            header {
                flex-wrap: wrap;
                /* Allow wrapping for menu */
            }

            .menu-toggle {
                display: flex;
            }

            nav {
                display: none;
                width: 100%;
                flex-direction: column;
                background: #111;
                margin-top: 15px;
                padding: 10px 0;
                text-align: center;
                border-top: 1px solid #333;
            }

            nav.active {
                display: flex;
            }

            nav a {
                display: block;
                margin: 10px 0;
                /* Vertical spacing */
                margin-left: 0 !important;
                /* Reset margin */
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <header>
        <a href="<?php echo BASE_URL; ?>" class="logo">CINERAMA</a>

        <div class="menu-toggle" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <!-- Menú opcional aquí -->
        <nav id="main-nav">
            <a href="<?php echo BASE_URL; ?>index.php" style="color: #fff; text-decoration: none; margin-left: 20px;">Cines</a>
            <a href="<?php echo BASE_URL; ?>cartelera.php" style="color: #fff; text-decoration: none; margin-left: 20px;">Cartelera</a>
            <a href="<?php echo BASE_URL; ?>estrenos.php" style="color: #fff; text-decoration: none; margin-left: 20px;">Proximos Estrenos</a>
            <a href="<?php echo BASE_URL; ?>contacto.php" style="color: #fff; text-decoration: none; margin-left: 20px;">Contacto</a>
        </nav>
    </header>

    <script>
        function toggleMenu() {
            const nav = document.getElementById('main-nav');
            nav.classList.toggle('active');
        }
    </script>