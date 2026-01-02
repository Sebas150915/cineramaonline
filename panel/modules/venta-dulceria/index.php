<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Verify permission
$isSuper = ($_SESSION['rol'] === 'superadmin' || $_SESSION['rol'] === 'admin');
$hasPerm = isset($_SESSION['permiso_dulceria']) && $_SESSION['permiso_dulceria'];

if (!$isSuper && !$hasPerm) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

// Check if user has an active Series assigned
$stmtSerie = $db->prepare("SELECT serie, correlativo FROM tbl_series WHERE id_usuario = ? AND estado = '1' AND tipo = 'B'");
$stmtSerie->execute([$_SESSION['user_id']]);
$mySerie = $stmtSerie->fetch();

$paymentMethods = $db->query("SELECT * FROM tbl_formas_pago WHERE estado = '1'")->fetchAll();

$page_title = "Venta Dulcería";
// Don't include sidebar.php normally to give full screen space? Or keep it? 
// The user asked for a "Split Screen", usually POS is full width.
// I'll include header but maybe hide sidebar or make it collapsible.
// For now, I'll stick to standard layout but use a full-width container.
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* POS Specific Styles */
    .pos-container {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 20px;
        height: calc(100vh - 100px);
        overflow: hidden;
    }

    .products-pane {
        overflow-y: auto;
        padding-right: 10px;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }

    .product-card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
    }

    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-color);
    }

    .product-name {
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 0.95rem;
    }

    .product-price {
        color: var(--primary-color);
        font-weight: bold;
        font-size: 1.1rem;
    }

    .product-stock {
        font-size: 0.8rem;
        color: #777;
    }

    .cart-pane {
        background: white;
        border-radius: 8px;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .cart-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
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
        padding: 10px;
        border-bottom: 1px solid #eee;
        animation: fadeIn 0.2s;
    }

    .cart-item-info {
        flex: 1;
    }

    .cart-item-title {
        font-weight: 600;
        display: block;
    }

    .cart-item-price {
        font-size: 0.9rem;
        color: #666;
    }

    .cart-controls {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .qty-btn {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        border: 1px solid #ddd;
        background: #fff;
        cursor: pointer;
    }

    .cart-footer {
        padding: 20px;
        background: #f8f9fa;
        border-top: 2px solid #ddd;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 15px;
    }

    /* Modal Styles Override */
    .payment-row {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .payment-summary {
        font-size: 1.2rem;
        text-align: right;
        margin: 20px 0;
        padding: 10px;
        background: #e9ecef;
        border-radius: 6px;
    }

    .quick-cash-btn {
        margin: 2px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<main class="admin-content" style="padding: 10px;">
    <?php if (!$mySerie && !$isSuper): ?> <!-- Super can debug -->
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> No tienes una serie asignada. No puedes realizar ventas.
        </div>
    <?php endif; ?>

    <div class="pos-container">
        <!-- LEFT: Products -->
        <div class="products-pane">
            <div class="mb-20" style="position: sticky; top: 0; z-index: 10; background: #f4f6f9; padding-bottom: 10px;">
                <input type="text" id="search-product" class="form-control" placeholder="Buscar productos (Nombre, Código)..." autofocus>
            </div>

            <div id="product-grid" class="product-grid">
                <!-- Products injected via JS -->
                <div class="text-center w-100 p-20">Cargando productos...</div>
            </div>
        </div>

        <!-- RIGHT: Cart -->
        <div class="cart-pane">
            <div class="cart-header">
                <h3 class="m-0"><i class="fas fa-shopping-cart"></i> Carrito</h3>
                <div class="text-sm text-muted mt-5">
                    Serie: <?php echo $mySerie ? $mySerie['serie'] . '-' . str_pad($mySerie['correlativo'] + 1, 8, '0', STR_PAD_LEFT) : 'N/A'; ?>
                </div>
            </div>

            <div id="cart-items" class="cart-items">
                <!-- Cart Items injected JS -->
                <div class="text-center text-muted mt-50">
                    <i class="fas fa-basket-shopping fa-3x mb-10"></i>
                    <p>Carrito vacío</p>
                </div>
            </div>

            <div class="cart-footer">
                <div class="total-row">
                    <span>Total:</span>
                    <span id="cart-total">S/ 0.00</span>
                </div>
                <button id="btn-pay" class="btn btn-primary btn-block btn-lg" disabled onclick="openPaymentModal()">
                    <i class="fas fa-money-bill-wave"></i> PAGAR
                </button>
            </div>
        </div>
    </div>
</main>

<!-- Modal de Pago -->
<div id="modal-payment" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closePaymentModal()">&times;</span>
        <h2><i class="fas fa-cash-register"></i> Finalizar Venta</h2>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Total a Pagar</label>
                    <input type="text" id="pay-total" class="form-control text-right" readonly style="font-size: 1.5rem; font-weight: bold;">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Cliente</label>
                    <input type="text" id="client-name" class="form-control" value="Público General">
                </div>
            </div>
        </div>

        <hr>

        <div class="row mb-10">
            <div class="col-md-5">
                <label>Método de Pago</label>
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
                <button type="button" class="btn btn-secondary btn-block" onclick="addPayment()">Agregar</button>
            </div>
        </div>

        <!-- Quick Cash Buttons -->
        <div class="mb-10 text-center" id="quick-cash-area">
            <button class="btn btn-sm btn-outline quick-cash-btn" onclick="setCash(10)">S/ 10</button>
            <button class="btn btn-sm btn-outline quick-cash-btn" onclick="setCash(20)">S/ 20</button>
            <button class="btn btn-sm btn-outline quick-cash-btn" onclick="setCash(50)">S/ 50</button>
            <button class="btn btn-sm btn-outline quick-cash-btn" onclick="setCash(100)">S/ 100</button>
            <button class="btn btn-sm btn-outline quick-cash-btn" onclick="setExactCash()">Exacto</button>
        </div>

        <div id="payment-list" class="mb-20">
            <!-- Added payments -->
        </div>

        <div class="payment-summary">
            <div class="d-flex justify-content-between">
                <span>Pagado:</span>
                <span id="label-paid" class="text-success">S/ 0.00</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Restante:</span>
                <span id="label-remaining" class="text-danger">S/ 0.00</span>
            </div>
            <div class="d-flex justify-content-between mt-10" style="border-top: 1px solid #ccc; padding-top: 5px;">
                <span>Vuelto:</span>
                <span id="label-change" class="text-primary font-bold">S/ 0.00</span>
            </div>
        </div>

        <div class="text-right">
            <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Cancelar</button>
            <button type="button" id="btn-finish-sale" class="btn btn-success" disabled onclick="processSale()">
                <i class="fas fa-check-circle"></i> Confirmar Venta
            </button>
        </div>
    </div>
</div>

<script>
    let products = [];
    let cart = [];
    let payments = [];
    let cartTotal = 0;

    // Load Products
    async function loadProducts() {
        try {
            const res = await fetch('get_products.php');
            const data = await res.json();
            if (data.error) {
                alert(data.error);
                return;
            }
            products = data;
            renderProducts(products);
        } catch (e) {
            console.error(e);
            document.getElementById('product-grid').innerHTML = '<div class="p-20 text-danger">Error cargando productos</div>';
        }
    }

    // Render Grid
    function renderProducts(list) {
        const grid = document.getElementById('product-grid');
        grid.innerHTML = '';
        list.forEach(p => {
            const el = document.createElement('div');
            el.className = 'product-card';
            el.onclick = () => addToCart(p);
            el.innerHTML = `
                <div class="product-name">${p.nombre}</div>
                <div class="product-price">S/ ${p.precio_venta.toFixed(2)}</div>
                <div class="product-stock">${p.tipo === 'combo' ? 'Combo' : 'Stock: ' + p.stock}</div>
            `;
            grid.appendChild(el);
        });
    }

    // Search
    document.getElementById('search-product').addEventListener('keyup', (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = products.filter(p =>
            p.nombre.toLowerCase().includes(term) ||
            (p.codigo_barras && p.codigo_barras.toLowerCase().includes(term))
        );
        renderProducts(filtered);
    });

    // Cart Logic
    function addToCart(product) {
        const exist = cart.find(i => i.id === product.id);
        if (exist) {
            exist.qty++;
        } else {
            cart.push({
                ...product,
                qty: 1,
                price: product.precio_venta
            });
        }
        updateCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCart();
    }

    function updateQty(index, change) {
        cart[index].qty += change;
        if (cart[index].qty <= 0) {
            removeFromCart(index);
        } else {
            updateCart();
        }
    }

    function updateCart() {
        const container = document.getElementById('cart-items');
        container.innerHTML = '';
        cartTotal = 0;

        if (cart.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted mt-50">
                    <i class="fas fa-basket-shopping fa-3x mb-10"></i>
                    <p>Carrito vacío</p>
                </div>`;
            document.getElementById('btn-pay').disabled = true;
        } else {
            cart.forEach((item, index) => {
                const subtotal = item.qty * item.price;
                cartTotal += subtotal;

                const el = document.createElement('div');
                el.className = 'cart-item';
                el.innerHTML = `
                    <div class="cart-item-info">
                        <span class="cart-item-title">${item.nombre}</span>
                        <span class="cart-item-price">${item.qty} x S/ ${item.price.toFixed(2)} = S/ ${subtotal.toFixed(2)}</span>
                    </div>
                    <div class="cart-controls">
                        <button class="qty-btn" onclick="updateQty(${index}, -1)">-</button>
                        <span>${item.qty}</span>
                        <button class="qty-btn" onclick="updateQty(${index}, 1)">+</button>
                        <button class="qty-btn text-danger" onclick="removeFromCart(${index})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                container.appendChild(el);
            });
            document.getElementById('btn-pay').disabled = false;
        }

        document.getElementById('cart-total').innerText = 'S/ ' + cartTotal.toFixed(2);
    }

    // Payment Logic
    function openPaymentModal() {
        document.getElementById('modal-payment').style.display = 'block';
        document.getElementById('pay-total').value = 'S/ ' + cartTotal.toFixed(2);
        document.getElementById('pay-amount').value = cartTotal.toFixed(2); // Prefill full amount
        payments = [];
        updatePaymentUI();
    }

    function closePaymentModal() {
        document.getElementById('modal-payment').style.display = 'none';
    }

    function addPayment() {
        const methodSelect = document.getElementById('pay-method'); // ID of method
        const methodName = methodSelect.options[methodSelect.selectedIndex].text;
        const amount = parseFloat(document.getElementById('pay-amount').value);

        if (!amount || amount <= 0) return;

        payments.push({
            id: methodSelect.value,
            name: methodName,
            amount: amount
        });
        updatePaymentUI();

        // Clear input logic for next payment?
        // Calculate remaining
        const paid = payments.reduce((sum, p) => sum + p.amount, 0);
        const remaining = cartTotal - paid;
        if (remaining > 0) {
            document.getElementById('pay-amount').value = remaining.toFixed(2);
        } else {
            document.getElementById('pay-amount').value = '';
        }
    }

    function removePayment(index) {
        payments.splice(index, 1);
        updatePaymentUI();
    }

    function setCash(val) {
        document.getElementById('pay-amount').value = val.toFixed(2);
        // Auto select Efectivo?
        const sel = document.getElementById('pay-method');
        // Find option with text 'Efectivo'
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].text.includes('Efectivo')) {
                sel.selectedIndex = i;
                break;
            }
        }
    }

    function setExactCash() {
        const paid = payments.reduce((sum, p) => sum + p.amount, 0);
        const remaining = Math.max(0, cartTotal - paid);
        setCash(remaining);
    }

    function updatePaymentUI() {
        const list = document.getElementById('payment-list');
        list.innerHTML = '';
        let totalPaid = 0;

        payments.forEach((p, idx) => {
            totalPaid += p.amount;
            const row = document.createElement('div');
            row.className = 'payment-row';
            row.innerHTML = `
                <span>${p.name}</span>
                <strong>S/ ${p.amount.toFixed(2)}</strong>
                <button class="btn btn-sm btn-danger" onclick="removePayment(${idx})"><i class="fas fa-times"></i></button>
            `;
            list.appendChild(row);
        });

        document.getElementById('label-paid').innerText = 'S/ ' + totalPaid.toFixed(2);
        const remaining = cartTotal - totalPaid;
        const change = totalPaid - cartTotal;

        if (remaining > 0.01) {
            document.getElementById('label-remaining').innerText = 'S/ ' + remaining.toFixed(2);
            document.getElementById('label-change').innerText = 'S/ 0.00';
            document.getElementById('btn-finish-sale').disabled = true;
        } else {
            document.getElementById('label-remaining').innerText = 'S/ 0.00';
            // Change is only relevant if there is Cash involved? Assuming yes.
            document.getElementById('label-change').innerText = 'S/ ' + (change > 0 ? change.toFixed(2) : '0.00');
            document.getElementById('btn-finish-sale').disabled = false;
        }
    }

    async function processSale() {
        const btn = document.getElementById('btn-finish-sale');
        btn.disabled = true;
        btn.innerText = 'Procesando...';

        const clientName = document.getElementById('client-name').value;

        const payload = {
            cart: cart.map(i => ({
                id: i.id,
                qty: i.qty,
                price: i.price
            })),
            payments: payments.map(p => ({
                id: p.id,
                amount: p.amount
            })),
            total: cartTotal,
            cliente: clientName
        };

        try {
            const res = await fetch('checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.error) {
                alert('Error: ' + data.error);
                btn.disabled = false;
                btn.innerText = 'Confirmar Venta';
            } else {
                alert('Venta Exitosa! Ticket: ' + data.ticket_number);
                // Reset everything
                cart = [];
                payments = [];
                updateCart();
                closePaymentModal();
                btn.disabled = false;
                btn.innerText = 'Confirmar Venta';
                // Reload to refresh stock or sidebar correlative? 
                window.open('print_ticket.php?id=' + data.venta_id, '_blank', 'width=400,height=600');
                location.reload();
            }
        } catch (e) {
            console.error(e);
            alert('Error de red al procesar venta');
            btn.disabled = false;
        }
    }

    // Init
    loadProducts();
</script>

<?php include '../../includes/footer.php'; ?>