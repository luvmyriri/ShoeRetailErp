<?php
// Include the unified backend
require_once('../../api/crm.php');

// Prepare data holders
$totalTickets = 0;
$openTickets = 0;
$inProgress = 0;
$resolved = 0;
$avgResponseHours = 0;
$satisfactionRate = 0;
$tickets = [];
$customersForSelect = [];
$agentsForSelect = [];
$storesForSelect = [];

$possibleTicketTables = ['support_tickets', 'tickets', 'customer_support', 'helpdesk_tickets'];
$ticketTable = null;
foreach ($possibleTicketTables as $tbl) {
    if (tableExists($pdo, $tbl)) {
        $ticketTable = $tbl;
        break;
    }
}

try {
    if ($ticketTable) {
        $stats = getTicketStats($pdo, $ticketTable);
        $totalTickets = $stats['totalTickets'];
        $openTickets = $stats['openTickets'];
        $inProgress = $stats['inProgress'];
        $resolved = $stats['resolved'];
        $avgResponseHours = $stats['avgResponseHours'];
        $satisfactionRate = $stats['satisfactionRate'];

        $ticketsData = getFilteredTickets($pdo, $ticketTable, []);
        $tickets = $ticketsData['tickets'];
    } else {
        $stmt = $pdo->prepare("SELECT s.SaleID, s.SaleDate, c.FirstName, c.LastName FROM sales s LEFT JOIN customers c ON s.CustomerID = c.CustomerID ORDER BY s.SaleDate DESC LIMIT 50");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $i => $r) {
            $custName = trim(($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? '')) ?: 'Customer';
            $tickets[] = [
                'id' => '#TKT-F' . ($r['SaleID'] ?? $i),
                'customer' => $custName,
                'customer_initials' => getInitials($custName),
                'subject' => 'Order inquiry',
                'description' => 'Customer inquiry related to sale',
                'category' => 'Order Issue',
                'priority' => 'Medium',
                'status' => 'Open',
                'assigned' => 'Unassigned',
                'assigned_initials' => 'ΓÇö',
                'created' => isset($r['SaleDate']) ? date('M d, Y H:i', strtotime($r['SaleDate'])) : '',
                'updated' => isset($r['SaleDate']) ? date('M d, Y H:i', strtotime($r['SaleDate'])) : '',
                'sale_ref' => isset($r['SaleID']) ? ('#SALE-' . $r['SaleID']) : '-',
                'contact_method' => 'Email',
                'due_date' => '',
                'tags' => ''
            ];
        }

        $totalTickets = count($tickets);
        $openTickets = $totalTickets;
    }

    $stmt = $pdo->query("SELECT UserID, FirstName, LastName, Role FROM users WHERE Role IN ('Support','Agent','Admin','Manager') ORDER BY FirstName LIMIT 200");
    $agentsForSelect = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT CustomerID, FirstName, LastName, Email, Phone FROM customers ORDER BY FirstName LIMIT 200");
    $customersForSelect = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (tableExists($pdo, 'stores')) {
        $stmt2 = $pdo->query("SELECT StoreID, StoreName, Location FROM stores ORDER BY StoreName LIMIT 100");
        $storesForSelect = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // keep defaults on error
}

$stats_dynamic = [
    ['icon' => '≡ƒÄ½', 'value' => number_format($totalTickets), 'label' => 'Total Tickets', 'sublabel' => 'All time', 'trend' => '+0.0%', 'trend_dir' => 'up', 'color' => '#dbeafe'],
    ['icon' => '≡ƒöô', 'value' => number_format($openTickets), 'label' => 'Open Tickets', 'sublabel' => 'Awaiting response', 'trend' => '', 'trend_dir' => 'down', 'color' => '#fee2e2'],
    ['icon' => 'ΓÅ│', 'value' => number_format($inProgress), 'label' => 'In Progress', 'sublabel' => 'Being handled', 'trend' => '', 'trend_dir' => 'up', 'color' => '#fef3c7'],
    ['icon' => 'Γ£à', 'value' => number_format($resolved), 'label' => 'Resolved', 'sublabel' => 'Closed tickets', 'trend' => '', 'trend_dir' => 'up', 'color' => '#d1fae5'],
    ['icon' => 'ΓÅ▒∩╕Å', 'value' => ($avgResponseHours ? $avgResponseHours . ' hrs' : 'N/A'), 'label' => 'Avg Response Time', 'sublabel' => 'First response', 'trend' => '', 'trend_dir' => 'up', 'color' => '#e9d5ff'],
    ['icon' => '≡ƒôê', 'value' => ($satisfactionRate ? $satisfactionRate . '%' : 'N/A'), 'label' => 'Satisfaction Rate', 'sublabel' => 'Customer feedback', 'trend' => '', 'trend_dir' => 'up', 'color' => '#ddd6fe']
];

