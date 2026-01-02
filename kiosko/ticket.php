<?php
require_once '../panel/config/config.php';

$id_venta = isset($_GET['id_venta']) ? (int)$_GET['id_venta'] : 0;

if (!$id_venta) {
    header('Location: index.php');
    exit;
}

try {
    // Reutilizamos consulta de venta
    $stmt = $db->prepare("
        SELECT v.*, f.fecha, p.nombre as pelicula, p.img as imagen,
               s.nombre as sala_nombre, l.nombre as local_nombre,
               h.hora as hora_valor
        FROM tbl_ventas v
        JOIN tbl_funciones f ON v.id_funcion = f.id
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_locales l ON s.local = l.id
        JOIN tbl_hora h ON f.id_hora = h.id
        WHERE v.id = ? AND v.estado = 'PAGADO'
    ");
    $stmt->execute([$id_venta]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        die("Ticket no disponible o no pagado.");
    }

    $stmtB = $db->prepare("SELECT * FROM tbl_boletos WHERE id_venta = ?");
    $stmtB->execute([$id_venta]);
    $boletos = $stmtB->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - Cinerama</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/kiosk.css">
</head>

<body class="ticket-body" style="background: #111;">

    <div class="ticket-container">

        <div class="success-icon">
            <div class="check-circle"><i class="fas fa-check"></i></div>
            <h1>¡Compra Exitosa!</h1>
            <p>Disfruta tu película</p>
        </div>

        <?php foreach ($boletos as $b): ?>
            <div class="digital-ticket glass-panel">
                <div class="ticket-top">
                    <div class="brand">CINERAMA</div>
                    <div class="ticket-code">#<?php echo $venta['codigo']; ?></div>
                </div>

                <div class="ticket-info">
                    <h2><?php echo htmlspecialchars($venta['pelicula']); ?></h2>
                    <div class="t-meta">
                        <span><?php echo htmlspecialchars($venta['local_nombre']); ?></span>
                        <span>|</span>
                        <span><?php echo htmlspecialchars($venta['sala_nombre']); ?></span>
                    </div>

                    <div class="t-datetime">
                        <div class="t-block">
                            <label>FECHA</label>
                            <div><?php echo date('d M', strtotime($venta['fecha'])); ?></div>
                        </div>
                        <div class="t-block">
                            <label>HORA</label>
                            <div><?php echo date('g:i A', strtotime($venta['hora_valor'])); ?></div>
                        </div>
                        <div class="t-block big">
                            <label>ASIENTO</label>
                            <div class="seat-num"><?php echo $b['columna'] . $b['numero']; ?></div>
                        </div>
                    </div>
                </div>

                <div class="ticket-barcode">
                    <!-- Mock Barcode -->
                    <div class="barcode-lines"></div>
                    <span><?php echo rand(10000000, 99999999); ?></span>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="home-actions">
            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
        </div>

    </div>

    <style>
        .ticket-body {
            display: flex;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .ticket-container {
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .success-icon {
            text-align: center;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease;
        }

        .check-circle {
            width: 80px;
            height: 80px;
            background: var(--accent);
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 15px;
            box-shadow: 0 0 20px rgba(0, 242, 234, 0.5);
        }

        .digital-ticket {
            background: #fff;
            color: #000;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            /* Recorte de ticket */
            mask-image: radial-gradient(circle at 0 70%, transparent 10px, black 11px),
                radial-gradient(circle at 100% 70%, transparent 10px, black 11px);
            mask-position: -10px 0, 10px 0;
            mask-composite: intersect;
            -webkit-mask-composite: source-in;
            /* Fallback simplified */
        }

        .ticket-top {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 800;
        }

        .ticket-info {
            padding: 20px;
            border-bottom: 2px dashed #ddd;
            /* Ticket tear line */
        }

        .ticket-info h2 {
            font-size: 1.4rem;
            margin-bottom: 5px;
            color: #000;
        }

        .t-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .t-datetime {
            display: flex;
            justify-content: space-between;
        }

        .t-block label {
            font-size: 0.7rem;
            color: #888;
            display: block;
            margin-bottom: 2px;
        }

        .t-block div {
            font-weight: 800;
            font-size: 1.1rem;
        }

        .t-block.big div {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .seat-num {
            font-size: 1.8rem !important;
        }

        .ticket-barcode {
            padding: 15px;
            text-align: center;
        }

        .barcode-lines {
            height: 40px;
            background: repeating-linear-gradient(90deg, #000 0px, #000 2px, transparent 2px, transparent 4px, #000 4px, #000 8px);
            width: 80%;
            margin: 0 auto 5px;
        }

        .btn-home {
            display: block;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            border: 1px solid var(--glass-border);
        }
    </style>
</body>

</html>