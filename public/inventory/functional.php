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
    <title>Inventory Management - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Inventory Management</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / Inventory
                    </div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" onclick="openAddProductModal()">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                    <button class="btn btn-outline" onclick="exportInventory()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <div style="font-size: 12px; color: #999; margin-bottom: 0.5rem;">Total Products</div>
                            <div style="font-size: 28px; font-weight: bold;" id="totalProducts">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <div style="font-size: 12px; color: #999; margin-bottom: 0.5rem;">Low Stock Items</div>
                            <div style="font-size: 28px; font-weight: bold; color: #e74c3c;" id="lowStockCount">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <div style="font-size: 12px; color: #999; margin-bottom: 0.5rem;">Total Inventory Value</div>
                            <div style="font-size: 28px; font-weight: bold;" id="inventoryValue">₱0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <div style="font-size: 12px; color: #999; margin-bottom: 0.5rem;">Last Updated</div>
                            <div style="font-size: 14px; font-weight: bold;" id="lastUpdated">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="card" style="margin-bottom: 1rem;">
                <div class="card-body" style="display: flex; gap: 1rem; align-items: center;">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by SKU, Brand, or Model..." 
                           style="flex: 1; max-width: 300px;">
                    <select id="statusFilter" class="form-control" style="width: 150px;">
                        <option value="">All Status</option>
                        <option value="low">Low Stock</option>
                        <option value="normal">Normal</option>
                        <option value="overstock">Overstock</option>
                    </select>
                    <button class="btn btn-outline" onclick="loadInventory()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Product Inventory</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Size</th>
                                    <th>Quantity</th>
                                    <th>Min Level</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTable">
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem;">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal" style="display: none;">
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                    background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                    width: 90%; max-width: 500px; z-index: 1000;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3>Add New Product</h3>
                <button onclick="closeAddProductModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form id="addProductForm" style="display: flex; flex-direction: column; gap: 1rem;">
                <input type="text" name="sku" placeholder="SKU" required class="form-control">
                <input type="text" name="brand" placeholder="Brand" required class="form-control">
                <input type="text" name="model" placeholder="Model" required class="form-control">
                <input type="text" name="size" placeholder="Size" required class="form-control">
                <input type="text" name="color" placeholder="Color" required class="form-control">
                <input type="number" name="cost_price" placeholder="Cost Price" step="0.01" required class="form-control">
                <input type="number" name="selling_price" placeholder="Selling Price" step="0.01" required class="form-control">
                <input type="number" name="min_stock" placeholder="Min Stock Level" value="10" required class="form-control">
                <input type="number" name="max_stock" placeholder="Max Stock Level" value="100" required class="form-control">
                <div style="display: flex; gap: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeAddProductModal()" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Backdrop -->
    <div id="modalBackdrop" class="modal-backdrop" style="display: none;" onclick="closeAddProductModal()"></div>

    <script>
        // API Base URL
        const API_URL = '/ShoeRetailErp/api';

        // Load inventory on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadInventory();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('keyup', debounce(loadInventory, 500));
            document.getElementById('statusFilter').addEventListener('change', loadInventory);
            
            // Form submission
            document.getElementById('addProductForm').addEventListener('submit', handleAddProduct);
        });

        // Load inventory data
        function loadInventory() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            
            let url = API_URL + '/inventory.php?action=get_products';
            if (search) url += '&search=' + encodeURIComponent(search);
            
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        populateInventoryTable(data.data);
                        updateInventoryStats(data.data);
                    } else {
                        showAlert('Error: ' + data.message, 'danger');
                    }
                })
                .catch(err => showAlert('Error loading inventory: ' + err.message, 'danger'));
        }

        // Populate inventory table
        function populateInventoryTable(products) {
            const tbody = document.getElementById('inventoryTable');
            
            if (!products || products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">No products found</td></tr>';
                return;
            }

            tbody.innerHTML = products.map(product => {
                const status = getStockStatus(product.Quantity, product.MinStockLevel, product.MaxStockLevel);
                const badgeClass = status === 'Low Stock' ? 'badge-danger' : (status === 'Overstock' ? 'badge-warning' : 'badge-success');
                
                return `
                    <tr>
                        <td><strong>${product.SKU}</strong></td>
                        <td>${product.Brand}</td>
                        <td>${product.Model}</td>
                        <td>${product.Size}</td>
                        <td><strong>${product.Quantity || 0}</strong></td>
                        <td>${product.MinStockLevel}</td>
                        <td><span class="badge ${badgeClass}">${status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline" onclick="editProduct(${product.ProductID})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline" onclick="addStock(${product.ProductID}, '${product.SKU}')">
                                <i class="fas fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Update statistics
        function updateInventoryStats(products) {
            document.getElementById('totalProducts').textContent = products.length;
            
            const lowStock = products.filter(p => p.Quantity <= p.MinStockLevel).length;
            document.getElementById('lowStockCount').textContent = lowStock;
            
            const totalValue = products.reduce((sum, p) => sum + ((p.Quantity || 0) * (p.CostPrice || 0)), 0);
            document.getElementById('inventoryValue').textContent = '₱' + totalValue.toLocaleString();
            
            document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString();
        }

        // Get stock status
        function getStockStatus(quantity, min, max) {
            if (quantity <= min) return 'Low Stock';
            if (quantity >= max) return 'Overstock';
            return 'Normal';
        }

        // Open/Close modals
        function openAddProductModal() {
            document.getElementById('addProductModal').style.display = 'block';
            document.getElementById('modalBackdrop').style.display = 'block';
        }

        function closeAddProductModal() {
            document.getElementById('addProductModal').style.display = 'none';
            document.getElementById('modalBackdrop').style.display = 'none';
            document.getElementById('addProductForm').reset();
        }

        // Handle add product
        function handleAddProduct(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('addProductForm'));
            const data = Object.fromEntries(formData);
            
            fetch(API_URL + '/inventory.php?action=add_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert('Product added successfully!', 'success');
                    closeAddProductModal();
                    loadInventory();
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(err => showAlert('Error: ' + err.message, 'danger'));
        }

        // Add stock
        function addStock(productId, sku) {
            const quantity = prompt(`Enter quantity to add for ${sku}:`);
            if (!quantity || isNaN(quantity)) return;
            
            fetch(API_URL + '/inventory.php?action=stock_entry', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: productId,
                    store_id: 1,
                    quantity: parseInt(quantity)
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert('Stock added successfully!', 'success');
                    loadInventory();
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            });
        }

        // Export inventory
        function exportInventory() {
            window.location.href = API_URL + '/inventory.php?action=export_inventory';
        }

        // Edit product
        function editProduct(productId) {
            showAlert('Edit feature coming soon!', 'info');
        }

        // Utility functions
        function showAlert(message, type = 'info') {
            const container = document.querySelector('.alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `${message} <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>`;
            container.appendChild(alert);
            
            setTimeout(() => alert.remove(), 5000);
        }

        function debounce(func, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }
    </script>
</body>
</html>
