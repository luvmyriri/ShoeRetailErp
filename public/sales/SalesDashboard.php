<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Role-based access control
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'Sales', 'Cashier', 'Customer Service'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../includes/core_functions.php';

$activeTab = $_GET['tab'] ?? 'sales';
$userId = $_SESSION['user_id'] ?? null;
$storeId = $_SESSION['store_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['process_sale'])) {
        try {
            $customerId = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
            $storeIdPost = !empty($_POST['store_id']) ? intval($_POST['store_id']) : $storeId;
            $paymentMethod = $_POST['payment_method'] ?? 'Cash';
            $discountAmount = floatval($_POST['discount_amount'] ?? 0);
            
            // Parse sale items from JSON
            $saleItemsJson = $_POST['sale_items_json'] ?? '[]';
            $saleItems = json_decode($saleItemsJson, true);
            
            if (!is_array($saleItems) || empty($saleItems)) {
                throw new Exception('Sale must contain at least one product');
            }
            
            // Process sale using core function (handles inventory, GL, AR automatically)
            $saleId = processSale($customerId, $storeIdPost, $saleItems, $paymentMethod, $discountAmount);
            
            $_SESSION['success_message'] = 'Sale processed successfully! Sale ID: #' . $saleId;
            logInfo('Sale processed via dashboard', ['sale_id' => $saleId, 'user_id' => $userId]);
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error processing sale: ' . $e->getMessage();
            logError('Sale processing failed', ['error' => $e->getMessage(), 'user_id' => $userId]);
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    
    if (isset($_POST['process_return'])) {
        try {
            $saleId = intval($_POST['sale_id']);
            $returnItemsJson = $_POST['return_items_json'] ?? '[]';
            $returnItems = json_decode($returnItemsJson, true);
            $reason = $_POST['reason'] ?? 'Customer Request';
            
            if (!is_array($returnItems) || empty($returnItems)) {
                throw new Exception('Return must contain at least one item');
            }
            
            // Process return using core function (handles inventory restoration, GL entries)
            $refundAmount = processReturn($saleId, $returnItems, $reason);
            
            $_SESSION['success_message'] = 'Return processed successfully! Refund: $' . number_format($refundAmount, 2);
            logInfo('Return processed via dashboard', ['sale_id' => $saleId, 'refund' => $refundAmount]);
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error processing return: ' . $e->getMessage();
            logError('Return processing failed', ['error' => $e->getMessage()]);
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

// Handle CSV export
if (isset($_GET['action']) && $_GET['action'] === 'export_sales') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_data_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Sale ID', 'Date', 'Customer', 'Store', 'Total Amount', 'Payment Method', 'Status']);
    
    try {
        $query = "SELECT s.SaleID, s.SaleDate, CONCAT(c.FirstName, ' ', COALESCE(c.LastName, '')) as CustomerName, 
                         st.StoreName, s.TotalAmount, s.PaymentMethod, s.PaymentStatus
                  FROM sales s
                  LEFT JOIN customers c ON s.CustomerID = c.CustomerID
                  LEFT JOIN stores st ON s.StoreID = st.StoreID
                  ORDER BY s.SaleDate DESC";
        $rows = dbFetchAll($query);
        foreach ($rows as $row) {
            fputcsv($output, [
                'SALE-' . $row['SaleID'],
                $row['SaleDate'],
                $row['CustomerName'] ?? 'Walk-in',
                $row['StoreName'] ?? 'N/A',
                number_format($row['TotalAmount'], 2),
                $row['PaymentMethod'],
                $row['PaymentStatus'] ?? 'Completed'
            ]);
        }
    } catch (Exception $e) {
        fputcsv($output, ['Error', $e->getMessage()]);
    }
    fclose($output);
    exit;
}

// Handle AJAX for fetching sale details
if (isset($_GET['action']) && $_GET['action'] === 'get_sale') {
    header('Content-Type: application/json');
    try {
        $saleId = $_GET['sale_id'] ?? '';
        $sale = dbFetchOne("SELECT s.*, CONCAT(c.FirstName, ' ', COALESCE(c.LastName, '')) as CustomerName, 
                                     st.StoreName
                              FROM sales s
                              LEFT JOIN customers c ON s.CustomerID = c.CustomerID
                              LEFT JOIN stores st ON s.StoreID = st.StoreID
                              WHERE s.SaleID = ?", [$saleId]);
        
        if ($sale) {
            $details = dbFetchAll("SELECT sd.*, p.Model as ProductName, p.SKU, p.ProductID
                                   FROM saledetails sd
                                   JOIN products p ON sd.ProductID = p.ProductID
                                   WHERE sd.SaleID = ?", [$saleId]);
            echo json_encode(['success' => true, 'data' => ['sale' => $sale, 'details' => $details]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sale not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Dashboard stats class
class SalesDashboardStats {
    public function getTodaySales() {
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(TotalAmount), 0) as total 
                FROM sales 
                WHERE DATE(SaleDate) = CURDATE()";
        $result = dbFetchOne($sql);
        $count = (int)($result['count'] ?? 0);
        $total = (float)($result['total'] ?? 0);
        return ['count' => $count, 'total' => number_format($total, 2)];
    }
    
    public function getMonthSales() {
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(TotalAmount), 0) as total 
                FROM sales 
                WHERE MONTH(SaleDate) = MONTH(CURDATE()) AND YEAR(SaleDate) = YEAR(CURDATE())";
        $result = dbFetchOne($sql);
        $count = (int)($result['count'] ?? 0);
        $total = (float)($result['total'] ?? 0);
        return ['count' => $count, 'total' => number_format($total, 2)];
    }
    
    public function getPendingReturns() {
        $sql = "SELECT COUNT(*) as total FROM returns WHERE Status = 'Pending'";
        $result = dbFetchOne($sql);
        return (int)($result['total'] ?? 0);
    }
}

$stats = new SalesDashboardStats();
$todayStats = $stats->getTodaySales();
$monthStats = $stats->getMonthSales();
$pendingReturns = $stats->getPendingReturns();

// Fetch recent sales
$recentSales = [];
try {
    $query = "SELECT s.SaleID, s.SaleDate, s.TotalAmount, s.PaymentMethod, s.PaymentStatus,
                     CONCAT(c.FirstName, ' ', COALESCE(c.LastName, '')) as CustomerName,
                     st.StoreName
              FROM sales s
              LEFT JOIN customers c ON s.CustomerID = c.CustomerID
              LEFT JOIN stores st ON s.StoreID = st.StoreID
              ORDER BY s.SaleDate DESC
              LIMIT 50";
    $recentSales = dbFetchAll($query);
} catch (Exception $e) {
    logError('Failed to fetch recent sales', ['error' => $e->getMessage()]);
}

// Fetch customers for dropdown
$customers = [];
try {
    $customers = dbFetchAll("SELECT CustomerID, FirstName, LastName, MemberNumber FROM customers ORDER BY FirstName, LastName");
} catch (Exception $e) {
    logError('Failed to fetch customers', ['error' => $e->getMessage()]);
}

// Fetch stores for dropdown
$stores = [];
try {
    $stores = dbFetchAll("SELECT StoreID, StoreName FROM stores WHERE Status = 'Active' ORDER BY StoreName");
} catch (Exception $e) {
    logError('Failed to fetch stores', ['error' => $e->getMessage()]);
}

// Fetch products for sale
$products = [];
try {
    $products = dbFetchAll("SELECT p.ProductID, p.SKU, p.Model, p.Brand, p.SellingPrice, i.Quantity
                            FROM products p
                            LEFT JOIN inventory i ON p.ProductID = i.ProductID
                            WHERE p.Status = 'Active'
                            ORDER BY p.Brand, p.Model");
} catch (Exception $e) {
    logError('Failed to fetch products', ['error' => $e->getMessage()]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - Sandals Retail ERP</title>
    <?php include '../includes/head.php'; ?>
    <link rel="stylesheet" href="sales-integration.css">
    <style>
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-icon { font-size: 32px; margin-bottom: 0.5rem; }
        .stat-value { font-size: 24px; font-weight: 600; color: #333; }
        .stat-label { font-size: 12px; color: #999; margin-top: 0.5rem; }
        .stat-trend { font-size: 12px; color: #27AE60; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; font-size: 13px; margin-bottom: 0.5rem; }
        .form-group input, .form-group select, .form-group textarea { padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-refunded { background: #fee2e2; color: #991b1b; }
        .btn-group { display: flex; gap: 0.5rem; }
        .tabs { display: flex; gap: 1rem; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem; }
        .tab { background: none; border: none; padding: 0.75rem 1rem; cursor: pointer; font-weight: 500; color: #666; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab.active { color: #714B67; border-bottom-color: #714B67; }
        
        /* Modal styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-title { margin: 0; font-size: 18px; font-weight: 600; }
        .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; padding: 0; }
        .modal-footer { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee; display: flex; gap: 1rem; justify-content: flex-end; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="main-wrapper" style="margin-left: 0;">
    <main class="main-content">
        <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Sales Management</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / Sales
                    </div>
                    <p style="margin: 0.5rem 0 0 0; font-size: 14px; color: #666;">
                        <i class="fas fa-info-circle"></i> Men's & Women's Sandals
                    </p>
                </div>
                <div class="page-header-actions">
                <a class="btn btn-primary btn-sm" href="pos.php">
                    <i class="fas fa-cash-register"></i> POS
                </a>
                <button class="btn btn-outline" onclick="window.location.href='?action=export_sales'">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" style="padding: 1rem; margin: 1rem; border-radius: 4px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;">
                <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error" style="padding: 1rem; margin: 1rem; border-radius: 4px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="row" style="margin-bottom: 2rem;">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">₱<?php echo $todayStats['total']; ?></div>
                    <div class="stat-label">Today's Sales</div>
                    <div class="stat-trend"><?php echo $todayStats['count']; ?> transactions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value">₱<?php echo $monthStats['total']; ?></div>
                    <div class="stat-label">This Month</div>
                    <div class="stat-trend"><?php echo $monthStats['count']; ?> transactions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">↩️</div>
                    <div class="stat-value"><?php echo $pendingReturns; ?></div>
                    <div class="stat-label">Pending Returns</div>
                    <div class="stat-trend">Requires attention</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo count($customers); ?></div>
                    <div class="stat-label">Total Customers</div>
                    <div class="stat-trend">Active base</div>
                </div>
            </div>
        </div>

        <!-- Recent Sales Table -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Recent Sales</h3>
                <input type="text" id="searchInput" placeholder="Search sales..." 
                       style="padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Store</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td>SALE-<?php echo htmlspecialchars($sale['SaleID']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($sale['SaleDate']))); ?></td>
                                <td><?php echo htmlspecialchars($sale['CustomerName'] ?? 'Walk-in'); ?></td>
                                <td><?php echo htmlspecialchars($sale['StoreName'] ?? 'N/A'); ?></td>
                                <td>₱<?php echo number_format($sale['TotalAmount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($sale['PaymentMethod']); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = 'status-' . strtolower($sale['PaymentStatus'] ?? 'paid');
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($sale['PaymentStatus'] ?? 'Completed'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-info" onclick="viewSale(<?php echo $sale['SaleID']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-secondary" onclick="window.open('print_invoice.php?sale_id=<?php echo $sale['SaleID']; ?>','_blank')">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline" onclick="window.open('print_invoice.php?sale_id=<?php echo $sale['SaleID']; ?>&download=1','_blank')">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <?php if (($sale['PaymentStatus'] ?? '') === 'Credit' || ($sale['PaymentStatus'] ?? '') === 'Partial'): ?>
                                        <button class="btn btn-sm btn-success" onclick="paySale(<?php echo $sale['SaleID']; ?>)">
                                            <i class="fas fa-money-bill"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-warning" onclick="processReturnForSale(<?php echo $sale['SaleID']; ?>)">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>


<!-- View Sale Modal -->
<div id="viewSaleModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Sale Details</h3>
            <button class="close-btn" onclick="closeModal('viewSaleModal')">&times;</button>
        </div>
        <div id="saleDetailsContent">
            <p style="text-align: center; color: #666;">Loading...</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('viewSaleModal')">Close</button>
        </div>
    </div>
</div>

<script>
// Modal management
let currentOpenModal = null;
let saleItems = [];

function openModal(modalId) {
    if (currentOpenModal) closeModal(currentOpenModal);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        currentOpenModal = modalId;
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        if (currentOpenModal === modalId) currentOpenModal = null;
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});


// View sale details
async function viewSale(saleId) {
    try {
        const response = await fetch(`?action=get_sale&sale_id=${saleId}`);
        const result = await response.json();
        
        if (result.success) {
            const sale = result.data.sale;
            const details = result.data.details;
            
            let html = `
                <div style="padding: 1rem;">
                    <div class="form-grid">
                        <div><strong>Sale ID:</strong> SALE-${sale.SaleID}</div>
                        <div><strong>Date:</strong> ${sale.SaleDate}</div>
                        <div><strong>Customer:</strong> ${sale.CustomerName || 'Walk-in'}</div>
                        <div><strong>Store:</strong> ${sale.StoreName}</div>
                        <div><strong>Payment:</strong> ${sale.PaymentMethod}</div>
                        <div><strong>Status:</strong> ${sale.PaymentStatus}</div>
                    </div>
                    <hr style="margin: 1rem 0;">
                    <h4 style="margin-bottom: 0.5rem;">Items:</h4>
                    <table class="table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            details.forEach(item => {
                html += `
                    <tr>
                        <td>${item.ProductName}</td>
                        <td>${item.SKU}</td>
                        <td>${item.Quantity}</td>
                        <td>₱${parseFloat(item.UnitPrice).toFixed(2)}</td>
                        <td>₱${parseFloat(item.Subtotal).toFixed(2)}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                    <div style="text-align: right; font-size: 18px; font-weight: 600; margin-top: 1rem;">
                        Total: ₱${parseFloat(sale.TotalAmount).toFixed(2)}
                    </div>
                </div>
            `;
            
            document.getElementById('saleDetailsContent').innerHTML = html;
            openModal('viewSaleModal');
        } else {
            alert('Failed to load sale details');
        }
    } catch (error) {
        console.error('Error fetching sale:', error);
        alert('Error loading sale details');
    }
}

async function processReturnForSale(saleId) { openReturnModalForSale(saleId); }

// Return modal (line-item partial returns)
let currentReturnSale = null;
async function openReturnModalForSale(saleId) {
    currentReturnSale = saleId;
    // Load items for the sale
    const res = await fetch(`?action=get_sale&sale_id=${saleId}`);
    const data = await res.json();
    if (!data.success) { alert('Failed to load sale items'); return; }
    const details = data.data.details || [];
    const tbody = document.getElementById('returnItemsTbody');
    tbody.innerHTML = details.map(it => {
        const maxQty = Number(it.Quantity || 0);
        return `
            <tr>
              <td>${it.SKU}</td>
              <td>${it.ProductName}</td>
              <td>${maxQty}</td>
              <td>
                <input type="number" class="form-control return-qty" data-product-id="${it.ProductID}" data-max="${maxQty}" value="0" min="0" max="${maxQty}">
              </td>
              <td>₱${Number(it.UnitPrice).toFixed(2)}</td>
            </tr>
        `;
    }).join('');
    document.getElementById('returnReasonInput').value = 'Customer Return';
    const modal = document.getElementById('returnLineModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeReturnModal() {
    const modal = document.getElementById('returnLineModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    currentReturnSale = null;
}

async function submitPartialReturn() {
    if (!currentReturnSale) return;
    const reason = document.getElementById('returnReasonInput').value || 'Customer Return';
    const items = [];
    document.querySelectorAll('#returnItemsTbody .return-qty').forEach(inp => {
        const qty = parseFloat(inp.value) || 0;
        const max = parseFloat(inp.dataset.max) || 0;
        if (qty > 0) {
            items.push({ product_id: parseInt(inp.dataset.productId, 10), quantity: Math.min(qty, max) });
        }
    });
    if (items.length === 0) { alert('Enter at least one quantity to return'); return; }
    try {
        const res = await fetch('returns_api.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
            body: JSON.stringify({ sale_id: currentReturnSale, reason, items })
        });
        const d = await res.json();
        if (!d.success) throw new Error(d.data?.message || 'Failed');
        alert('Return processed. Refund: ₱' + Number(d.data.refund || 0).toLocaleString());
        closeReturnModal();
        location.reload();
    } catch (e) {
        alert('Return error: ' + e.message);
    }
}
function paySale(saleId) {
    const amt = prompt('Enter payment amount for Sale #' + saleId);
    if (amt === null) return;
    const method = prompt('Payment method (Cash/Card/Transfer)?','Cash') || 'Cash';
    fetch('ar_payment_api.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
        body: JSON.stringify({ sale_id: saleId, amount: parseFloat(amt), method })
    }).then(r=>r.json()).then(d => {
        if (!d.success) throw new Error(d.data?.message || 'Failed');
        alert('Payment recorded');
        location.reload();
    }).catch(e => alert('Payment error: ' + e.message));
}
</script>

<!-- Partial Return Modal (inventory-style) -->
<style>
#returnLineModal { position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); z-index:10000; display:none; align-items:center; justify-content:center; }
#returnLineModal.active { display:flex; }
#returnLineModal .modal-box { background:#fff; width: 90%; max-width: 800px; border-radius:8px; box-shadow:0 10px 40px rgba(0,0,0,0.2); overflow:hidden; }
#returnLineModal .modal-header { padding:1rem; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
#returnLineModal .modal-body { padding:1rem; max-height:60vh; overflow:auto; }
#returnLineModal .modal-footer { padding:1rem; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:.5rem; }
</style>
<div id="returnLineModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 style="margin:0;">Process Return</h3>
      <button class="btn btn-outline" onclick="closeReturnModal()">×</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group full-width">
          <label>Reason</label>
          <input type="text" id="returnReasonInput" class="form-control" value="Customer Return">
        </div>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>SKU</th><th>Product</th><th>Sold Qty</th><th>Return Qty</th><th>Unit Price</th></tr>
          </thead>
          <tbody id="returnItemsTbody"></tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeReturnModal()">Cancel</button>
      <button class="btn btn-primary" onclick="submitPartialReturn()">Submit Return</button>
    </div>
  </div>
</div>
</body>
</html>
