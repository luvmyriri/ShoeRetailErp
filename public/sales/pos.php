<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Retail POS System</title>
    <link rel="stylesheet" href="../css/pos_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="pos-wrapper">
        <!-- Left Panel - Products -->
        <div class="products-panel">
            <!-- Top Bar -->
            <div class="top-bar">
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

            <div class="cart-items-container" id="cartItems">
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <p>No items in cart</p>
                    <p style="font-size: 0.875rem; margin-top: 0.5rem;">Select products to add to order</p>
                </div>
            </div>

            <div class="cart-summary">
                <div class="summary-row subtotal">
                    <span>Subtotal</span>
                    <span id="subtotal">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>VAT (12%)</span>
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
        const products = [{
                id: 1,
                name: 'Classic Leather Sneakers',
                price: 2450,
                sizes: [7, 8, 9, 10, 11],
                category: 'Sneakers',
                stock: 15,
                icon: '👟'
            },
            {
                id: 2,
                name: 'Running Pro Elite',
                price: 3200,
                sizes: [8, 9, 10, 11, 12],
                category: 'Running',
                stock: 8,
                icon: '🏃'
            },
            {
                id: 3,
                name: 'Formal Oxford Black',
                price: 4500,
                sizes: [7, 8, 9, 10],
                category: 'Formal',
                stock: 12,
                icon: '👞'
            },
            {
                id: 4,
                name: 'Casual Canvas Slip-On',
                price: 1850,
                sizes: [6, 7, 8, 9, 10],
                category: 'Casual',
                stock: 20,
                icon: '👟'
            },
            {
                id: 5,
                name: 'High-Top Basketball',
                price: 5200,
                sizes: [9, 10, 11, 12, 13],
                category: 'Sports',
                stock: 6,
                icon: '🏀'
            },
            {
                id: 6,
                name: 'Elegant Heels',
                price: 3800,
                sizes: [5, 6, 7, 8],
                category: 'Formal',
                stock: 10,
                icon: '👠'
            },
            {
                id: 7,
                name: 'Trail Hiking Boots',
                price: 4800,
                sizes: [8, 9, 10, 11, 12],
                category: 'Sports',
                stock: 7,
                icon: '🥾'
            },
            {
                id: 8,
                name: 'Summer Sandals',
                price: 1200,
                sizes: [6, 7, 8, 9, 10],
                category: 'Casual',
                stock: 25,
                icon: '👡'
            },
            {
                id: 9,
                name: 'Sport Running Shoes',
                price: 2900,
                sizes: [7, 8, 9, 10, 11],
                category: 'Running',
                stock: 18,
                icon: '👟'
            },
            {
                id: 10,
                name: 'Premium Loafers',
                price: 4200,
                sizes: [8, 9, 10, 11],
                category: 'Formal',
                stock: 9,
                icon: '👞'
            },
            {
                id: 11,
                name: 'Comfort Slip-Ons',
                price: 2100,
                sizes: [7, 8, 9, 10],
                category: 'Casual',
                stock: 22,
                icon: '👟'
            },
            {
                id: 12,
                name: 'Training Sneakers',
                price: 2650,
                sizes: [8, 9, 10, 11, 12],
                category: 'Sports',
                stock: 14,
                icon: '👟'
            }
        ];

        let cart = [];
        let currentProduct = null;
        let selectedSize = null;
        let currentCategory = 'All';
        let totalAmount = 0;

        // ==== INIT ====
        window.addEventListener('DOMContentLoaded', () => {
            renderProducts();
            updateCart();
            document.getElementById('optionsDropdown').addEventListener('change', updateCheckoutState);
        });

        // ==== PRODUCT HANDLING ====
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
                <div class="product-icon">${product.icon}</div>
                <div class="product-details">
                    <div class="product-name">${product.name}</div>
                    <div class="product-meta">
                        <div class="product-price">₱${product.price.toLocaleString()}</div>
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
            const tax = subtotal * 0.12;
            const total = subtotal + tax;
            document.getElementById('subtotal').textContent = `₱${subtotal.toLocaleString()}`;
            document.getElementById('tax').textContent = `₱${tax.toLocaleString()}`;
            document.getElementById('total').textContent = `₱${total.toLocaleString()}`;
        }

        // ==== CHECKOUT BUTTON STATE ====
        function updateCheckoutState() {
            const checkoutBtn = document.getElementById('checkoutBtn');
            const paymentDropdown = document.getElementById('optionsDropdown');
            const hasItems = cart.length > 0;
            const hasPayment = paymentDropdown.value !== "";
            checkoutBtn.disabled = !(hasItems && hasPayment);
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
                alert(`✅ Payment Successful via GCash!\nTotal: ₱${total.toLocaleString()}`);
                cart = [];
                updateCart();
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
            alert("✅ Payment Successful! Thank you for your purchase.");
            cart = [];
            updateCart();
            closePaymentModal();
        });
    </script>

</body>

</html>