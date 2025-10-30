<?php
require_once('../../api/crm.php');

// Assuming you already have the user ID from session
$userId = $_SESSION['user_id'] ?? null;

$userInitials = '';

// Fetch user's first name from the database
if ($userId) {
    $stmt = $db->prepare("SELECT FirstName FROM users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['FirstName'])) {
        // Extract initials (first two letters, uppercase)
        $userInitials = strtoupper(substr($user['FirstName'], 0, 2));
    } else {
        $userInitials = 'NA'; // fallback if no name found
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profiles - CRM System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./styles/crmGlobalStyles.css">
    <style>
        /* Additional custom styles for customer-specific elements */
        .contact-info-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 13px;
            color: var(--gray-600);
        }

        .loyalty-points {
            font-weight: 700;
            color: var(--warning);
            font-size: 14px;
        }

        .tier-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .tier-platinum {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #4338ca;
        }

        .tier-gold {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .tier-silver {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #4b5563;
        }

        .tier-bronze {
            background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
            color: #9a3412;
        }

        .type-vip {
            background: #fce7f3;
            color: #9f1239;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .type-wholesale {
            background: #ddd6fe;
            color: #5b21b6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .type-retail {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            color: #10b981;
            font-weight: 600;
        }

        .status-inactive {
            color: #ef4444;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            background: #f3f4f6;
            color: #4b5563;
        }

        .action-btn.delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        .notification {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 500;
        }

        .notification.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .notification.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .filters-form {
            display: contents;
        }

        .filter-submit {
            align-self: end;
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .filter-submit:hover {
            background: #2563eb;
        }

        /* Modal positioning */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal {
            transform: scale(1);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: between;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin: 0;
            flex: 1;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6b7280;
            padding: 4px;
        }

        .close-btn:hover {
            color: #374151;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-input,
        .form-select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .customer-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 14px;
            color: #111827;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-inner">
            <div class="brand">
                <div class="brand-icon">C</div>
                <span>CRM Shoe Retail</span>
            </div>
            <ul class="nav-menu">
                <li><a href="./CrmDashboard.php">Dashboard</a></li>
                <li><a href="./customerProfile.php" class="active">Customer Profiles</a></li>
                <li><a href="./loyaltyProgram.php">Loyalty Program</a></li>
                <li><a href="./customerSupport.php">Customer Support</a></li>
                <li><a href="./reportsManagement.php">Reports & Analytics</a></li>
            </ul>
            <div class="nav-right">
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-box" placeholder="Search customers...">
                </div>
                <button class="notification-btn">
                    🔔
                    <span class="notification-badge">5</span>
                </button>
                <a href="./crmProfile.php">
                    <div class="user-avatar"><?php echo htmlspecialchars($userInitials); ?></div>
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="header-content">
                <div class="breadcrumb">
                    <span>Home</span>
                    <span class="breadcrumb-separator">/</span>
                    <span>Customer Management</span>
                    <span class="breadcrumb-separator">/</span>
                    <span>Customer Profiles</span>
                </div>
                <h1 class="page-title">Customer Profiles</h1>
                <p class="page-subtitle">Manage customer data, loyalty points, and purchase history</p>
            </div>
            <div class="header-actions">
                <a href="?export=1" class="btn btn-secondary">
                    <span>📊</span>
                    <span>Export</span>
                </a>
                <button class="btn btn-primary" onclick="openModal('addCustomerModal')">
                    <span>+</span>
                    <span>Add Customer</span>
                </button>
            </div>
        </div>

        <!-- Display notifications -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="notification success">
                <?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="notification error">
                <?php echo $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <?php
            $stats = $stats_dynamic;

            foreach ($stats as $stat) {
                echo '<div class="stat-card">';
                echo '<div class="stat-header">';
                echo '<div class="stat-icon" style="background: ' . $stat['color'] . ';">' . $stat['icon'] . '</div>';
                echo '<div class="stat-trend ' . $stat['trend_dir'] . '">';
                echo '<span>' . ($stat['trend_dir'] === 'up' ? '↑' : '↓') . '</span>';
                echo '<span>' . $stat['trend'] . '</span>';
                echo '</div>';
                echo '</div>';
                echo '<div class="stat-value">' . $stat['value'] . '</div>';
                echo '<div class="stat-label">' . $stat['label'] . '</div>';
                echo '<div class="stat-sublabel">' . $stat['sublabel'] . '</div>';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-header">
                <div class="filters-title">🔍 Filter Customers</div>
                <a href="customerProfile.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">Reset Filters</a>
            </div>
            <form method="GET" class="filters-form">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Customer Type</label>
                        <select class="filter-select" name="type">
                            <option value="all" <?php echo $filters['type'] === 'all' ? 'selected' : ''; ?>>All Customers</option>
                            <option value="Retail" <?php echo $filters['type'] === 'Retail' ? 'selected' : ''; ?>>Retail</option>
                            <option value="VIP" <?php echo $filters['type'] === 'VIP' ? 'selected' : ''; ?>>VIP</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Loyalty Tier</label>
                        <select class="filter-select" name="tier">
                            <option value="all" <?php echo $filters['tier'] === 'all' ? 'selected' : ''; ?>>All Tiers</option>
                            <option value="Bronze" <?php echo $filters['tier'] === 'Bronze' ? 'selected' : ''; ?>>Bronze (0-99 pts)</option>
                            <option value="Silver" <?php echo $filters['tier'] === 'Silver' ? 'selected' : ''; ?>>Silver (100-499 pts)</option>
                            <option value="Gold" <?php echo $filters['tier'] === 'Gold' ? 'selected' : ''; ?>>Gold (500-999 pts)</option>
                            <option value="Platinum" <?php echo $filters['tier'] === 'Platinum' ? 'selected' : ''; ?>>Platinum (1000+ pts)</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-select" name="status">
                            <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Active" <?php echo $filters['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $filters['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Registration Date</label>
                        <select class="filter-select" name="date_range">
                            <option value="all" <?php echo $filters['date_range'] === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="this_month" <?php echo $filters['date_range'] === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                            <option value="last_3_months" <?php echo $filters['date_range'] === 'last_3_months' ? 'selected' : ''; ?>>Last 3 Months</option>
                            <option value="last_6_months" <?php echo $filters['date_range'] === 'last_6_months' ? 'selected' : ''; ?>>Last 6 Months</option>
                            <option value="this_year" <?php echo $filters['date_range'] === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="filter-submit">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Customer Table -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="section-title">All Customers (<?php echo number_format($totalCustomers) ?>)</h2>
                <div class="card-actions">
                    <button class="icon-btn" title="Filter">🔽</button>
                    <button class="icon-btn" title="Sort">⇅</button>
                    <button class="icon-btn" title="Settings">⚙️</button>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>Customer ID</th>
                            <th>Member Number</th>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>Customer Type</th>
                            <th>Loyalty Points</th>
                            <th>Tier</th>
                            <th>Total Purchases</th>
                            <th>Last Purchase</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // $customers array is prepared by backend queries at the top of the file
                        foreach ($customers as $customer) {
                            // Determine tier badge class
                            $tierClass = match ($customer['tier']) {
                                'Platinum' => 'tier-platinum',
                                'Gold' => 'tier-gold',
                                'Silver' => 'tier-silver',
                                default => 'tier-bronze'
                            };

                            // Determine type badge class
                            $typeClass = match ($customer['type']) {
                                'VIP' => 'type-vip',
                                'Wholesale' => 'type-wholesale',
                                default => 'type-retail'
                            };

                            $statusClass = $customer['status'] === 'Active' ? 'status-active' : 'status-inactive';

                            echo '<tr>';
                            echo '<td><input type="checkbox" class="row-checkbox"></td>';
                            echo '<td><span class="contact-id">' . $customer['display_id'] . '</span></td>';
                            echo '<td>' . $customer['member_number'] . '</td>';
                            echo '<td>';
                            echo '<div class="contact-name-cell">';
                            echo '<div class="contact-avatar">' . $customer['initials'] . '</div>';
                            echo '<div class="contact-name-info">';
                            echo '<div class="contact-name-primary">' . $customer['name'] . '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</td>';
                            echo '<td>';
                            echo '<div class="contact-info-details">';
                            echo '<div>📧 ' . $customer['email'] . '</div>';
                            echo '<div>📱 ' . $customer['phone'] . '</div>';
                            echo '</div>';
                            echo '</td>';
                            echo '<td><span class="type-badge ' . $typeClass . '">' . strtoupper($customer['type']) . '</span></td>';
                            echo '<td><span class="loyalty-points">' . number_format($customer['points']) . ' pts</span></td>';
                            echo '<td><span class="tier-badge ' . $tierClass . '">' . $customer['tier'] . '</span></td>';
                            echo '<td><span class="value-cell">' . $customer['total_purchases'] . '</span></td>';
                            echo '<td>' . $customer['last_purchase'] . '</td>';
                            echo '<td><span class="' . $statusClass . '">● ' . $customer['status'] . '</span></td>';
                            echo '<td>';
                            echo '<div class="action-buttons">';
                            echo '<button class="action-btn" onclick="viewCustomer(' . $customer['id'] . ')">View</button>';
                            echo '<button class="action-btn" onclick="editCustomer(' . $customer['id'] . ')">Edit</button>';
                            echo '<button class="action-btn delete" onclick="deleteCustomer(' . $customer['id'] . ', \'' . $customer['name'] . '\')">Delete</button>';
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <?php
                $start = $offset + 1;
                $end = min($offset + $perPage, $totalCustomers);
                ?>
                <div class="showing-text">
                    Showing <strong><?php echo $start ?>-<?php echo $end ?></strong> of <strong><?php echo number_format($totalCustomers) ?></strong> customers
                </div>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn">‹</a>
                    <?php endif; ?>

                    <?php
                    $maxPagesToShow = 7;
                    $startPage = max(1, min($page - floor($maxPagesToShow / 2), max(1, $totalPages - $maxPagesToShow + 1)));
                    $endPage = min($startPage + $maxPagesToShow - 1, $totalPages);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="btn <?php echo ($i == $page ? 'active' : '') ?>"><?php echo $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn">›</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal-overlay" id="addCustomerModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add New Customer</h3>
                <button class="close-btn" onclick="closeModal('addCustomerModal')">✕</button>
            </div>
            <div class="modal-body">
                <form id="customerForm" method="POST">
                    <input type="hidden" name="action" value="add_customer">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-input" name="first_name" placeholder="Enter first name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-input" name="last_name" placeholder="Enter last name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-input" name="email" placeholder="customer@email.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone *</label>
                            <input type="tel" class="form-input" name="phone" placeholder="+63 XXX XXX XXXX" required>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-input" name="address" placeholder="Street address">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Initial Loyalty Points</label>
                            <input type="number" class="form-input" name="loyalty_points" placeholder="0" value="0" min="0">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addCustomerModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('customerForm').submit()">Save Customer</button>
            </div>
        </div>
    </div>

    <!-- View Customer Modal -->
    <div class="modal-overlay" id="viewCustomerModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Customer Details</h3>
                <button class="close-btn" onclick="closeModal('viewCustomerModal')">✕</button>
            </div>
            <div class="modal-body">
                <div id="customerDetails">
                    <!-- Customer details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('viewCustomerModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal-overlay" id="editCustomerModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Customer</h3>
                <button class="close-btn" onclick="closeModal('editCustomerModal')">✕</button>
            </div>
            <div class="modal-body">
                <form id="editCustomerForm" method="POST">
                    <input type="hidden" name="action" value="update_customer">
                    <input type="hidden" name="customer_id" id="editCustomerId">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-input" id="editFirstName" name="first_name" placeholder="Enter first name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-input" id="editLastName" name="last_name" placeholder="Enter last name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-input" id="editEmail" name="email" placeholder="customer@email.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone *</label>
                            <input type="tel" class="form-input" id="editPhone" name="phone" placeholder="+63 XXX XXX XXXX" required>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-input" id="editAddress" name="address" placeholder="Street address">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Loyalty Points</label>
                            <input type="number" class="form-input" id="editLoyaltyPoints" name="loyalty_points" placeholder="0" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('editCustomerModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('editCustomerForm').submit()">Update Customer</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="close-btn" onclick="closeModal('deleteModal')">✕</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete customer <span id="deleteCustomerName"></span>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_customer">
                    <input type="hidden" name="customer_id" id="deleteCustomerId">
                    <button class="btn btn-danger" type="submit">Delete Customer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Customer data from PHP
        const customers = <?php echo json_encode($customers); ?>;

        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function viewCustomer(customerId) {
            const customer = customers.find(c => c.id === customerId);
            if (customer) {
                const detailsHtml = `
                    <div class="customer-details">
                        <div class="detail-group">
                            <div class="detail-label">Customer ID</div>
                            <div class="detail-value">${customer.display_id}</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Member Number</div>
                            <div class="detail-value">${customer.member_number}</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Name</div>
                            <div class="detail-value">${customer.name}</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">${customer.email}</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value">${customer.phone}</div>
                        </div>
                        <div class="detail-group full-width">
                            <div class="detail-label">Address</div>
                            <div class="detail-value">${customer.address || 'Not provided'}</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Customer Type</div>
                            <div class="detail-value">${customer.type}</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Loyalty Points</div>
                            <div class="detail-value">${customer.points.toLocaleString()} pts</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Tier</div>
                            <div class="detail-value">${customer.tier}</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Total Purchases</div>
                            <div class="detail-value">${customer.total_purchases}</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Last Purchase</div>
                            <div class="detail-value">${customer.last_purchase}</div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">${customer.status}</div>
                        </div>
                    </div>
                `;
                document.getElementById('customerDetails').innerHTML = detailsHtml;
                openModal('viewCustomerModal');
            }
        }

        function editCustomer(customerId) {
            const customer = customers.find(c => c.id === customerId);
            if (customer) {
                document.getElementById('editCustomerId').value = customer.id;
                document.getElementById('editFirstName').value = customer.first_name;
                document.getElementById('editLastName').value = customer.last_name;
                document.getElementById('editEmail').value = customer.email;
                document.getElementById('editPhone').value = customer.phone;
                document.getElementById('editAddress').value = customer.address || '';
                document.getElementById('editLoyaltyPoints').value = customer.points;
                document.getElementById('editStatus').value = customer.status;
                openModal('editCustomerModal');
            }
        }

        function deleteCustomer(customerId, customerName) {
            document.getElementById('deleteCustomerName').textContent = customerName;
            document.getElementById('deleteCustomerId').value = customerId;
            openModal('deleteModal');
        }

        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Search functionality
        const searchBox = document.querySelector('.search-box');
        searchBox.addEventListener('input', function(e) {
            console.log('Searching for:', e.target.value);
            // Implement search logic here
        });

        // Select all checkbox
        const selectAll = document.getElementById('select-all');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }

        // Filter change listeners
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', function() {
                console.log('Filter changed:', this.value);
                // The form will submit when the Apply Filters button is clicked
            });
        });

        // Table row clicks
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.addEventListener('click', function(e) {
                if (!e.target.closest('button') && !e.target.closest('input[type="checkbox"]')) {
                    const customerId = this.querySelector('.contact-id').textContent.replace('#CUST-', '');
                    viewCustomer(parseInt(customerId));
                }
            });
        });
    </script>
</body>

</html>