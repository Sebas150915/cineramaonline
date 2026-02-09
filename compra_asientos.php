<?php
require_once 'includes/front_config.php';

// Validar ID básico solo para asegurar que existe parámetro
$id_funcion = isset($_GET['id_funcion']) ? (int)$_GET['id_funcion'] : 0;

if ($id_funcion <= 0) {
    header('Location: index.php');
    exit;
}

$page_title = "Selección de Asientos";
include 'includes/header_front.php';
?>

<div class="container">
    <h2 class="section-title">Elige tus Butacas</h2>
    
    <!-- Info Container (Filled by JS) -->
    <p id="movie-info" style="text-align: center; color: #aaa;">
        Cargando información de la función...
    </p>

    <div class="screen-container">
        <div class="screen">PANTALLA</div>
    </div>

    <div id="seats-wrapper">
        <input type="hidden" id="id_funcion_static" value="<?php echo $id_funcion; ?>">

        <div id="seats-container" class="seats-container" style="margin: 30px auto; width: max-content; min-height: 300px; display: flex; align-items: center; justify-content: center;">
             <div class="loader">Cargando mapa de asientos...</div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <div style="margin-bottom: 20px; color: #333; font-weight: bold;">
                <span style="margin-right: 15px;"><span class="seat example"></span> Libre</span>
                <span style="margin-right: 15px;"><span class="seat example selected"></span> Seleccionado</span>
                <span><span class="seat example occupied"></span> Ocupado</span>
            </div>

            <button type="button" id="btn-continuar" class="btn btn-danger" onclick="submitSeats()" disabled>Continuar a la Compra</button>
        </div>
    </div>
</div>

<style>
    /* ... (Existing Styles) ... */
    .screen {
        width: 80%;
        height: 40px;
        background: #333;
        margin: 0 auto 30px;
        border-radius: 5px;
        text-align: center;
        line-height: 40px;
        color: #666;
        box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
        transform: perspective(300px) rotateX(-5deg);
    }

    .seat {
        width: 35px;
        height: 35px;
        background: #444;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #fff;
        user-select: none;
        transition: background .2s;
    }

    .seat.normal:hover {
        background: #666;
    }

    /* Selected state */
    .seat.selected {
        background: #4CAF50 !important;
        color: #fff;
    }

    /* Occupied state */
    .seat.occupied {
        background: #b71c1c !important;
        color: #fff !important;
        cursor: not-allowed;
    }

    /* Aisle (if rendered) */
    .seat.aisle {
        background: transparent;
        cursor: default;
        pointer-events: none;
    }

    /* Example legend icons override */
    .seat.example {
        width: 20px;
        height: 20px;
        display: inline-block;
        vertical-align: middle;
        margin-right: 5px;
        font-size: 0;
    }
</style>

