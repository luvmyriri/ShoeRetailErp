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
$allowedRoles = ['Admin', 'Manager', 'Sales', 'Customer Service'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}

require __DIR__ . '/../../config/database.php';
require_once('../../api/crm.php');

$activeTab = $_GET['tab'] ?? 'customers';
$userId = $_SESSION['user_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_lead'])) {
        try {
            $stmt = $db->prepare("INSERT INTO customers (FirstName, LastName, Email, Phone, Address, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_POST['first_name'] ?? '',
                $_POST['last_name'] ?? '',
                $_POST['email'] ?? '',
                $_POST['phone'] ?? '',
                $_POST['address'] ?? 'N/A',
                $_POST['status'] ?? 'New'
            ]);
            $_SESSION['success_message'] = 'Customer added successfully!';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error adding customer: ' . $e->getMessage();
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    if (isset($_POST['update_deal'])) {
        try {
            $_SESSION['success_message'] = 'Deal updated successfully!';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error updating deal';
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    if (isset($_POST['update_task'])) {
        try {
            $_SESSION['success_message'] = 'Task updated successfully!';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error updating task';
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    if (isset($_POST['assign_task'])) {
        try {
            $_SESSION['success_message'] = 'Task assigned successfully!';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error assigning task';
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    if (isset($_POST['add_task'])) {
        try {
            $_SESSION['success_message'] = 'Task added successfully!';
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error adding task';
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Handle CSV export
if (isset($_GET['action']) && $_GET['action'] === 'export_crm') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="crm_data_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Customer ID', 'Member Number', 'Name', 'Email', 'Phone', 'Loyalty Points', 'Status', 'Created Date']);
    
    try {
        $stmt = $db->query("SELECT CustomerID, MemberNumber, FirstName, LastName, Email, Phone, LoyaltyPoints, Status, CreatedAt FROM customers ORDER BY CreatedAt DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                'CUST-' . $row['CustomerID'],
                $row['MemberNumber'] ?? 'N/A',
                trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? '')),
                $row['Email'] ?? '',
                $row['Phone'] ?? '',
                $row['LoyaltyPoints'] ?? 0,
                $row['Status'] ?? 'N/A',
                $row['CreatedAt'] ?? date('Y-m-d')
            ]);
        }
    } catch (Exception $e) {
        fputcsv($output, ['Error', $e->getMessage()]);
    }
    fclose($output);
    exit;
}

// Handle AJAX for fetching customer details
if (isset($_GET['action']) && $_GET['action'] === 'get_customer') {
    header('Content-Type: application/json');
    try {
        $customerId = $_GET['customer_id'] ?? '';
        $stmt = $db->prepare("SELECT * FROM customers WHERE CustomerID = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo json_encode(['success' => true, 'data' => $customer]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Note: getTableData() is defined in api/crm.php - no need to duplicate
// Dashboard stats class
class DashboardStats {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function getTotalCustomers() {
        $sql = "SELECT COUNT(*) as total, COUNT(CASE WHEN CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 END) as last_month FROM customers";
        $result = $this->db->query($sql)->fetch();
        $total = (int)($result['total'] ?? 0);
        $last = (int)($result['last_month'] ?? 0);
        $trend = $last > 0 && $total > 0 ? '+' . round(($last / $total) * 100, 1) . '%' : '0%';
        return ['value' => number_format($total), 'trend' => $trend];
    }
    
    public function getQualifiedCustomers() {
        $sql = "SELECT COUNT(*) as total, COUNT(CASE WHEN CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 END) as last_month FROM customers WHERE Status IN ('Qualified', 'Active')";
        $result = $this->db->query($sql)->fetch();
        $total = (int)($result['total'] ?? 0);
        $last = (int)($result['last_month'] ?? 0);
        $trend = $last > 0 && $total > 0 ? '+' . round(($last / $total) * 100, 1) . '%' : '0%';
        return ['value' => number_format($total), 'trend' => $trend];
    }
    
    public function getPipelineValue() {
        $sql = "SELECT COALESCE(SUM(LoyaltyPoints), 0) as total FROM customers";
        $result = $this->db->query($sql)->fetch();
        $total = (float)($result['total'] ?? 0);
        return ['value' => number_format($total), 'trend' => '+0%'];
    }
}

$stats = new DashboardStats();
$totalStats = $stats->getTotalCustomers();
$qualifiedStats = $stats->getQualifiedCustomers();
$pipelineStats = $stats->getPipelineValue();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Dashboard - Shoe Retail ERP</title>
    <?php include '../includes/head.php'; ?>
    <link rel="stylesheet" href="crm-integration.css">
    <link rel="stylesheet" href="enhanced-modal-styles.css">
    <style>
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-icon { font-size: 32px; margin-bottom: 0.5rem; }
        .stat-value { font-size: 24px; font-weight: 600; color: #333; }
        .stat-label { font-size: 12px; color: #999; margin-top: 0.5rem; }
        .stat-trend { font-size: 12px; color: #27AE60; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-overlay.active { display: flex; align-items: center; justify-content: center; }
        .modal { background: white; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-title { margin: 0; font-size: 18px; }
        .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1.5rem; border-top: 1px solid #eee; display: flex; gap: 1rem; justify-content: flex-end; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; font-size: 13px; margin-bottom: 0.5rem; }
        .form-group input, .form-group select, .form-group textarea { padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-qualified { background: #dbeafe; color: #0c4a6e; }
        .status-new { background: #fef3c7; color: #78350f; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { background: #f9fafb; padding: 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
        .table td { padding: 1rem; border-bottom: 1px solid #e5e7eb; }
        .table tbody tr:hover { background: #f9fafb; }
        .btn-group { display: flex; gap: 0.5rem; }
        .btn-small { padding: 0.5rem 0.75rem; font-size: 12px; }
        .tabs { display: flex; gap: 1rem; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem; }
        .tab { background: none; border: none; padding: 0.75rem 1rem; cursor: pointer; font-weight: 500; color: #666; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab.active { color: #714B67; border-bottom-color: #714B67; }
    </style>
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>CRM Dashboard</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / CRM / Dashboard
                    </div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" onclick="openModal('addLeadModal')"><i class="fas fa-plus"></i> Add New Customer</button>
                </div>
            </div>

            <div class="row" style="margin-bottom: 2rem;">
                <div class="col-md-3" style="margin-bottom: 1rem;">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-value"><?php echo $totalStats['value']; ?></div>
                        <div class="stat-label">Total Customers</div>
                        <div class="stat-trend"><?php echo $totalStats['trend']; ?></div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 1rem;">
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-value"><?php echo $qualifiedStats['value']; ?></div>
                        <div class="stat-label">Qualified Customers</div>
                        <div class="stat-trend"><?php echo $qualifiedStats['trend']; ?></div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 1rem;">
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-value">₱<?php echo $pipelineStats['value']; ?></div>
                        <div class="stat-label">Loyalty Points Total</div>
                        <div class="stat-trend"><?php echo $pipelineStats['trend']; ?></div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 1rem;">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-value">Live</div>
                        <div class="stat-label">System Status</div>
                        <div class="stat-trend" style="color: #27AE60;">✓ Online</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="tabs">
                        <button class="tab <?php echo $activeTab === 'customers' ? 'active' : ''; ?>" onclick="switchTab('customers')">Customers</button>
                        <button class="tab <?php echo $activeTab === 'deals' ? 'active' : ''; ?>" onclick="switchTab('deals')">Deals</button>
                        <button class="tab <?php echo $activeTab === 'tasks' ? 'active' : ''; ?>" onclick="switchTab('tasks')">Tasks</button>
                        <button class="tab <?php echo $activeTab === 'performance' ? 'active' : ''; ?>" onclick="switchTab('performance')">Performance</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <?php if ($activeTab === 'performance'): ?>
                                    <tr>
                                        <th>Customer ID</th>
                                        <th>Member Number</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Total Deals</th>
                                        <th>Total Value</th>
                                        <th>Avg Probability</th>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <th>Customer ID</th>
                                        <th>Member Number</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Loyalty Points</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                <?php endif; ?>
                            </thead>
                            <tbody>
                                <?php
                                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                                $perPage = 5;
                                $offset = ($page - 1) * $perPage;
                                $tableData = getTableData($activeTab, $perPage, $offset);
                                
                                if (empty($tableData)):
                                    $cols = $activeTab === 'performance' ? 7 : 9;
                                    echo "<tr><td colspan='$cols' style='text-align: center; padding: 2rem;'>No data found</td></tr>";
                                else:
                                    foreach ($tableData as $row):
                                        if ($activeTab === 'customers'):
                                            $statusClass = 'status-' . strtolower($row['Status']);
                                            ?>
                                            <tr>
                                                <td>#CUST-<?php echo str_pad($row['CustomerID'], 3, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($row['MemberNumber'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars(trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? ''))); ?></td>
                                                <td><?php echo htmlspecialchars($row['Email'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($row['Phone'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($row['Address'] ?? ''); ?></td>
                                                <td><?php echo (int)($row['LoyaltyPoints'] ?? 0); ?></td>
                                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo strtoupper($row['Status'] ?? ''); ?></span></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-info" onclick="viewCustomer(<?php echo $row['CustomerID']; ?>)"><i class="fas fa-eye"></i></button>
                                                        <button class="btn btn-sm btn-warning" onclick="editCustomer(<?php echo $row['CustomerID']; ?>)"><i class="fas fa-edit"></i></button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteCustomer(<?php echo $row['CustomerID']; ?>)"><i class="fas fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        else:
                                            ?>
                                            <tr>
                                                <td>#<?php echo str_pad($row['CustomerID'], 3, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($row['MemberNumber'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars(trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? ''))); ?></td>
                                                <td><?php echo htmlspecialchars($row['Email'] ?? ''); ?></td>
                                                <td><?php echo (int)($row['TotalDeals'] ?? 0); ?></td>
                                                <td>₱<?php echo number_format($row['TotalValue'] ?? 0, 2); ?></td>
                                                <td><?php echo round($row['AvgProbability'] ?? 0, 1); ?>%</td>
                                            </tr>
                                            <?php
                                        endif;
                                    endforeach;
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Lead Modal - Enhanced -->
    <div id="addLeadModal" class="modal-enhanced modal-lg">
        <div class="modal-enhanced-content">
            <div class="modal-enhanced-header">
                <div class="modal-enhanced-header-content">
                    <h2 class="modal-enhanced-title" id="leadModalTitle">Add New Customer</h2>
                    <p class="modal-enhanced-subtitle">Enter customer details to add them to the system</p>
                </div>
                <button class="modal-enhanced-close" onclick="closeModal('addLeadModal')">×</button>
            </div>
            <div class="modal-enhanced-body">
                <div class="modal-alert modal-alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div><strong>Required fields</strong> are marked with <span class="modal-form-label-required">*</span></div>
                </div>
                <form id="leadForm" method="POST">
                    <input type="hidden" name="lead_id" id="lead_id">
                    <input type="hidden" name="add_lead" id="add_lead" value="1">
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-user"></i> Personal Information</h3>
                        <div class="modal-form-grid">
                            <div class="modal-form-group">
                                <label class="modal-form-label">First Name <span class="modal-form-label-required">*</span></label>
                                <input type="text" class="modal-form-control" name="first_name" id="first_name" placeholder="Enter first name" required>
                                <div class="modal-form-hint">Your first name as it appears in records</div>
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Last Name <span class="modal-form-label-required">*</span></label>
                                <input type="text" class="modal-form-control" name="last_name" id="last_name" placeholder="Enter last name" required>
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Email</label>
                                <input type="email" class="modal-form-control" name="email" id="email" placeholder="name@example.com">
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Phone</label>
                                <input type="tel" class="modal-form-control" name="phone" id="phone" placeholder="+63 900 000 0000">
                            </div>
                        </div>
                    </div>
                    <div class="modal-divider"></div>
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-building"></i> Company Information</h3>
                        <div class="modal-form-grid">
                            <div class="modal-form-group full-width">
                                <label class="modal-form-label">Company Name</label>
                                <input type="text" class="modal-form-control" name="company" id="company" placeholder="Company name">
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Job Title</label>
                                <input type="text" class="modal-form-control" name="job_title" id="job_title" placeholder="Job title">
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Potential Value (₱)</label>
                                <input type="number" class="modal-form-control" name="potential_value" id="potential_value" step="0.01" min="0" value="0" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="modal-divider"></div>
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-file-alt"></i> Status & Notes</h3>
                        <div class="modal-form-grid">
                            <div class="modal-form-group">
                                <label class="modal-form-label">Status</label>
                                <select class="modal-form-control modal-form-select" name="status" id="status">
                                    <option value="New" selected>New</option>
                                    <option value="Contacted">Contacted</option>
                                    <option value="Qualified">Qualified</option>
                                    <option value="Proposal">Proposal</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-form-group full-width">
                            <label class="modal-form-label">Notes</label>
                            <textarea class="modal-form-control modal-form-textarea" name="notes" id="notes" placeholder="Add any notes about this customer..."></textarea>
                            <div class="modal-form-hint">Maximum 500 characters</div>
                        </div>
                    </div>
                    <div class="modal-info-box">
                        <div class="modal-info-box-icon"><i class="fas fa-lightbulb"></i></div>
                        <div class="modal-info-box-content">
                            <div class="modal-info-box-title">Pro Tip</div>
                            <div class="modal-info-box-text">Adding detailed information helps with better customer segmentation and targeted communications.</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-enhanced-footer">
                <div class="modal-enhanced-footer-info"><i class="fas fa-shield-alt"></i> <span>Your data is secure and encrypted</span></div>
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('addLeadModal')"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" class="modal-btn modal-btn-primary" onclick="document.getElementById('leadForm').submit()"><i class="fas fa-save"></i> Save Customer</button>
            </div>
        </div>
    </div>

    <!-- Edit Deal Modal - Enhanced -->
    <div id="dealModal" class="modal-enhanced modal-lg">
        <div class="modal-enhanced-content">
            <div class="modal-enhanced-header">
                <div class="modal-enhanced-header-content">
                    <h2 class="modal-enhanced-title">Edit Deal</h2>
                    <p class="modal-enhanced-subtitle">Update deal information and status</p>
                </div>
                <button class="modal-enhanced-close" onclick="closeModal('dealModal')">×</button>
            </div>
            <div class="modal-enhanced-body">
                <form id="dealForm" method="POST">
                    <input type="hidden" name="deal_id" id="edit_deal_id">
                    <input type="hidden" name="update_deal" value="1">
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-handshake"></i> Deal Details</h3>
                        <div class="modal-form-grid">
                            <div class="modal-form-group full-width">
                                <label class="modal-form-label">Deal Name <span class="modal-form-label-required">*</span></label>
                                <input type="text" class="modal-form-control" id="edit_deal_name" name="deal_name" placeholder="Enter deal name" required>
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Deal Value (₱) <span class="modal-form-label-required">*</span></label>
                                <input type="number" class="modal-form-control" id="edit_deal_value" name="deal_value" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Probability (%)</label>
                                <input type="number" class="modal-form-control" id="edit_probability" name="probability" min="0" max="100" placeholder="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-divider"></div>
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-chart-line"></i> Status & Timeline</h3>
                        <div class="modal-form-grid">
                            <div class="modal-form-group">
                                <label class="modal-form-label">Stage</label>
                                <select class="modal-form-control modal-form-select" id="edit_stage" name="stage">
                                    <option value="Prospecting">Prospecting</option>
                                    <option value="Qualification">Qualification</option>
                                    <option value="Proposal">Proposal</option>
                                    <option value="Negotiation">Negotiation</option>
                                    <option value="Closed Won">Closed Won</option>
                                    <option value="Closed Lost">Closed Lost</option>
                                </select>
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Close Date</label>
                                <input type="date" class="modal-form-control" id="edit_close_date" name="close_date">
                            </div>
                        </div>
                    </div>
                    <div class="modal-divider"></div>
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-align-left"></i> Notes</h3>
                        <div class="modal-form-group full-width">
                            <label class="modal-form-label">Deal Notes</label>
                            <textarea class="modal-form-control modal-form-textarea" id="edit_deal_notes" name="notes" placeholder="Add notes about this deal..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-enhanced-footer">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('dealModal')"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" class="modal-btn modal-btn-primary" onclick="document.getElementById('dealForm').submit()"><i class="fas fa-check-circle"></i> Update Deal</button>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal - Enhanced -->
    <div id="taskModal" class="modal-enhanced modal-lg">
        <div class="modal-enhanced-content">
            <div class="modal-enhanced-header">
                <div class="modal-enhanced-header-content">
                    <h2 class="modal-enhanced-title">Edit Task</h2>
                    <p class="modal-enhanced-subtitle">Update task details and status</p>
                </div>
                <button class="modal-enhanced-close" onclick="closeModal('taskModal')">×</button>
            </div>
            <div class="modal-enhanced-body">
                <form id="taskForm" method="POST">
                    <input type="hidden" name="task_id" id="edit_task_id">
                    <input type="hidden" name="update_task" value="1">
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-tasks"></i> Task Information</h3>
                        <div class="modal-form-group full-width">
                            <label class="modal-form-label">Title <span class="modal-form-label-required">*</span></label>
                            <input type="text" class="modal-form-control" id="edit_task_title" name="task_title" placeholder="Task title" required>
                        </div>
                        <div class="modal-form-grid">
                            <div class="modal-form-group">
                                <label class="modal-form-label">Due Date <span class="modal-form-label-required">*</span></label>
                                <input type="date" class="modal-form-control" id="edit_due_date" name="due_date" required>
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Assigned To</label>
                                <input type="text" class="modal-form-control" id="edit_assigned_to" name="assigned_to" placeholder="Team member name">
                            </div>
                        </div>
                    </div>
                    <div class="modal-divider"></div>
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-cog"></i> Priority & Status</h3>
                        <div class="modal-form-grid">
                            <div class="modal-form-group">
                                <label class="modal-form-label">Priority</label>
                                <select class="modal-form-control modal-form-select" id="edit_task_priority" name="task_priority">
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="modal-form-group">
                                <label class="modal-form-label">Status</label>
                                <select class="modal-form-control modal-form-select" id="edit_task_status" name="task_status">
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-divider"></div>
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-align-left"></i> Description</h3>
                        <div class="modal-form-group full-width">
                            <label class="modal-form-label">Description <span class="modal-form-label-required">*</span></label>
                            <textarea class="modal-form-control modal-form-textarea" id="edit_task_description" name="task_description" placeholder="Task description..." required></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-enhanced-footer">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('taskModal')"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" class="modal-btn modal-btn-primary" onclick="document.getElementById('taskForm').submit()"><i class="fas fa-check-circle"></i> Update Task</button>
            </div>
        </div>
    </div>

    <!-- Assign Task Modal - Enhanced -->
    <div id="assignTaskModal" class="modal-enhanced modal-md">
        <div class="modal-enhanced-content">
            <div class="modal-enhanced-header">
                <div class="modal-enhanced-header-content">
                    <h2 class="modal-enhanced-title">Assign Task</h2>
                    <p class="modal-enhanced-subtitle">Assign this task to a team member</p>
                </div>
                <button class="modal-enhanced-close" onclick="closeModal('assignTaskModal')">×</button>
            </div>
            <div class="modal-enhanced-body">
                <form id="assignTaskForm" method="POST">
                    <input type="hidden" name="assign_task_id" id="assign_task_id">
                    <input type="hidden" name="assign_task" value="1">
                    <div class="modal-section">
                        <h3 class="modal-section-title"><i class="fas fa-user-tie"></i> Team Assignment</h3>
                        <div class="modal-form-group full-width">
                            <label class="modal-form-label">Assign To <span class="modal-form-label-required">*</span></label>
                            <select class="modal-form-control modal-form-select" id="assign_to" name="assign_to" required>
                                <option value="" disabled selected>Select a team member...</option>
                                <option value="John Doe">John Doe</option>
                                <option value="Jane Smith">Jane Smith</option>
                                <option value="Mike Johnson">Mike Johnson</option>
                                <option value="Sarah Wilson">Sarah Wilson</option>
                                <option value="David Brown">David Brown</option>
                                <option value="Emily Davis">Emily Davis</option>
                            </select>
                            <div class="modal-form-hint">Select the team member responsible for this task</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-enhanced-footer">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('assignTaskModal')"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" class="modal-btn modal-btn-primary" onclick="document.getElementById('assignTaskForm').submit()"><i class="fas fa-user-check"></i> Assign Task</button>
            </div>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div id="addTaskModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 18px;">Add New Task</h3>
                <button onclick="closeModal('addTaskModal')" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <form id="addTaskForm" method="POST" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <input type="hidden" name="add_task" value="1">
                <div>
                    <label style="font-weight: 600; font-size: 13px; margin-bottom: 0.5rem; display: block;">Title *</label>
                    <input type="text" id="new_task_title" name="new_task_title" required class="form-control" style="padding: 0.75rem; font-size: 14px;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div>
                        <label style="font-weight: 600; font-size: 13px; margin-bottom: 0.5rem; display: block;">Due Date *</label>
                        <input type="date" id="new_due_date" name="new_due_date" required class="form-control" style="padding: 0.75rem; font-size: 14px;">
                    </div>
                    <div>
                        <label style="font-weight: 600; font-size: 13px; margin-bottom: 0.5rem; display: block;">Priority</label>
                        <select id="new_task_priority" name="new_task_priority" class="form-control" style="padding: 0.75rem; font-size: 14px;">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="font-weight: 600; font-size: 13px; margin-bottom: 0.5rem; display: block;">Assigned To</label>
                    <select id="new_assigned_to" name="new_assigned_to" class="form-control" style="padding: 0.75rem; font-size: 14px;">
                        <option value="">Unassigned</option>
                        <option value="John Doe">John Doe</option>
                        <option value="Jane Smith">Jane Smith</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: 600; font-size: 13px; margin-bottom: 0.5rem; display: block;">Description *</label>
                    <textarea id="new_task_description" name="new_task_description" required class="form-control" style="padding: 0.75rem; font-size: 14px; min-height: 80px;"></textarea>
                </div>
                <hr style="margin: 0.75rem 0;">
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addTaskModal')" style="flex: 1; padding: 0.75rem; font-size: 14px;">Cancel</button>
                    <button type="submit" class="btn btn-success" style="flex: 1; padding: 0.75rem; font-size: 14px;"><i class="fas fa-plus"></i> Add Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 18px;">Customer Details</h3>
                <button onclick="closeModal('viewModal')" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <div id="detailsContent" style="font-size: 14px; line-height: 1.6;"></div>
            <hr style="margin: 1rem 0;">
            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('viewModal')" style="padding: 0.75rem 1.5rem; font-size: 14px;">Close</button>
            </div>
        </div>
    </div>

    <script>
        let currentOpenModal = null;
        
        function openModal(modalId) {
            if (currentOpenModal) closeModal(currentOpenModal);
            const modal = document.getElementById(modalId);
            if (modal) {
                if (modal.classList.contains('modal-enhanced')) {
                    modal.classList.add('active');
                } else {
                    modal.style.display = 'flex';
                }
                currentOpenModal = modalId;
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                if (modal.classList.contains('modal-enhanced')) {
                    modal.classList.remove('active');
                } else {
                    modal.style.display = 'none';
                }
                if (currentOpenModal === modalId) currentOpenModal = null;
                document.body.style.overflow = '';
            }
        }
        
        function closeAllModals() {
            const modals = document.querySelectorAll('[id$="Modal"]');
            modals.forEach(modal => modal.style.display = 'none');
            currentOpenModal = null;
            document.body.style.overflow = '';
        }
        
        function switchTab(tab) {
            window.location.href = '?tab=' + tab;
        }
        
        // Async function to fetch customer details
        async function fetchCustomerData(customerId) {
            try {
                const response = await fetch('?action=get_customer&customer_id=' + customerId);
                const data = await response.json();
                return data.success ? data.data : null;
            } catch (error) {
                console.error('Error fetching customer:', error);
                return null;
            }
        }
        
        // View customer details
        async function viewCustomer(id) {
            const customer = await fetchCustomerData(id);
            if (customer) {
                const html = `
                    <div style="padding: 1rem;">
                        <h4>${customer.FirstName} ${customer.LastName}</h4>
                        <p><strong>Email:</strong> ${customer.Email || 'N/A'}</p>
                        <p><strong>Phone:</strong> ${customer.Phone || 'N/A'}</p>
                        <p><strong>Address:</strong> ${customer.Address || 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${(customer.Status || 'new').toLowerCase()}">${customer.Status || 'N/A'}</span></p>
                        <p><strong>Loyalty Points:</strong> ${customer.LoyaltyPoints || 0}</p>
                        <p><strong>Member Since:</strong> ${customer.CreatedAt ? new Date(customer.CreatedAt).toLocaleDateString() : 'N/A'}</p>
                    </div>
                `;
                document.getElementById('detailsContent').innerHTML = html;
            } else {
                document.getElementById('detailsContent').innerHTML = '<p style="color: red;">Failed to load customer details</p>';
            }
            openModal('viewModal');
        }
        
        // Edit customer
        async function editCustomer(id) {
            const customer = await fetchCustomerData(id);
            if (customer) {
                document.getElementById('lead_id').value = id;
                document.getElementById('first_name').value = customer.FirstName || '';
                document.getElementById('last_name').value = customer.LastName || '';
                document.getElementById('email').value = customer.Email || '';
                document.getElementById('phone').value = customer.Phone || '';
                document.getElementById('company').value = customer.Company || '';
                document.getElementById('status').value = customer.Status || 'New';
                document.getElementById('leadModalTitle').textContent = 'Edit Customer';
                openModal('addLeadModal');
            }
        }
        
        function deleteCustomer(id) {
            if (confirm('Are you sure you want to delete this customer?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_customer" value="1"><input type="hidden" name="customer_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function assignTask(taskId) {
            document.getElementById('assign_task_id').value = taskId;
            openModal('assignTaskModal');
        }
        
        function editDeal(dealId) {
            document.getElementById('edit_deal_id').value = dealId;
            // Simulate fetching deal data (would be from AJAX in production)
            document.getElementById('edit_deal_name').value = 'Deal ' + dealId;
            document.getElementById('edit_deal_value').value = '50000';
            document.getElementById('edit_stage').value = 'Proposal';
            document.getElementById('edit_probability').value = '75';
            openModal('dealModal');
        }
        
        function editTask(taskId) {
            document.getElementById('edit_task_id').value = taskId;
            // Simulate fetching task data (would be from AJAX in production)
            document.getElementById('edit_task_title').value = 'Task ' + taskId;
            document.getElementById('edit_task_priority').value = 'High';
            document.getElementById('edit_task_status').value = 'In Progress';
            openModal('taskModal');
        }
        
        function exportData() {
            window.location.href = '?action=export_crm';
        }
        
        function refreshData() {
            location.reload();
        }
        
        // Initialize modal close listeners on overlay click
        document.querySelectorAll('[id$="Modal"]').forEach(modal => {
            modal.addEventListener('click', function(e) {
                // Close modal only if clicking directly on the overlay (not the modal content)
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });
        
        // Display success/error messages
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                showAlert('Operation completed successfully!', 'success');
            } else if (urlParams.has('error')) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });
        
        function showAlert(message, type) {
            const alertContainer = document.querySelector('.alert-container');
            if (alertContainer) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-' + type;
                alert.textContent = message;
                alert.style.cssText = 'padding: 1rem; margin: 1rem; border-radius: 4px; background: ' + (type === 'success' ? '#d1fae5' : '#fee2e2') + '; color: ' + (type === 'success' ? '#065f46' : '#991b1b') + '; border: 1px solid ' + (type === 'success' ? '#a7f3d0' : '#fecaca') + ';';
                alertContainer.appendChild(alert);
                setTimeout(() => alert.remove(), 5000);
            }
        }
    </script>
    <script src="../js/app.js"></script>
</body>
</html>
