<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

// Role-based access control
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'Sales', 'Cashier'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Retail POS System</title>
    <?php include '../includes/head.php'; ?>
    <link rel="stylesheet" href="../css/pos_style.css">
</head>

<body>
    <div class="pos-wrapper">
        <!-- Left Panel - Products -->
        <div class="products-panel">
            <!-- Top Bar -->
            <div class="top-bar" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <div class="nav-actions">
                    <a href="/ShoeRetailErp/public/index.php" class="btn btn-outline" title="Back to Dashboard" style="display:inline-flex; align-items:center; gap:6px;">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Dashboard</span>
                    </a>
                </div>
                <div class="store-info">
                    <h1>👞 Shoe Retail POS</h1>
                    <p>Terminal #1 • Store Location: Main Branch</p>
                </div>
                <div class="cashier-info">
                    <p>Cashier</p>
                    <p class="cashier-name">Juan Dela Cruz</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="search-container">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search products by name or code..." onkeyup="filterProducts()">
                </div>
                <div class="category-tabs">
                    <button class="category-tab active" onclick="filterByCategory('All')">All Products</button>
                    <button class="category-tab" onclick="filterByCategory('Sneakers')">Sneakers</button>
                    <button class="category-tab" onclick="filterByCategory('Running')">Running</button>
                    <button class="category-tab" onclick="filterByCategory('Formal')">Formal</button>
                    <button class="category-tab" onclick="filterByCategory('Casual')">Casual</button>
                    <button class="category-tab" onclick="filterByCategory('Sports')">Sports</button>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-container">
                <div class="products-grid" id="productsGrid"></div>
            </div>
        </div>

        <!-- Right Panel - Cart -->
        <div class="cart-panel">
            <div class="cart-header">
                <div class="cart-title">Current Order</div>
                <div class="order-number">Order #<span id="orderNumber">00124</span></div>
            </div>

            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #eee;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <div style="position:relative; flex:1;">
                        <input type="text" id="memberInput" class="form-control" placeholder="Member # or name" style="width:100%;">
                        <div id="customerSuggest" class="suggest-box" style="display:none;"></div>
                    </div>
                </div>
                <div id="customerInfo" style="font-size:12px; color:#555; margin-top:6px;">Walk-in</div>
                <div id="pointsHint" style="font-size:12px; color:#777; margin-top:2px;"></div>
                <div style="display:flex; gap:8px; align-items:center; margin-top:6px;">
                    <input type="number" id="pointsUse" class="form-control" placeholder="Points to use (₱)" min="0" value="0" style="flex:1;">
                </div>
            </div>

            <div class="cart-items-container" id="cartItems">
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <p>No items in cart</p>
                    <p style="font-size: 0.875rem; margin-top: 0.5rem;">Select products to add to order</p>
                </div>
            </div>

            <div class="cart-summary">
                <div style="padding: 0.5rem 0; display:flex; gap:8px; align-items:center;">
                    <input type="number" id="discountInput" class="form-control" placeholder="Discount (₱)" min="0" step="0.01" style="flex:1;">
                </div>
                <div class="summary-row subtotal">
                    <span>Subtotal</span>
                    <span id="subtotal">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>Tax (10%)</span>
                    <span id="tax">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>Discount</span>
                    <span id="discount">₱0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span id="total">₱0.00</span>
                </div>
                <!-- 
                <div class="page-header-actions">
                    <button class="btn btn-secondary" id="addVoucherBtn"></i> Add Voucher</button>
                </div> -->

            <div class="cart-actions">
                    <select id="optionsDropdown" class="dropdown" disabled>
                        <option value="" disabled selected>Select Payment Mode</option>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                    </select>
                    <select id="paymentStatusSelect" class="dropdown" style="margin-left:8px;">
                        <option value="Paid" selected>Paid</option>
                        <option value="Credit">Credit</option>
                        <option value="Partial">Partial</option>
                    </select>
                    <input type="number" id="partialAmountInput" class="form-control" placeholder="Amount Paid" min="0" step="0.01" style="width:140px; display:none; margin-left:8px;">
                    <button class="btn btn-outline" id="voucherBtn">Add Voucher</button>
                    <button class="btn btn-outline" id="clearBtn" onclick="clearCart()">Clear</button>
                    <button class="btn btn-primary" id="checkoutBtn" onclick="checkout()" disabled>Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Voucher Modal -->
    <div class="modal-backdrop" id="voucherModalBackdrop"></div>
    <div class="modal" id="voucherModal">
        <div class="modal-header">
            <h3>Apply Voucher</h3>
            <button class="modal-close" id="closeVoucherModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="voucherForm">
                <div class="form-group">
                    <label for="voucherCode" class="form-label">Voucher Code</label>
                    <input type="text" id="voucherCode" name="voucherCode" class="form-control" placeholder="Enter your code">
                </div>
                <div class="form-help">Example: <code>DISCOUNT10</code> or <code>FREESHIP</code></div>
            </form>
            <div id="voucherMessage" class="form-help"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="closeVoucherBtn">Cancel</button>
            <button class="btn btn-success" id="applyVoucherBtn">Apply</button>
        </div>
    </div>
    </div>

    <!-- Size Selection Modal -->
    <div class="modal-overlay" id="sizeModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Select Size</div>
                <div class="modal-subtitle" id="modalProductName"></div>
            </div>
            <div class="size-grid" id="sizeOptions"></div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="addToCart()">Add to Order</button>
            </div>
        </div>
    </div>
    <!-- Cash Payment Modal -->
    <!-- Payment Mode Modal -->
    <div class="modal-backdrop" id="paymentModalBackdrop"></div>
    <div class="modal" id="paymentModal">
        <div class="modal-header">
            <h3>Select Payment Mode</h3>
            <button class="modal-close" id="closePaymentModal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="selectedPaymentMode" class="form-label">Payment Mode</label>
                <select id="selectedPaymentMode" class="form-control">
                    <option value="" disabled selected>Select Payment Mode</option>
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                </select>
            </div>
            <div id="paymentMessage" class="form-help"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="cancelPaymentBtn">Cancel</button>
            <button class="btn btn-success" id="confirmPaymentBtn" disabled>Confirm</button>
        </div>
    </div>
    <!-- PAYMENT INPUT MODAL -->
    <div class="modal-backdrop" id="paymentCalcBackdrop"></div>
    <div class="modal" id="paymentCalcModal">
        <div class="modal-header">
            <h3>Cash Payment</h3>
            <button class="modal-close" id="closePaymentCalc">&times;</button>
        </div>

        <div class="modal-body">
            <p style="font-size: 1rem;">Total Due: <strong id="paymentTotal">₱0.00</strong></p>
            <input type="text" id="paymentInput" class="form-control" placeholder="Enter Amount" readonly>

            <div class="calc-grid">
                <button class="calc-btn">7</button>
                <button class="calc-btn">8</button>
                <button class="calc-btn">9</button>
                <button class="calc-btn">4</button>
                <button class="calc-btn">5</button>
                <button class="calc-btn">6</button>
                <button class="calc-btn">1</button>
                <button class="calc-btn">2</button>
                <button class="calc-btn">3</button>
                <button class="calc-btn">0</button>
                <button class="calc-btn clear">Clear</button>
            </div>

            <p id="changeOutput" style="margin-top:10px; font-weight:bold; color:#333;"></p>
        </div>

        <div class="modal-footer">
            <button class="btn btn-outline" id="backBtn">Back</button>
            <button class="btn btn-primary" id="confirmBtn" disabled>Confirm</button>
        </div>
    </div>


    <script>
        // Product catalog will be fetched from backend for real-time data
        let products = [];

        let cart = [];
        let currentProduct = null;
        let selectedSize = null;
        let currentCategory = 'All';
        let totalAmount = 0;
        let selectedCustomer = null;
        let pointsToUse = 0;
        let availablePoints = 0;
        let discountAmount = 0;
        // suggestions state
        let suggestList = [];
        let suggestIndex = -1;

        // ==== INIT ====
        // small debounce helper
        const debounce = (fn, d=350) => { let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), d); }; };

        window.addEventListener('DOMContentLoaded', async () => {
            await loadProducts();
            renderProducts();
            updateCart();
            document.getElementById('optionsDropdown').addEventListener('change', updateCheckoutState);
            const ps = document.getElementById('paymentStatusSelect');
            const pa = document.getElementById('partialAmountInput');
            ps.addEventListener('change', () => {
                pa.style.display = ps.value === 'Partial' ? 'inline-block' : 'none';
                updateCheckoutState();
            });

            // Auto customer search on typing
            const memberInput = document.getElementById('memberInput');
            const suggestBox = document.getElementById('customerSuggest');
            const debouncedFind = debounce(()=> findCustomer(), 300);
            memberInput.addEventListener('keyup', (e)=>{
                if (!memberInput.value.trim()) { hideSuggestions(); selectedCustomer=null; availablePoints=0; updateCustomerInfo(); updateSummary(); return; }
                // ignore navigation keys here; handled in keydown
                if (!['ArrowDown','ArrowUp','Enter','Escape'].includes(e.key)) debouncedFind();
            });
            memberInput.addEventListener('keydown', (e)=>{
                if (suggestBox.style.display !== 'block') return;
                if (e.key === 'ArrowDown') { e.preventDefault(); moveSuggest(1); }
                if (e.key === 'ArrowUp') { e.preventDefault(); moveSuggest(-1); }
                if (e.key === 'Enter') { e.preventDefault(); chooseSuggest(); }
                if (e.key === 'Escape') { hideSuggestions(); }
            });
            document.addEventListener('click', (e)=>{
                if (!suggestBox.contains(e.target) && e.target !== memberInput) hideSuggestions();
            });

            // Auto apply points/discount on input
            document.getElementById('pointsUse').addEventListener('input', applyPoints);
            document.getElementById('discountInput').addEventListener('input', applyDiscount);
        });

        async function findCustomer() {
            const q = document.getElementById('memberInput').value.trim();
            const suggestBox = document.getElementById('customerSuggest');
            if (!q) { hideSuggestions(); selectedCustomer = null; availablePoints = 0; updateCustomerInfo(); return; }
            try {
                const res = await fetch('pos_api.php?action=customer&q=' + encodeURIComponent(q), { credentials:'same-origin' });
                const data = await res.json();
                if (data.success && data.data.customer) {
                    setSelectedCustomer(data.data.customer);
                    hideSuggestions();
                } else if (data.success && data.data.matches) {
                    const list = data.data.matches || [];
                    if (list.length === 1) {
                        setSelectedCustomer(list[0]);
                        hideSuggestions();
                    } else if (list.length > 1) {
                        // show suggestions (max 5 already from API)
                        suggestList = list;
                        suggestIndex = -1;
                        renderSuggestions(list);
                    } else {
                        hideSuggestions();
                        setSelectedCustomer(null);
                    }
                } else {
                    hideSuggestions();
                    setSelectedCustomer(null);
                }
            } catch (e) {
                console.error('Customer lookup error', e);
            }
        }

        function setSelectedCustomer(cust) {
            selectedCustomer = cust ? { ...cust } : null;
            availablePoints = selectedCustomer ? Number(selectedCustomer.LoyaltyPoints || 0) : 0;
            updateCustomerInfo();
            const memberInput = document.getElementById('memberInput');
            if (selectedCustomer) memberInput.value = selectedCustomer.name || (selectedCustomer.MemberNumber || '');
            updateSummary();
        }

        function renderSuggestions(list) {
            const suggestBox = document.getElementById('customerSuggest');
            if (!list || list.length === 0) { hideSuggestions(); return; }
            suggestBox.innerHTML = list.map((c, i)=>
                `<div class="suggest-item${i===suggestIndex?' active':''}" data-idx="${i}">
                    <div>${c.name || ''}</div>
                    <div class="suggest-meta">${c.MemberNumber || ''} • Pts: ${c.LoyaltyPoints || 0}</div>
                 </div>`
            ).join('');
            suggestBox.style.display = 'block';
            // click handlers
            suggestBox.querySelectorAll('.suggest-item').forEach(el=>{
                el.addEventListener('click', ()=> {
                    const idx = parseInt(el.getAttribute('data-idx'),10);
                    setSelectedCustomer(suggestList[idx]);
                    hideSuggestions();
                });
            });
        }

        function moveSuggest(step) {
            if (!suggestList || suggestList.length === 0) return;
            suggestIndex = (suggestIndex + step + suggestList.length) % suggestList.length;
            renderSuggestions(suggestList);
        }

        function chooseSuggest() {
            if (!suggestList || suggestIndex < 0) return;
            setSelectedCustomer(suggestList[suggestIndex]);
            hideSuggestions();
        }

        function hideSuggestions() {
            const suggestBox = document.getElementById('customerSuggest');
            suggestBox.style.display = 'none';
            suggestList = [];
            suggestIndex = -1;
        }

        function updateCustomerInfo() {
            const el = document.getElementById('customerInfo');
            if (!selectedCustomer) { el.textContent = 'Walk-in'; return; }
            el.textContent = `${selectedCustomer.name} • Member: ${selectedCustomer.MemberNumber || 'N/A'} • Points: ${availablePoints}`;
        }

        function applyPoints() {
            const val = parseFloat(document.getElementById('pointsUse').value) || 0;
            const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
            const cap = Math.max(0, Math.min(availablePoints, subtotal - discountAmount));
            pointsToUse = Math.max(0, Math.min(val, cap));
            const hint = document.getElementById('pointsHint');
            if (hint) hint.textContent = `Max usable: ₱${cap.toLocaleString()}`;
            updateSummary();
        }

        function applyDiscount() {
            const val = parseFloat(document.getElementById('discountInput').value) || 0;
            const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
            discountAmount = Math.max(0, Math.min(val, subtotal - pointsToUse));
            updateSummary();
        }

        // ==== PRODUCT HANDLING ====
        async function loadProducts() {
            try {
                const res = await fetch('pos_api.php?action=products', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success) {
                    // Backend returns: id, name, price, stock, category
                    products = data.data.products.map(p => ({
                        ...p,
                        // Provide default sizes for UI; actual size not persisted in sale
                        sizes: [6,7,8,9,10,11,12],
                        icon: '👟'
                    }));
                } else {
                    products = [];
                }
            } catch (e) {
                console.error('Failed to load products', e);
                products = [];
            }
        }

        function renderProducts() {
            const grid = document.getElementById('productsGrid');
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            const filtered = products.filter(p => {
                const matchSearch = p.name.toLowerCase().includes(searchTerm);
                const matchCategory = currentCategory === 'All' || p.category === currentCategory;
                return matchSearch && matchCategory;
            });

            grid.innerHTML = filtered.map(product => `
            <div class="product-item" onclick="openSizeModal(${product.id})">
                <div class="product-icon">${product.icon || '👟'}</div>
                <div class="product-details">
                    <div class="product-name">${product.name}</div>
                    <div class="product-meta">
                        <div class="product-price">₱${Number(product.price).toLocaleString()}</div>
                        <div class="product-stock">${product.stock} left</div>
                    </div>
                </div>
            </div>
        `).join('');
        }

        function filterProducts() {
            renderProducts();
        }

        function filterByCategory(category) {
            currentCategory = category;
            document.querySelectorAll('.category-tab').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            renderProducts();
        }

        function openSizeModal(id) {
            currentProduct = products.find(p => p.id === id);
            selectedSize = null;

            document.getElementById('modalProductName').textContent = currentProduct.name;
            const sizeOptions = document.getElementById('sizeOptions');
            sizeOptions.innerHTML = currentProduct.sizes.map(size =>
                `<button class="size-option" onclick="selectSize(${size})">${size}</button>`
            ).join('');

            document.getElementById('sizeModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('sizeModal').classList.remove('show');
            currentProduct = null;
            selectedSize = null;
        }

        function selectSize(size) {
            selectedSize = size;
            document.querySelectorAll('.size-option').forEach(btn => btn.classList.remove('selected'));
            event.target.classList.add('selected');
        }

        function addToCart() {
            if (!selectedSize) return alert('Please select a size first.');

            const existing = cart.find(item => item.id === currentProduct.id && item.size === selectedSize);
            if (existing) existing.quantity++;
            else cart.push({
                ...currentProduct,
                size: selectedSize,
                quantity: 1
            });

            closeModal();
            updateCart();
        }

        // ==== CART ====
        function updateCart() {
            const cartItems = document.getElementById('cartItems');
            const checkoutBtn = document.getElementById('checkoutBtn');
            const paymentDropdown = document.getElementById('optionsDropdown');

            if (cart.length === 0) {
                cartItems.innerHTML = `
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <p>No items in cart</p>
                    <p style="font-size: 0.875rem; margin-top: 0.5rem;">Select products to add to order</p>
                </div>
            `;
                paymentDropdown.disabled = true;
            } else {
                cartItems.innerHTML = cart.map((item, i) => `
                <div class="cart-item">
                    <div class="cart-item-top">
                        <div class="item-info">
                            <div class="item-name">${item.name}</div>
                            <span class="item-size">Size: ${item.size}</span>
                        </div>
                        <button class="remove-btn" onclick="removeFromCart(${i})">×</button>
                    </div>
                    <div class="cart-item-bottom">
                        <div class="quantity-controls">
                            <button class="qty-btn" onclick="updateQuantity(${i}, -1)">−</button>
                            <span class="qty-value">${item.quantity}</span>
                            <button class="qty-btn" onclick="updateQuantity(${i}, 1)">+</button>
                        </div>
                        <div class="item-total">₱${(item.price * item.quantity).toLocaleString()}</div>
                    </div>
                </div>
            `).join('');
                paymentDropdown.disabled = false;
            }

            if (cart.length === 0) paymentDropdown.value = "";
            updateSummary();
            updateCheckoutState();
        }

        function updateQuantity(i, delta) {
            cart[i].quantity += delta;
            if (cart[i].quantity <= 0) cart.splice(i, 1);
            updateCart();
        }

        function removeFromCart(i) {
            cart.splice(i, 1);
            updateCart();
        }

        function clearCart() {
            if (cart.length === 0) return;
            if (confirm("Clear all items from cart?")) {
                cart = [];
                updateCart();
            }
        }

        function updateSummary() {
            const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
            const taxable = Math.max(0, subtotal - discountAmount - pointsToUse);
            const tax = taxable * 0.10; // align with backend procedure
            const total = taxable + tax;
            document.getElementById('subtotal').textContent = `₱${subtotal.toLocaleString()}`;
            document.getElementById('tax').textContent = `₱${tax.toLocaleString()}`;
            document.getElementById('discount').textContent = `₱${discountAmount.toLocaleString()}`;
            document.getElementById('total').textContent = `₱${total.toLocaleString()}`;
        }

        // ==== CHECKOUT BUTTON STATE ====
        function updateCheckoutState() {
            const checkoutBtn = document.getElementById('checkoutBtn');
            const paymentDropdown = document.getElementById('optionsDropdown');
            const statusSel = document.getElementById('paymentStatusSelect');
            const partialInput = document.getElementById('partialAmountInput');
            const hasItems = cart.length > 0;
            const hasPayment = paymentDropdown.value !== "";
            let ok = hasItems && hasPayment;
            if (statusSel.value === 'Partial') {
                const subtotal = cart.reduce((s,i)=>s+i.price*i.quantity,0);
                const taxable = Math.max(0, subtotal - discountAmount - pointsToUse);
                const total = taxable * 1.10 + 0; // taxable + tax
                const paid = parseFloat(partialInput.value) || 0;
                ok = ok && paid > 0 && paid < total;
            }
            checkoutBtn.disabled = !ok;
        }

        // ==== PAYMENT MODAL (CASH INPUT) ====
        const paymentModal = document.getElementById('paymentCalcModal');
        const paymentBackdrop = document.getElementById('paymentCalcBackdrop');
        const paymentInput = document.getElementById('paymentInput');
        const paymentTotal = document.getElementById('paymentTotal');
        const changeOutput = document.getElementById('changeOutput');
        const confirmBtn = document.getElementById('confirmBtn');
        const backBtn = document.getElementById('backBtn');
        const closePaymentCalc = document.getElementById('closePaymentCalc');
        const calcBtns = document.querySelectorAll('.calc-btn');

        async function submitCheckout(paymentMethod) {
            try {
                const statusSel = document.getElementById('paymentStatusSelect');
                const partialInput = document.getElementById('partialAmountInput');
                const baseTotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
                const taxable = Math.max(0, baseTotal - discountAmount - pointsToUse);
                const tax = taxable * 0.10;
                const grandTotal = taxable + tax;
                let paymentStatus = statusSel.value;
                let amountPaid = paymentStatus === 'Partial' ? (parseFloat(partialInput.value) || 0) : (paymentStatus === 'Credit' ? 0 : grandTotal);
                amountPaid = Math.max(0, Math.min(amountPaid, grandTotal));
                const payload = {
                    items: cart.map(i => ({ id: i.id, quantity: i.quantity, price: i.price })),
                    payment_method: paymentMethod === 'cash' ? 'Cash' : 'GCash',
                    discount: discountAmount,
                    customer_id: selectedCustomer ? selectedCustomer.id : null,
                    points_used: pointsToUse,
                    payment_status: paymentStatus,
                    amount_paid: amountPaid,
                    // store_id will be taken from session on server if omitted
                };
                const res = await fetch('pos_api.php?action=checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.data?.message || 'Checkout failed');
                const saleId = data.data.sale_id;
                alert(`✅ Sale completed!\nSale ID: ${saleId}`);
                cart = [];
                updateCart();
                closePaymentModal();
            } catch (e) {
                alert('Checkout error: ' + e.message);
            }
        }

        function checkout() {
            const mode = document.getElementById('optionsDropdown').value;
            if (!mode) return alert("Please select mode of payment first.");
            if (cart.length === 0) return alert("No items to checkout.");

            const total = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0) * 1.12;
            totalAmount = total;
            paymentTotal.textContent = `₱${total.toLocaleString()}`;

            if (mode === "cash") {
                paymentModal.classList.add('show');
                paymentBackdrop.classList.add('show');
                paymentInput.value = "";
                changeOutput.textContent = "";
                confirmBtn.disabled = true;
            } else if (mode === "gcash") {
                // Direct submit for GCash
                submitCheckout('gcash');
            }
        }

        calcBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.classList.contains('clear')) {
                    paymentInput.value = "";
                    changeOutput.textContent = "";
                    confirmBtn.disabled = true;
                    return;
                }
                paymentInput.value += btn.textContent;
                const entered = parseFloat(paymentInput.value);
                if (!isNaN(entered)) {
                    if (entered >= totalAmount) {
                        const change = entered - totalAmount;
                        changeOutput.textContent = `Change: ₱${change.toLocaleString()}`;
                        changeOutput.style.color = "green";
                        confirmBtn.disabled = false;
                    } else {
                        changeOutput.textContent = "Insufficient amount";
                        changeOutput.style.color = "red";
                        confirmBtn.disabled = true;
                    }
                }
            });
        });

        function closePaymentModal() {
            paymentModal.classList.remove('show');
            paymentBackdrop.classList.remove('show');
        }
        closePaymentCalc.addEventListener('click', closePaymentModal);
        backBtn.addEventListener('click', closePaymentModal);

        confirmBtn.addEventListener('click', () => {
            // Submit checkout with Cash after sufficient amount entered
            submitCheckout('cash');
        });
    </script>

</body>

</html>