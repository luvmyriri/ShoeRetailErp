<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 999; }
        .modal-overlay.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .table-container { overflow-x: auto; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-outline { background: transparent; border: 1px solid #007bff; color: #007bff; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 12px; }
    </style>
</head>
<body>
    <div class="alert-container" id="alerts"></div>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Inventory Management</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Inventory</div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" onclick="showAddProductModal()">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                    <button class="btn btn-outline" onclick="exportData()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="row" style="margin-bottom: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 12px; color: #999;">Total Products</div>
                        <div style="font-size: 28px; font-weight: bold;" id="statTotal">0</div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 12px; color: #999;">Low Stock</div>
                        <div style="font-size: 28px; font-weight: bold; color: #dc3545;" id="statLow">0</div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="card" style="margin-bottom: 1rem;">
                <div class="card-body" style="display: flex; gap: 1rem;">
                    <input type="text" id="searchBox" placeholder="Search SKU, Brand, Model..." class="form-control" style="flex: 1; max-width: 300px;">
                    <button class="btn btn-outline" onclick="loadInventory()"><i class="fas fa-search"></i> Search</button>
                </div>
            </div>

            <!-- Table -->
            <div class="card">
                <div class="card-header"><h3>Products</h3></div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #ddd;">
                                    <th style="padding: 0.75rem; text-align: left;">SKU</th>
                                    <th style="padding: 0.75rem; text-align: left;">Brand</th>
                                    <th style="padding: 0.75rem; text-align: left;">Model</th>
                                    <th style="padding: 0.75rem; text-align: left;">Qty</th>
                                    <th style="padding: 0.75rem; text-align: left;">Min</th>
                                    <th style="padding: 0.75rem; text-align: left;">Price</th>
                                    <th style="padding: 0.75rem; text-align: left;">Status</th>
                                    <th style="padding: 0.75rem; text-align: left;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr><td colspan="8" style="text-align: center; padding: 2rem;">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Product Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <h3 style="margin-top: 0;">Add New Product</h3>
            <form id="addForm">
                <div class="form-group">
                    <label>SKU *</label>
                    <input type="text" name="sku" required>
                </div>
                <div class="form-group">
                    <label>Brand *</label>
                    <input type="text" name="brand" required>
                </div>
                <div class="form-group">
                    <label>Model *</label>
                    <input type="text" name="model" required>
                </div>
                <div class="form-group">
                    <label>Size *</label>
                    <input type="text" name="size" required>
                </div>
                <div class="form-group">
                    <label>Color *</label>
                    <input type="text" name="color" required>
                </div>
                <div class="form-group">
                    <label>Cost Price *</label>
                    <input type="number" name="cost_price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Selling Price *</label>
                    <input type="number" name="selling_price" step="0.01" required>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Add Product</button>
                    <button type="button" class="btn btn-outline" onclick="hideAddProductModal()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API = '/ShoeRetailErp/api';

        // Load on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadInventory();
            document.getElementById('addForm').addEventListener('submit', addProduct);
            document.getElementById('searchBox').addEventListener('keyup', () => {
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(loadInventory, 500);
            });
        });

        // Load inventory
        function loadInventory() {
            const search = document.getElementById('searchBox').value;
            let url = API + '/inventory.php?action=get_products';
            if (search) url += '&search=' + encodeURIComponent(search);

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        showAlert('Error: ' + data.message, 'danger');
                        return;
                    }

                    const products = data.data || [];
                    const tbody = document.getElementById('tableBody');

                    if (!products.length) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">No products found</td></tr>';
                        document.getElementById('statTotal').textContent = '0';
                        document.getElementById('statLow').textContent = '0';
                        return;
                    }

                    // Update stats
                    document.getElementById('statTotal').textContent = products.length;
                    const lowCount = products.filter(p => (p.Quantity || 0) <= (p.MinStockLevel || 10)).length;
                    document.getElementById('statLow').textContent = lowCount;

                    // Populate table
                    tbody.innerHTML = products.map(p => {
                        const qty = p.Quantity || 0;
                        const min = p.MinStockLevel || 10;
                        const status = qty <= min ? '<span class="badge badge-danger">Low</span>' : 
                                     qty >= (p.MaxStockLevel || 100) ? '<span class="badge badge-warning">Over</span>' :
                                     '<span class="badge badge-success">OK</span>';
                        
                        return `<tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 0.75rem;"><strong>${p.SKU}</strong></td>
                            <td style="padding: 0.75rem;">${p.Brand}</td>
                            <td style="padding: 0.75rem;">${p.Model}</td>
                            <td style="padding: 0.75rem;"><strong>${qty}</strong></td>
                            <td style="padding: 0.75rem;">${min}</td>
                            <td style="padding: 0.75rem;">₱${(p.SellingPrice || 0).toFixed(2)}</td>
                            <td style="padding: 0.75rem;">${status}</td>
                            <td style="padding: 0.75rem;">
                                <button class="btn btn-sm btn-outline" onclick="addStock(${p.ProductID}, '${p.SKU}')">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </td>
                        </tr>`;
                    }).join('');
                })
                .catch(e => showAlert('Error: ' + e.message, 'danger'));
        }

        // Add product
        function addProduct(e) {
            e.preventDefault();
            const form = document.getElementById('addForm');
            const data = new FormData(form);
            const obj = Object.fromEntries(data);

            fetch(API + '/inventory.php?action=add_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(obj)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert('Product added successfully!', 'success');
                    hideAddProductModal();
                    form.reset();
                    loadInventory();
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(e => showAlert('Error: ' + e.message, 'danger'));
        }

        // Add stock
        function addStock(productId, sku) {
            const qty = prompt(`Add quantity for ${sku}:`);
            if (!qty || isNaN(qty)) return;

            fetch(API + '/inventory.php?action=stock_entry', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, store_id: 1, quantity: parseInt(qty) })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert('Stock added!', 'success');
                    loadInventory();
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(e => showAlert('Error: ' + e.message, 'danger'));
        }

        // Export
        function exportData() {
            window.location.href = API + '/inventory.php?action=export_inventory';
        }

        // Modal functions
        function showAddProductModal() {
            document.getElementById('addModal').classList.add('show');
        }

        function hideAddProductModal() {
            document.getElementById('addModal').classList.remove('show');
        }

        document.getElementById('addModal').addEventListener('click', (e) => {
            if (e.target.id === 'addModal') hideAddProductModal();
        });

        // Alert
        function showAlert(msg, type) {
            const alerts = document.getElementById('alerts');
            const div = document.createElement('div');
            div.className = `alert alert-${type}`;
            div.innerHTML = msg + ' <button onclick="this.parentElement.remove()" style="float: right; border: none; background: none; cursor: pointer; font-size: 20px;">&times;</button>';
            alerts.appendChild(div);
            setTimeout(() => div.remove(), 5000);
        }
    </script>
</body>
</html>
