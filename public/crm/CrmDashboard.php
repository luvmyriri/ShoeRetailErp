<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$activeTab = $_GET['tab'] ?? 'customers';

// dashboard/index.php
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
    <title>CRM Dashboard - Enterprise Edition</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./styles/crmGlobalStyles.css">
</head>

<body>
    <nav class="navbar">
        <div class="navbar-inner">
            <div class="brand">
                <div class="brand-icon">C</div>
                <span>CRM Shoe Retail</span>
            </div>
            <ul class="nav-menu">
                <li><a href="#" class="active">Dashboard</a></li>
                <li><a href="./customerProfile.php">Customer Profiles</a></li>
                <li><a href="./loyaltyProgram.php">Loyalty Program</a></li>
                <li><a href="./customerSupport.php">Customer Support</a></li>
                <li><a href="./reportsManagement.php">Reports & Analytics</a></li>
            </ul>
            <div class="nav-right">
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-box" placeholder="Search anything..." id="globalSearch">
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
                    <span>Dashboard</span>
                </div>
                <h1 class="page-title">CRM Dashboard</h1>
                <p class="page-subtitle">Track and manage your customer relationships in real-time</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="exportData()">
                    <span>📊</span>
                    <span>Export</span>
                </button>
                <button class="btn btn-primary" onclick="openModal('addCustomerModal')">
                    <span>+</span>
                    <span>Add New Customer</span>
                </button>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php
                switch ($_GET['success']) {
                    case 'customer_added':
                        echo 'Customer added successfully!';
                        break;
                    case 'customer_updated':
                        echo 'Customer updated successfully!';
                        break;
                    case 'customer_deleted':
                        echo 'Customer deleted successfully!';
                        break;
                    case 'deal_updated':
                        echo 'Deal updated successfully!';
                        break;
                    case 'task_updated':
                        echo 'Task updated successfully!';
                        break;
                    case 'task_assigned':
                        echo 'Task assigned successfully!';
                        break;
                    case 'task_added':
                        echo 'Task added successfully!';
                        break;
                    case 'task_deleted':
                        echo 'Task deleted successfully!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <?php
            class DashboardStats
            {
                private $db;
                public function __construct()
                {
                    global $db;
                    $this->db = $db;
                }

                public function getTotalCustomers()
                {
                    $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 END) as last_month
                        FROM customers";
                    $result = $this->db->query($sql)->fetch();
                    $total = (int)($result['total'] ?? 0);
                    $last = (int)($result['last_month'] ?? 0);
                    $trend = $last > 0 && $total > 0 ? '+' . round(($last / $total) * 100, 1) . '%' : '0%';
                    return ['value' => number_format($total), 'trend' => $trend];
                }

                public function getQualifiedCustomers()
                {
                    $sql = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 END) as last_month
            FROM customers
            WHERE Status IN ('Qualified', 'Active')";

                    $result = $this->db->query($sql)->fetch();
                    $total = (int)($result['total'] ?? 0);
                    $last = (int)($result['last_month'] ?? 0);
                    $trend = $last > 0 && $total > 0 ? '+' . round(($last / $total) * 100, 1) . '%' : '0%';

                    return [
                        'value' => number_format($total),
                        'trend' => $trend
                    ];
                }


                public function getPipelineValue()
                {
                    $sql = "SELECT 
                COALESCE(SUM(LoyaltyPoints), 0) as total
            FROM customers";
                    $result = $this->db->query($sql)->fetch();
                    $total = (float)($result['total'] ?? 0);
                    return ['value' => number_format($total), 'trend' => '+0%'];
                }
            }

            $stats = new DashboardStats();
            $total = $stats->getTotalCustomers();
            $qualified = $stats->getQualifiedCustomers();
            $value = $stats->getPipelineValue();

            $statCards = [
                ['icon' => '👥', 'label' => 'Total Customers', 'sublabel' => 'Active in CRM', 'data' => $total, 'color' => '#dbeafe'],
                ['icon' => '✅', 'label' => 'Qualified Customers', 'sublabel' => 'Ready for deals', 'data' => $qualified, 'color' => '#d1fae5'],
                ['icon' => '💰', 'label' => 'Pipeline Value', 'sublabel' => 'Potential revenue', 'data' => $value, 'color' => '#fef3c7']
            ];

            foreach ($statCards as $s) {
                $dir = strpos($s['data']['trend'], '+') !== false ? 'up' : 'down';
                echo '<div class="stat-card">';
                echo '<div class="stat-header">';
                echo '<div class="stat-icon" style="background:' . $s['color'] . ';">' . $s['icon'] . '</div>';
                echo '<div class="stat-trend ' . $dir . '"><span>' . ($dir === 'up' ? '↑' : '↓') . '</span><span>' . $s['data']['trend'] . '</span></div>';
                echo '</div>';
                echo '<div class="stat-body">';
                echo '<div class="stat-value">' . $s['data']['value'] . '</div>';
                echo '<div class="stat-label">' . $s['label'] . '</div>';
                echo '<div class="stat-sublabel">' . $s['sublabel'] . '</div>';
                echo '</div></div>';
            }
            ?>
        </div>

        <div class="tabs-wrapper">
            <div class="tabs">
                <button class="tab <?php echo $activeTab === 'customers' ? 'active' : ''; ?>"
                    onclick="window.location.href='?tab=customers'">Customers</button>
                <button class="tab <?php echo $activeTab === 'performance' ? 'active' : ''; ?>"
                    onclick="window.location.href='?tab=performance'">Performance</button>
            </div>
        </div>


        <div class="content-card">
            <div class="card-header">
                <h2 class="section-title">
                    <?php
                    echo match ($activeTab) {
                        'performance' => 'Performance Overview',
                        default => 'Customers Overview'
                    };
                    ?>
                </h2>
            </div>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <?php
                        $activeTab = $_GET['tab'] ?? 'customers';

                        switch ($activeTab) {
                            case 'performance':
                                echo '<tr>
                <th>Customer ID</th>
                <th>Member Number</th>
                <th>Name</th>
                <th>Email</th>
                <th>Total Deals</th>
                <th>Total Value</th>
                <th>Average Probability</th>
              </tr>';
                                break;

                            default: // customers
                                echo '<tr>
                <th>Customer ID</th>
                <th>Member Number</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Loyalty Points</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>';
                                break;
                        }
                        ?>
                    </thead>
                    <tbody>
                        <?php
                        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                        $perPage = 5;
                        $offset = ($page - 1) * $perPage;

                        $activeTab = $_GET['tab'] ?? 'customers';
                        $tableData = getTableData($activeTab, $perPage, $offset);

                        if (empty($tableData)) {
                            echo '<tr><td colspan="7" style="text-align:center; padding:20px;">No data found</td></tr>';
                        } else {
                            foreach ($tableData as $row) {
                                if ($activeTab === 'customers') {
                                    echo '<tr>';
                                    echo '<td>#CUST-' . str_pad($row['CustomerID'], 3, '0', STR_PAD_LEFT) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['MemberNumber']) . '</td>';
                                    echo '<td>' . htmlspecialchars(trim($row['FirstName'] . ' ' . $row['LastName'])) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['Email']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['Phone']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['Address']) . '</td>';
                                    echo '<td>' . (int)$row['LoyaltyPoints'] . '</td>';
                                    echo '<td><span class="status-badge status-' . strtolower($row['Status']) . '">' . strtoupper($row['Status']) . '</span></td>';
                                    echo '<td>
                    <div class="action-buttons">
                        <button class="view-btn" onclick="viewCustomer(' . $row['CustomerID'] . ')">View</button>
                        <button class="edit-btn" onclick="editCustomer(' . $row['CustomerID'] . ')">Edit</button>
                        <button class="delete-btn" onclick="deleteCustomer(' . $row['CustomerID'] . ')">Delete</button>
                    </div>
                  </td>';
                                    echo '</tr>';
                                } elseif ($activeTab === 'performance') {
                                    echo '<tr>';
                                    echo '<td>#' . str_pad($row['CustomerID'], 3, '0', STR_PAD_LEFT) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['MemberNumber']) . '</td>';
                                    echo '<td>' . htmlspecialchars(trim($row['FirstName'] . ' ' . $row['LastName'])) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['Email']) . '</td>';
                                    echo '<td>' . (int)$row['TotalDeals'] . '</td>';
                                    echo '<td>₱' . number_format($row['TotalValue'], 2) . '</td>';
                                    echo '<td>' . round($row['AvgProbability'], 1) . '%</td>';
                                    echo '</tr>';
                                }
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Lead Modal -->
    <div class="modal-overlay" id="addLeadModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="leadModalTitle">Add New Lead</h3>
                <button class="close-btn" onclick="closeModal('addLeadModal')">✕</button>
            </div>
            <div class="modal-body">
                <form id="leadForm" method="POST">
                    <input type="hidden" name="lead_id" id="lead_id">
                    <input type="hidden" name="add_lead" id="add_lead" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone">
                        </div>

                        <div class="form-group">
                            <label for="company">Company</label>
                            <input type="text" id="company" name="company">
                        </div>

                        <div class="form-group">
                            <label for="job_title">Job Title</label>
                            <input type="text" id="job_title" name="job_title">
                        </div>

                        <div class="form-group full-width">
                            <label for="deal_name">Deal Name (Optional)</label>
                            <input type="text" id="deal_name" name="deal_name" placeholder="e.g., Enterprise Software License">
                        </div>

                        <div class="form-group">
                            <label for="potential_value">Potential Value (₱)</label>
                            <input type="number" id="potential_value" name="potential_value" step="0.01" min="0" value="0">
                        </div>

                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                <option value="Low">Low</option>
                                <option value="Normal" selected>Normal</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="New" selected>New</option>
                                <option value="Contacted">Contacted</option>
                                <option value="Qualified">Qualified</option>
                                <option value="Proposal">Proposal</option>
                                <option value="Negotiation">Negotiation</option>
                                <option value="Won">Won</option>
                                <option value="Lost">Lost</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="source">Source</label>
                            <select id="source" name="source">
                                <option value="Website">Website</option>
                                <option value="Referral">Referral</option>
                                <option value="Social Media">Social Media</option>
                                <option value="Cold Call">Cold Call</option>
                                <option value="Event">Event</option>
                                <option value="Other" selected>Other</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="4" placeholder="Add any notes about this lead..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addLeadModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('leadForm').submit()">Save Lead</button>
            </div>
        </div>
    </div>

    <!-- Edit Deal Modal -->
    <div class="modal-overlay" id="dealModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Deal</h3>
                <button class="close-btn" onclick="closeModal('dealModal')">✕</button>
            </div>
            <div class="modal-body">
                <form id="dealForm" method="POST">
                    <input type="hidden" name="deal_id" id="edit_deal_id">
                    <input type="hidden" name="update_deal" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_deal_name">Deal Name</label>
                            <input type="text" id="edit_deal_name" name="deal_name" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_deal_value">Deal Value (₱)</label>
                            <input type="number" id="edit_deal_value" name="deal_value" step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_stage">Stage</label>
                            <select id="edit_stage" name="stage">
                                <option value="Prospecting">Prospecting</option>
                                <option value="Qualification">Qualification</option>
                                <option value="Proposal">Proposal</option>
                                <option value="Negotiation">Negotiation</option>
                                <option value="Closed Won">Closed Won</option>
                                <option value="Closed Lost">Closed Lost</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_probability">Probability (%)</label>
                            <input type="number" id="edit_probability" name="probability" min="0" max="100" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_close_date">Close Date</label>
                            <input type="date" id="edit_close_date" name="close_date" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="edit_deal_notes">Notes</label>
                            <textarea id="edit_deal_notes" name="notes" rows="4"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('dealModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('dealForm').submit()">Update Deal</button>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div class="modal-overlay" id="taskModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Task</h3>
                <button class="close-btn" onclick="closeModal('taskModal')">✕</button>
            </div>
            <div class="modal-body">
                <form id="taskForm" method="POST">
                    <input type="hidden" name="task_id" id="edit_task_id">
                    <input type="hidden" name="update_task" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_task_title">Title</label>
                            <input type="text" id="edit_task_title" name="task_title" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="edit_task_description">Description</label>
                            <textarea id="edit_task_description" name="task_description" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="edit_due_date">Due Date</label>
                            <input type="date" id="edit_due_date" name="due_date" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_task_priority">Priority</label>
                            <select id="edit_task_priority" name="task_priority">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_task_status">Status</label>
                            <select id="edit_task_status" name="task_status">
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_assigned_to">Assigned To</label>
                            <input type="text" id="edit_assigned_to" name="assigned_to" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('taskModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('taskForm').submit()">Update Task</button>
            </div>
        </div>
    </div>

    <!-- Assign Task Modal -->
    <div class="modal-overlay" id="assignTaskModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Assign Task</h3>
                <button class="close-btn" onclick="closeModal('assignTaskModal')">✕</button>
            </div>
            <div class="modal-body">
                <form id="assignTaskForm" method="POST">
                    <input type="hidden" name="assign_task_id" id="assign_task_id">
                    <input type="hidden" name="assign_task" value="1">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="assign_to">Assign To</label>
                            <select id="assign_to" name="assign_to" required>
                                <option value="">Select Team Member</option>
                                <option value="John Doe">John Doe</option>
                                <option value="Jane Smith">Jane Smith</option>
                                <option value="Mike Johnson">Mike Johnson</option>
                                <option value="Sarah Wilson">Sarah Wilson</option>
                                <option value="David Brown">David Brown</option>
                                <option value="Emily Davis">Emily Davis</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('assignTaskModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('assignTaskForm').submit()">Assign Task</button>
            </div>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div class="modal-overlay" id="addTaskModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add New Task</h3>
                <button class="close-btn" onclick="closeModal('addTaskModal')">✕</button>
            </div>
            <div class="modal-body">
                <form id="addTaskForm" method="POST">
                    <input type="hidden" name="add_task" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="new_task_title">Title *</label>
                            <input type="text" id="new_task_title" name="new_task_title" required>
                        </div>

                        <div class="form-group">
                            <label for="new_task_description">Description *</label>
                            <textarea id="new_task_description" name="new_task_description" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="new_due_date">Due Date *</label>
                            <input type="date" id="new_due_date" name="new_due_date" required>
                        </div>

                        <div class="form-group">
                            <label for="new_task_priority">Priority</label>
                            <select id="new_task_priority" name="new_task_priority">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="new_assigned_to">Assigned To</label>
                            <select id="new_assigned_to" name="new_assigned_to">
                                <option value="">Unassigned</option>
                                <option value="John Doe">John Doe</option>
                                <option value="Jane Smith">Jane Smith</option>
                                <option value="Mike Johnson">Mike Johnson</option>
                                <option value="Sarah Wilson">Sarah Wilson</option>
                                <option value="David Brown">David Brown</option>
                                <option value="Emily Davis">Emily Davis</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="new_related_to">Related To</label>
                            <select id="new_related_to" name="new_related_to">
                                <option value="General" selected>General</option>
                                <option value="Lead">Lead</option>
                                <option value="Deal">Deal</option>
                                <option value="Customer">Customer</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="new_related_id">Related ID (Optional)</label>
                            <input type="number" id="new_related_id" name="new_related_id" min="0">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addTaskModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('addTaskForm').submit()">Add Task</button>
            </div>
        </div>
    </div>

    <!-- View Lead Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Lead Details</h3>
                <button class="close-btn" onclick="closeModal('viewModal')">✕</button>
            </div>
            <div class="modal-body">
                <div id="leadDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Modal management system
        let currentOpenModal = null;

        function openModal(modalId) {
            // Close any currently open modal
            if (currentOpenModal) {
                closeModal(currentOpenModal);
            }

            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                currentOpenModal = modalId;
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                if (currentOpenModal === modalId) {
                    currentOpenModal = null;
                }
                document.body.style.overflow = ''; // Restore scrolling
            }
        }

        function closeAllModals() {
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => {
                modal.classList.remove('active');
            });
            currentOpenModal = null;
            document.body.style.overflow = ''; // Restore scrolling
        }

        // Fetch data from database functions
        async function fetchLeadData(leadId) {
            try {
                const response = await fetch(`?action=get_lead_data&lead_id=${leadId}`);
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error fetching lead data:', error);
                return null;
            }
        }

        async function fetchDealData(dealId) {
            try {
                const response = await fetch(`?action=get_deal_data&deal_id=${dealId}`);
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error fetching deal data:', error);
                return null;
            }
        }

        async function fetchTaskData(taskId) {
            try {
                const response = await fetch(`?action=get_task_data&task_id=${taskId}`);
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error fetching task data:', error);
                return null;
            }
        }

        // Specific modal functions
        function assignTask(taskId) {
            document.getElementById('assign_task_id').value = taskId;
            openModal('assignTaskModal');
        }

        async function viewLead(leadId) {
            const details = document.getElementById('leadDetails');
            details.innerHTML = '<div class="loading-spinner"></div> Loading lead details...';
            openModal('viewModal');

            const leadData = await fetchLeadData(leadId);

            if (leadData) {
                details.innerHTML = `
                    <div class="lead-details">
                        <div class="detail-row">
                            <div class="detail-label">Lead ID:</div>
                            <div class="detail-value">#LEAD-${String(leadData.LeadID).padStart(3, '0')}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Name:</div>
                            <div class="detail-value">${leadData.FirstName} ${leadData.LastName}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Email:</div>
                            <div class="detail-value">${leadData.Email || 'N/A'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Phone:</div>
                            <div class="detail-value">${leadData.Phone || 'N/A'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Company:</div>
                            <div class="detail-value">${leadData.CompanyName || 'N/A'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Job Title:</div>
                            <div class="detail-value">${leadData.JobTitle || 'N/A'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Potential Value:</div>
                            <div class="detail-value">₱${parseFloat(leadData.PotentialValue).toFixed(2)}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Priority:</div>
                            <div class="detail-value">${leadData.Priority}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">${leadData.Status}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Source:</div>
                            <div class="detail-value">${leadData.Source}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Notes:</div>
                            <div class="detail-value">${leadData.Notes || 'No notes available'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Created:</div>
                            <div class="detail-value">${new Date(leadData.CreatedAt).toLocaleDateString()}</div>
                        </div>
                    </div>
                `;
            } else {
                details.innerHTML = '<div class="error-message">Failed to load lead details. Please try again.</div>';
            }
        }

        async function editLead(leadId) {
            const leadData = await fetchLeadData(leadId);

            if (leadData) {
                // Update the modal for editing
                document.getElementById('leadModalTitle').textContent = 'Edit Lead';
                document.getElementById('lead_id').value = leadData.LeadID;
                document.getElementById('add_lead').value = '';
                document.getElementById('first_name').value = leadData.FirstName;
                document.getElementById('last_name').value = leadData.LastName;
                document.getElementById('email').value = leadData.Email || '';
                document.getElementById('phone').value = leadData.Phone || '';
                document.getElementById('company').value = leadData.CompanyName || '';
                document.getElementById('job_title').value = leadData.JobTitle || '';
                document.getElementById('potential_value').value = leadData.PotentialValue;
                document.getElementById('priority').value = leadData.Priority;
                document.getElementById('status').value = leadData.Status;
                document.getElementById('source').value = leadData.Source;
                document.getElementById('notes').value = leadData.Notes || '';

                // Change the form action to update instead of add
                const form = document.getElementById('leadForm');
                // Remove any existing update_lead input
                const existingUpdateInput = form.querySelector('input[name="update_lead"]');
                if (existingUpdateInput) {
                    existingUpdateInput.remove();
                }

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'update_lead';
                hiddenInput.value = '1';
                form.appendChild(hiddenInput);

                openModal('addLeadModal');
            } else {
                alert('Failed to load lead data. Please try again.');
            }
        }

        async function editDeal(dealId) {
            const dealData = await fetchDealData(dealId);

            if (dealData) {
                document.getElementById('edit_deal_id').value = dealData.DealID;
                document.getElementById('edit_deal_name').value = dealData.DealName;
                document.getElementById('edit_deal_value').value = dealData.DealValue;
                document.getElementById('edit_stage').value = dealData.Stage;
                document.getElementById('edit_probability').value = dealData.Probability;
                document.getElementById('edit_close_date').value = dealData.CloseDate;
                document.getElementById('edit_deal_notes').value = dealData.Notes || '';

                openModal('dealModal');
            } else {
                alert('Failed to load deal data. Please try again.');
            }
        }

        async function editTask(taskId) {
            const taskData = await fetchTaskData(taskId);

            if (taskData) {
                document.getElementById('edit_task_id').value = taskData.TaskID;
                document.getElementById('edit_task_title').value = taskData.Title;
                document.getElementById('edit_task_description').value = taskData.Description || '';
                document.getElementById('edit_due_date').value = taskData.DueDate;
                document.getElementById('edit_task_priority').value = taskData.Priority;
                document.getElementById('edit_task_status').value = taskData.Status;
                document.getElementById('edit_assigned_to').value = taskData.AssignedTo || '';

                openModal('taskModal');
            } else {
                alert('Failed to load task data. Please try again.');
            }
        }

        // Close modals when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });

        // Tab switching
        function switchTab(tabName) {
            window.location.href = '?tab=' + tabName;
        }

        // Dropdown functions
        function toggleFilter() {
            document.getElementById('filterContent').classList.toggle('show');
            document.getElementById('sortContent').classList.remove('show');
            document.getElementById('settingsContent').classList.remove('show');
        }

        function toggleSort() {
            document.getElementById('sortContent').classList.toggle('show');
            document.getElementById('filterContent').classList.remove('show');
            document.getElementById('settingsContent').classList.remove('show');
        }

        function toggleSettings() {
            document.getElementById('settingsContent').classList.toggle('show');
            document.getElementById('filterContent').classList.remove('show');
            document.getElementById('sortContent').classList.remove('show');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.matches('.icon-btn')) {
                document.getElementById('filterContent').classList.remove('show');
                document.getElementById('sortContent').classList.remove('show');
                document.getElementById('settingsContent').classList.remove('show');
            }
        });

        // Utility functions
        function refreshData() {
            location.reload();
        }

        function resetView() {
            window.location.href = '?tab=<?php echo $activeTab; ?>';
        }

        function exportData() {
            const tab = '<?php echo $activeTab; ?>';
            window.location.href = '?export=1&tab=' + tab;
        }

        // Delete functions
        function deleteLead(leadId) {
            if (confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
                window.location.href = '?delete=lead&id=' + leadId + '&tab=customers';
            }
        }

        function deleteTask(taskId) {
            if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                window.location.href = '?delete=task&id=' + taskId + '&tab=tasks';
            }
        }

        // Search functionality
        const searchBox = document.getElementById('globalSearch');
        searchBox.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.table tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Notification button
        document.querySelector('.notification-btn').addEventListener('click', function() {
            alert('5 new notifications');
        });

        // Auto-hide success message after 5 seconds
        setTimeout(() => {
            const successMessage = document.querySelector('.success-message');
            if (successMessage) {
                successMessage.style.display = 'none';
            }
        }, 5000);

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAllModals();
            }
        });

        // Ensure all modals are closed on page load
        document.addEventListener('DOMContentLoaded', function() {
            closeAllModals();
        });
    </script>
</body>

</html>