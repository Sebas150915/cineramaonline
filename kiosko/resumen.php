<?php
require_once '../panel/config/config.php';

$id_venta = isset($_GET['id_venta']) ? (int)$_GET['id_venta'] : 0;

if (!$id_venta) {
    header('Location: index.php');
    exit;
}

try {
    // 1. Obtener Datos de la Venta, Función, Película y Sala
    $stmt = $db->prepare("
        SELECT v.*, f.fecha, f.id_hora, f.id_sala,
               p.nombre as pelicula, p.img as imagen, p.duracion,
               s.nombre as sala_nombre, l.id as id_local, l.nombre as local_nombre,
               h.hora as hora_valor
        FROM tbl_ventas v
        JOIN tbl_funciones f ON v.id_funcion = f.id
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_locales l ON s.local = l.id
        JOIN tbl_hora h ON f.id_hora = h.id
        WHERE v.id = ?
    ");
    $stmt->execute([$id_venta]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta || $venta['estado'] == 'ANULADO') {
        die("Venta no válida o expirada.");
    }

    // 2. Obtener Boletos seleccionados
    $stmtB = $db->prepare("SELECT * FROM tbl_boletos WHERE id_venta = ? AND estado = 'ACTIVO'");
    $stmtB->execute([$id_venta]);
    $boletos = $stmtB->fetchAll(PDO::FETCH_ASSOC);

    if (empty($boletos)) {
        die("No hay asientos seleccionados.");
    }

    // 3. Calcular Precio (Si aún es 0)
    // Lógica Simple: Buscar Tarifa GENERAL para hoy en este local
    $total = 0;

    // Mapeo dia semala (0=Dom, 6=Sab) a col DB (l,m,x,j,v,s,d) - DB usa date('w')? No, date('w') 0 es Domingo.
    // Columns: l, m, x, j, v, s, d
    $days_map = ['d', 'l', 'm', 'x', 'j', 'v', 's']; // 0->d, 1->l ...
    $today_idx = date('w');
    $col_dia = $days_map[$today_idx];

    // Buscar tarifa
    $stmtTarifa = $db->prepare("
        SELECT * FROM tbl_tarifa 
        WHERE local = ? AND $col_dia = '1'
        ORDER BY precio DESC
    ");
    // Ordenamos por precio DESC asumiendo la más alta es la general? O buscar por nombre 'General'
    $stmtTarifa->execute([$venta['id_local']]);
    $tarifas = $stmtTarifa->fetchAll(PDO::FETCH_ASSOC);

    $tarifa_aplicada = null;
    foreach ($tarifas as $t) {
        if (stripos($t['nombre'], 'General') !== false) {
            $tarifa_aplicada = $t;
            break;
        }
    }
    if (!$tarifa_aplicada && !empty($tarifas)) {
        $tarifa_aplicada = $tarifas[0]; // Fallback
    }

    $precio_unitario = $tarifa_aplicada ? $tarifa_aplicada['precio'] : 15.00; // Default harcoded fallback
    $id_tarifa = $tarifa_aplicada ? $tarifa_aplicada['id'] : null;

    // Actualizar Boletos y Total Venta
    foreach ($boletos as $b) {
        if ($b['precio'] == 0) {
            $updateB = $db->prepare("UPDATE tbl_boletos SET precio = ?, id_tarifa = ? WHERE id = ?");
            $updateB->execute([$precio_unitario, $id_tarifa, $b['id']]);
            $total += $precio_unitario;
        } else {
            $total += $b['precio'];
        }
    }

    // Actualizar Venta Total
    if ($venta['total'] != $total) {
        $db->prepare("UPDATE tbl_ventas SET total = ? WHERE id = ?")->execute([$total, $id_venta]);
        $venta['total'] = $total;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Resumen de Compra - Cinerama</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/kiosk.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="checkout-body">

    <header class="kiosk-header glass-panel" style="border-radius: 0 0 16px 16px; border-top: none;">
        <a href="sala.php?cartelera_id=0&hora_id=<?php echo $venta['id_hora']; ?>" class="back-link" onclick="history.back(); return false;">
            <i class="fas fa-chevron-left"></i> Modificar
        </a>
        <div class="brand-logo" style="font-size: 1.5rem;">Caja</div>
        <div class="clock" style="font-weight: 600;">
            <?php echo date('g:i A'); ?>
        </div>
    </header>

    <main class="kiosk-main">

        <div class="checkout-grid">
            <!-- Columna Izquierda: Poster y Detalles -->
            <div class="movie-summary glass-panel">
                <img src="../uploads/peliculas/<?php echo htmlspecialchars($venta['imagen']); ?>"
                    class="summary-poster" onerror="this.src='../assets/img/no-poster.jpg'">

                <div class="summary-details">
                    <h2 class="summary-title"><?php echo htmlspecialchars($venta['pelicula']); ?></h2>
                    <div class="summary-row">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></span>
                    </div>
                    <div class="summary-row">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('g:i A', strtotime($venta['hora_valor'])); ?></span>
                    </div>
                    <div class="summary-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($venta['local_nombre']); ?> - <?php echo htmlspecialchars($venta['sala_nombre']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Boletos y Total -->
            <div class="order-details glass-panel">
                <h3>Resumen del Pedido</h3>

                <div class="ticket-list">
                    <?php foreach ($boletos as $b): ?>
                        <div class="ticket-item">
                            <div class="ticket-seat">
                                <span class="seat-icon"><i class="fas fa-chair"></i></span>
                                <div>
                                    <div class="seat-label">Asiento <?php echo $b['columna'] . $b['numero']; // Use visual label if stored, here fallback 
                                                                    ?></div>
                                    <div class="seat-type">General</div>
                                </div>
                            </div>
                            <div class="ticket-price">
                                S/ <?php echo number_format($precio_unitario, 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-divider"></div>

                <div class="total-row">
                    <span>Total a Pagar</span>
                    <span class="total-amount">S/ <?php echo number_format($total, 2); ?></span>
                </div>

                <div class="payment-section">
                    <h4>Seleccione Método de Pago</h4>
                    <div class="payment-methods">
                        <button class="pay-btn primary" onclick="pagar('TARJETA')">
                            <i class="fas fa-credit-card"></i> Tarjeta
                        </button>
                        <button class="pay-btn secondary" onclick="pagar('EFECTIVO')">
                            <i class="fas fa-money-bill-wave"></i> Efectivo
                        </button>
                        <button class="pay-btn secondary" onclick="pagar('YAPE')">
                            <i class="fas fa-qrcode"></i> Yape / Plin
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <script>
        function pagar(metodo) {
            Swal.fire({
                title: 'Procesando Pago',
                text: 'Por favor espere...',
                icon: 'info',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Simular delay de red
            setTimeout(() => {
                fetch('kiosk_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=pagar&id_venta=<?php echo $id_venta; ?>&metodo=${metodo}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Pago Exitoso!',
                                text: 'Imprimiendo tus boletos...',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = `ticket.php?id_venta=<?php echo $id_venta; ?>`;
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            }, 1000);
        }
    </script>

    <style>
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .movie-summary {
            padding: 20px;
            text-align: center;
        }

        .summary-poster {
            width: 100%;
            max-width: 250px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            margin-bottom: 20px;
        }

        .summary-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .summary-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .order-details {
            padding: 30px;
            display: flex;
            flex-direction: column;
        }

        .ticket-list {
            margin: 20px 0;
            flex: 1;
            overflow-y: auto;
            max-height: 300px;
        }

        .ticket-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 10px;
            border-radius: 10px;
        }

        .ticket-seat {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .seat-icon {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .seat-label {
            font-weight: bold;
            font-size: 1.1rem;
        }

        .seat-type {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .ticket-price {
            font-weight: bold;
            font-size: 1.2rem;
        }

        .order-divider {
            border-top: 1px dashed var(--glass-border);
            margin: 20px 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 30px;
        }

        .total-amount {
            color: var(--accent);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .pay-btn {
            border: none;
            padding: 20px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .pay-btn i {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .pay-btn.primary {
            background: var(--primary);
            color: white;
            grid-column: 1 / -1;
        }

        .pay-btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-main);
        }

        .pay-btn:active {
            transform: scale(0.95);
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>

</html>