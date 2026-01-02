<?php
define('IS_POS', true);
require_once '../panel/config/config.php';
require_once 'includes/auth_check.php';

// Verify permission
// POS Rule: Only 'cajero' role (checked in auth_check?? no strictly in auth.php)
// Verify specific module permission
$userId = $_SESSION['pos_user_id'];
$stmtUser = $db->prepare("SELECT permiso_dulceria, permiso_boleteria FROM tbl_usuarios WHERE id = ?");
$stmtUser->execute([$userId]);
$perms = $stmtUser->fetch();

if (!$perms['permiso_dulceria']) {
    // If no permission, redirect to dashboard (Boleteria) if allowed, or error
    if ($perms['permiso_boleteria']) {
        header("Location: dashboard.php");
        exit;
    } else {
        die("No tienes permisos asignados.");
    }
}

// Check Series
$stmtSerie = $db->prepare("SELECT serie, correlativo FROM tbl_series WHERE id_usuario = ? AND estado = '1' AND tipo = 'B'");
$stmtSerie->execute([$userId]);
$mySerie = $stmtSerie->fetch();

$paymentMethods = $db->query("SELECT * FROM tbl_formas_pago WHERE estado = '1'")->fetchAll();

include 'includes/header.php';
?>

<div class="pos-container">
    <!-- LEFT: Products -->
    <div class="products-pane">
        <div class="mb-20" style="position: sticky; top: 0; z-index: 10; background: #fff; padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">
            <input type="text" id="search-product" class="form-control" placeholder="Buscar productos..." style="width: 70%;" autofocus>
            <?php if ($perms['permiso_boleteria']): ?>
                <a href="dashboard.php" class="btn btn-secondary">IR A BOLETERÍA</a>
            <?php endif; ?>
        </div>

        <div id="product-grid" class="product-grid"></div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="cart-pane">
        <div class="cart-header">
            <h3 class="m-0"><i class="fas fa-candy-cane"></i> DULCERÍA</h3>
            <div class="text-sm text-muted mt-5">
                Serie: <?php echo $mySerie ? $mySerie['serie'] . '-' . str_pad($mySerie['correlativo'] + 1, 8, '0', STR_PAD_LEFT) : '<span class="text-danger">SIN SERIE</span>'; ?>
            </div>
        </div>

        <div id="cart-items" class="cart-items"></div>

        <div class="cart-footer">
            <div class="total-row">
                <span>Total:</span>
                <span id="cart-total">S/ 0.00</span>
            </div>
            <button id="btn-pay" class="btn btn-primary btn-block btn-lg" disabled onclick="openPaymentModal()">
                <i class="fas fa-money-bill-wave"></i> PAGAR (F10)
            </button>
        </div>
    </div>
</div>

