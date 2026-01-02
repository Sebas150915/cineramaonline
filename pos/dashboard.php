<?php
define('IS_POS', true);
require_once '../panel/config/config.php';
require_once 'includes/auth_check.php';
include 'includes/header.php';
?>

<div class="pos-container">

    <?php
    // Check Permission for this Module
    if (empty($_SESSION['pos_permiso_boleteria'])) {
        if (!empty($_SESSION['pos_permiso_dulceria'])) {
            header("Location: dulceria.php");
            exit;
        } else {
            die("Sin permiso de Boletería.");
        }
    }
    ?>

    <!-- COL 1: PELICULAS -->
    <div class="col-movies">
        <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
            <span>PELÍCULAS</span>
            <?php if (!empty($_SESSION['pos_permiso_dulceria'])): ?>
                <a href="dulceria.php" class="btn btn-sm btn-light" style="color:#333; font-size:0.8rem; padding: 2px 5px; text-decoration:none;">DULCERÍA</a>
            <?php endif; ?>
        </div>
        <div id="movies-list" class="scrollable">
            <div class="loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
        </div>
    </div>

    <!-- COL 2: MAPA -->
    <div class="col-map">
        <div class="panel-header">
            <span id="map-title">SELECCIONE UNA FUNCIÓN</span>
        </div>
        <div id="screen-display" class="screen-box" style="display:none;">PANTALLA</div>
        <div id="seat-map-container" class="scrollable center-content">
            <!-- Map renders here -->
        </div>
        <div id="map-legend" style="display:none; text-align: center; margin-top: 10px;">
            <span class="dot free"></span> Libre
            <span class="dot selected"></span> Seleccionado
            <span class="dot occupied"></span> Ocupado
        </div>
    </div>

    <!-- COL 3: CART & PAY -->
    <div class="col-cart">
        <div class="panel-header">ENTRADAS</div>

        <div id="cart-items" class="scrollable" style="flex: 1;">
            <p style="text-align: center; color: #666; margin-top: 20px;">No hay asientos seleccionados.</p>
        </div>

        <div class="cart-footer">
            <div class="total-row">
                <span>TOTAL</span>
                <span id="cart-total">S/ 0.00</span>
            </div>

            <form id="checkout-form" action="finalizar_venta.php" method="POST">
                <input type="hidden" name="id_funcion" id="input_funcion">
                <input type="hidden" name="cliente_nombre" value="PUBLICO GENERAL">
                <input type="hidden" name="medio_pago" value="EFECTIVO"> <!-- Configurable via modal later -->

                <!-- Dynamic inputs will be appended here -->
                <div id="hidden-inputs"></div>

                <button type="button" class="btn-pay" onclick="processPayment()">
                    <i class="fas fa-cash-register"></i> PAGAR (F10)
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* LAYOUT Grid */
    .pos-container {
        display: flex;
        height: calc(100vh - 70px);
        /* minus header */
        gap: 10px;
    }

    .col-movies {
        flex: 0 0 300px;
        background: white;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #ddd;
    }

    .col-map {
        flex: 1;
        background: #e0e0e0;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .col-cart {
        flex: 0 0 320px;
        background: white;
        display: flex;
        flex-direction: column;
        border-left: 1px solid #ddd;
    }

    .panel-header {
        background: #333;
        color: white;
        padding: 10px;
        font-weight: bold;
        text-align: center;
    }

    .scrollable {
        overflow-y: auto;
        flex: 1;
        padding: 10px;
    }

    /* MOVIES */
    .movie-item {
        border-bottom: 1px solid #eee;
        padding: 10px;
        cursor: pointer;
    }

    .movie-item:hover {
        background: #f9f9f9;
    }

    .movie-item.active {
        background: #fee;
        border-left: 4px solid #c01820;
    }

    .movie-title {
        font-weight: bold;
        font-size: 0.95rem;
    }

    .movie-times {
        margin-top: 5px;
        display: none;
    }

    /* Hidden by default */
    .movie-item.active .movie-times {
        display: block;
    }

    .time-slot {
        display: inline-block;
        background: #ddd;
        padding: 4px 8px;
        margin: 2px;
        border-radius: 4px;
        font-size: 0.85rem;
        cursor: pointer;
    }

    .time-slot:hover {
        background: #ccc;
    }

    .time-slot.active {
        background: #c01820;
        color: white;
    }

    /* MAP */
    .screen-box {
        background: #666;
        color: #aaa;
        text-align: center;
        padding: 5px;
        margin: 10px auto;
        width: 60%;
        transform: perspective(200px) rotateX(-5deg);
        border-radius: 4px;
        font-size: 0.8rem;
    }

    .center-content {
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .seats-grid {
        display: grid;
        gap: 5px;
    }

    /* Columns set via JS */

    .seat {
        width: 32px;
        height: 32px;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        cursor: pointer;
        user-select: none;
    }

    .seat.pasillo {
        background: transparent;
        border: none;
        cursor: default;
    }

    .seat.occupied {
        background: #b00;
        color: white;
        border-color: #900;
        cursor: not-allowed;
    }

    .seat.selected {
        background: #28a745;
        color: white;
        border-color: #1e7e34;
    }

    .dot {
        width: 10px;
        height: 10px;
        display: inline-block;
        border-radius: 50%;
        margin-right: 5px;
    }

    .dot.free {
        background: #fff;
        border: 1px solid #ccc;
    }

    .dot.selected {
        background: #28a745;
    }

    .dot.occupied {
        background: #b00;
    }

    /* CART */
    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .cart-seat-id {
        font-weight: bold;
        width: 40px;
    }

    .cart-select {
        width: 120px;
        font-size: 0.85rem;
        padding: 2px;
    }

    .cart-price {
        font-weight: bold;
        width: 60px;
        text-align: right;
    }

    .cart-footer {
        background: #f4f4f4;
        padding: 15px;
        border-top: 1px solid #ddd;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .btn-pay {
        width: 100%;
        padding: 15px;
        background: #c01820;
        color: white;
        border: none;
        font-size: 1rem;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-pay:hover {
        background: #a0141b;
    }
</style>

<script>
    let currentMovies = [];
    let currentTariffs = [];
    let selectedSeats = new Map(); // id -> {label, tariffId, price}
    let currentFunctionId = 0;

    document.addEventListener('DOMContentLoaded', () => {
        loadMovies();
    });

    // 1. DATA LOADING
    async function loadMovies() {
        const res = await fetch('ajax/get_movies.php');
        const response = await res.json();

        if (response.error) {
            document.getElementById('movies-list').innerHTML = `<p class="error">${response.error}</p>`;
            return;
        }

        const movies = response.data || [];
        console.log("Local ID used:", response.debug_local);
        currentMovies = movies;
        renderMovies(movies);
    }

    function renderMovies(movies) {
        const container = document.getElementById('movies-list');
        container.innerHTML = '';

        if (movies.error) {
            container.innerHTML = `<p class="error">${movies.error}</p>`;
            return;
        }

        movies.forEach(m => {
            const div = document.createElement('div');
            div.className = 'movie-item';
            div.onclick = (e) => toggleMovie(div); // Expand accordion

            let timesHtml = '';
            if (m.funciones) {
                for (const [date, funcs] of Object.entries(m.funciones)) {
                    // Format Date
                    const dateObj = new Date(date + 'T12:00:00'); // Prevent timezone shift
                    const dateStr = dateObj.toLocaleDateString('es-ES', {
                        weekday: 'short',
                        day: 'numeric'
                    });

                    timesHtml += `<div style="margin:5px 0; font-size:0.8rem; color:#666;">${dateStr.toUpperCase()}</div>`;

                    funcs.forEach(f => {
                        timesHtml += `<span class="time-slot" onclick="loadFunction(${f.id}, '${m.nombre}', '${f.sala}', event)">${f.hora.substring(0,5)}</span>`;
                    });
                }
            }

            div.innerHTML = `
            <div class="movie-title">${m.nombre}</div>
            <div class="movie-times">${timesHtml}</div>
        `;
            container.appendChild(div);
        });
    }

    function toggleMovie(el) {
        // Accordion logic: only one active at a time
        document.querySelectorAll('.movie-item').forEach(i => i.classList.remove('active'));
        el.classList.add('active');
    }

    // 2. MAP LOGIC
    async function loadFunction(funcId, movieName, roomName, event) {
        if (event) event.stopPropagation(); // Prevent toggling accordion again

        // UI Update
        document.querySelectorAll('.time-slot').forEach(t => t.classList.remove('active'));
        event.target.classList.add('active');

        // Clear State
        currentFunctionId = funcId;
        selectedSeats.clear();
        updateCartUI();
        document.getElementById('input_funcion').value = funcId;
        document.getElementById('map-title').innerText = `${movieName} - ${roomName}`;
        document.getElementById('seat-map-container').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Cargando Sala...</div>';
        document.querySelector('.screen-box').style.display = 'block';
        document.getElementById('map-legend').style.display = 'block';

        // Fetch Map
        const res = await fetch(`ajax/get_map.php?id=${funcId}`);
        const data = await res.json();

        currentTariffs = data.tarifas;
        // Prefer max_cols from info, fallback to columns
        const cols = data.info.max_cols || data.info.columns || 20;
        renderMap(data.seats, cols);
    }

    function renderMap(seats, cols) {
        // cols parameter might be the old auto value, rely on stored data or pass max_cols explicitly
        // Actually, we passed data.info.columns to this function, but now we have data.info.max_cols too.

        // Use implicit access to the last fetched data or just fix the caller. 
        // Let's assume the caller passes max_cols or we handle it here.
        // Actually, let's just use the 'cols' argument but ensure the caller passes the right one.

        const container = document.getElementById('seat-map-container');
        container.innerHTML = '';

        const grid = document.createElement('div');
        grid.className = 'seats-grid';
        // Use the passed cols which should be max_cols
        grid.style.gridTemplateColumns = `repeat(${cols}, 32px)`;
        // Add relative positioning or grid-area if needed, but grid-column/row is enough.

        seats.forEach(s => {
            const div = document.createElement('div');
            // Use cx and rx if available
            // If checking existence: (s.cx && s.rx)

            div.className = `seat ${s.t == 'PASILLO' ? 'pasillo' : ''} ${s.o ? 'occupied' : ''}`;
            div.innerText = s.t == 'PASILLO' ? '' : (s.r + s.c);
            div.dataset.id = s.id;
            div.dataset.label = s.r + s.c;

            // EXPLICIT POSITIONING
            if (s.cx && s.rx) {
                div.style.gridColumn = s.cx;
                div.style.gridRow = s.rx;
            }

            if (s.t != 'PASILLO' && !s.o) {
                div.onclick = () => toggleSeat(div);
            }

            grid.appendChild(div);
        });

        container.appendChild(grid);
    }

    function toggleSeat(el) {
        const id = el.dataset.id;
        const label = el.dataset.label;

        if (selectedSeats.has(id)) {
            selectedSeats.delete(id);
            el.classList.remove('selected');
        } else {
            // Default tariff: First one (Adulto usually)
            const defaultTariff = currentTariffs[0];
            selectedSeats.set(id, {
                label: label,
                tariffId: defaultTariff.id,
                price: parseFloat(defaultTariff.precio)
            });
            el.classList.add('selected');
        }
        updateCartUI();
    }

    // 3. CART LOGIC
    function updateCartUI() {
        const container = document.getElementById('cart-items');
        container.innerHTML = '';

        if (selectedSeats.size === 0) {
            container.innerHTML = '<p style="text-align: center; color: #666; margin-top: 20px;">Seleccione asientos</p>';
            document.getElementById('cart-total').innerText = 'S/ 0.00';
            return;
        }

        let total = 0;

        // Sort logic removed for brevity, iteration order is insertion order in Maps
        selectedSeats.forEach((data, id) => {
            const row = document.createElement('div');
            row.className = 'cart-item';

            // Tariff Options
            let options = '';
            currentTariffs.forEach(t => {
                const selected = t.id == data.tariffId ? 'selected' : '';
                options += `<option value="${t.id}" data-price="${t.precio}" ${selected}>${t.nombre}</option>`;
            });

            row.innerHTML = `
            <span class="cart-seat-id">${data.label}</span>
            <select class="cart-select" onchange="changeTariff('${id}', this)">${options}</select>
            <span class="cart-price">${data.price.toFixed(2)}</span>
        `;
            container.appendChild(row);
            total += data.price;
        });

        document.getElementById('cart-total').innerText = 'S/ ' + total.toFixed(2);
    }

    function changeTariff(seatId, selectEl) {
        const newTariffId = selectEl.value;
        const newPrice = parseFloat(selectEl.selectedOptions[0].dataset.price);

        const seat = selectedSeats.get(seatId);
        seat.tariffId = newTariffId;
        seat.price = newPrice;
        selectedSeats.set(seatId, seat);

        updateCartUI();
    }

    function processPayment() {
        if (selectedSeats.size === 0) {
            alert('Seleccione al menos un asiento.');
            return;
        }

        // Build hidden inputs
        const container = document.getElementById('hidden-inputs');
        container.innerHTML = '';

        let i = 0;
        selectedSeats.forEach((data, id) => {
            container.innerHTML += `
            <input type="hidden" name="asientos[${i}][id]" value="${id}">
            <input type="hidden" name="asientos[${i}][id_tarifa]" value="${data.tariffId}">
            <input type="hidden" name="asientos[${i}][nombre]" value="${data.label}">
        `;
            i++;
        });

        document.getElementById('checkout-form').submit();
    }
</script>

<?php include 'includes/footer.php'; ?>