<script>
    const ID_FUNCION = <?php echo $id_funcion; ?>;
    const MAX_SEATS = 5;
    const POLLING_INTERVAL = 2000; 

    let selectedCount = 0;
    let salaData = null; // Will hold API data
    let rowMap = {}; // Helper for rendering

    // Main function to fetch and render
    async function loadSalaData() {
        try {
            const response = await fetch(`api/get_sala_data.php?id_funcion=${ID_FUNCION}`);
            if (!response.ok) throw new Error('Error de red al cargar datos');
            
            const data = await response.json();
            
            if (data.error) {
                alert(data.error);
                window.location.href = 'index.php';
                return;
            }

            salaData = data;
            renderInfo();
            renderGrid();
            
        } catch (error) {
            console.error(error);
            document.getElementById('seats-container').innerHTML = '<p style="color:red">Error al cargar el mapa de la sala. Por favor recarga la página.</p>';
        }
    }

    function renderInfo() {
        const infoDiv = document.getElementById('movie-info');
        if (salaData && salaData.info) {
            infoDiv.innerHTML = `
                ${salaData.info.cine} - ${salaData.info.sala} | 
                <strong>${salaData.info.pelicula}</strong>
            `;
        }
    }

    function renderGrid() {
        const container = document.getElementById('seats-container');
        container.innerHTML = ''; 

        // 1. Calculate Grid Dimensions
        // Unique rows
        const uniqueRows = [...new Set(salaData.layout.map(s => s.fila))];
        // Sort rows if needed (usually DB returns ordered, but let's trust API order)
        
        // Map row letter to index (1-based)
        rowMap = {};
        uniqueRows.forEach((r, i) => rowMap[r] = i + 1);

        const cols = salaData.info.cols || 10; // Fallback
        
        // Apply Grid Styles
        container.style.display = 'grid';
        container.style.gridTemplateColumns = `30px repeat(${cols}, 35px) 30px`;
        container.style.gap = '5px';
        container.style.alignItems = 'start'; // Reset alignment

        // 2. Render Labels
        uniqueRows.forEach((letter, index) => {
            const rowIndex = index + 1;
            // Left
            container.appendChild(createLabel(letter, 1, rowIndex));
            // Right
            container.appendChild(createLabel(letter, cols + 2, rowIndex));
        });

        // 3. Render Seats
        const occupiedSet = new Set(salaData.occupied.map(String));

        salaData.layout.forEach(seat => {
            if (seat.tipo === 'PASILLO') return;

            const rIndex = rowMap[seat.fila];
            const cIndex = parseInt(seat.columna) + 1; // +1 for label

            const seatDiv = document.createElement('div');
            seatDiv.className = `seat ${seat.tipo.toLowerCase()}`;
            seatDiv.dataset.id = seat.id;
            seatDiv.dataset.rotulo = seat.fila + seat.num_asiento;
            seatDiv.style.gridColumn = cIndex;
            seatDiv.style.gridRow = rIndex;
            seatDiv.textContent = seat.fila + seat.num_asiento;

            if (occupiedSet.has(String(seat.id))) {
                seatDiv.classList.add('occupied');
            }

            seatDiv.onclick = () => toggleSeat(seatDiv);
            container.appendChild(seatDiv);
        });
    }

    function createLabel(text, col, row) {
        const div = document.createElement('div');
        div.textContent = text;
        div.style.gridColumn = col;
        div.style.gridRow = row;
        div.style.fontWeight = 'bold';
        div.style.color = '#333';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.justifyContent = 'center';
        return div;
    }

    function toggleSeat(element) {
        if (element.classList.contains('occupied')) return;
        
        if (element.classList.contains('selected')) {
            element.classList.remove('selected');
            selectedCount--;
        } else {
            if (selectedCount >= MAX_SEATS) {
                alert('Solo puedes seleccionar un máximo de ' + MAX_SEATS + ' butacas.');
                return;
            }
            element.classList.add('selected');
            selectedCount++;
        }
        updateForm();
    }

    function updateForm() {
        const selected = document.querySelectorAll('.seat.selected');
        const ids = Array.from(selected).map(el => el.dataset.id);
        
        document.getElementById('btn-continuar').disabled = (ids.length === 0);
    }

    // Polling only for status updates
    async function updateAvailability() {
        if (!salaData) return;

        try {
            // We can reuse the same endpoint or a lighter one. 
            // Reusing get_sala_data is easier but heavier. 
            // Ideally we use a 'check_status' param or the old check_seats logic inside get_sala_data
            // Let's call the same endpoint for simplicity as requested "eliminar todo del front"
            
            const response = await fetch(`api/get_sala_data.php?id_funcion=${ID_FUNCION}`);
            if (!response.ok) return;
            const data = await response.json();
            
            if (data.occupied) {
                const newOccupied = new Set(data.occupied.map(String));
                const allSeats = document.querySelectorAll('.seat[data-id]');
                let selectionChanged = false;

                allSeats.forEach(seat => {
                    const id = seat.dataset.id;
                    const isOccupied = newOccupied.has(id);
                    const isSelected = seat.classList.contains('selected');
                    const wasOccupied = seat.classList.contains('occupied');

                    if (isOccupied) {
                        if (!wasOccupied) seat.classList.add('occupied');
                        if (isSelected) {
                            seat.classList.remove('selected');
                            selectedCount--;
                            selectionChanged = true;
                        }
                    } else {
                        if (wasOccupied) seat.classList.remove('occupied');
                    }
                });

                if (selectionChanged) {
                    alert('Algunos asientos seleccionados ya no están disponibles.');
                    updateForm();
                }
            }
        } catch (e) {
            console.error("Polling error", e);
        }
    }

    function submitSeats() {
        const selected = document.querySelectorAll('.seat.selected');
        const ids = Array.from(selected).map(el => el.dataset.id);

        if (ids.length === 0) {
            alert("Por favor selecciona al menos una butaca.");
            return;
        }

        // Direct URL redirection (Foolproof GET)
        const selectedStr = ids.join(',');
        const url = `compra_pre_booking.php?id_funcion=${ID_FUNCION}&selected_seats=${selectedStr}`;
        
        // Debug for user
        alert("Redirigiendo a: " + url);
        
        console.log("Redirecting to:", url);
        window.location.href = url;
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadSalaData();
        setInterval(updateAvailability, POLLING_INTERVAL);
    });
</script>

<?php include 'includes/footer_front.php'; ?>