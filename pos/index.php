<?php
session_start();
if (isset($_SESSION['pos_user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cinerama POS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #1a1a1a;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            width: 350px;
            text-align: center;
        }

        .login-card h2 {
            margin-top: 0;
            color: #c01820;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            /* Fix padding issue */
        }

        .btn-login {
            background: #c01820;
            color: white;
            border: none;
            padding: 0.75rem;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background: #a0141b;
        }

        .alert {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: left;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h2><i class="fas fa-ticket-alt"></i> POS CINERAMA</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i> Credenciales incorrectas
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" required autofocus placeholder="Usuario">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required placeholder="Contraseña">
            </div>
            <button type="submit" class="btn-login">INGRESAR</button>
        </form>
    </div>
</body>

</html>