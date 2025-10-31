<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Retail POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #714B67;
            --primary-light: #8B5E7F;
            --primary-dark: #5A3B54;
            --secondary-color: #F5B041;
            --success-color: #27AE60;
            --danger-color: #E74C3C;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-500: #6B7280;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 12px -2px rgba(0, 0, 0, 0.1);
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--gray-50);
            overflow: hidden;
        }

        .pos-wrapper {
            display: flex;
            height: 100vh;
        }

        .products-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .top-bar {
            background: white;
            color: var(--gray-900);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .store-info h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--primary-color);
        }

        .store-info p {
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .cashier-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cashier-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.125rem;
            border: 2px solid var(--secondary-color);
        }

        .cashier-details {
            text-align: right;
        }

        .cashier-details p {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-bottom: 0.125rem;
        }

        .cashier-name {
            font-weight: 600;
            font-size: 0.9375rem;
            color: var(--gray-900);
        }

        .filter-bar {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .search-container {
            margin-bottom: 1rem;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            transition: all var(--transition-fast);
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 1.125rem;
        }

        .category-tabs {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
        }

        .category-tabs::-webkit-scrollbar {
            height: 4px;
        }

        .category-tabs::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 2px;
        }

        .category-tab {
            padding: 0.5rem 1rem;
            border: none;
            background: white;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            transition: all var(--transition-fast);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }

        .category-tab:hover {
            background: var(--gray-100);
            border-color: var(--primary-color);
        }

        .category-tab.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .products-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .product-item {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 1rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            gap: 1rem;
        }

        .product-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .product-icon {
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.125rem;
        }

        .product-stock {
            background: var(--gray-100);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8125rem;
            color: var(--gray-700);
        }

        .cart-panel {
            width: 420px;
            background: var(--gray-50);
            border-left: 2px solid var(--gray-200);
            display: flex;
            flex-direction: column;
        }

        .cart-header {
            padding: 1.5rem;
            border-bottom: 2px solid var(--gray-200);
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-header-left {
            flex: 1;
        }

        .cart-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .order-info {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .order-info span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .cart-items-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        .cart-item-cost {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .empty-cart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
        }

        .empty-cart-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .cart-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }

        .cart-item-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .item-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .item-size {
            font-size: 0.8125rem;
            color: #64748b;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #ef4444;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }

        .cart-item-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            border: none;
            background: #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background: #cbd5e1;
        }

        .qty-value {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }

        .item-total {
            font-weight: 700;
            color: #667eea;
        }

        .cart-summary {
            padding: 1.5rem;
            border-top: 2px solid var(--gray-200);
            background: white;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--gray-700);
        }

        .summary-row.total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            padding-top: 0.75rem;
            border-top: 2px solid var(--gray-200);
            margin-top: 0.5rem;
        }

        .cart-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .dropdown {
            grid-column: 1 / -1;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            cursor: pointer;
            background: white;
            color: var(--gray-700);
            font-weight: 500;
            transition: all var(--transition-fast);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%23714B67'%3E%3Cpath fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' clip-rule='evenodd' /%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 20px;
            padding-right: 2.5rem;
        }

        .dropdown:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.1);
        }

        .dropdown:disabled {
            background-color: var(--gray-100);
            color: var(--gray-500);
            cursor: not-allowed;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9375rem;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-outline {
            background: white;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .btn-outline:hover:not(:disabled) {
            background: #f8fafc;
        }

        .btn-primary {
            grid-column: 1 / -1;
            background: #714B67;
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            background: #059669;
        }

        .modal-overlay, .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .modal-overlay.show, .modal-backdrop.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content, .modal {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
        }

        .modal.show {
            display: block;
        }

        .modal-header {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            color: #1e293b;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #94a3b8;
            line-height: 1;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .modal-subtitle {
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .size-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .size-option {
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .size-option:hover {
            border-color: #667eea;
        }

        .size-option.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .modal-actions, .modal-footer {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9375rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-help {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .calc-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .calc-btn {
            padding: 1.25rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            font-size: 1.25rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .calc-btn:hover {
            background: #f8fafc;
            border-color: #667eea;
        }

        .calc-btn.clear {
            grid-column: 2 / -1;
            background: #fee2e2;
            color: #ef4444;
            border-color: #fecaca;
        }

        .calc-btn.clear:hover {
            background: #fecaca;
        }

        .discount-indicator {
            color: #10b981;
            font-weight: 600;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #475569;
        }
    </style>
</head>
<body>
    <div class="pos-wrapper">
        <div class="products-panel">
            <div class="top-bar">
                <div class="store-info">
                    <h1>Shoe Retail POS</h1>
                    <p>Terminal #<span id="terminalNumber">1</span> • <span id="storeLocation">Loading...</span></p>
                </div>
                <div class="cashier-info">
                    <div class="cashier-details">
                        <p>Cashier</p>
                        <div class="cashier-name" id="cashierName">System</div>
                    </div>
                    <div class="cashier-avatar" id="cashierAvatar">?</div>
                </div>
            </div>

            <div class="filter-bar">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search by SKU, brand, or model..." onkeyup="filterProducts()">
                </div>
                <div class="category-tabs">
                    <button class="category-tab active" onclick="filterByBrand('All')">All Products</button>
                    <button class="category-tab" onclick="filterByBrand('Nike')">Nike</button>
                    <button class="category-tab" onclick="filterByBrand('Adidas')">Adidas</button>
                    <button class="category-tab" onclick="filterByBrand('Puma')">Puma</button>
                    <button class="category-tab" onclick="filterByBrand('Converse')">Converse</button>
                </div>
            </div>

            <div class="products-container">
                <div class="products-grid" id="productsGrid"></div>
            </div>
        </div>

        <div class="cart-panel">
            <div class="cart-header">
                <div class="cart-header-left">
                    <div class="cart-title">Current Order</div>
                    <div class="order-info">
                        <span><i class="fas fa-receipt"></i> Order #<span id="orderNumber">-</span></span>
                        <span><i class="fas fa-percentage"></i> VAT: <span id="vatDisplay">12%</span></span>
                        <span><i class="fas fa-shopping-cart"></i> <span id="cartItemCount">0</span> items</span>
                    </div>
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
                <div class="summary-row">
                    <span>Quantity</span>
                    <span id="subtotal"></span>
                </div>
                <div class="summary-row">
                    <span>Unit Cost</span>
                    <span id="subtotal">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>VAT (<span id="vatRate">12</span>%)</span>
                    <span id="tax">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>Discount <span id="discountLabel" class="discount-indicator"></span></span>
                    <span id="discount">₱0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span id="total">₱0.00</span>
                </div>

                <div class="cart-actions">
                    <select id="paymentMode" class="dropdown" disabled>
                        <option value="" disabled selected>Select Payment Mode</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Credit">Credit (On Account)</option>
                    </select>
                    <button class="btn btn-outline" onclick="openVoucherModal()" disabled id="voucherBtn">Add Voucher</button>
                    <button class="btn btn-outline" onclick="clearCart()">Clear</button>
                    <button class="btn btn-primary" onclick="checkout()" disabled id="checkoutBtn">Checkout</button>
                </div>
            </div>
        </div>
    </div>

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

    <div class="modal-backdrop" id="voucherModalBackdrop"></div>
    <div class="modal" id="voucherModal">
        <div class="modal-header">
            <h3>Apply Voucher</h3>
            <button class="modal-close" onclick="closeVoucherModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="voucherCode" class="form-label">Voucher Code</label>
                <input type="text" id="voucherCode" class="form-control" placeholder="Enter voucher code">
            </div>
            <div class="form-help">Available codes: <code>SAVE10</code>, <code>WELCOME20</code></div>
            <div id="voucherMessage" class="form-help"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeVoucherModal()">Cancel</button>
            <button class="btn btn-success" onclick="applyVoucher()">Apply</button>
        </div>
    </div>

    <div class="modal-backdrop" id="paymentModalBackdrop"></div>
    <div class="modal" id="paymentModal">
        <div class="modal-header">
            <h3>Cash Payment</h3>
            <button class="modal-close" onclick="closePaymentModal()">×</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 1rem; margin-bottom: 1rem;">Total Due: <strong id="paymentTotal">₱0.00</strong></p>
            <input type="text" id="paymentInput" class="form-control" placeholder="Enter amount" readonly>
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
            <p id="changeOutput" style="margin-top: 10px; font-weight: bold;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closePaymentModal()">Back</button>
            <button class="btn btn-primary" id="confirmPaymentBtn" disabled onclick="confirmPayment()">Confirm</button>
        </div>
    </div>

    <script>
        // Global variables
        let products = [];
        let cart = [];
        let currentProduct = null;
        let selectedSize = null;
        let currentBrand = 'All';
        let appliedDiscount = 0;
        let discountCode = '';
        let storeId = null;
        const VAT_RATE = 0.12;

        // Database voucher codes (hardcoded for now)
        const VOUCHER_CODES = {
            'SAVE10': 0.10,
            'WELCOME20': 0.20,
            'CLEARANCE15': 0.15
        };

        // Initialize
        window.addEventListener('DOMContentLoaded', () => {
            loadProducts();
            generateOrderNumber();
            updateCartButtons();
            setupCalculator();
        });

        // Load products from database
        async function loadProducts() {
            try {
                // Simulated database fetch - replace with actual API call
                products = [
                    {id: 1, sku: 'NK-AM-001-9.5-BLK', brand: 'Nike', model: 'Air Max 90', size: 9.5, color: 'Black', price: 120.00, stock: 25, icon: '👟'},
                    {id: 2, sku: 'NK-AM-001-10-WHT', brand: 'Nike', model: 'Air Max 90', size: 10.0, color: 'White', price: 120.00, stock: 15, icon: '👟'},
                    {id: 3, sku: 'AD-UB-001-9-BLU', brand: 'Adidas', model: 'Ultraboost 22', size: 9.0, color: 'Blue', price: 140.00, stock: 12, icon: '👟'},
                    {id: 4, sku: 'AD-UB-001-10-GRY', brand: 'Adidas', model: 'Ultraboost 22', size: 10.0, color: 'Grey', price: 140.00, stock: 10, icon: '👟'},
                    {id: 5, sku: 'PM-RS-001-8.5-RED', brand: 'Puma', model: 'RS-X', size: 8.5, color: 'Red', price: 95.00, stock: 18, icon: '👟'},
                    {id: 6, sku: 'CV-AS-001-9-BLK', brand: 'Converse', model: 'All Star', size: 9.0, color: 'Black', price: 65.00, stock: 30, icon: '👟'}
                ];
                
                // Set store location
                document.getElementById('storeLocation').textContent = 'Main Branch';
                storeId = 1;
                
                renderProducts();
            } catch (error) {
                console.error('Error loading products:', error);
                alert('Failed to load products');
            }
        }

        // Render products
        function renderProducts() {
            const grid = document.getElementById('productsGrid');
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            const filtered = products.filter(p => {
                const matchSearch = p.sku.toLowerCase().includes(searchTerm) || 
                                   p.brand.toLowerCase().includes(searchTerm) || 
                                   p.model.toLowerCase().includes(searchTerm);
                const matchBrand = currentBrand === 'All' || p.brand === currentBrand;
                return matchSearch && matchBrand && p.stock > 0;
            });

            grid.innerHTML = filtered.map(product => `
                <div class="product-item" onclick="openSizeModal(${product.id})">
                    <div class="product-icon">${product.icon}</div>
                    <div class="product-details">
                        <div class="product-name">${product.brand} ${product.model}</div>
                        <div style="font-size: 0.8125rem; color: #64748b; margin-bottom: 0.5rem;">${product.sku}</div>
                        <div class="product-meta">
                            <div class="product-price">₱${product.price.toLocaleString()}</div>
                            <div class="product-stock">${product.stock} in stock</div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Filter functions
        function filterProducts() {
            renderProducts();
        }

        function filterByBrand(brand) {
            currentBrand = brand;
            document.querySelectorAll('.category-tab').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            renderProducts();
        }

        // Size modal
        function openSizeModal(id) {
            currentProduct = products.find(p => p.id === id);
            selectedSize = currentProduct.size;

            document.getElementById('modalProductName').textContent = `${currentProduct.brand} ${currentProduct.model} - ${currentProduct.color}`;
            const sizeOptions = document.getElementById('sizeOptions');
            
            const sizes = products.filter(p => p.brand === currentProduct.brand && p.model === currentProduct.model && p.stock > 0)
                                 .map(p => p.size);
            
            sizeOptions.innerHTML = sizes.map(size =>
                `<button class="size-option ${size === selectedSize ? 'selected' : ''}" onclick="selectSize(${currentProduct.id}, ${size})">${size}</button>`
            ).join('');

            document.getElementById('sizeModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('sizeModal').classList.remove('show');
            currentProduct = null;
            selectedSize = null;
        }

        function selectSize(productId, size) {
            currentProduct = products.find(p => p.id === productId || (p.brand === currentProduct.brand && p.model === currentProduct.model && p.size === size));
            selectedSize = size;
            document.querySelectorAll('.size-option').forEach(btn => btn.classList.remove('selected'));
            event.target.classList.add('selected');
        }

        function addToCart() {
            if (!currentProduct || !selectedSize) return alert('Please select a size');

            const existing = cart.find(item => item.id === currentProduct.id);
            if (existing) {
                if (existing.quantity >= currentProduct.stock) {
                    return alert('Cannot add more items than available in stock');
                }
                existing.quantity++;
            } else {
                cart.push({
                    ...currentProduct,
                    quantity: 1
                });
            }

            closeModal();
            updateCart();
        }

        // Cart management
        function updateCart() {
            const cartItems = document.getElementById('cartItems');
            const itemCount = document.getElementById('cartItemCount');

            itemCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);

            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <p>No items in cart</p>
                        <p style="font-size: 0.875rem; margin-top: 0.5rem;">Select products to add to order</p>
                    </div>
                `;
            } else {
                cartItems.innerHTML = cart.map((item, i) => `
                    <div class="cart-item">
                        <div class="cart-item-top">
                            <div class="item-info">
                                <div class="item-name">${item.brand} ${item.model}</div>
                                <span class="item-size">Size: ${item.size} • ${item.color}</span>
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
            }

            updateSummary();
            updateCartButtons();
        }

        function updateQuantity(i, delta) {
            const item = cart[i];
            const newQuantity = item.quantity + delta;

            if (newQuantity <= 0) {
                cart.splice(i, 1);
            } else if (newQuantity <= item.stock) {
                item.quantity = newQuantity;
            } else {
                alert(`Only ${item.stock} items available in stock`);
                return;
            }

            updateCart();
        }

        function removeFromCart(i) {
            cart.splice(i, 1);
            updateCart();
        }

        function clearCart() {
            if (cart.length === 0) return;
            if (confirm('Clear all items from cart?')) {
                cart = [];
                appliedDiscount = 0;
                discountCode = '';
                updateCart();
            }
        }

        function updateSummary() {
            const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
            const tax = subtotal * VAT_RATE;
            const discountAmount = subtotal * appliedDiscount;
            const total = subtotal + tax - discountAmount;

            document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('tax').textContent = `₱${tax.toFixed(2)}`;
            document.getElementById('discount').textContent = discountAmount > 0 ? `-₱${discountAmount.toFixed(2)}` : '₱0.00';
            document.getElementById('total').textContent = `₱${total.toFixed(2)}`;

            if (discountCode) {
                document.getElementById('discountLabel').textContent = `(${discountCode})`;
            } else {
                document.getElementById('discountLabel').textContent = '';
            }
        }

        function updateCartButtons() {
            const hasItems = cart.length > 0;
            const paymentMode = document.getElementById('paymentMode');
            const voucherBtn = document.getElementById('voucherBtn');
            const checkoutBtn = document.getElementById('checkoutBtn');

            paymentMode.disabled = !hasItems;
            voucherBtn.disabled = !hasItems;
            
            if (!hasItems) {
                paymentMode.value = '';
            }

            const hasPayment = paymentMode.value !== '';
            checkoutBtn.disabled = !(hasItems && hasPayment);
        }

        // Voucher management
        function openVoucherModal() {
            document.getElementById('voucherModal').classList.add('show');
            document.getElementById('voucherModalBackdrop').classList.add('show');
            document.getElementById('voucherCode').value = '';
            document.getElementById('voucherMessage').innerHTML = '';
        }

        function closeVoucherModal() {
            document.getElementById('voucherModal').classList.remove('show');
            document.getElementById('voucherModalBackdrop').classList.remove('show');
        }

        function applyVoucher() {
            const code = document.getElementById('voucherCode').value.trim().toUpperCase();
            const msgEl = document.getElementById('voucherMessage');

            if (!code) {
                msgEl.innerHTML = '<span style="color: #ef4444;">Please enter a voucher code</span>';
                return;
            }

            if (VOUCHER_CODES[code]) {
                appliedDiscount = VOUCHER_CODES[code];
                discountCode = code;
                updateSummary();
                msgEl.innerHTML = `<span style="color: #10b981;">Voucher applied! ${(appliedDiscount * 100)}% discount</span>`;
                setTimeout(closeVoucherModal, 1000);
            } else {
                msgEl.innerHTML = '<span style="color: #ef4444;">Invalid voucher code</span>';
            }
        }

        // Payment handling
        function checkout() {
            const mode = document.getElementById('paymentMode').value;
            if (!mode) return alert('Please select payment mode');
            if (cart.length === 0) return alert('No items to checkout');

            const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
            const tax = subtotal * VAT_RATE;
            const discountAmount = subtotal * appliedDiscount;
            const total = subtotal + tax - discountAmount;

            if (mode === 'Cash') {
                openPaymentModal(total);
            } else if (mode === 'Card') {
                processCardPayment(total);
            } else if (mode === 'Credit') {
                // Credit payment - will require customer selection
                // For now, simulate success
                alert(`Credit payment processed!\nTotal: ₱${total.toFixed(2)}\n\nNote: Customer account will be charged.`);
                completeSale(mode, total);
            }
        }

        function openPaymentModal(total) {
            document.getElementById('paymentTotal').textContent = `₱${total.toFixed(2)}`;
            document.getElementById('paymentInput').value = '';
            document.getElementById('changeOutput').textContent = '';
            document.getElementById('confirmPaymentBtn').disabled = true;
            document.getElementById('paymentModal').classList.add('show');
            document.getElementById('paymentModalBackdrop').classList.add('show');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('show');
            document.getElementById('paymentModalBackdrop').classList.remove('show');
        }

        function setupCalculator() {
            const calcBtns = document.querySelectorAll('.calc-btn');
            const paymentInput = document.getElementById('paymentInput');
            const changeOutput = document.getElementById('changeOutput');
            const confirmBtn = document.getElementById('confirmPaymentBtn');

            calcBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
                    const tax = subtotal * VAT_RATE;
                    const discountAmount = subtotal * appliedDiscount;
                    const total = subtotal + tax - discountAmount;

                    if (btn.classList.contains('clear')) {
                        paymentInput.value = '';
                        changeOutput.textContent = '';
                        confirmBtn.disabled = true;
                        return;
                    }

                    paymentInput.value += btn.textContent;
                    const entered = parseFloat(paymentInput.value);

                    if (!isNaN(entered)) {
                        if (entered >= total) {
                            const change = entered - total;
                            changeOutput.textContent = `Change: ₱${change.toFixed(2)}`;
                            changeOutput.style.color = '#10b981';
                            confirmBtn.disabled = false;
                        } else {
                            changeOutput.textContent = 'Insufficient amount';
                            changeOutput.style.color = '#ef4444';
                            confirmBtn.disabled = true;
                        }
                    }
                });
            });
        }

        function confirmPayment() {
            const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
            const tax = subtotal * VAT_RATE;
            const discountAmount = subtotal * appliedDiscount;
            const total = subtotal + tax - discountAmount;
            
            completeSale('Cash', total);
            closePaymentModal();
        }

        function processCardPayment(total) {
            // Simulate card payment processing
            setTimeout(() => {
                if (confirm(`Process card payment of ₱${total.toFixed(2)}?`)) {
                    alert('✅ Card payment successful!');
                    completeSale('Card', total);
                }
            }, 100);
        }

        function completeSale(paymentMethod, total) {
            // Prepare sale data for database
            const saleData = {
                storeId: storeId,
                customerId: null, // Will be set if customer is identified
                paymentMethod: paymentMethod,
                paymentStatus: paymentMethod === 'Credit' ? 'Credit' : 'Paid',
                subtotal: cart.reduce((sum, i) => sum + (i.price * i.quantity), 0),
                taxAmount: cart.reduce((sum, i) => sum + (i.price * i.quantity), 0) * VAT_RATE,
                discountAmount: cart.reduce((sum, i) => sum + (i.price * i.quantity), 0) * appliedDiscount,
                totalAmount: total,
                items: cart.map(item => ({
                    productId: item.id,
                    quantity: item.quantity,
                    unitPrice: item.price,
                    subtotal: item.price * item.quantity
                }))
            };

            // TODO: Send to database via API
            // fetch('/api/sales/process', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify(saleData)
            // }).then(response => response.json())
            //   .then(data => {
            //       if (data.success) {
            //           printReceipt(data.saleId);
            //       }
            //   });

            console.log('Sale completed:', saleData);
            
            alert(`✅ Payment Successful!\n\nTotal: ₱${total.toFixed(2)}\nPayment Method: ${paymentMethod}\n\nThank you for your purchase!`);
            
            // Reset cart
            cart = [];
            appliedDiscount = 0;
            discountCode = '';
            document.getElementById('paymentMode').value = '';
            updateCart();
            generateOrderNumber();
        }

        function generateOrderNumber() {
            const orderNum = 'ORD-' + Date.now().toString().slice(-6);
            document.getElementById('orderNumber').textContent = orderNum;
        }

        // Event listeners
        document.getElementById('paymentMode').addEventListener('change', updateCartButtons);
    </script>

</body>
</html>