// Helper function to render ticket rows
function renderTicketRow($ticket)
{
    $statusClass = match ($ticket['status']) {
        'Open' => 'ticket-status-open',
        'In Progress' => 'ticket-status-progress',
        'Resolved' => 'ticket-status-resolved',
        'Closed' => 'ticket-status-closed',
        default => 'ticket-status-open'
    };

    $priorityClass = match ($ticket['priority']) {
        'Critical' => 'ticket-priority-critical',
        'High' => 'priority-high',
        'Medium' => 'priority-medium',
        'Low' => 'priority-low',
        default => 'priority-medium'
    };

    $categoryClass = match ($ticket['category']) {
        'Product Issue' => 'category-product',
        'Order Issue' => 'category-order',
        'Refund Request' => 'category-refund',
        'Billing Issue' => 'category-billing',
        'Technical Support' => 'category-technical',
        'Account Issue' => 'category-account',
        'Shipping Issue' => 'category-shipping',
        'Complaint' => 'category-complaint',
        'Inquiry' => 'category-inquiry',
        'Feature Request' => 'category-feature',
        default => 'category-inquiry'
    };

    ob_start();
?>
    <tr>
        <td><input type="checkbox"></td>
        <td><span class="contact-id"><?php echo $ticket['id']; ?></span></td>
        <td>
            <div class="contact-name-cell">
                <div class="contact-avatar"><?php echo $ticket['customer_initials']; ?></div>
                <div class="contact-name-info">
                    <div class="contact-name-primary"><?php echo $ticket['customer']; ?></div>
                </div>
            </div>
        </td>
        <td>
            <div class="ticket-subject">
                <div class="ticket-subject-title"><?php echo $ticket['subject']; ?></div>
                <div class="ticket-subject-desc"><?php echo $ticket['description']; ?></div>
                <?php if ($ticket['sale_ref'] !== '-'): ?>
                    <div class="ticket-sale-ref">≡ƒöù <?php echo $ticket['sale_ref']; ?></div>
                <?php endif; ?>
            </div>
        </td>
        <td><span class="category-badge <?php echo $categoryClass; ?>"><?php echo $ticket['category']; ?></span></td>
        <td><span class="priority-badge <?php echo $priorityClass; ?>"><?php echo $ticket['priority']; ?></span></td>
        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo strtoupper($ticket['status']); ?></span></td>
        <td>
            <div class="contact-name-cell">
                <div class="contact-avatar" style="width: 32px; height: 32px; font-size: 12px;"><?php echo $ticket['assigned_initials']; ?></div>
                <span><?php echo $ticket['assigned']; ?></span>
            </div>
        </td>
        <td><?php echo $ticket['created']; ?></td>
        <td><?php echo $ticket['updated']; ?></td>
        <td>
            <div class="action-buttons">
                <button class="action-btn" onclick="viewTicket('<?php echo $ticket['id']; ?>')">View</button>
                <button class="action-btn" onclick="updateTicket('<?php echo $ticket['id']; ?>')">Update</button>
            </div>
        </td>
    </tr>
<?php
    return ob_get_clean();
}
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
    <title>Customer Support - CRM System</title>
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
                <li><a href="./CrmDashboard.php">Dashboard</a></li>
                <li><a href="./customerProfile.php">Customer Profiles</a></li>
                <li><a href="./loyaltyProgram.php">Loyalty Program</a></li>
                <li><a href="./customerSupport.php" class="active">Customer Support</a></li>
                <li><a href="./reportsManagement.php">Reports & Analytics</a></li>
            </ul>
            <div class="nav-right">
                <div class="search-wrapper">
                    <span class="search-icon">≡ƒöì</span>
                    <input type="text" class="search-box" placeholder="Search tickets..." id="globalSearch">
                </div>
                <button class="notification-btn">
                    ≡ƒöö
                    <span class="notification-badge">12</span>
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
                    <span>Customer Support</span>
                </div>
                <h1 class="page-title">Customer Support</h1>
                <p class="page-subtitle">Manage support tickets and customer inquiries</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="exportTickets()">
                    <span>≡ƒôÑ</span>
                    <span>Export</span>
                </button>
                <button class="btn btn-primary" onclick="openTicketModal()">
                    <span>+</span>
                    <span>Create Ticket</span>
                </button>
            </div>
        </div>

        <div class="stats-grid" id="statsGrid">
            <?php
            $stats = $stats_dynamic;
            foreach ($stats as $stat) {
                echo '<div class="stat-card">';
                echo '<div class="stat-header">';
                echo '<div class="stat-icon" style="background: ' . $stat['color'] . ';">' . $stat['icon'] . '</div>';
                echo '<div class="stat-trend ' . $stat['trend_dir'] . '">';
                echo '<span>' . ($stat['trend_dir'] === 'up' ? 'Γåæ' : 'Γåô') . '</span>';
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

        <div class="filters-section">
            <div class="filters-header">
                <div class="filters-title">≡ƒöì Filter Support Tickets</div>
                <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;" onclick="resetFilters()">Reset Filters</button>
            </div>
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="All Status">All Status</option>
                        <option value="Open">Open</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Priority</label>
                    <select class="filter-select" id="priorityFilter">
                        <option value="All Priority">All Priority</option>
                        <option value="Critical">Critical</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Category</label>
                    <select class="filter-select" id="categoryFilter">
                        <option value="All Categories">All Categories</option>
                        <option value="Product Issue">Product Issue</option>
                        <option value="Order Issue">Order Issue</option>
                        <option value="Refund Request">Refund Request</option>
                        <option value="Billing Issue">Billing Issue</option>
                        <option value="Technical Support">Technical Support</option>
                        <option value="Account Issue">Account Issue</option>
                        <option value="Shipping Issue">Shipping Issue</option>
                        <option value="Complaint">Complaint</option>
                        <option value="Inquiry">Inquiry</option>
                        <option value="Feature Request">Feature Request</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Date Range</label>
                    <select class="filter-select" id="dateFilter">
                        <option value="All Time">All Time</option>
                        <option value="Today">Today</option>
                        <option value="This Week">This Week</option>
                        <option value="This Month">This Month</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2 class="section-title">Support Tickets (<span id="ticketCount"><?php echo count($tickets); ?></span>)</h2>
                <div class="card-actions">
                    <button class="icon-btn" title="Refresh" onclick="refreshTickets()">≡ƒöä</button>
                    <div class="realtime-indicator" id="realtimeIndicator" style="display: none;">
                        <div class="pulse-dot"></div>
                        <span>Live Updates</span>
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Ticket ID</th>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th>Last Update</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ticketsTableBody">
                        <?php
                        foreach ($tickets as $ticket) {
                            echo renderTicketRow($ticket);
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <div class="showing-text">
                    Showing <strong id="showingRange">1-<?php echo min(50, count($tickets)); ?></strong> of <strong id="totalTicketsCount"><?php echo count($tickets); ?></strong> tickets
                </div>
                <div class="pagination">
                    <button onclick="changePage('prev')">ΓÇ╣</button>
                    <button class="active">1</button>
                    <button onclick="changePage(2)">2</button>
                    <button onclick="changePage(3)">3</button>
                    <button onclick="changePage(4)">4</button>
                    <button onclick="changePage(5)">5</button>
                    <button onclick="changePage('next')">ΓÇ║</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Ticket Modal -->
    <div class="modal-overlay" id="ticketModal">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">Create New Support Ticket</h3>
                <button class="close-btn" onclick="closeTicketModal()">Γ£ò</button>
            </div>
            <div class="modal-body">
                <form id="ticketForm">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label">Select Customer *</label>
                            <select class="filter-select" name="customer_id" required id="customerSelect" onchange="updateCustomerInfo()">
                                <option value="">Choose customer</option>
                                <?php
                                foreach ($customersForSelect as $c) {
                                    $cid = $c['CustomerID'];
                                    $name = trim(($c['FirstName'] ?? '') . ' ' . ($c['LastName'] ?? ''));
                                    $email = $c['Email'] ?? '';
                                    $phone = $c['Phone'] ?? '';
                                    echo '<option value="' . htmlspecialchars($cid) . '" data-email="' . htmlspecialchars($email) . '" data-phone="' . htmlspecialchars($phone) . '">' . htmlspecialchars($name) . ($email ? ' - ' . htmlspecialchars($email) : '') . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Customer Email</label>
                            <input type="text" class="form-input" id="customerEmail" readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Customer Phone</label>
                            <input type="text" class="form-input" id="customerPhone" readonly style="background: #f5f5f5;">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <select class="filter-select" name="category" required>
                                <option value="">Select category</option>
                                <option value="Product Issue">Product Issue</option>
                                <option value="Order Issue">Order Issue</option>
                                <option value="Refund Request">Refund Request</option>
                                <option value="Billing Issue">Billing Issue</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Account Issue">Account Issue</option>
                                <option value="Shipping Issue">Shipping Issue</option>
                                <option value="Complaint">Complaint</option>
                                <option value="Inquiry">Inquiry</option>
                                <option value="Feature Request">Feature Request</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Priority *</label>
                            <select class="filter-select" name="priority" required>
                                <option value="">Select priority</option>
                                <option value="Critical">Critical</option>
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Contact Method</label>
                            <select class="filter-select" name="contact_method">
                                <option value="Email">Email</option>
                                <option value="Phone">Phone</option>
                                <option value="Chat">Chat</option>
                                <option value="In-Person">In-Person</option>
                                <option value="Social Media">Social Media</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-input" name="due_date" min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Subject *</label>
                            <input type="text" class="form-input" name="subject" placeholder="Brief description of the issue" required>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Description *</label>
                            <textarea class="form-textarea" name="description" placeholder="Detailed description of the issue or inquiry..." required style="min-height: 120px;"></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Related Sale ID</label>
                            <input type="text" class="form-input" name="sale_ref" placeholder="#SALE-0000 (optional)">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Store Location</label>
                            <select class="filter-select" name="store_id">
                                <option value="">Select store</option>
                                <?php
                                foreach ($storesForSelect as $s) {
                                    $sid = $s['StoreID'];
                                    $sname = $s['StoreName'];
                                    $location = $s['Location'] ?? '';
                                    echo '<option value="' . htmlspecialchars($sid) . '">' . htmlspecialchars($sname) . ($location ? ' - ' . htmlspecialchars($location) : '') . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Assign To *</label>
                            <select class="filter-select" name="assigned_to" required>
                                <option value="">Select agent</option>
                                <?php
                                foreach ($agentsForSelect as $a) {
                                    $aid = $a['UserID'];
                                    $aname = trim(($a['FirstName'] ?? '') . ' ' . ($a['LastName'] ?? ''));
                                    $role = $a['Role'] ?? '';
                                    echo '<option value="' . htmlspecialchars($aid) . '">' . htmlspecialchars($aname) . ($role ? ' (' . htmlspecialchars($role) . ')' : '') . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Tags</label>
                            <input type="text" class="form-input" name="tags" placeholder="urgent, vip, technical (comma separated)">
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Internal Notes</label>
                            <textarea class="form-textarea" name="notes" placeholder="Add internal notes (not visible to customer)..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeTicketModal()">Cancel</button>
                <button class="btn btn-primary" id="createTicketBtn" type="button">Create Ticket</button>
            </div>
        </div>
    </div>

    <!-- Update Ticket Modal -->
    <div class="modal-overlay" id="updateTicketModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Update Ticket Status</h3>
                <button class="close-btn" onclick="closeUpdateModal()">Γ£ò</button>
            </div>
            <div class="modal-body">
                <form id="updateTicketForm">
                    <input type="hidden" id="updateTicketId" name="ticket_id">
                    <div class="form-group full-width">
                        <label class="form-label">Status *</label>
                        <select class="filter-select" id="updateStatus" name="status" required>
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Customer Feedback Score (1-5)</label>
                        <select class="filter-select" id="feedbackScore" name="feedback_score">
                            <option value="">No feedback yet</option>
                            <option value="1">1 - Very Poor</option>
                            <option value="2">2 - Poor</option>
                            <option value="3">3 - Average</option>
                            <option value="4">4 - Good</option>
                            <option value="5">5 - Excellent</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Additional Notes</label>
                        <textarea class="form-textarea" id="updateNotes" name="notes" placeholder="Add update notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeUpdateModal()">Cancel</button>
                <button class="btn btn-primary" id="updateTicketBtn" type="button">Update Ticket</button>
            </div>
        </div>
    </div>

    <!-- View Ticket Modal -->
    <div class="modal-overlay" id="viewTicketModal">
        <div class="modal" style="max-width: 900px;">
            <div class="modal-header">
                <h3 class="modal-title">Ticket Details</h3>
                <button class="close-btn" onclick="closeViewModal()">Γ£ò</button>
            </div>
            <div class="modal-body">
                <div class="ticket-details-container">
                    <div class="details-grid">
                        <div class="detail-section full-width">
                            <div class="ticket-header">
                                <div class="ticket-id-badge" id="viewTicketId">#TKT-0000</div>
                                <div class="ticket-status-badge" id="viewTicketStatus">Open</div>
                                <div class="ticket-priority-badge" id="viewTicketPriority">Medium</div>
                            </div>
                            <h4 class="ticket-subject" id="viewTicketSubject">Ticket Subject</h4>
                        </div>

                        <div class="detail-section">
                            <h4 class="section-title">Customer Information</h4>
                            <div class="detail-item">
                                <label>Name:</label>
                                <span id="viewCustomerName">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Email:</label>
                                <span id="viewCustomerEmail">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Phone:</label>
                                <span id="viewCustomerPhone">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Address:</label>
                                <span id="viewCustomerAddress">ΓÇö</span>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4 class="section-title">Ticket Information</h4>
                            <div class="detail-item">
                                <label>Category:</label>
                                <span id="viewTicketCategory">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Priority:</label>
                                <span id="viewTicketPriorityText">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Status:</label>
                                <span id="viewTicketStatusText">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Contact Method:</label>
                                <span id="viewContactMethod">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Due Date:</label>
                                <span id="viewDueDate">ΓÇö</span>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4 class="section-title">Assignment</h4>
                            <div class="detail-item">
                                <label>Assigned To:</label>
                                <span id="viewAssignedTo">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Agent Email:</label>
                                <span id="viewAgentEmail">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Store:</label>
                                <span id="viewStoreInfo">ΓÇö</span>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h4 class="section-title">Related Information</h4>
                            <div class="detail-item">
                                <label>Sale Reference:</label>
                                <span id="viewSaleRef">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Tags:</label>
                                <span id="viewTags">ΓÇö</span>
                            </div>
                            <div class="detail-item">
                                <label>Feedback Score:</label>
                                <span id="viewFeedbackScore">ΓÇö</span>
                            </div>
                        </div>

                        <div class="detail-section full-width">
                            <h4 class="section-title">Timeline</h4>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <strong>Created</strong>
                                        <span id="viewCreatedAt">ΓÇö</span>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <strong>First Response</strong>
                                        <span id="viewFirstResponse">ΓÇö</span>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <strong>Last Updated</strong>
                                        <span id="viewUpdatedAt">ΓÇö</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section full-width">
                            <h4 class="section-title">Description</h4>
                            <div class="description-content" id="viewDescription">ΓÇö</div>
                        </div>

                        <div class="detail-section full-width">
                            <h4 class="section-title">Internal Notes</h4>
                            <div class="notes-content" id="viewInternalNotes">ΓÇö</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeViewModal()">Close</button>
                <button class="btn btn-primary" onclick="editCurrentTicket()">Edit Ticket</button>
            </div>
        </div>
    </div>

    <style>
        .modal {
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }

        .form-input,
        .filter-select,
        .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-input:focus,
        .filter-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .ticket-details-container {
            padding: 0;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-section.full-width {
            grid-column: 1 / -1;
        }

        .detail-section {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b82f6;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            padding: 6px 0;
        }

        .detail-item label {
            font-weight: 500;
            color: #64748b;
            min-width: 120px;
        }

        .detail-item span {
            color: #1e293b;
            text-align: right;
            flex: 1;
        }

        .ticket-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .ticket-id-badge {
            background: #1e293b;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        .ticket-status-badge,
        .ticket-priority-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .ticket-subject {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .description-content,
        .notes-content {
            background: white;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .timeline {
            position: relative;
            padding-left: 20px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 16px;
        }

        .timeline-marker {
            position: absolute;
            left: -20px;
            top: 6px;
            width: 8px;
            height: 8px;
            background: #3b82f6;
            border-radius: 50%;
        }

        .timeline-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .timeline-content strong {
            color: #1e293b;
        }

        .timeline-content span {
            color: #64748b;
            font-size: 14px;
        }

        .status-open {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-progress {
            background: #fef3c7;
            color: #92400e;
        }

        .status-resolved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-closed {
            background: #f3f4f6;
            color: #4b5563;
        }

        .priority-critical {
            background: #fecaca;
            color: #991b1b;
        }

        .priority-high {
            background: #fed7aa;
            color: #9a3412;
        }

        .priority-medium {
            background: #fef08a;
            color: #854d0e;
        }

        .priority-low {
            background: #dcfce7;
            color: #166534;
        }

        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-right-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .btn {
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .btn-primary:hover:not(:disabled) {
            background: #2563eb;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #4b5563;
        }

        .realtime-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #10b981;
            font-weight: 500;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                opacity: 0.7;
            }

            50% {
                transform: scale(1.1);
                opacity: 1;
            }

            100% {
                transform: scale(0.95);
                opacity: 0.7;
            }
        }
    </style>

    <script>
        let currentFilters = {
            status: 'All Status',
            priority: 'All Priority',
            category: 'All Categories',
            date_range: 'All Time',
            search: ''
        };
        let lastUpdateTime = '<?php echo date('Y-m-d H:i:s'); ?>';
        let realtimeInterval = null;
        let currentPage = 1;
        const itemsPerPage = 50;

        function updateCustomerInfo() {
            const customerSelect = document.getElementById('customerSelect');
            const selectedOption = customerSelect.options[customerSelect.selectedIndex];
            const email = selectedOption.getAttribute('data-email') || '';
            const phone = selectedOption.getAttribute('data-phone') || '';

            document.getElementById('customerEmail').value = email;
            document.getElementById('customerPhone').value = phone;
        }

        async function viewTicket(ticketId) {
            try {
                const response = await fetch(`crm.php?action=get_ticket_details&ticket_id=${encodeURIComponent(ticketId)}`);
                const data = await response.json();

                if (data.success) {
                    displayTicketDetails(data.ticket);
                    openViewModal();
                } else {
                    showError('Error loading ticket: ' + data.message);
                }
            } catch (error) {
                showError('Error loading ticket details: ' + error.message);
            }
        }

        function displayTicketDetails(ticket) {
            document.getElementById('viewTicketId').textContent = ticket.id;
            document.getElementById('viewTicketSubject').textContent = ticket.subject;

            const statusBadge = document.getElementById('viewTicketStatus');
            statusBadge.textContent = ticket.status;
            statusBadge.className = 'ticket-status-badge status-' + ticket.status.toLowerCase().replace(' ', '-');

            const priorityBadge = document.getElementById('viewTicketPriority');
            priorityBadge.textContent = ticket.priority;
            priorityBadge.className = 'ticket-priority-badge priority-' + ticket.priority.toLowerCase();

            document.getElementById('viewCustomerName').textContent = ticket.customer_name;
            document.getElementById('viewCustomerEmail').textContent = ticket.customer_email;
            document.getElementById('viewCustomerPhone').textContent = ticket.customer_phone || 'ΓÇö';
            document.getElementById('viewCustomerAddress').textContent = ticket.customer_address || 'ΓÇö';

            document.getElementById('viewTicketCategory').textContent = ticket.category;
            document.getElementById('viewTicketPriorityText').textContent = ticket.priority;
            document.getElementById('viewTicketStatusText').textContent = ticket.status;
            document.getElementById('viewContactMethod').textContent = ticket.contact_method;
            document.getElementById('viewDueDate').textContent = ticket.due_date;

            document.getElementById('viewAssignedTo').textContent = ticket.assigned_to;
            document.getElementById('viewAgentEmail').textContent = ticket.assigned_email || 'ΓÇö';
            document.getElementById('viewStoreInfo').textContent = ticket.store_name ?
                `${ticket.store_name}${ticket.store_location ? ' - ' + ticket.store_location : ''}` : 'ΓÇö';

            document.getElementById('viewSaleRef').textContent = ticket.sale_ref || 'ΓÇö';
            document.getElementById('viewTags').textContent = ticket.tags || 'ΓÇö';
            document.getElementById('viewFeedbackScore').textContent = ticket.feedback_score;

            document.getElementById('viewCreatedAt').textContent = ticket.created_at;
            document.getElementById('viewFirstResponse').textContent = ticket.first_response_at;
            document.getElementById('viewUpdatedAt').textContent = ticket.updated_at;

            document.getElementById('viewDescription').textContent = ticket.description;
            document.getElementById('viewInternalNotes').textContent = ticket.internal_notes || 'No internal notes';
        }

        function openViewModal() {
            document.getElementById('viewTicketModal').classList.add('active');
        }

        function closeViewModal() {
            document.getElementById('viewTicketModal').classList.remove('active');
        }

        function editCurrentTicket() {
            const ticketId = document.getElementById('viewTicketId').textContent;
            closeViewModal();
            updateTicket(ticketId);
        }

        function startRealtimeUpdates() {
            realtimeInterval = setInterval(checkForUpdates, 5000);
            document.getElementById('realtimeIndicator').style.display = 'flex';
        }

        function stopRealtimeUpdates() {
            if (realtimeInterval) {
                clearInterval(realtimeInterval);
                realtimeInterval = null;
            }
            document.getElementById('realtimeIndicator').style.display = 'none';
        }

        async function checkForUpdates() {
            try {
                const response = await fetch(`crm.php?action=get_ticket_updates&last_update=${encodeURIComponent(lastUpdateTime)}`);
                const data = await response.json();

                if (data.updated) {
                    lastUpdateTime = '<?php echo date('Y-m-d H:i:s'); ?>';

                    if (data.stats) {
                        updateStats(data.stats);
                    }

                    if (data.tickets.length > 0) {
                        showUpdateNotification(data.tickets.length);
                        refreshTickets();
                    }
                }
            } catch (error) {
                console.error('Error checking for updates:', error);
            }
        }

        function updateStats(stats) {
            const statCards = document.querySelectorAll('.stat-card');
            if (statCards.length >= 6) {
                statCards[0].querySelector('.stat-value').textContent = numberFormat(stats.totalTickets);
                statCards[1].querySelector('.stat-value').textContent = numberFormat(stats.openTickets);
                statCards[2].querySelector('.stat-value').textContent = numberFormat(stats.inProgress);
                statCards[3].querySelector('.stat-value').textContent = numberFormat(stats.resolved);
                statCards[4].querySelector('.stat-value').textContent = stats.avgResponseHours ? stats.avgResponseHours + ' hrs' : 'N/A';
                statCards[5].querySelector('.stat-value').textContent = stats.satisfactionRate ? stats.satisfactionRate + '%' : 'N/A';
            }
        }

        function showUpdateNotification(count) {
            const notification = document.createElement('div');
            notification.className = 'update-notification';
            notification.innerHTML = `
                <div style="padding: 12px; background: #10b981; color: white; border-radius: 6px; margin-bottom: 10px; font-size: 14px;">
                    ≡ƒöä ${count} ticket${count > 1 ? 's' : ''} updated. <a href="javascript:void(0)" onclick="refreshTickets()" style="color: white; text-decoration: underline;">Refresh</a>
                </div>
            `;

            const container = document.querySelector('.container');
            container.insertBefore(notification, container.firstChild);

            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        async function applyFilters() {
            const filters = {
                status: document.getElementById('statusFilter').value,
                priority: document.getElementById('priorityFilter').value,
                category: document.getElementById('categoryFilter').value,
                date_range: document.getElementById('dateFilter').value,
                search: document.getElementById('globalSearch').value
            };

            currentFilters = filters;

            try {
                const params = new URLSearchParams({
                    action: 'filter_tickets',
                    ...filters
                });

                const response = await fetch(`crm.php?${params}`);
                const data = await response.json();

                if (!data.error) {
                    updateTicketsTable(data.tickets, data.total_count);
                } else {
                    showError('Error applying filters: ' + data.error);
                }
            } catch (error) {
                showError('Error applying filters: ' + error.message);
            }
        }

        function updateTicketsTable(tickets, totalCount) {
            const tbody = document.getElementById('ticketsTableBody');
            const ticketCount = document.getElementById('ticketCount');
            const totalTicketsCount = document.getElementById('totalTicketsCount');
            const showingRange = document.getElementById('showingRange');

            ticketCount.textContent = totalCount;
            totalTicketsCount.textContent = totalCount;
            const showingEnd = Math.min(itemsPerPage, tickets.length);
            showingRange.innerHTML = `1-${showingEnd}`;

            tbody.innerHTML = '';

            tickets.forEach(ticket => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox"></td>
                    <td><span class="contact-id">${ticket.id}</span></td>
                    <td>
                        <div class="contact-name-cell">
                            <div class="contact-avatar">${ticket.customer_initials}</div>
                            <div class="contact-name-info">
                                <div class="contact-name-primary">${ticket.customer}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="ticket-subject">
                            <div class="ticket-subject-title">${ticket.subject}</div>
                            <div class="ticket-subject-desc">${ticket.description}</div>
                            ${ticket.sale_ref !== '-' ? `<div class="ticket-sale-ref">≡ƒöù ${ticket.sale_ref}</div>` : ''}
                        </div>
                    </td>
                    <td><span class="category-badge category-${ticket.category.toLowerCase().replace(' ', '-')}">${ticket.category}</span></td>
                    <td><span class="priority-badge priority-${ticket.priority.toLowerCase()}">${ticket.priority}</span></td>
                    <td><span class="status-badge ticket-status-${ticket.status.toLowerCase().replace(' ', '-')}">${ticket.status.toUpperCase()}</span></td>
                    <td>
                        <div class="contact-name-cell">
                            <div class="contact-avatar" style="width: 32px; height: 32px; font-size: 12px;">${ticket.assigned_initials}</div>
                            <span>${ticket.assigned}</span>
                        </div>
                    </td>
                    <td>${ticket.created}</td>
                    <td>${ticket.updated}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn" onclick="viewTicket('${ticket.id}')">View</button>
                            <button class="action-btn" onclick="updateTicket('${ticket.id}')">Update</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function resetFilters() {
            document.getElementById('statusFilter').value = 'All Status';
            document.getElementById('priorityFilter').value = 'All Priority';
            document.getElementById('categoryFilter').value = 'All Categories';
            document.getElementById('dateFilter').value = 'All Time';
            document.getElementById('globalSearch').value = '';

            applyFilters();
        }

        function refreshTickets() {
            applyFilters();
            lastUpdateTime = '<?php echo date('Y-m-d H:i:s'); ?>';
        }

        function generateReports() {
            const filters = currentFilters;
            const reportUrl = `reportsManagement.php?source=support&status=${filters.status}&priority=${filters.priority}&category=${filters.category}&date_range=${filters.date_range}`;
            window.open(reportUrl, '_blank');
        }

        function exportTickets() {
            const filters = currentFilters;
            const exportUrl = `crm.php?action=export_tickets&status=${filters.status}&priority=${filters.priority}&category=${filters.category}&date_range=${filters.date_range}&search=${filters.search}`;
            window.location.href = exportUrl;
        }

        function numberFormat(number) {
            return new Intl.NumberFormat().format(number);
        }

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'update-notification';
            errorDiv.innerHTML = `
                <div style="padding: 12px; background: #ef4444; color: white; border-radius: 6px; margin-bottom: 10px; font-size: 14px;">
                    Γ¥î ${message}
                </div>
            `;

            const container = document.querySelector('.container');
            container.insertBefore(errorDiv, container.firstChild);

            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }

        function showSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'update-notification';
            successDiv.innerHTML = `
                <div style="padding: 12px; background: #10b981; color: white; border-radius: 6px; margin-bottom: 10px; font-size: 14px;">
                    Γ£à ${message}
                </div>
            `;

            const container = document.querySelector('.container');
            container.insertBefore(successDiv, container.firstChild);

            setTimeout(() => {
                successDiv.remove();
            }, 5000);
        }

        function openTicketModal() {
            document.getElementById('ticketModal').classList.add('active');
            document.getElementById('customerEmail').value = '';
            document.getElementById('customerPhone').value = '';
        }

        function closeTicketModal() {
            document.getElementById('ticketModal').classList.remove('active');
            document.getElementById('ticketForm').reset();
        }

        function openUpdateModal(ticketId) {
            document.getElementById('updateTicketId').value = ticketId;
            document.getElementById('updateTicketModal').classList.add('active');
        }

        function closeUpdateModal() {
            document.getElementById('updateTicketModal').classList.remove('active');
            document.getElementById('updateTicketForm').reset();
        }

        async function saveTicket() {
            const form = document.getElementById('ticketForm');
            const submitBtn = document.getElementById('createTicketBtn');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;

            const formData = new FormData(form);
            formData.append('action', 'create_ticket');

            try {
                const response = await fetch('crm.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess(result.message);
                    closeTicketModal();
                    refreshTickets();
                    lastUpdateTime = '<?php echo date('Y-m-d H:i:s'); ?>';
                } else {
                    showError('Error: ' + result.message);
                }
            } catch (error) {
                showError('Error creating ticket: ' + error.message);
            } finally {
                submitBtn.classList.remove('btn-loading');
                submitBtn.disabled = false;
            }
        }

        async function submitTicketUpdate() {
            const form = document.getElementById('updateTicketForm');
            const submitBtn = document.getElementById('updateTicketBtn');
            const formData = new FormData(form);
            formData.append('action', 'update_ticket_status');

            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;

            try {
                const response = await fetch('crm.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess(result.message);
                    closeUpdateModal();
                    refreshTickets();
                    lastUpdateTime = '<?php echo date('Y-m-d H:i:s'); ?>';
                } else {
                    showError('Error: ' + result.message);
                }
            } catch (error) {
                showError('Error updating ticket: ' + error.message);
            } finally {
                submitBtn.classList.remove('btn-loading');
                submitBtn.disabled = false;
            }
        }

        function updateTicket(ticketId) {
            openUpdateModal(ticketId);
        }

        function changePage(direction) {
            console.log('Changing page:', direction);
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('statusFilter').addEventListener('change', applyFilters);
            document.getElementById('priorityFilter').addEventListener('change', applyFilters);
            document.getElementById('categoryFilter').addEventListener('change', applyFilters);
            document.getElementById('dateFilter').addEventListener('change', applyFilters);
            document.getElementById('globalSearch').addEventListener('input', applyFilters);

            document.getElementById('createTicketBtn').addEventListener('click', saveTicket);
            document.getElementById('updateTicketBtn').addEventListener('click', submitTicketUpdate);

            startRealtimeUpdates();

            document.getElementById('ticketModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeTicketModal();
                }
            });

            document.getElementById('updateTicketModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeUpdateModal();
                }
            });

            document.getElementById('viewTicketModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeViewModal();
                }
            });

            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const rowCheckboxes = document.querySelectorAll('.table tbody input[type="checkbox"]');
                    rowCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }
        });
    </script>
</body>

</html>