<!-- Modal Payment -->
<div id="modal-payment" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color:#fefefe; margin:5% auto; padding:20px; border:1px solid #888; width:50%; border-radius:8px;">
        <div class="d-flex justify-content-between mb-20">
            <h3><i class="fas fa-cash-register"></i> Finalizar Venta</h3>
            <span class="close" onclick="closePaymentModal()" style="font-size:28px; cursor:pointer;">&times;</span>
        </div>

        <div class="row">
            <div class="col-md-6">
                <label>Total a Pagar</label>
                <input type="text" id="pay-total" class="form-control text-right" readonly style="font-size: 1.5rem; font-weight: bold;">
            </div>
            <div class="col-md-6">
                <label>Cliente</label>
                <input type="text" id="client-name" class="form-control" value="Público General">
            </div>
        </div>
        <hr>
        <div class="row mb-10">
            <div class="col-md-5">
                <label>Método</label>
                <select id="pay-method" class="form-control">
                    <?php foreach ($paymentMethods as $pm): ?>
                        <option value="<?php echo $pm['id']; ?>"><?php echo htmlspecialchars($pm['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label>Monto</label>
                <input type="number" id="pay-amount" class="form-control" step="0.10">
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button class="btn btn-secondary btn-block" onclick="addPayment()">Agregar</button>
            </div>
        </div>

        <div id="payment-list" class="mb-20" style="background:#f9f9f9; padding:10px; border-radius:4px;"></div>

        <div class="payment-summary text-right">
            <h3>Pagado: <span id="label-paid" class="text-success">S/ 0.00</span></h3>
            <h3>Restante: <span id="label-remaining" class="text-danger">S/ 0.00</span></h3>
            <h3>Vuelto: <span id="label-change" class="text-primary">S/ 0.00</span></h3>
        </div>

        <div class="text-right mt-20">
            <button class="btn btn-secondary" onclick="closePaymentModal()">Cancelar</button>
            <button id="btn-finish-sale" class="btn btn-success" disabled onclick="processSale()">Confirmar Venta</button>
        </div>
    </div>
</div>

<style>
    .pos-container {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 20px;
        height: calc(100vh - 70px);
    }

    .products-pane {
        overflow-y: auto;
        background: #fff;
        border-right: 1px solid #ddd;
    }

    .cart-pane {
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
        padding: 10px;
    }

    .product-card {
        border: 1px solid #eee;
        padding: 10px;
        text-align: center;
        cursor: pointer;
        transition: 0.2s;
        border-radius: 6px;
    }

    .product-card:hover {
        border-color: #c01820;
        transform: translateY(-3px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .product-price {
        color: #c01820;
        font-weight: bold;
        font-size: 1.1rem;
    }

    .cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
        padding: 8px 0;
    }

    .cart-footer {
        padding: 15px;
        background: #f4f4f4;
        border-top: 1px solid #ddd;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        font-weight: bold;
        font-size: 1.4rem;
        margin-bottom: 10px;
    }

    .qty-btn {
        width: 25px;
        height: 25px;
        border: 1px solid #ddd;
        background: #fff;
        cursor: pointer;
    }

    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-primary {
        background: #c01820;
        color: white;
    }

    .btn-secondary {
        background: #666;
        color: white;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-lg {
        padding: 15px;
        font-size: 1.2rem;
    }

    .btn-block {
        width: 100%;
    }

    .form-control {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .d-flex {
        display: flex;
    }

    .justify-content-between {
        justify-content: space-between;
    }

    .text-right {
        text-align: right;
    }

    .mb-20 {
        margin-bottom: 20px;
    }

    .text-danger {
        color: #dc3545;
    }

    .text-success {
        color: #28a745;
    }

    .text-primary {
        color: #007bff;
    }
</style>

<script>
    let products = [];
    let cart = [];
    let payments = [];
    let cartTotal = 0;

    async function loadProducts() {
        try {
            const res = await fetch('dulceria/get_products.php');
            const data = await res.json();
            if (data.error) {
                alert(data.error);
                return;
            }
            products = data;
            renderProducts(products);
        } catch (e) {
            console.error(e);
        }
    }

    function renderProducts(list) {
        const grid = document.getElementById('product-grid');
        grid.innerHTML = '';
        list.forEach(p => {
            const el = document.createElement('div');
            el.className = 'product-card';
            el.onclick = () => addToCart(p);

            // Image Handling
            let imgHtml = '';
            if (p.imagen) {
                imgHtml = `<img src="../uploads/${p.imagen}" style="width:100%; height:120px; object-fit:cover; border-radius:4px; margin-bottom:10px;" onerror="this.src='https://via.placeholder.com/150?text=No+Image'">`;
            } else {
                imgHtml = `<div style="width:100%; height:120px; background:#eee; display:flex; align-items:center; justify-content:center; border-radius:4px; margin-bottom:10px; color:#aaa;"><i class="fas fa-image fa-2x"></i></div>`;
            }

            el.innerHTML = `
                ${imgHtml}
                <div style="font-weight:600; font-size:0.9rem; height:40px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">${p.nombre}</div>
                <div class="product-price">S/ ${p.precio_venta.toFixed(2)}</div>
             `;
            grid.appendChild(el);
        });
    }

    document.getElementById('search-product').addEventListener('keyup', (e) => {
        const term = e.target.value.toLowerCase();
        renderProducts(products.filter(p => p.nombre.toLowerCase().includes(term)));
    });

    function addToCart(p) {
        const item = cart.find(i => i.id === p.id);
        if (item) item.qty++;
        else cart.push({
            ...p,
            qty: 1,
            price: p.precio_venta
        });
        updateCart();
    }

    function updateCart() {
        const container = document.getElementById('cart-items');
        container.innerHTML = '';
        cartTotal = 0;
        cart.forEach((item, idx) => {
            cartTotal += item.price * item.qty;
            const row = document.createElement('div');
            row.className = 'cart-item';
            row.innerHTML = `
                <div style="flex:1;">
                    <div style="font-weight:600;">${item.nombre}</div>
                    <div style="color:#666;">${item.qty} x ${item.price.toFixed(2)}</div>
                </div>
                <div>
                     <button class="qty-btn" onclick="updateQty(${idx}, -1)">-</button>
                     <span style="margin:0 5px;">${item.qty}</span>
                     <button class="qty-btn" onclick="updateQty(${idx}, 1)">+</button>
                </div>
            `;
            container.appendChild(row);
        });
        document.getElementById('cart-total').innerText = 'S/ ' + cartTotal.toFixed(2);
        document.getElementById('btn-pay').disabled = cart.length === 0;
    }

    function updateQty(idx, change) {
        cart[idx].qty += change;
        if (cart[idx].qty <= 0) cart.splice(idx, 1);
        updateCart();
    }

    function openPaymentModal() {
        if (cart.length === 0) return;
        document.getElementById('modal-payment').style.display = 'block';
        document.getElementById('pay-total').value = 'S/ ' + cartTotal.toFixed(2);
        document.getElementById('pay-amount').value = cartTotal.toFixed(2);
        payments = [];
        updatePaymentUI();
    }

    function closePaymentModal() {
        document.getElementById('modal-payment').style.display = 'none';
    }

    function addPayment() {
        const inputAmount = document.getElementById('pay-amount');
        const amount = parseFloat(inputAmount.value);
        if (!amount || amount <= 0) return;

        const select = document.getElementById('pay-method');
        // Prevent duplicate method adding? Maybe yes, maybe no. Let's allow for now but typically unique.

        payments.push({
            id: select.value,
            name: select.options[select.selectedIndex].text,
            amount: amount
        });
        updatePaymentUI();

        // Auto-calc remaining and focus
        const paid = payments.reduce((sum, p) => sum + p.amount, 0); // Recalculate based on NEW list
        let rem = cartTotal - paid;

        // Fix precision
        rem = Math.round(rem * 100) / 100;

        if (rem > 0) {
            inputAmount.value = rem.toFixed(2);
            inputAmount.focus();
        } else {
            inputAmount.value = '';
        }
    }

    function updatePaymentUI() {
        const list = document.getElementById('payment-list');
        list.innerHTML = '';
        let totalPaid = 0;
        payments.forEach((p, idx) => {
            totalPaid += p.amount;
            list.innerHTML += `<div class="d-flex justify-content-between p-2 border-bottom">
                <span>${p.name}</span><strong>S/ ${p.amount.toFixed(2)}</strong>
                <button class="btn btn-sm btn-danger" onclick="payments.splice(${idx}, 1); updatePaymentUI();">X</button>
            </div>`;
        });
        document.getElementById('label-paid').innerText = 'S/ ' + totalPaid.toFixed(2);
        const rem = cartTotal - totalPaid;
        document.getElementById('label-remaining').innerText = 'S/ ' + (rem > 0 ? rem.toFixed(2) : '0.00');
        document.getElementById('label-change').innerText = 'S/ ' + (rem < 0 ? Math.abs(rem).toFixed(2) : '0.00');
        document.getElementById('btn-finish-sale').disabled = rem > 0.01;
    }

    async function processSale() {
        const btn = document.getElementById('btn-finish-sale');
        btn.disabled = true;
        btn.innerText = 'Procesando...';

        try {
            const res = await fetch('dulceria/checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    cart: cart.map(i => ({
                        id: i.id,
                        qty: i.qty,
                        price: i.price
                    })),
                    payments: payments,
                    total: cartTotal,
                    cliente: document.getElementById('client-name').value
                })
            });
            const data = await res.json();
            if (data.error) {
                alert(data.error);
                btn.disabled = false;
            } else {
                window.open('dulceria/print_ticket.php?id=' + data.venta_id, '_blank', 'width=400,height=600');
                location.reload();
            }
        } catch (e) {
            console.error(e);
            alert("Error procesando venta");
            btn.disabled = false;
        }
    }

    // Event Listeners for Payment
    document.getElementById('pay-amount').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addPayment();
        }
    });

    // Optional: Live Change Preview?
    // User confusion: They type 20 and don't see change. 
    // Let's rely on adding the payment first for clarity, as 'adding' commits that tender.
    // But we can make 'addPayment' smoother.

    loadProducts();
</script>

<?php include 'includes/footer.php'; ?>