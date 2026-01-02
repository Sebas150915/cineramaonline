<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['usuario']);
    $password = $_POST['password'];

    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Error de seguridad (CSRF). Por favor recargue la página.";
    } elseif (empty($username) || empty($password)) {
        $error = "Por favor ingrese usuario y contraseña.";
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM tbl_usuarios WHERE usuario = ? AND estado = '1'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Strict Role Check: 'cajero' NOT allowed in Panel
                if ($user['rol'] === 'cajero') {
                    $error = "Acceso denegado. Los cajeros deben usar el módulo POS.";
                } else {
                    // Login Success
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['usuario'] = $user['usuario'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['rol'] = $user['rol'];
                    $_SESSION['id_local'] = $user['id_local'];

                    if ($user['id_local']) {
                        $lStmt = $db->prepare("SELECT nombre FROM tbl_locales WHERE id = ?");
                        $lStmt->execute([$user['id_local']]);
                        $_SESSION['local_nombre'] = $lStmt->fetchColumn();
                    }

                    $_SESSION['permiso_boleteria'] = $user['permiso_boleteria'];
                    $_SESSION['permiso_dulceria'] = $user['permiso_dulceria'];

                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);

                    header("Location: " . BASE_URL . "index.php");
                    exit;
                }
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            // General error message for security
            $error = "Error de sistema. Intente nuevamente.";
            error_log($e->getMessage()); // Log actual error
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cinerama Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --danger: #e74a3b;
            --dark: #5a5c69;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            position: relative;
            animation: fadeIn 0.8s ease-out;
        }

        .login-header {
            background: #fff;
            padding: 40px 30px 20px;
            text-align: center;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #4e73df;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .logo span {
            color: #2e59d9;
        }

        .subtitle {
            color: #858796;
            font-size: 14px;
        }

        .login-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            box-sizing: border-box;
            border: 2px solid #eaecf4;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            outline: none;
            color: #6e707e;
        }

        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
        }

        .form-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #d1d3e2;
            transition: color 0.3s;
        }

        .form-control:focus+.form-icon {
            color: #4e73df;
        }

        .btn-login {
            width: 100%;
            background: #4e73df;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }

        .btn-login:hover {
            background: #2e59d9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }

        .alert-error {
            background: #ffe3e6;
            color: #e74a3b;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #ffcdd2;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Particles/Background decoration */
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: -1;
        }

        .c1 {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
        }

        .c2 {
            width: 200px;
            height: 200px;
            bottom: -50px;
            right: -50px;
        }
    </style>
</head>

<body>
    <div class="bg-circle c1"></div>
    <div class="bg-circle c2"></div>

    <div class="login-card">
        <div class="login-header">
            <div class="logo">CINERAMA<span>ADMIN</span></div>
            <div class="subtitle">Ingrese sus credenciales para continuar</div>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="form-group">
                    <input type="text" name="usuario" class="form-control" placeholder="Usuario" required autocomplete="off">
                    <i class="fas fa-user form-icon"></i>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                    <i class="fas fa-lock form-icon"></i>
                </div>
                <button type="submit" class="btn-login">
                    Iniciar Sesión <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                </button>
            </form>
        </div>
    </div>
</body>

</html>