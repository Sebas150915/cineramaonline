<?php
require_once 'includes/front_config.php';

// Obtener cines activos para el formulario
try {
    $cines = $db->query("SELECT * FROM tbl_locales WHERE estado = '1' ORDER BY orden ASC, nombre ASC")->fetchAll();
} catch (PDOException $e) {
    $cines = [];
}

$mensaje_status = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    // ... rest of the existing POST logic ...
    $apellidos = $_POST['apellidos'] ?? '';
    // Combine names if needed or store separately. DB has both.
    $correo = $_POST['correo'] ?? '';
    $asunto = $_POST['asunto'] ?? '';
    $cine = $_POST['cine'] ?? '';
    $mensaje = $_POST['mensaje'] ?? '';

    if (!empty($nombre) && !empty($correo) && !empty($mensaje)) {
        try {
            $sql = "INSERT INTO tbl_contactos (nombre, apellidos, correo, asunto, cine, mensaje) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$nombre, $apellidos, $correo, $asunto, $cine, $mensaje]);
            $mensaje_status = "<div style='color: green; text-align: center; margin-bottom: 20px;'>¡Mensaje enviado correctamente! Nos pondremos en contacto pronto.</div>";
        } catch (PDOException $e) {
            $mensaje_status = "<div style='color: red; text-align: center; margin-bottom: 20px;'>Error al enviar mensaje: " . $e->getMessage() . "</div>";
        }
    } else {
        $mensaje_status = "<div style='color: red; text-align: center; margin-bottom: 20px;'>Por favor complete todos los campos obligatorios.</div>";
    }
}

$page_title = "Contáctenos";
$extra_head = '
    <link rel="stylesheet" href="contacto.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            /* Override header generic font */
            font-family: \'Montserrat\', sans-serif !important;
            background: #ffffff !important;
            color: #333 !important;
        }
        
        /* Banner fix */
        .banner img {
            width: 100%;
            height: auto;
            display: block;
        }

        .contact-container {
            width: 80%;
            max-width: 1100px;
            margin: 50px auto;
        }

        h2 {
            font-size: 32px;
            color: #b8161f;
            margin-bottom: 5px;
        }

        .line {
            width: 70px;
            height: 3px;
            background: #b8161f;
            margin-bottom: 40px;
        }

        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .row {
            display: flex;
            gap: 25px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            flex: 100%;
        }

        label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }

        input,
        select,
        textarea {
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 4px;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
            color: #333;
            background: #fff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #b8161f;
        }

        textarea {
            resize: vertical;
        }

        .btn-submit {
            width: 220px;
            padding: 14px;
            border: none;
            border-radius: 4px;
            background: #c01820;
            color: #fff;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #9d121b;
        }

        @media(max-width: 768px) {
            .row {
                flex-direction: column;
            }

            .btn-submit {
                width: 100%;
            }

            .contact-container {
                width: 90%;
            }
        }
    </style>
';

include 'includes/header_front.php';
include 'includes/slider_front.php';
?>

<!-- Banner moved inside content -->
<!-- <div class="banner">
    <img src="https://www.cinerama.com.pe/assets/img/header_contacto.jpg" alt="banner contacto">
</div> -->

<section class="contact-container">
    <h2>Contáctenos</h2>
    <div class="line"></div>

    <?php echo $mensaje_status; ?>
    <form class="contact-form" method="POST" action="">

        <div class="row">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" placeholder="Nombre" required>
            </div>

            <div class="form-group">
                <label>Apellidos</label>
                <input type="text" name="apellidos" placeholder="Apellidos" required>
            </div>
        </div>

        <div class="row">
            <div class="form-group full">
                <label>Correo Electrónico</label>
                <input type="email" name="correo" placeholder="Correo electrónico" required>
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Asunto</label>
                <input type="text" name="asunto" placeholder="Asunto" required>
            </div>

            <div class="form-group">
                <label>Seleccione un Cine</label>
                <select name="cine">
                    <option selected disabled>Seleccione un cine</option>
                    <?php foreach ($cines as $cine): ?>
                        <option value="<?php echo htmlspecialchars($cine['nombre']); ?>">
                            <?php echo htmlspecialchars($cine['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="form-group full">
                <label>Mensaje</label>
                <textarea name="mensaje" rows="5" placeholder="Escribe tu mensaje" required></textarea>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            ✉ ENVIAR MENSAJE
        </button>

    </form>
</section>

<?php include 'includes/footer_front.php'; ?>