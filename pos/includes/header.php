<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinerama POS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #c01820;
            --dark: #1a1a1a;
            --light: #f4f4f4;
            --white: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        header {
            background: var(--dark);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            margin: 0;
            font-size: 1.2rem;
        }

        .user-panel {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-logout {
            color: white;
            text-decoration: none;
            border: 1px solid white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        main {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <header>
        <h1>CINERAMA POS - <?php echo $_SESSION['pos_user_nombre'] ?? 'Cajero'; ?></h1>
        <div class="user-panel">
            <span><i class="fas fa-user-circle"></i> <?php echo $_SESSION['pos_user_usuario'] ?? ''; ?></span>
            <a href="logout.php" class="btn-logout">Salir</a>
        </div>
    </header>
    <main>