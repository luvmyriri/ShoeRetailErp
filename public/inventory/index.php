<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

// Role-based access control
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'Inventory', 'Procurement'];

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
    <title>Inventory - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Product Management</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Products</div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-warning" onclick="openBulkRestockModal()"><i class="fas fa-boxes"></i> Request Restock</button>
                </div>
            </div>

            <div class="row" style="margin-bottom: 2rem;">
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">👡</div><div class="stat-value" id="totalProducts">0</div><div class="stat-label">Total Sandal Products</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">🎨</div><div class="stat-value" id="variantCount">0</div><div class="stat-label">Product Variants</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value" id="inventoryValue">₱0</div><div class="stat-label">Total Inventory Value</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">⚠️</div><div class="stat-value" id="lowStockCount">0</div><div class="stat-label">Low Stock Alert</div></div></div>
            </div>

            <ul class="nav-tabs">
                <li><a href="#" class="nav-link active" data-tab="productsTab">All Products</a></li>
                <li><a href="#" class="nav-link" data-tab="analyticsTab">Analytics</a></li>
                <li><a href="#" class="nav-link" data-tab="stockViewTab">Stock View</a></li>
                <li><a href="#" class="nav-link" data-tab="alertsTab">Low Stock Alerts</a></li>
            </ul>

            <div id="analyticsTab" class="tab-pane" style="display:none;">
                <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;">Inventory Analytics</h3>
                    <div>
                        <label style="margin-right: 0.5rem; font-weight: 600;">Time Period:</label>
                        <select id="analyticsTimePeriod" class="form-control" style="display: inline-block; width: auto; padding: 0.4rem;">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly" selected>Monthly</option>
                        </select>
                        <button class="btn btn-sm btn-primary" onclick="loadAnalytics()" style="margin-left: 0.5rem;">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="row" style="margin-bottom: 1rem;">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h3>Stock Status Distribution</h3></div>
                            <div class="card-body">
                                <canvas id="stockStatusChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h3>Top 10 Products by Stock Value</h3></div>
                            <div class="card-body">
                                <canvas id="topProductsChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header"><h3>Products by Brand</h3></div>
                            <div class="card-body">
                                <canvas id="brandDistributionChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="productsTab" class="tab-pane active">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="margin: 0;">Store Products</h3>
                                <p style="margin: 0.5rem 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-store"></i> <span id="currentStoreName">Loading...</span></p>
                            </div>
                            <input type="text" id="searchProducts" placeholder="Search by SKU, Brand, or Model..." style="padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="inventoryTable">
                                <thead><tr><th>SKU</th><th>Brand/Model</th><th>Size</th><th>Color</th><th>Cost Price</th><th>Selling Price</th><th>Stock</th><th>Actions</th></tr></thead>
                                <tbody id="inventoryBody">
                                </tbody>
                            </table>
                        </div>
                        <div id="paginationControls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                            <div>
                                <span style="font-size: 14px;">Showing <span id="showingStart">0</span>-<span id="showingEnd">0</span> of <span id="totalProducts">0</span> products</span>
                            </div>
                            <div>
                                <label style="margin-right: 0.5rem; font-size: 14px;">Items per page:</label>
                                <select id="itemsPerPage" class="form-control" style="display: inline-block; width: auto; padding: 0.3rem; font-size: 14px;" onchange="changeItemsPerPage()">
                                    <option value="10">10</option>
                                    <option value="25" selected>25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div id="paginationButtons">
                                </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="stockViewTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Multi-Store Stock View</h3>
                        <p style="margin: 0.5rem 0 0 0; font-size: 14px; color: #666;">
                            <i class="fas fa-info-circle"></i> Compare inventory levels across all stores (Read-only)
                        </p>
                    </div>
                    <div class="card-body">
                        <div id="stockViewContent"></div>
                    </div>
                </div>
            </div>

            <div id="alertsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Low Stock Alerts</h3></div>
                    <div class="card-body" id="alertsBody">
                        <p>You have 3 products with low stock levels.</p>
                    </div>
                </div>
            </div>

            <div id="reportsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Inventory Reports</h3></div>
                    <div class="card-body">
                        <p>Reports will be generated here.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="bulkRestockModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 800px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 18px;">Request Product Restocking</h3>
                <button onclick="closeBulkRestockModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <div style="margin-bottom: 1rem; padding: 1rem; background: #fff3cd; border-radius: 4px; font-size: 14px;">
                <strong><i class="fas fa-info-circle"></i> Instructions:</strong> Select products and specify quantities to request from Procurement.
            </div>
            <form id="bulkRestockForm">
                <div style="margin-bottom: 1rem; padding: 0.75rem; background: #e8f4f8; border-radius: 4px;">
                    <strong><i class="fas fa-store"></i> Store:</strong> Maria Collections Bagong Silang
                </div>
                
                <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #ddd;">
                    <button type="button" class="btn btn-success btn-sm" onclick="openAddNewProductModal()">
                        <i class="fas fa-plus"></i> Add New Product
                    </button>
                    <span style="margin-left: 0.5rem; font-size: 13px; color: #666;">Add a product variant that isn't in the system yet.</span>
                </div>
                
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                    <table class="table" style="margin: 0;">
                        <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 1;">
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="selectAllProducts" onclick="toggleAllProducts(this)"></th>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th style="width: 120px;">Quantity</th>
                            </tr>
                        </thead>
                        <tbody id="bulkRestockProductList">
                            </tbody>
                    </table>
                </div>
                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeBulkRestockModal()" style="flex: 1; padding: 0.5rem; font-size: 14px;">Cancel</button>
                    <button type="submit" class="btn btn-warning" style="flex: 1; padding: 0.5rem; font-size: 14px;"><i class="fas fa-paper-plane"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addNewProductModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10001; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 450px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 18px;">Add New Product to Request</h3>
                <button onclick="closeAddNewProductModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <form id="addNewProductForm" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <input type="text" id="new_brand" placeholder="Brand" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="text" id="new_model" placeholder="Model/Style" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="text" id="new_size" placeholder="Size" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="text" id="new_color" placeholder="Color" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="number" id="new_cost_price" placeholder="Cost Price" step="0.01" min="0" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="number" id="new_selling_price" placeholder="Selling Price" step="0.01" min="0" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="number" id="new_min_stock" placeholder="Min Stock Level" value="10" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="number" id="new_max_stock" placeholder="Max Stock Level" value="50" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <hr style="margin: 0.25rem 0;">
                <label style="font-weight: 600; font-size: 14px;">Quantity to Request:</label>
                <input type="number" id="new_quantity" placeholder="Quantity to Request" min="1" required class="form-control" style="padding: 0.5rem; font-size: 14px; border: 2px solid #714B67;">
                <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeAddNewProductModal()" style="flex: 1; padding: 0.5rem; font-size: 14px;">Cancel</button>
                    <button type="submit" class="btn btn-success" style="flex: 1; padding: 0.5rem; font-size: 14px;"><i class="fas fa-plus"></i> Add to List</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="editProductModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 450px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 18px;">Edit Product</h3>
                <button onclick="closeEditProductModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <form id="editProductForm" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <input type="hidden" name="product_id" id="edit_product_id">
                <input type="text" name="sku" id="edit_sku" placeholder="SKU" readonly class="form-control" style="padding: 0.5rem; font-size: 14px; background: #f5f5f5;">
                <input type="text" name="brand" id="edit_brand" placeholder="Brand" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="text" name="model" id="edit_model" placeholder="Model/Style" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="text" name="size" id="edit_size" placeholder="Size" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="text" name="color" id="edit_color" placeholder="Color" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="number" name="cost_price" id="edit_cost_price" placeholder="Cost Price" step="0.01" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="number" name="selling_price" id="edit_selling_price" placeholder="Selling Price" step="0.01" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="number" name="min_stock" id="edit_min_stock" placeholder="Min Stock Level" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <input type="number" name="max_stock" id="edit_max_stock" placeholder="Max Stock Level" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <select name="status" id="edit_status" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                    <option value="Active">Available</option>
                    <option value="Inactive">Unavailable</option>
                </select>
                <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeEditProductModal()" style="flex: 1; padding: 0.5rem; font-size: 14px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.5rem; font-size: 14px;">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <div id="restockModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 450px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 18px;">Request Restocking</h3>
                <button onclick="closeRestockModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <form id="restockForm" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <input type="hidden" name="product_id" id="restock_product_id">
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 4px; margin-bottom: 0.5rem;">
                    <div><strong>Product:</strong> <span id="restock_product_name"></span></div>
                    <div><strong>SKU:</strong> <span id="restock_sku"></span></div>
                    <div><strong>Store:</strong> Maria Collections Bagong Silang</div>
                </div>
                <label style="font-weight: 600; font-size: 14px;">Quantity to Order:</label>
                <input type="number" name="quantity" id="restock_quantity" placeholder="Quantity" min="1" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
                <textarea name="notes" placeholder="Notes (optional)" class="form-control" style="padding: 0.5rem; font-size: 14px; min-height: 60px;"></textarea>
                <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeRestockModal()" style="flex: 1; padding: 0.5rem; font-size: 14px;">Cancel</button>
                    <button type="submit" class="btn btn-warning" style="flex: 1; padding: 0.5rem; font-size: 14px;"><i class="fas fa-paper-plane"></i> Send Request</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewProductModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 18px;">Product Details</h3>
                <button onclick="closeViewProductModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <div id="viewProductContent" style="font-size: 14px;">
                </div>
            <div style="margin-top: 1rem; text-align: right;">
                <button class="btn btn-outline" onclick="closeViewProductModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // API Base URL
        const API_URL = '/ShoeRetailErp/api';

        let currentProducts = [];
        let currentStore = null;
        let currentPage = 1;
        let itemsPerPage = 25;
        let filteredProducts = [];
        
        // Load inventory on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStore();
            loadInventory();
            
            // Tab switching
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.tab-pane').forEach(pane => pane.style.display = 'none');
                    document.getElementById(this.dataset.tab).style.display = 'block';
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Load analytics when tab is opened
                    if (this.dataset.tab === 'analyticsTab') {
                        loadAnalytics();
                    }
                    
                    // Load stock view when tab is opened
                    if (this.dataset.tab === 'stockViewTab') {
                        loadStockView();
                    }
                });
            });
            
            // Form submissions
            document.getElementById('editProductForm').addEventListener('submit', handleEditProduct);
            document.getElementById('restockForm').addEventListener('submit', handleRestockRequest);
            document.getElementById('bulkRestockForm').addEventListener('submit', handleBulkRestockRequest);
            
            // NEW: Add new product form submission
            document.getElementById('addNewProductForm').addEventListener('submit', handleAddNewProductToList);
        });

        // Load inventory data
        function loadInventory() {
            let url = API_URL + '/inventory.php?action=get_products';
            
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        currentProducts = data.data;
                        filteredProducts = data.data;
                        currentPage = 1;
                        renderPaginatedTable();
                        updateInventoryStats(data.data);
                    } else {
                        showAlert('Error: ' + data.message, 'danger');
                    }
                })
                .catch(err => showAlert('Error loading inventory: ' + err.message, 'danger'));
        }
        
        // Pagination functions
        function renderPaginatedTable() {
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedProducts = filteredProducts.slice(start, end);
            
            populateInventoryTable(paginatedProducts);
            updatePaginationControls();
        }
        
        function updatePaginationControls() {
            const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
            const start = (currentPage - 1) * itemsPerPage + 1;
            const end = Math.min(currentPage * itemsPerPage, filteredProducts.length);
            
            document.getElementById('showingStart').textContent = filteredProducts.length > 0 ? start : 0;
            document.getElementById('showingEnd').textContent = end;
            document.getElementById('totalProducts').textContent = filteredProducts.length;
            
            // Create pagination buttons
            const buttonsContainer = document.getElementById('paginationButtons');
            let buttonsHTML = '';
            
            // Previous button
            buttonsHTML += `<button class="btn btn-sm btn-outline" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i> Previous
            </button>`;
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    buttonsHTML += `<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline'}" onclick="goToPage(${i})" style="margin: 0 2px;">
                        ${i}
                    </button>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    buttonsHTML += '<span style="padding: 0 5px;">...</span>';
                }
            }
            
            // Next button
            buttonsHTML += `<button class="btn btn-sm btn-outline" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}>
                Next <i class="fas fa-chevron-right"></i>
            </button>`;
            
            buttonsContainer.innerHTML = buttonsHTML;
        }
        
        function goToPage(page) {
            const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderPaginatedTable();
        }
        
        function changeItemsPerPage() {
            itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
            currentPage = 1;
            renderPaginatedTable();
        }
        
        // Load store information
        function loadStore() {
            fetch(API_URL + '/inventory.php?action=get_stores')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        currentStore = data.data[0]; // Get first (and only) store
                        updateStoreDisplay();
                    }
                })
                .catch(err => console.error('Error loading store:', err));
        }
        
        function updateStoreDisplay() {
            if (currentStore) {
                document.getElementById('currentStoreName').textContent = currentStore.StoreName;
            }
        }

        // Populate inventory table
        function populateInventoryTable(products) {
            const tbody = document.getElementById('inventoryBody');
            
            if (!products || products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">No sandal products found. Add your first product!</td></tr>';
                return;
            }

            tbody.innerHTML = products.map(product => {
                const qty = product.Quantity || 0;
                const min = product.MinStockLevel || 10;
                const stockBadge = qty <= min ? '<span class="badge badge-danger">Low Stock</span>' : 
                                  '<span class="badge badge-success">' + qty + '</span>';
                
                const needsRestock = qty <= min;
                const restockBtn = needsRestock ? 
                    `<button class="btn btn-sm btn-warning" onclick="requestRestock(${product.ProductID}, '${product.SKU}', '${product.Brand}', '${product.Model}')" style="margin-right: 5px;" title="Request Restocking">
                        <i class="fas fa-boxes"></i> Restock
                    </button>` : '';
                
                return `<tr>
                    <td><strong>${product.SKU}</strong></td>
                    <td><strong>${product.Brand}</strong><br><small style="color: #666;">${product.Model}</small></td>
                    <td>${product.Size}</td>
                    <td>${product.Color}</td>
                    <td>₱${parseFloat(product.CostPrice).toFixed(2)}</td>
                    <td>₱${parseFloat(product.SellingPrice).toFixed(2)}</td>
                    <td>${stockBadge}</td>
                    <td style="white-space: nowrap;">
                        ${restockBtn}
                        <button class="btn btn-sm btn-primary" onclick="editProduct(${product.ProductID})" style="margin-right: 5px;">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-info" onclick="viewProduct(${product.ProductID})">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        // Update statistics
        function updateInventoryStats(products) {
            document.getElementById('totalProducts').textContent = products.length;
            
            // Count unique variants (unique combinations of size and color)
            const variants = new Set(products.map(p => `${p.Size}-${p.Color}`));
            document.getElementById('variantCount').textContent = variants.size;
            
            const lowStock = products.filter(p => (p.Quantity || 0) <= (p.MinStockLevel || 10)).length;
            document.getElementById('lowStockCount').textContent = lowStock;
            
            const totalValue = products.reduce((sum, p) => sum + ((p.Quantity || 0) * (p.CostPrice || 0)), 0);
            document.getElementById('inventoryValue').textContent = '₱' + totalValue.toLocaleString(undefined, {minimumFractionDigits: 2});
            
            // Update alerts
            const alertsBody = document.getElementById('alertsBody');
            if (lowStock > 0) {
                const lowStockProducts = products.filter(p => (p.Quantity || 0) <= (p.MinStockLevel || 10))
                    .map(p => `<li><strong>${p.Brand} ${p.Model}</strong> - Size ${p.Size}, ${p.Color} (Stock: ${p.Quantity || 0})</li>`).join('');
                alertsBody.innerHTML = `<p><strong>${lowStock}</strong> product(s) need restocking:</p><ul>${lowStockProducts}</ul><p><em>Create a purchase order in Procurement to restock these items.</em></p>`;
            } else {
                alertsBody.innerHTML = '<p><i class="fas fa-check-circle" style="color: #28a745;"></i> All stock levels are optimal!</p>';
            }
        }


        // Open/Close modals
        function openBulkRestockModal() {
            // Load products into the bulk restock modal
            const tbody = document.getElementById('bulkRestockProductList');
            tbody.innerHTML = ''; // Clear previous entries
            
            currentProducts.forEach(product => {
                const row = `
                    <tr>
                        <td><input type="checkbox" class="product-checkbox" value="${product.ProductID}" data-sku="${product.SKU}"></td>
                        <td><strong>${product.Brand} ${product.Model}</strong><br><small>${product.SKU} - Size ${product.Size}, ${product.Color}</small></td>
                        <td>${product.Quantity || 0}</td>
                        <td><input type="number" min="1" value="${product.MinStockLevel || 10}" class="form-control" style="padding: 0.3rem; font-size: 13px;" id="qty_${product.ProductID}" disabled></td>
                    </tr>`;
                tbody.innerHTML += row;
            });
            
            // Enable quantity input when checkbox is checked
            document.querySelectorAll('.product-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const qtyInput = document.getElementById(`qty_${this.value}`);
                    qtyInput.disabled = !this.checked;
                });
            });
            
            document.getElementById('bulkRestockModal').style.display = 'flex';
        }

        function closeBulkRestockModal() {
            document.getElementById('bulkRestockModal').style.display = 'none';
            document.getElementById('bulkRestockForm').reset();
            // Clear dynamically added new products
            document.getElementById('bulkRestockProductList').innerHTML = '';
        }
        
        // NEW: Open/Close "Add New Product" Modal
        function openAddNewProductModal() {
            document.getElementById('addNewProductModal').style.display = 'flex';
        }

        function closeAddNewProductModal() {
            document.getElementById('addNewProductModal').style.display = 'none';
            document.getElementById('addNewProductForm').reset();
        }

        // NEW: Handle adding a new product to the restock list
        function handleAddNewProductToList(e) {
            e.preventDefault();
            
            const brand = document.getElementById('new_brand').value;
            const model = document.getElementById('new_model').value;
            const size = document.getElementById('new_size').value;
            const color = document.getElementById('new_color').value;
            const costPrice = document.getElementById('new_cost_price').value;
            const sellingPrice = document.getElementById('new_selling_price').value;
            const minStock = document.getElementById('new_min_stock').value;
            const maxStock = document.getElementById('new_max_stock').value;
            const quantity = document.getElementById('new_quantity').value;
            
            if (quantity <= 0) {
                showAlert('Quantity to Request must be at least 1', 'warning');
                return;
            }
            
            const tempId = 'NEW_' + Date.now();
            
            const newRowHTML = `
                <tr id="row_${tempId}" style="background: #f0fff0;">
                    <td><input type="checkbox" class="product-checkbox" value="${tempId}" 
                               data-is-new="true"
                               data-brand="${brand}"
                               data-model="${model}"
                               data-size="${size}"
                               data-color="${color}"
                               data-cost-price="${costPrice}"
                               data-selling-price="${sellingPrice}"
                               data-min-stock="${minStock}"
                               data-max-stock="${maxStock}"
                               checked></td>
                    <td><strong>${brand} ${model}</strong><br><small>Size ${size}, ${color} <span class="badge badge-success">NEW</span></small></td>
                    <td>0</td>
                    <td><input type="number" min="1" value="${quantity}" class="form-control" style="padding: 0.3rem; font-size: 13px;" id="qty_${tempId}"></td>
                </tr>
            `;
            
            // Prepend to the list
            document.getElementById('bulkRestockProductList').insertAdjacentHTML('afterbegin', newRowHTML);
            
            // Re-attach listener for the new checkbox
            document.querySelector(`#row_${tempId} .product-checkbox`).addEventListener('change', function() {
                const qtyInput = document.getElementById(`qty_${this.value}`);
                qtyInput.disabled = !this.checked;
            });
            
            closeAddNewProductModal();
        }
        
        function toggleAllProducts(checkbox) {
            document.querySelectorAll('.product-checkbox').forEach(cb => {
                cb.checked = checkbox.checked;
                const qtyInput = document.getElementById(`qty_${cb.value}`);
                qtyInput.disabled = !checkbox.checked;
            });
        }
        
        function openEditProductModal() {
            document.getElementById('editProductModal').style.display = 'flex';
        }

        function closeEditProductModal() {
            document.getElementById('editProductModal').style.display = 'none';
            document.getElementById('editProductForm').reset();
        }
        
        function openViewProductModal() {
            document.getElementById('viewProductModal').style.display = 'flex';
        }

        function closeViewProductModal() {
            document.getElementById('viewProductModal').style.display = 'none';
            document.getElementById('viewProductContent').innerHTML = '';
        }
        
        function openRestockModal() {
            document.getElementById('restockModal').style.display = 'flex';
        }

        function closeRestockModal() {
            document.getElementById('restockModal').style.display = 'none';
            document.getElementById('restockForm').reset();
        }

        // Handle bulk restock request
        function handleBulkRestockRequest(e) {
            e.preventDefault();
            
            if (!currentStore) {
                showAlert('Store information not loaded', 'danger');
                return;
            }
            
            const storeId = currentStore.StoreID;
            
            const selectedProducts = [];
            document.querySelectorAll('.product-checkbox:checked').forEach(checkbox => {
                const isNew = checkbox.dataset.isNew === 'true';
                const productId = checkbox.value; // This is tempId for new products
                const quantity = document.getElementById(`qty_${productId}`).value;
                
                if (quantity && quantity > 0) {
                    if (isNew) {
                        selectedProducts.push({
                            is_new: true,
                            brand: checkbox.dataset.brand,
                            model: checkbox.dataset.model,
                            size: checkbox.dataset.size,
                            color: checkbox.dataset.color,
                            cost_price: checkbox.dataset.costPrice,
                            selling_price: checkbox.dataset.sellingPrice,
                            min_stock: checkbox.dataset.minStock,
                            max_stock: checkbox.dataset.maxStock,
                            quantity: parseInt(quantity)
                        });
                    } else {
                        selectedProducts.push({
                            is_new: false,
                            product_id: productId,
                            quantity: parseInt(quantity)
                        });
                    }
                }
            });
            
            if (selectedProducts.length === 0) {
                showAlert('Please select at least one product', 'warning');
                return;
            }
            
            // Submit bulk restock request
            fetch(API_URL + '/inventory.php?action=bulk_request_restock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    store_id: storeId,
                    products: selectedProducts
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message || `Successfully created ${selectedProducts.length} restock request(s)!`, 'success');
                    closeBulkRestockModal();
                    loadInventory(); // Refresh inventory to show new products (if they get stock)
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(err => showAlert('Error: ' + err.message, 'danger'));
        }
        
        // Handle edit product
        function handleEditProduct(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('editProductForm'));
            const data = Object.fromEntries(formData);
            
            fetch(API_URL + '/inventory.php?action=update_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert('Product updated successfully!', 'success');
                    closeEditProductModal();
                    loadInventory();
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(err => showAlert('Error: ' + err.message, 'danger'));
        }

        // Edit product
        function editProduct(productId) {
            fetch(`${API_URL}/inventory.php?action=get_product&product_id=${productId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const product = data.data.product;
                        document.getElementById('edit_product_id').value = product.ProductID;
                        document.getElementById('edit_sku').value = product.SKU;
                        document.getElementById('edit_brand').value = product.Brand;
                        document.getElementById('edit_model').value = product.Model;
                        document.getElementById('edit_size').value = product.Size;
                        document.getElementById('edit_color').value = product.Color;
                        document.getElementById('edit_cost_price').value = product.CostPrice;
                        document.getElementById('edit_selling_price').value = product.SellingPrice;
                        document.getElementById('edit_min_stock').value = product.MinStockLevel;
                        document.getElementById('edit_max_stock').value = product.MaxStockLevel;
                        document.getElementById('edit_status').value = product.Status;
                        openEditProductModal();
                    } else {
                        showAlert('Error: ' + data.message, 'danger');
                    }
                })
                .catch(err => showAlert('Error loading product: ' + err.message, 'danger'));
        }
        
        // View product details
        function viewProduct(productId) {
            fetch(`${API_URL}/inventory.php?action=get_product&product_id=${productId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const product = data.data.product;
                        const stockLevels = data.data.stock_levels;
                        
                        let html = `
                            <div style="margin-bottom: 1.5rem;">
                                <h4 style="margin: 0 0 1rem 0; padding-bottom: 0.5rem; border-bottom: 2px solid #714B67;">Product Information</h4>
                                <div style="display: grid; grid-template-columns: 150px 1fr; gap: 0.5rem;">
                                    <strong>SKU:</strong><span>${product.SKU}</span>
                                    <strong>Brand:</strong><span>${product.Brand}</span>
                                    <strong>Model:</strong><span>${product.Model}</span>
                                    <strong>Size:</strong><span>${product.Size}</span>
                                    <strong>Color:</strong><span>${product.Color}</span>
                                    <strong>Cost Price:</strong><span>₱${parseFloat(product.CostPrice).toFixed(2)}</span>
                                    <strong>Selling Price:</strong><span>₱${parseFloat(product.SellingPrice).toFixed(2)}</span>
                                    <strong>Min Stock:</strong><span>${product.MinStockLevel}</span>
                                    <strong>Max Stock:</strong><span>${product.MaxStockLevel}</span>
                                    <strong>Status:</strong><span><span class="badge badge-${product.Status === 'Active' ? 'success' : 'secondary'}">${product.Status === 'Active' ? 'Available' : 'Unavailable'}</span></span>
                                </div>
                            </div>
                            <div>
                                <h4 style="margin: 0 0 1rem 0; padding-bottom: 0.5rem; border-bottom: 2px solid #714B67;">Stock Levels by Store</h4>
                                <table class="table" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Store</th>
                                            <th>Quantity</th>
                                            <th>Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                        
                        if (stockLevels && stockLevels.length > 0) {
                            stockLevels.forEach(stock => {
                                html += `<tr>
                                    <td>${stock.StoreName}</td>
                                    <td><span class="badge badge-${stock.Quantity <= product.MinStockLevel ? 'danger' : 'success'}">${stock.Quantity}</span></td>
                                    <td>${new Date(stock.LastUpdated).toLocaleString()}</td>
                                </tr>`;
                            });
                        } else {
                            html += '<tr><td colspan="3" style="text-align: center;">No stock data available</td></tr>';
                        }
                        
                        html += `</tbody></table>
                            <p style="font-size: 12px; color: #666; margin-top: 1rem;"><em>Note: Stock levels are managed automatically through Sales and Procurement modules.</em></p>
                        </div>`;
                        
                        document.getElementById('viewProductContent').innerHTML = html;
                        openViewProductModal();
                    } else {
                        showAlert('Error: ' + data.message, 'danger');
                    }
                })
                .catch(err => showAlert('Error loading product: ' + err.message, 'danger'));
        }
        
        // Request restock
        function requestRestock(productId, sku, brand, model) {
            document.getElementById('restock_product_id').value = productId;
            document.getElementById('restock_sku').textContent = sku;
            document.getElementById('restock_product_name').textContent = `${brand} ${model}`;
            openRestockModal();
        }
        
        // Handle restock request submission
        function handleRestockRequest(e) {
            e.preventDefault();
            
            if (!currentStore) {
                showAlert('Store information not loaded', 'danger');
                return;
            }
            
            const formData = new FormData(document.getElementById('restockForm'));
            const data = Object.fromEntries(formData);
            data.store_id = currentStore.StoreID;
            
            fetch(API_URL + '/inventory.php?action=request_restock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert('Restock request sent to Procurement successfully!', 'success');
                    closeRestockModal();
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(err => showAlert('Error: ' + err.message, 'danger'));
        }
        
        // Load stock view
        function loadStockView() {
            fetch(`${API_URL}/inventory.php?action=get_stock_view`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        renderStockView(data.data);
                    } else {
                        showAlert('Error: ' + data.message, 'danger');
                    }
                })
                .catch(err => showAlert('Error loading stock view: ' + err.message, 'danger'));
        }
        
        // Render stock view
        function renderStockView(stockData) {
            const container = document.getElementById('stockViewContent');
            
            if (!stockData || stockData.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666;">No stock data available.</p>';
                return;
            }
            
            // Group by store
            const storeGroups = {};
            stockData.forEach(item => {
                if (!storeGroups[item.StoreName]) {
                    storeGroups[item.StoreName] = [];
                }
                storeGroups[item.StoreName].push(item);
            });
            
            let html = '';
            Object.keys(storeGroups).forEach(storeName => {
                const products = storeGroups[storeName];
                const totalValue = products.reduce((sum, p) => sum + (p.Quantity * (p.CostPrice || 0)), 0);
                
                html += `
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header" style="background: #714B67; color: white;">
                            <h4 style="margin: 0; font-size: 16px;">
                                <i class="fas fa-store"></i> ${storeName}
                                <span style="float: right; font-size: 14px;">Total Value: ₱${totalValue.toFixed(2)}</span>
                            </h4>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <table class="table" style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                
                products.forEach(product => {
                    const qty = product.Quantity || 0;
                    const min = product.MinStockLevel || 10;
                    const max = product.MaxStockLevel || 100;
                    let status = 'Normal';
                    let statusClass = 'success';
                    
                    if (qty <= min) {
                        status = 'Low Stock';
                        statusClass = 'danger';
                    } else if (qty >= max) {
                        status = 'Overstock';
                        statusClass = 'warning';
                    }
                    
                    html += `
                        <tr>
                            <td>${product.SKU}</td>
                            <td>${product.Brand} ${product.Model} - ${product.Size} (${product.Color})</td>
                            <td><strong>${qty}</strong></td>
                            <td><span class="badge badge-${statusClass}">${status}</span></td>
                            <td>${new Date(product.LastUpdated).toLocaleString()}</td>
                        </tr>`;
                });
                
                html += `</tbody>
                            </table>
                        </div>
                    </div>`;
            });
            
            container.innerHTML = html;
        }
        
        // Load analytics with time period
        function loadAnalytics() {
            if (currentProducts.length === 0) return;
            
            const timePeriod = document.getElementById('analyticsTimePeriod').value;
            
            // Fetch analytics data from backend based on time period
            fetch(`${API_URL}/inventory.php?action=get_analytics&store_id=${currentStore?.StoreID || ''}&time_period=${timePeriod}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        renderAnalyticsCharts(data.data, timePeriod);
                    } else {
                        // Fallback to current products if API doesn't support time-based analytics yet
                        renderAnalyticsCharts({products: currentProducts}, timePeriod);
                    }
                })
                .catch(err => {
                    // Fallback to current products on error
                    renderAnalyticsCharts({products: currentProducts}, timePeriod);
                });
        }
        
        // Render analytics charts
        function renderAnalyticsCharts(analyticsData, timePeriod) {
            const products = analyticsData.products || currentProducts;
            
            // Stock Status Distribution
            const lowStock = products.filter(p => (p.Quantity || 0) <= (p.MinStockLevel || 10)).length;
            const normal = products.filter(p => {
                const qty = p.Quantity || 0;
                const min = p.MinStockLevel || 10;
                const max = p.MaxStockLevel || 100;
                return qty > min && qty < max;
            }).length;
            const overstock = products.filter(p => (p.Quantity || 0) >= (p.MaxStockLevel || 100)).length;
            
            createChart('stockStatusChart', 'doughnut', {
                labels: ['Low Stock', 'Normal', 'Overstock'],
                datasets: [{
                    data: [lowStock, normal, overstock],
                    backgroundColor: ['#dc3545', '#28a745', '#ffc107']
                }]
            });
            
            // Top 10 Products by Value
            const topProducts = [...products]
                .map(p => ({...p, value: (p.Quantity || 0) * (p.CostPrice || 0)}))
                .sort((a, b) => b.value - a.value)
                .slice(0, 10);
            
            createChart('topProductsChart', 'bar', {
                labels: topProducts.map(p => p.SKU),
                datasets: [{
                    label: 'Stock Value (₱)',
                    data: topProducts.map(p => p.value),
                    backgroundColor: '#714B67'
                }]
            }, {indexAxis: 'y'});
            
            // Products by Brand
            const brandCounts = {};
            products.forEach(p => {
                brandCounts[p.Brand] = (brandCounts[p.Brand] || 0) + 1;
            });
            
            createChart('brandDistributionChart', 'bar', {
                labels: Object.keys(brandCounts),
                datasets: [{
                    label: 'Number of Products',
                    data: Object.values(brandCounts),
                    backgroundColor: '#F5B041'
                }]
            });
        }
        
        // Helper to create charts
        function createChart(canvasId, type, data, options = {}) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            
            // Destroy existing chart if any
            if (ctx.chart) {
                ctx.chart.destroy();
            }
            
            ctx.chart = new Chart(ctx, {
                type: type,
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    ...options
                }
            });
        }

        // Export inventory
        function exportInventory() {
            window.location.href = API_URL + '/inventory.php?action=export_inventory';
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
    <script src="../js/app.js"></script>
</body>
</html>