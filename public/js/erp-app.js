/**
 * Shoe Retail ERP - Frontend Application
 * Handles all UI interactions, API calls, and data management
 */

const ERP = {
    baseURL: '/ShoeRetailErp/api',
    config: {
        pageSize: 20,
        debounceDelay: 500
    },
    state: {
        currentPage: 'dashboard',
        currentModule: null,
        filters: {},
        sortBy: 'id',
        sortOrder: 'asc'
    },

    // =====================================================
    // INITIALIZATION
    // =====================================================

    init() {
        this.setupEventListeners();
        this.setupModals();
        this.setupNotifications();
        this.loadDashboard();
    },

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.sidebar-link, .navbar-nav a').forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                if (href.includes('?page=')) {
                    e.preventDefault();
                    const page = new URL(href, window.location.origin).searchParams.get('page');
                    this.loadModule(page);
                }
            });
        });

        // Search functionality
        const globalSearch = document.getElementById('globalSearch');
        if (globalSearch) {
            globalSearch.addEventListener('keyup', this.debounce((e) => {
                this.performSearch(e.target.value);
            }, this.config.debounceDelay));
        }

        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('erp-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });

        // Tab switching
        document.querySelectorAll('[data-tab]').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const tabName = tab.getAttribute('data-tab');
                this.switchTab(tabName);
            });
        });
    },

    setupModals() {
        // Modal close buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-close') || e.target.hasAttribute('data-dismiss')) {
                const modal = e.target.closest('.modal');
                if (modal) this.closeModal(modal);
            }
        });

        // Close modal on backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-backdrop')) {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    this.closeModal(modal);
                });
            }
        });
    },

    setupNotifications() {
        // Global notification close buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('alert-close')) {
                e.target.closest('.alert').remove();
            }
        });
    },

    // =====================================================
    // NAVIGATION & MODULES
    // =====================================================

    loadModule(moduleName) {
        this.state.currentModule = moduleName;
        document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
        document.querySelector(`[href*="page=${moduleName}"]`)?.classList.add('active');

        switch (moduleName) {
            case 'inventory':
                this.loadInventory();
                break;
            case 'sales':
                this.loadSales();
                break;
            case 'procurement':
                this.loadProcurement();
                break;
            case 'customers':
                this.loadCustomers();
                break;
            case 'accounting':
                this.loadAccounting();
                break;
            case 'hr':
                this.loadHR();
                break;
            default:
                this.loadDashboard();
        }
    },

    // =====================================================
    // DASHBOARD
    // =====================================================

    loadDashboard() {
        this.showLoader();
        this.fetchAPI('/api/dashboard.php?action=get_stats')
            .then(data => {
                this.updateDashboardStats(data);
                this.loadRecentSales();
                this.hideLoader();
            })
            .catch(err => this.showError('Failed to load dashboard: ' + err.message));
    },

    updateDashboardStats(stats) {
        document.querySelector('[data-stat="total-revenue"]').textContent = 
            this.formatCurrency(stats.total_revenue || 0);
        document.querySelector('[data-stat="total-customers"]').textContent = 
            stats.total_customers || 0;
        document.querySelector('[data-stat="items-in-stock"]').textContent = 
            stats.items_in_stock || 0;
        document.querySelector('[data-stat="order-fulfillment"]').textContent = 
            stats.order_fulfillment + '%' || '0%';
    },

    loadRecentSales() {
        this.fetchAPI(this.baseURL + '/sales.php?action=get_orders&limit=5')
            .then(data => {
                this.populateTable('recent-sales-table', data.data);
            })
            .catch(err => console.error('Failed to load recent sales:', err));
    },

    // =====================================================
    // INVENTORY MODULE
    // =====================================================

    loadInventory() {
        this.state.currentModule = 'inventory';
        this.showLoader();
        
        const container = document.getElementById('main-content');
        container.innerHTML = this.getInventoryTemplate();
        
        this.setupInventoryFilters();
        this.loadInventoryData();
    },

    getInventoryTemplate() {
        return `
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Inventory Management</h1>
                    <div class="page-header-breadcrumb">
                        <a href="#" onclick="ERP.loadDashboard()">Home</a> / Inventory
                    </div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" onclick="ERP.openModal('addProductModal')">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                    <button class="btn btn-outline" onclick="ERP.exportInventory()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Stock Inventory</h3>
                    <div style="display: flex; gap: 1rem;">
                        <input type="text" id="inventory-search" class="form-control" 
                               placeholder="Search products..." style="width: 250px;">
                        <select id="inventory-filter" class="form-control" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="low">Low Stock</option>
                            <option value="normal">Normal</option>
                            <option value="overstock">Overstock</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="inventory-table">
                            <thead>
                                <tr>
                                    <th onclick="ERP.sortTable('sku')">SKU <i class="fas fa-sort"></i></th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Size</th>
                                    <th>Color</th>
                                    <th onclick="ERP.sortTable('quantity')">Quantity <i class="fas fa-sort"></i></th>
                                    <th>Min Level</th>
                                    <th>Max Level</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="inventory-tbody">
                                <tr><td colspan="10" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination" id="inventory-pagination"></div>
                </div>
            </div>

            ${this.getAddProductModalTemplate()}
            ${this.getEditProductModalTemplate()}
        `;
    },

    getAddProductModalTemplate() {
        return `
            <div id="addProductModal" class="modal">
                <div class="modal-header">
                    <h3>Add New Product</h3>
                    <button class="modal-close" onclick="ERP.closeModal(document.getElementById('addProductModal'))">&times;</button>
                </div>
                <form class="erp-form" data-action="inventory" data-method="add_product">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Size</label>
                                <input type="text" name="size" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Color</label>
                                <input type="text" name="color" class="form-control" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Cost Price</label>
                                <input type="number" name="cost_price" class="form-control" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Selling Price</label>
                                <input type="number" name="selling_price" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Min Stock Level</label>
                                <input type="number" name="min_stock" class="form-control" value="10" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Stock Level</label>
                                <input type="number" name="max_stock" class="form-control" value="100" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-control" required>
                                <option value="">Select Supplier</option>
                                <option value="1">Nike</option>
                                <option value="2">Adidas</option>
                                <option value="3">Puma</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="ERP.closeModal(document.getElementById('addProductModal'))">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        `;
    },

    getEditProductModalTemplate() {
        return `
            <div id="editProductModal" class="modal">
                <div class="modal-header">
                    <h3>Edit Product</h3>
                    <button class="modal-close" onclick="ERP.closeModal(document.getElementById('editProductModal'))">&times;</button>
                </div>
                <form class="erp-form" data-action="inventory" data-method="update_product">
                    <div class="modal-body">
                        <input type="hidden" name="product_id">
                        <div class="form-group">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Size</label>
                                <input type="text" name="size" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Color</label>
                                <input type="text" name="color" class="form-control" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Cost Price</label>
                                <input type="number" name="cost_price" class="form-control" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Selling Price</label>
                                <input type="number" name="selling_price" class="form-control" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="ERP.closeModal(document.getElementById('editProductModal'))">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        `;
    },

    setupInventoryFilters() {
        const searchInput = document.getElementById('inventory-search');
        const filterSelect = document.getElementById('inventory-filter');

        if (searchInput) {
            searchInput.addEventListener('keyup', this.debounce(() => {
                this.state.filters.search = searchInput.value;
                this.loadInventoryData();
            }, 300));
        }

        if (filterSelect) {
            filterSelect.addEventListener('change', () => {
                this.state.filters.status = filterSelect.value;
                this.loadInventoryData();
            });
        }
    },

    loadInventoryData() {
        let url = this.baseURL + '/inventory.php?action=get_products';
        if (this.state.filters.search) {
            url += '&search=' + encodeURIComponent(this.state.filters.search);
        }

        this.fetchAPI(url)
            .then(data => {
                this.populateInventoryTable(data.data);
                this.hideLoader();
            })
            .catch(err => this.showError('Failed to load inventory: ' + err.message));
    },

    populateInventoryTable(products) {
        const tbody = document.getElementById('inventory-tbody');
        if (!products || products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">No products found</td></tr>';
            return;
        }

        tbody.innerHTML = products.map(product => `
            <tr>
                <td>${product.SKU}</td>
                <td>${product.Brand}</td>
                <td>${product.Model}</td>
                <td>${product.Size}</td>
                <td>${product.Color}</td>
                <td><strong>${product.Quantity || 0}</strong></td>
                <td>${product.MinStockLevel}</td>
                <td>${product.MaxStockLevel}</td>
                <td>
                    <span class="badge ${this.getStockStatusBadge(product.Quantity, product.MinStockLevel, product.MaxStockLevel)}">
                        ${this.getStockStatus(product.Quantity, product.MinStockLevel, product.MaxStockLevel)}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline" onclick="ERP.editProduct(${product.ProductID})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="ERP.addStock(${product.ProductID})">
                        <i class="fas fa-plus"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    },

    getStockStatus(quantity, min, max) {
        if (quantity <= min) return 'Low Stock';
        if (quantity >= max) return 'Overstock';
        return 'Normal';
    },

    getStockStatusBadge(quantity, min, max) {
        if (quantity <= min) return 'badge-danger';
        if (quantity >= max) return 'badge-warning';
        return 'badge-success';
    },

    editProduct(productId) {
        this.fetchAPI(this.baseURL + '/inventory.php?action=get_product&product_id=' + productId)
            .then(data => {
                const product = data.data.product;
                const modal = document.getElementById('editProductModal');
                modal.querySelector('[name="product_id"]').value = product.ProductID;
                modal.querySelector('[name="brand"]').value = product.Brand;
                modal.querySelector('[name="model"]').value = product.Model;
                modal.querySelector('[name="size"]').value = product.Size;
                modal.querySelector('[name="color"]').value = product.Color;
                modal.querySelector('[name="cost_price"]').value = product.CostPrice;
                modal.querySelector('[name="selling_price"]').value = product.SellingPrice;
                this.openModal(modal);
            });
    },

    addStock(productId) {
        const quantity = prompt('Enter quantity to add:');
        if (quantity === null || !quantity) return;

        this.fetchAPI(this.baseURL + '/inventory.php?action=stock_entry', 'POST', {
            product_id: productId,
            store_id: 1,
            quantity: parseInt(quantity)
        })
        .then(data => {
            this.showSuccess('Stock added successfully');
            this.loadInventoryData();
        })
        .catch(err => this.showError('Failed to add stock: ' + err.message));
    },

    exportInventory() {
        window.location.href = this.baseURL + '/inventory.php?action=export_inventory';
    },

    // =====================================================
    // SALES MODULE
    // =====================================================

    loadSales() {
        this.state.currentModule = 'sales';
        this.showLoader();
        
        const container = document.getElementById('main-content');
        container.innerHTML = `
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Sales Management</h1>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" onclick="ERP.openNewSaleModal()">
                        <i class="fas fa-plus"></i> New Sale
                    </button>
                </div>
            </div>
            
            <ul class="nav-tabs">
                <li><a href="#" class="nav-link active" data-tab="ordersTab">Orders</a></li>
                <li><a href="#" class="nav-link" data-tab="invoicesTab">Invoices</a></li>
                <li><a href="#" class="nav-link" data-tab="returnsTab">Returns</a></li>
            </ul>
            
            <div id="ordersTab" class="tab-pane active">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="sales-table">
                                <thead>
                                    <tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                                </thead>
                                <tbody id="sales-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="invoicesTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="invoices-table">
                                <thead>
                                    <tr><th>Invoice</th><th>Customer</th><th>Amount</th><th>Due Date</th><th>Status</th></tr>
                                </thead>
                                <tbody id="invoices-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="returnsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="returns-table">
                                <thead>
                                    <tr><th>Return ID</th><th>Order ID</th><th>Customer</th><th>Reason</th><th>Amount</th><th>Status</th></tr>
                                </thead>
                                <tbody id="returns-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.loadSalesData();
    },

    loadSalesData() {
        this.fetchAPI(this.baseURL + '/sales.php?action=get_orders&limit=50')
            .then(data => {
                this.populateSalesTable(data.data);
                this.hideLoader();
            })
            .catch(err => this.showError('Failed to load sales: ' + err.message));
    },

    populateSalesTable(orders) {
        const tbody = document.getElementById('sales-tbody');
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No orders found</td></tr>';
            return;
        }

        tbody.innerHTML = orders.map(order => `
            <tr>
                <td>#${order.SaleID}</td>
                <td>${order.CustomerName}</td>
                <td>${this.formatCurrency(order.TotalAmount)}</td>
                <td>${new Date(order.SaleDate).toLocaleDateString()}</td>
                <td><span class="badge badge-info">${order.PaymentStatus}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline" onclick="ERP.viewSaleDetails(${order.SaleID})">View</button>
                </td>
            </tr>
        `).join('');
    },

    viewSaleDetails(saleId) {
        this.fetchAPI(this.baseURL + '/sales.php?action=get_sale_details&sale_id=' + saleId)
            .then(data => {
                const sale = data.data.sale;
                const details = data.data.details;
                
                let detailsHtml = details.map(d => `
                    <tr>
                        <td>${d.ProductID}</td>
                        <td>${d.Quantity}</td>
                        <td>${this.formatCurrency(d.UnitPrice)}</td>
                        <td>${this.formatCurrency(d.Quantity * d.UnitPrice)}</td>
                    </tr>
                `).join('');
                
                alert(`Sale #${saleId}\nTotal: ${this.formatCurrency(sale.TotalAmount)}\n\nDetails:\n${detailsHtml}`);
            });
    },

    openNewSaleModal() {
        this.showAlert('New Sale feature will be implemented with POS interface', 'info');
    },

    // =====================================================
    // PROCUREMENT MODULE
    // =====================================================

    loadProcurement() {
        this.state.currentModule = 'procurement';
        this.showAlert('Procurement module is being loaded...', 'info');
        // Implementation pending
    },

    // =====================================================
    // CUSTOMERS MODULE
    // =====================================================

    loadCustomers() {
        this.state.currentModule = 'customers';
        this.showAlert('Customers module is being loaded...', 'info');
        // Implementation pending
    },

    // =====================================================
    // ACCOUNTING MODULE
    // =====================================================

    loadAccounting() {
        this.state.currentModule = 'accounting';
        this.showAlert('Accounting module is being loaded...', 'info');
        // Implementation pending
    },

    // =====================================================
    // HR MODULE
    // =====================================================

    loadHR() {
        this.state.currentModule = 'hr';
        this.showAlert('HR module is being loaded...', 'info');
        // Implementation pending
    },

    // =====================================================
    // UTILITIES
    // =====================================================

    fetchAPI(url, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        return fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'API request failed');
                }
                return data;
            });
    },

    handleFormSubmit(form) {
        const action = form.getAttribute('data-action');
        const method = form.getAttribute('data-method');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        let url = this.baseURL + `/${action}.php?action=${method}`;

        this.fetchAPI(url, 'POST', data)
            .then(response => {
                this.showSuccess(response.message);
                this.closeAllModals();
                
                if (action === 'inventory') {
                    this.loadInventoryData();
                } else if (action === 'sales') {
                    this.loadSalesData();
                }
            })
            .catch(err => this.showError('Error: ' + err.message));
    },

    switchTab(tabName) {
        document.querySelectorAll('.tab-pane').forEach(pane => pane.style.display = 'none');
        document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
        
        const tab = document.getElementById(tabName);
        if (tab) tab.style.display = 'block';
        
        event.target.classList.add('active');
    },

    sortTable(column) {
        this.state.sortBy = column;
        this.state.sortOrder = this.state.sortOrder === 'asc' ? 'desc' : 'asc';
        // Implement sorting based on module
    },

    performSearch(query) {
        // Implement global search
        console.log('Searching for:', query);
    },

    openModal(modalId) {
        const modal = typeof modalId === 'string' ? 
            document.getElementById(modalId) : modalId;
        if (modal) {
            modal.classList.add('show');
            document.querySelector('.modal-backdrop')?.classList.add('show');
        }
    },

    closeModal(modal) {
        if (modal) {
            modal.classList.remove('show');
        }
    },

    closeAllModals() {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.remove('show');
        });
    },

    populateTable(tableId, data) {
        const tbody = document.getElementById(tableId)?.querySelector('tbody');
        if (!tbody) return;
        
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center">No data available</td></tr>';
            return;
        }

        tbody.innerHTML = data.map((row, idx) => `
            <tr>
                ${Object.values(row).map(val => `<td>${val}</td>`).join('')}
            </tr>
        `).join('');
    },

    showLoader() {
        const loader = document.createElement('div');
        loader.className = 'loader-overlay';
        loader.id = 'app-loader';
        loader.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loader);
    },

    hideLoader() {
        document.getElementById('app-loader')?.remove();
    },

    showAlert(message, type = 'info', duration = 3000) {
        const container = document.querySelector('.alert-container') || document.body;
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            ${message}
            <button class="alert-close">&times;</button>
        `;
        container.appendChild(alert);

        if (duration) {
            setTimeout(() => alert.remove(), duration);
        }
    },

    showSuccess(message) {
        this.showAlert(message, 'success', 3000);
    },

    showError(message) {
        this.showAlert(message, 'danger', 5000);
    },

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount || 0);
    },

    debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    ERP.init();
});
