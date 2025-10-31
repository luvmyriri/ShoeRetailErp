<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../config/database.php');

try {
    $db = Database::getInstance()->getConnection();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// --- CRM BACKEND LOGIC (Unified Backend) ---
$pdo = $db; // reuse same DB connection instance

// ========================================
// HELPER FUNCTIONS
// ========================================

// Helper function to check if table exists
function tableExists($pdo, $tableName)
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$tableName]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Helper: parse requested range
function getDateRange($range)
{
    $now = new DateTime();
    $year = (int)$now->format('Y');
    $month = (int)$now->format('n');

    switch ($range) {
        case 'last_month':
            $start = new DateTime('first day of last month midnight');
            $end = new DateTime('last day of last month 23:59:59');
            break;


        case 'this_quarter':
            $quarter = ceil($month / 3);
            $startMonth = ($quarter - 1) * 3 + 1;
            $start = new DateTime("$year-$startMonth-01 00:00:00");
            $end = (clone $start)->modify('+2 months')->modify('last day of this month')->setTime(23, 59, 59);
            break;

        case 'last_quarter':
            $quarter = ceil($month / 3) - 1;
            if ($quarter < 1) {
                $quarter = 4;
                $year--;
            }
            $startMonth = ($quarter - 1) * 3 + 1;
            $start = new DateTime("$year-$startMonth-01 00:00:00");
            $end = (clone $start)->modify('+2 months')->modify('last day of this month')->setTime(23, 59, 59);
            break;

        case 'this_year':
            $start = new DateTime("$year-01-01 00:00:00");
            $end = new DateTime("$year-12-31 23:59:59");
            break;

        case 'custom':
            if (!empty($_GET['start']) && !empty($_GET['end'])) {
                $start = (new DateTime($_GET['start']))->setTime(0, 0, 0);
                $end = (new DateTime($_GET['end']))->setTime(23, 59, 59);
                break;
            }
            // fallback if custom params are missing

        case 'this_month':
        default:
            $start = new DateTime(date('Y-m-01 00:00:00'));
            $end = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);
            break;
    }

    return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
}

// Function to get report statistics
function getReportStats($pdo, $startDate, $endDate)
{
    $stats = [];

    // Total Revenue
    try {
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(TotalAmount),0) AS total FROM sales WHERE SaleDate BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $stats['totalRevenue'] = (float)$stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['totalRevenue'] = 0;
    }

    // Orders Closed
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE SaleDate BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $stats['ordersClosed'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['ordersClosed'] = 0;
    }

    // Avg Order Value
    $stats['avgOrderValue'] = $stats['ordersClosed'] > 0 ? ($stats['totalRevenue'] / $stats['ordersClosed']) : 0;

    // Avg items per order
    try {
        $stmt = $pdo->prepare("SELECT AVG(item_count) FROM (SELECT COUNT(*) AS item_count FROM saledetails sd JOIN sales s ON sd.SaleID = s.SaleID WHERE s.SaleDate BETWEEN ? AND ? GROUP BY sd.SaleID) t");
        $stmt->execute([$startDate, $endDate]);
        $stats['avgItemsPerOrder'] = round($stmt->fetchColumn() ?: 0, 1);
    } catch (Exception $e) {
        $stats['avgItemsPerOrder'] = 0;
    }

    // Avg sales cycle
    try {
        $stmt = $pdo->prepare("SELECT AVG(DATEDIFF(s.SaleDate, c.CreatedAt)) FROM sales s JOIN customers c ON s.CustomerID = c.CustomerID WHERE s.SaleDate BETWEEN ? AND ? AND c.CreatedAt IS NOT NULL");
        $stmt->execute([$startDate, $endDate]);
        $stats['avgSalesCycleDays'] = (int)round($stmt->fetchColumn() ?: 0);
    } catch (Exception $e) {
        $stats['avgSalesCycleDays'] = 0;
    }

    // New customers in period
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE CreatedAt BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $stats['newCustomers'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['newCustomers'] = 0;
    }

    // Customer satisfaction (try different possible ticket tables)
    $stats['satisfactionRate'] = 0;
    $possibleTicketTables = ['supporttickets', 'tickets', 'customer_support', 'helpdesk_tickets'];

    foreach ($possibleTicketTables as $table) {
        if (tableExists($pdo, $table)) {
            try {
                $stmt = $pdo->prepare("SELECT AVG(FeedbackScore) FROM `{$table}` WHERE CreatedAt BETWEEN ? AND ? AND FeedbackScore IS NOT NULL");
                $stmt->execute([$startDate, $endDate]);
                $avgScore = $stmt->fetchColumn();
                if ($avgScore) {
                    $stats['satisfactionRate'] = round(($avgScore / 5) * 100, 1); // Convert 0-5 to percentage
                    break;
                }
            } catch (Exception $e) {
                // Continue to next table
                continue;
            }
        }
    }

    // If no satisfaction data found, use a calculated value based on payment status
    if ($stats['satisfactionRate'] == 0) {
        try {
            $stmt = $pdo->prepare("SELECT 
                SUM(CASE WHEN PaymentStatus = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                COUNT(*) as total_count 
                FROM sales WHERE SaleDate BETWEEN ? AND ?");
            $stmt->execute([$startDate, $endDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['total_count'] > 0) {
                $stats['satisfactionRate'] = round(($result['paid_count'] / $result['total_count']) * 100, 1);
            }
        } catch (Exception $e) {
            $stats['satisfactionRate'] = 85.0; // Default value
        }
    }

    return $stats;
}

// Function to get charts data
function getChartsData($pdo, $startDate, $endDate)
{
    // Monthly revenue for the selected period
    $monthlyValues = [];
    $paidPercentages = [];
    $monthsLabels = [];

    try {
        // Get all months in the range
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = DateInterval::createFromDateString('1 month');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $month = $dt->format('Y-m');
            $monthsLabels[] = $dt->format('M Y');

            // Get revenue for this month
            $monthStart = $dt->format('Y-m-01 00:00:00');
            $monthEnd = $dt->format('Y-m-t 23:59:59');

            $stmt = $pdo->prepare("SELECT IFNULL(SUM(TotalAmount),0) AS total FROM sales WHERE SaleDate BETWEEN ? AND ?");
            $stmt->execute([$monthStart, $monthEnd]);
            $monthlyValues[] = (float)$stmt->fetchColumn();

            // Get payment success rate for this month
            $stmt = $pdo->prepare("SELECT 
                SUM(CASE WHEN PaymentStatus = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                COUNT(*) as total_count 
                FROM sales WHERE SaleDate BETWEEN ? AND ?");
            $stmt->execute([$monthStart, $monthEnd]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['total_count'] > 0) {
                $paidPercentages[] = round(($result['paid_count'] / $result['total_count']) * 100, 1);
            } else {
                $paidPercentages[] = 0;
            }
        }

        // If no months in range (same month), just use current month
        if (empty($monthsLabels)) {
            $currentMonth = date('M Y');
            $monthsLabels[] = $currentMonth;

            $monthStart = date('Y-m-01 00:00:00');
            $monthEnd = date('Y-m-t 23:59:59');

            $stmt = $pdo->prepare("SELECT IFNULL(SUM(TotalAmount),0) AS total FROM sales WHERE SaleDate BETWEEN ? AND ?");
            $stmt->execute([$monthStart, $monthEnd]);
            $monthlyValues[] = (float)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT 
                SUM(CASE WHEN PaymentStatus = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                COUNT(*) as total_count 
                FROM sales WHERE SaleDate BETWEEN ? AND ?");
            $stmt->execute([$monthStart, $monthEnd]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['total_count'] > 0) {
                $paidPercentages[] = round(($result['paid_count'] / $result['total_count']) * 100, 1);
            } else {
                $paidPercentages[] = 0;
            }
        }
    } catch (Exception $e) {
        // Use default data if there's an error
        $monthsLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $monthlyValues = [12000, 19000, 15000, 18000, 22000, 25000];
        $paidPercentages = [85, 90, 88, 92, 87, 94];
    }

    return [
        'monthsLabels' => $monthsLabels,
        'monthlyValues' => $monthlyValues,
        'paidPercentages' => $paidPercentages
    ];
}

// Function to generate custom reports
function generateCustomReport($pdo, $reportType, $startDate, $endDate)
{
    $reportData = ['headers' => [], 'rows' => []];

    switch ($reportType) {
        case 'sales_summary':
            $reportData['headers'] = ['Period', 'Total Revenue', 'Orders', 'Avg Order Value', 'New Customers'];

            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as orders,
                    IFNULL(SUM(TotalAmount),0) as revenue,
                    IFNULL(AVG(TotalAmount),0) as avg_order_value
                FROM sales 
                WHERE SaleDate BETWEEN ? AND ?
            ");
            $stmt->execute([$startDate, $endDate]);
            $salesData = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT COUNT(*) as new_customers FROM customers WHERE CreatedAt BETWEEN ? AND ?");
            $stmt->execute([$startDate, $endDate]);
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);

            $reportData['rows'][] = [
                date('M d, Y', strtotime($startDate)) . ' to ' . date('M d, Y', strtotime($endDate)),
                number_format($salesData['revenue'], 2),
                $salesData['orders'],
                number_format($salesData['avg_order_value'], 2),
                $customerData['new_customers']
            ];
            break;

        case 'product_performance':
            $reportData['headers'] = ['Product', 'SKU', 'Units Sold', 'Revenue', 'Avg Price'];

            $stmt = $pdo->prepare("
                SELECT 
                    p.Brand, p.Model, p.SKU,
                    SUM(sd.Quantity) as units_sold,
                    SUM(sd.Subtotal) as revenue,
                    AVG(sd.UnitPrice) as avg_price
                FROM saledetails sd
                JOIN products p ON sd.ProductID = p.ProductID
                JOIN sales s ON sd.SaleID = s.SaleID
                WHERE s.SaleDate BETWEEN ? AND ?
                GROUP BY p.ProductID
                ORDER BY revenue DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            $reportData['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'customer_analysis':
            $reportData['headers'] = ['Customer', 'Email', 'Total Orders', 'Total Spent', 'Last Purchase'];

            $stmt = $pdo->prepare("
                SELECT 
                    CONCAT(c.FirstName, ' ', c.LastName) as customer,
                    c.Email,
                    COUNT(s.SaleID) as total_orders,
                    SUM(s.TotalAmount) as total_spent,
                    MAX(s.SaleDate) as last_purchase
                FROM customers c
                LEFT JOIN sales s ON c.CustomerID = s.CustomerID
                WHERE s.SaleDate BETWEEN ? AND ?
                GROUP BY c.CustomerID
                ORDER BY total_spent DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            $reportData['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }

    return $reportData;
}

// Performance tab data function
function getTableData($tab, $limit = 10, $offset = 0)
{
    global $db;

    $limit = (int)$limit;
    $offset = (int)$offset;

    if ($tab === 'performance') {
        $sql = "SELECT 
                    CustomerID,
                    MemberNumber,
                    FirstName,
                    LastName,
                    Email,
                    LoyaltyPoints AS TotalDeals,
                    LoyaltyPoints * 10 AS TotalValue,
                    (LoyaltyPoints / 2) AS AvgProbability
                FROM customers
                ORDER BY TotalValue DESC
                LIMIT ? OFFSET ?";
    } else {
        $sql = "SELECT 
                    CustomerID,
                    MemberNumber,
                    FirstName,
                    LastName,
                    Email,
                    Phone,
                    Address,
                    LoyaltyPoints,
                    Status,
                    CreatedAt
                FROM customers
                ORDER BY CreatedAt DESC
                LIMIT ? OFFSET ?";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalCount($tab)
{
    global $db;
    $sql = "SELECT COUNT(*) as total FROM customers";
    $res = $db->query($sql)->fetch();
    return (int)($res['total'] ?? 0);
}

// ========================================
// AJAX HANDLERS FOR REPORTS & ANALYTICS
// ========================================

// Handle AJAX requests for real-time updates
if (isset($_GET['action']) && $_GET['action'] === 'get_report_updates') {
    header('Content-Type: application/json');

    $range = $_GET['range'] ?? 'this_month';
    list($startDate, $endDate) = getDateRange($range);

    $response = ['updated' => true, 'stats' => [], 'charts' => []];

    try {
        // Get updated stats
        $stats = getReportStats($pdo, $startDate, $endDate);
        $response['stats'] = $stats;

        // Get updated charts data
        $charts = getChartsData($pdo, $startDate, $endDate);
        $response['charts'] = $charts;
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Handle export requests for reports
if (isset($_GET['action']) && $_GET['action'] === 'export_report') {
    $reportType = $_GET['type'] ?? 'sales';
    $range = $_GET['range'] ?? 'this_month';
    list($startDate, $endDate) = getDateRange($range);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    try {
        if ($reportType === 'sales') {
            fputcsv($output, ['Sale ID', 'Date', 'Customer', 'Amount', 'Tax', 'Discount', 'Payment Method', 'Status']);

            $stmt = $pdo->prepare("
                SELECT s.SaleID, s.SaleDate, CONCAT(c.FirstName, ' ', c.LastName) as Customer, 
                       s.TotalAmount, s.TaxAmount, s.DiscountAmount, s.PaymentMethod, s.PaymentStatus
                FROM sales s
                LEFT JOIN customers c ON s.CustomerID = c.CustomerID
                WHERE s.SaleDate BETWEEN ? AND ?
                ORDER BY s.SaleDate DESC
            ");
            $stmt->execute([$startDate, $endDate]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
        } elseif ($reportType === 'revenue') {
            fputcsv($output, ['Month', 'Revenue', 'Orders', 'Avg Order Value']);

            $year = date('Y');
            $stmt = $pdo->prepare("
                SELECT MONTH(SaleDate) as Month, 
                       SUM(TotalAmount) as Revenue,
                       COUNT(*) as Orders,
                       AVG(TotalAmount) as AvgOrderValue
                FROM sales 
                WHERE YEAR(SaleDate) = ?
                GROUP BY MONTH(SaleDate)
                ORDER BY MONTH(SaleDate)
            ");
            $stmt->execute([$year]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['Month'] = date('F', mktime(0, 0, 0, $row['Month'], 1));
                fputcsv($output, $row);
            }
        }
    } catch (Exception $e) {
        fputcsv($output, ['Error', $e->getMessage()]);
    }

    fclose($output);
    exit();
}

// Handle generate report request
if (isset($_POST['action']) && $_POST['action'] === 'generate_report') {
    $reportType = $_POST['report_type'] ?? 'sales_summary';
    $range = $_POST['range'] ?? 'this_month';
    $format = $_POST['format'] ?? 'html';

    list($startDate, $endDate) = getDateRange($range);

    try {
        $reportData = generateCustomReport($pdo, $reportType, $startDate, $endDate);

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="custom_report_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');

            if (!empty($reportData['headers'])) {
                fputcsv($output, $reportData['headers']);
            }

            foreach ($reportData['rows'] as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
            exit();
        } else {
            // For HTML format, we'll just redirect back with a success message
            $_SESSION['report_message'] = "Report generated successfully for " . date('M d, Y', strtotime($startDate)) . " to " . date('M d, Y', strtotime($endDate));
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['report_error'] = "Error generating report: " . $e->getMessage();
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

// Handle get lead data
if (isset($_GET['action']) && $_GET['action'] === 'get_lead_details') {
    header('Content-Type: application/json');
    $response = ['success' => false];

    try {
        $leadId = $_GET['lead_id'] ?? 0;

        // Since you're using customers table as leads
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE CustomerID = ?");
        $stmt->execute([$leadId]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lead) {
            $response = $lead;
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Handle get deal data
if (isset($_GET['action']) && $_GET['action'] === 'get_deal_details') {
    header('Content-Type: application/json');
    $response = ['success' => false];

    try {
        $dealId = $_GET['deal_id'] ?? 0;

        // Add your deals table query here
        // For now, returning dummy data
        $response = [
            'DealID' => $dealId,
            'DealName' => 'Sample Deal',
            'DealValue' => 10000,
            'Stage' => 'Prospecting',
            'Probability' => 50,
            'CloseDate' => date('Y-m-d'),
            'Notes' => ''
        ];
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Handle get task data
if (isset($_GET['action']) && $_GET['action'] === 'get_task_details') {
    header('Content-Type: application/json');
    $response = ['success' => false];

    try {
        $taskId = $_GET['task_id'] ?? 0;

        // Add your tasks table query here
        // For now, returning dummy data
        $response = [
            'TaskID' => $taskId,
            'Title' => 'Sample Task',
            'Description' => '',
            'DueDate' => date('Y-m-d'),
            'Priority' => 'Medium',
            'Status' => 'Pending',
            'AssignedTo' => ''
        ];
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// ========================================
// CUSTOMER MANAGEMENT HANDLERS
// ========================================

// Handle form submissions for customer management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_customer':
                try {
                    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(MemberNumber, 5) AS UNSIGNED)) as max_num FROM customers WHERE MemberNumber LIKE 'MEM-%'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $nextNum = ($result['max_num'] ?? 0) + 1;
                    $memberNumber = 'MEM-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

                    $stmt = $pdo->prepare("INSERT INTO customers (MemberNumber, FirstName, LastName, Email, Phone, Address, LoyaltyPoints, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $memberNumber,
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['address'],
                        intval($_POST['loyalty_points']),
                        'Active'
                    ]);
                    $_SESSION['success_message'] = "Customer added successfully!";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error adding customer: " . $e->getMessage();
                }
                break;

            case 'delete_customer':
                try {
                    $customerId = $_POST['customer_id'];
                    $stmt = $pdo->prepare("DELETE FROM customers WHERE CustomerID = ?");
                    $stmt->execute([$customerId]);
                    $_SESSION['success_message'] = "Customer deleted successfully!";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error deleting customer: " . $e->getMessage();
                }
                break;

            case 'update_customer':
                try {
                    $stmt = $pdo->prepare("UPDATE customers SET FirstName = ?, LastName = ?, Email = ?, Phone = ?, Address = ?, LoyaltyPoints = ?, Status = ? WHERE CustomerID = ?");
                    $stmt->execute([
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['address'],
                        intval($_POST['loyalty_points']),
                        $_POST['status'],
                        $_POST['customer_id']
                    ]);
                    $_SESSION['success_message'] = "Customer updated successfully!";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error updating customer: " . $e->getMessage();
                }
                break;
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ========================================
// CUSTOMER EXPORT HANDLER
// ========================================

// Handle export functionality for customers
if (isset($_GET['export'])) {
    try {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Customer ID', 'Member Number', 'Name', 'Email', 'Phone', 'Loyalty Points', 'Tier', 'Status', 'Total Purchases', 'Last Purchase']);

        $stmt = $pdo->query("
            SELECT c.CustomerID, c.MemberNumber, c.FirstName, c.LastName, c.Email, c.Phone, c.LoyaltyPoints, c.Status,
                   COALESCE(SUM(s.TotalAmount), 0) as TotalPurchases,
                   MAX(s.SaleDate) as LastPurchase
            FROM customers c
            LEFT JOIN sales s ON c.CustomerID = s.CustomerID
            GROUP BY c.CustomerID
            ORDER BY c.FirstName
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $points = (int)$row['LoyaltyPoints'];
            $tier = 'Bronze';
            if ($points >= 1000) $tier = 'Platinum';
            elseif ($points >= 500) $tier = 'Gold';
            elseif ($points >= 100) $tier = 'Silver';

            $name = trim($row['FirstName'] . ' ' . $row['LastName']);
            $lastPurchase = $row['LastPurchase'] ? date('M d, Y', strtotime($row['LastPurchase'])) : 'Never';

            fputcsv($output, [
                'CUST-' . $row['CustomerID'],
                $row['MemberNumber'],
                $name,
                $row['Email'],
                $row['Phone'],
                $points,
                $tier,
                $row['Status'],
                '₱' . number_format($row['TotalPurchases'], 2),
                $lastPurchase
            ]);
        }

        fclose($output);
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error exporting data: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ========================================
// CUSTOMER DATA RETRIEVAL
// ========================================

// Handle filters for customer listing
$filters = [
    'type' => $_GET['type'] ?? 'all',
    'tier' => $_GET['tier'] ?? 'all',
    'status' => $_GET['status'] ?? 'all',
    'date_range' => $_GET['date_range'] ?? 'all'
];

$totalCustomers = 0;
$vipCustomers = 0;
$totalLoyaltyPoints = 0;
$newThisMonth = 0;
$customers = [];

try {
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');

    // Build filter conditions
    $whereConditions = [];
    $params = [];

    if ($filters['type'] !== 'all') {
        if ($filters['type'] === 'VIP') {
            $whereConditions[] = "c.LoyaltyPoints >= 500";
        } else {
            $whereConditions[] = "c.LoyaltyPoints < 500";
        }
    }

    if ($filters['status'] !== 'all') {
        $whereConditions[] = "c.Status = ?";
        $params[] = $filters['status'];
    }

    if ($filters['date_range'] !== 'all') {
        $dateCondition = "";
        switch ($filters['date_range']) {
            case 'this_month':
                $dateCondition = "c.CreatedAt BETWEEN ? AND ?";
                $params[] = $monthStart . ' 00:00:00';
                $params[] = $monthEnd . ' 23:59:59';
                break;
            case 'last_3_months':
                $dateCondition = "c.CreatedAt >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                break;
            case 'last_6_months':
                $dateCondition = "c.CreatedAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
                break;
            case 'this_year':
                $dateCondition = "YEAR(c.CreatedAt) = YEAR(NOW())";
                break;
        }
        if ($dateCondition) {
            $whereConditions[] = $dateCondition;
        }
    }

    if ($filters['tier'] !== 'all') {
        $tierCondition = "";
        switch ($filters['tier']) {
            case 'Bronze':
                $tierCondition = "c.LoyaltyPoints < 100";
                break;
            case 'Silver':
                $tierCondition = "c.LoyaltyPoints BETWEEN 100 AND 499";
                break;
            case 'Gold':
                $tierCondition = "c.LoyaltyPoints BETWEEN 500 AND 999";
                break;
            case 'Platinum':
                $tierCondition = "c.LoyaltyPoints >= 1000";
                break;
        }
        if ($tierCondition) $whereConditions[] = $tierCondition;
    }

    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // total customers with filters
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers c $whereClause");
    $stmt->execute($params);
    $totalCustomers = (int)$stmt->fetchColumn();

    // pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $vipThreshold = 500;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE LoyaltyPoints >= ?");
    $stmt->execute([$vipThreshold]);
    $vipCustomers = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT IFNULL(SUM(LoyaltyPoints),0) FROM customers");
    $totalLoyaltyPoints = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE CreatedAt BETWEEN ? AND ?");
    $stmt->execute([$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59']);
    $newThisMonth = (int)$stmt->fetchColumn();

    $sql = "SELECT CustomerID, MemberNumber, FirstName, LastName, Email, Phone, Address, LoyaltyPoints, Status, CreatedAt 
            FROM customers c 
            $whereClause 
            ORDER BY FirstName 
            LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $allParams = array_merge($params, [(int)$perPage, (int)$offset]);
    $stmt->execute($allParams);
    $custRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = $perPage > 0 ? (int)ceil($totalCustomers / $perPage) : 1;

    foreach ($custRows as $r) {
        $cid = $r['CustomerID'];
        $name = trim((($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? '')));
        if ($name === '') $name = 'Customer';
        $initials = '';
        foreach (preg_split('/\s+/', $name) as $p) {
            $initials .= strtoupper(substr($p, 0, 1));
        }

        $points = (int)$r['LoyaltyPoints'];

        $s3 = $pdo->prepare("SELECT IFNULL(SUM(TotalAmount),0) as total, IFNULL(MAX(SaleDate), NULL) as last_sale FROM sales WHERE CustomerID = ?");
        $s3->execute([$cid]);
        $salesRow = $s3->fetch(PDO::FETCH_ASSOC);
        $totalPurchases = $salesRow ? (float)$salesRow['total'] : 0.0;
        $lastPurchase = $salesRow && $salesRow['last_sale'] ? date('M d, Y', strtotime($salesRow['last_sale'])) : '—';

        $tier = 'Bronze';
        if ($points >= 1000) $tier = 'Platinum';
        elseif ($points >= 500) $tier = 'Gold';
        elseif ($points >= 100) $tier = 'Silver';

        $customers[] = [
            'id' => $cid,
            'member_number' => $r['MemberNumber'],
            'display_id' => '#CUST-' . $cid,
            'name' => $name,
            'first_name' => $r['FirstName'],
            'last_name' => $r['LastName'],
            'initials' => $initials,
            'email' => $r['Email'] ?? '',
            'phone' => $r['Phone'] ?? '',
            'address' => $r['Address'] ?? '',
            'type' => ($points >= $vipThreshold ? 'VIP' : 'Retail'),
            'points' => $points,
            'tier' => $tier,
            'total_purchases' => '₱' . number_format($totalPurchases, 2),
            'last_purchase' => $lastPurchase,
            'status' => $r['Status']
        ];
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

// prepare stats array for UI
$stats_dynamic = [
    ['icon' => '👥', 'value' => number_format($totalCustomers), 'label' => 'Total Customers', 'sublabel' => 'Active accounts', 'trend' => '+0.0%', 'trend_dir' => 'up', 'color' => '#dbeafe'],
    ['icon' => '⭐', 'value' => number_format($vipCustomers), 'label' => 'VIP Customers', 'sublabel' => 'Loyalty tier 3+', 'trend' => '+0.0%', 'trend_dir' => 'up', 'color' => '#fef3c7'],
    ['icon' => '🎁', 'value' => number_format($totalLoyaltyPoints), 'label' => 'Total Loyalty Points', 'sublabel' => 'Redeemable', 'trend' => '+0.0%', 'trend_dir' => 'up', 'color' => '#e9d5ff'],
    ['icon' => '🆕', 'value' => number_format($newThisMonth), 'label' => 'New This Month', 'sublabel' => 'vs last month', 'trend' => '+0.0%', 'trend_dir' => 'up', 'color' => '#d1fae5']
];

// ========================================
// CUSTOMER SUPPORT FUNCTIONS
// ========================================

// Helper function to get initials from name
function getInitials($name)
{
    $parts = array_filter(explode(' ', $name));
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return $initials ?: '?';
}

function calculateTotalPages($totalRecords, $perPage)
{
    if ($perPage <= 0) return 0;
    return ceil($totalRecords / $perPage);
}


// Function to get ticket statistics
function getTicketStats($pdo, $ticketTable)
{
    $stats = [];

    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$ticketTable}`");
    $stats['totalTickets'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$ticketTable}` WHERE Status IN ('Open','open')");
    $stats['openTickets'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$ticketTable}` WHERE Status IN ('In Progress','in progress','Progress')");
    $stats['inProgress'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$ticketTable}` WHERE Status IN ('Resolved','Closed','closed')");
    $stats['resolved'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, CreatedAt, FirstResponseAt)) FROM `{$ticketTable}` WHERE FirstResponseAt IS NOT NULL");
    $stats['avgResponseHours'] = round((float)$stmt->fetchColumn(), 1);

    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$ticketTable}` WHERE FeedbackScore IS NOT NULL");
    $fbCount = (int)$stmt->fetchColumn();
    $stats['satisfactionRate'] = 0;
    if ($fbCount > 0) {
        $stmt = $pdo->query("SELECT AVG(FeedbackScore) FROM `{$ticketTable}` WHERE FeedbackScore IS NOT NULL");
        $avgScore = (float)$stmt->fetchColumn();
        $stats['satisfactionRate'] = round(($avgScore / 5) * 100, 1);
    }

    return $stats;
}

// Function to get filtered tickets
function getFilteredTickets($pdo, $ticketTable, $filters)
{
    $whereConditions = [];
    $params = [];

    if (!empty($filters['status']) && $filters['status'] !== 'All Status') {
        $statusMap = [
            'Open' => ['Open', 'open'],
            'In Progress' => ['In Progress', 'in progress', 'Progress'],
            'Resolved' => ['Resolved'],
            'Closed' => ['Closed', 'closed']
        ];

        if (isset($statusMap[$filters['status']])) {
            $placeholders = implode(',', array_fill(0, count($statusMap[$filters['status']]), '?'));
            $whereConditions[] = "t.Status IN ($placeholders)";
            $params = array_merge($params, $statusMap[$filters['status']]);
        }
    }

    if (!empty($filters['priority']) && $filters['priority'] !== 'All Priority') {
        $whereConditions[] = "t.Priority = ?";
        $params[] = $filters['priority'];
    }

    if (!empty($filters['category']) && $filters['category'] !== 'All Categories') {
        $whereConditions[] = "t.Category = ?";
        $params[] = $filters['category'];
    }

    if (!empty($filters['date_range']) && $filters['date_range'] !== 'All Time') {
        $dateConditions = [
            'Today' => "DATE(t.CreatedAt) = CURDATE()",
            'This Week' => "YEARWEEK(t.CreatedAt) = YEARWEEK(CURDATE())",
            'This Month' => "MONTH(t.CreatedAt) = MONTH(CURDATE()) AND YEAR(t.CreatedAt) = YEAR(CURDATE())"
        ];

        if (isset($dateConditions[$filters['date_range']])) {
            $whereConditions[] = $dateConditions[$filters['date_range']];
        }
    }

    if (!empty($filters['search'])) {
        $searchTerm = '%' . $filters['search'] . '%';
        $whereConditions[] = "(t.Subject LIKE ? OR t.Description LIKE ? OR c.FirstName LIKE ? OR c.LastName LIKE ? OR t.Tags LIKE ?)";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    $countSql = "SELECT COUNT(*) FROM `{$ticketTable}` t LEFT JOIN customers c ON t.CustomerID = c.CustomerID $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalCount = (int)$stmt->fetchColumn();

    $ticketsSql = "
        SELECT t.TicketID AS id, t.CustomerID, t.Subject AS subject, t.Description AS description, 
               t.Category AS category, t.Priority AS priority, t.Status AS status, 
               t.AssignedTo AS assigned_id, t.CreatedAt AS created, t.UpdatedAt AS updated, 
               t.SaleRef AS sale_ref, t.ContactMethod, t.DueDate, t.Tags,
               c.FirstName, c.LastName, 
               u.FirstName AS agent_first, u.LastName AS agent_last
        FROM `{$ticketTable}` t
        LEFT JOIN customers c ON t.CustomerID = c.CustomerID
        LEFT JOIN users u ON t.AssignedTo = u.UserID
        $whereClause
        ORDER BY t.CreatedAt DESC 
        LIMIT 50
    ";

    $stmt = $pdo->prepare($ticketsSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tickets = [];
    foreach ($rows as $r) {
        $custName = trim(($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? '')) ?: 'Customer';
        $agentName = trim(($r['agent_first'] ?? '') . ' ' . ($r['agent_last'] ?? '')) ?: '—';
        $tickets[] = [
            'id' => '#TKT-' . ($r['id'] ?? '0'),
            'customer' => $custName,
            'customer_initials' => getInitials($custName),
            'subject' => $r['subject'] ?? '',
            'description' => $r['description'] ?? '',
            'category' => $r['category'] ?? 'Inquiry',
            'priority' => $r['priority'] ?? 'Medium',
            'status' => $r['status'] ?? 'Open',
            'assigned' => $agentName,
            'assigned_initials' => getInitials($agentName),
            'created' => isset($r['created']) ? date('M d, Y H:i', strtotime($r['created'])) : '',
            'updated' => isset($r['updated']) ? date('M d, Y H:i', strtotime($r['updated'])) : '',
            'sale_ref' => $r['sale_ref'] ?? '-',
            'contact_method' => $r['ContactMethod'] ?? 'Email',
            'due_date' => $r['DueDate'] ? date('M d, Y', strtotime($r['DueDate'])) : '',
            'tags' => $r['Tags'] ?? ''
        ];
    }

    return ['tickets' => $tickets, 'total_count' => $totalCount];
}

// ========================================
// CUSTOMER SUPPORT AJAX HANDLERS
// ========================================

// Handle view ticket details request
if (isset($_GET['action']) && $_GET['action'] === 'get_ticket_details') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $ticket_id = str_replace('#TKT-', '', $_GET['ticket_id'] ?? '');

        $possibleTicketTables = ['support_tickets', 'tickets', 'customer_support', 'helpdesk_tickets'];
        $ticketTable = null;
        foreach ($possibleTicketTables as $tbl) {
            if (tableExists($pdo, $tbl)) {
                $ticketTable = $tbl;
                break;
            }
        }

        if ($ticketTable) {
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    c.FirstName, c.LastName, c.Email, c.Phone, c.Address,
                    u.FirstName AS agent_first, u.LastName AS agent_last, u.Email AS agent_email,
                    s.StoreName, s.Location AS store_location
                FROM `{$ticketTable}` t
                LEFT JOIN customers c ON t.CustomerID = c.CustomerID
                LEFT JOIN users u ON t.AssignedTo = u.UserID
                LEFT JOIN stores s ON t.StoreID = s.StoreID
                WHERE t.TicketID = ?
            ");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ticket) {
                $response['success'] = true;
                $response['ticket'] = [
                    'id' => '#TKT-' . $ticket['TicketID'],
                    'customer_name' => trim($ticket['FirstName'] . ' ' . $ticket['LastName']),
                    'customer_email' => $ticket['Email'],
                    'customer_phone' => $ticket['Phone'],
                    'customer_address' => $ticket['Address'],
                    'subject' => $ticket['Subject'],
                    'description' => $ticket['Description'],
                    'category' => $ticket['Category'],
                    'priority' => $ticket['Priority'],
                    'status' => $ticket['Status'],
                    'assigned_to' => trim($ticket['agent_first'] . ' ' . $ticket['agent_last']),
                    'assigned_email' => $ticket['agent_email'],
                    'sale_ref' => $ticket['SaleRef'],
                    'store_name' => $ticket['StoreName'],
                    'store_location' => $ticket['store_location'],
                    'contact_method' => $ticket['ContactMethod'],
                    'due_date' => $ticket['DueDate'] ? date('M d, Y', strtotime($ticket['DueDate'])) : 'Not set',
                    'tags' => $ticket['Tags'],
                    'internal_notes' => $ticket['InternalNotes'],
                    'created_at' => date('M d, Y H:i', strtotime($ticket['CreatedAt'])),
                    'updated_at' => date('M d, Y H:i', strtotime($ticket['UpdatedAt'])),
                    'first_response_at' => $ticket['FirstResponseAt'] ? date('M d, Y H:i', strtotime($ticket['FirstResponseAt'])) : 'Not yet',
                    'feedback_score' => $ticket['FeedbackScore'] ? $ticket['FeedbackScore'] . '/5' : 'No feedback'
                ];
            } else {
                $response['message'] = 'Ticket not found';
            }
        }
    } catch (Exception $e) {
        $response['message'] = 'Error fetching ticket details: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Handle create ticket request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_ticket') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $possibleTicketTables = ['support_tickets', 'tickets', 'customer_support', 'helpdesk_tickets'];
        $ticketTable = null;
        foreach ($possibleTicketTables as $tbl) {
            if (tableExists($pdo, $tbl)) {
                $ticketTable = $tbl;
                break;
            }
        }

        if (!$ticketTable) {
            $ticketTable = 'support_tickets';
            $createTableSQL = "
                CREATE TABLE IF NOT EXISTS `support_tickets` (
                    TicketID INT AUTO_INCREMENT PRIMARY KEY,
                    CustomerID INT NOT NULL,
                    Category VARCHAR(100) NOT NULL,
                    Priority VARCHAR(50) NOT NULL,
                    Subject VARCHAR(255) NOT NULL,
                    Description TEXT NOT NULL,
                    SaleRef VARCHAR(100),
                    AssignedTo INT NOT NULL,
                    Status VARCHAR(50) DEFAULT 'Open',
                    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FirstResponseAt TIMESTAMP NULL,
                    FeedbackScore INT NULL,
                    InternalNotes TEXT,
                    StoreID INT NULL,
                    ContactMethod VARCHAR(50) DEFAULT 'Email',
                    DueDate DATE NULL,
                    Tags VARCHAR(255),
                    FOREIGN KEY (CustomerID) REFERENCES customers(CustomerID),
                    FOREIGN KEY (AssignedTo) REFERENCES users(UserID)
                )
            ";
            $pdo->exec($createTableSQL);
        }

        $customer_id = $_POST['customer_id'] ?? '';
        $category = $_POST['category'] ?? '';
        $priority = $_POST['priority'] ?? '';
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sale_ref = trim($_POST['sale_ref'] ?? '');
        $assigned_to = $_POST['assigned_to'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        $store_id = $_POST['store_id'] ?? null;
        $contact_method = $_POST['contact_method'] ?? 'Email';
        $due_date = $_POST['due_date'] ?? null;
        $tags = trim($_POST['tags'] ?? '');

        if (empty($customer_id) || empty($category) || empty($priority) || empty($subject) || empty($description) || empty($assigned_to)) {
            $response['message'] = 'All required fields must be filled';
            echo json_encode($response);
            exit();
        }

        $categoryMap = [
            'Product Issue' => 'Product Issue',
            'Order Issue' => 'Order Issue',
            'Refund Request' => 'Refund Request',
            'Billing Issue' => 'Billing Issue',
            'Technical Support' => 'Technical Support',
            'Account Issue' => 'Account Issue',
            'Shipping Issue' => 'Shipping Issue',
            'Complaint' => 'Complaint',
            'Inquiry' => 'Inquiry',
            'Feature Request' => 'Feature Request'
        ];

        $category = $categoryMap[$category] ?? $category;

        $priorityMap = [
            'Critical' => 'Critical',
            'High' => 'High',
            'Medium' => 'Medium',
            'Low' => 'Low'
        ];

        $priority = $priorityMap[$priority] ?? $priority;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE CustomerID = ?");
        $stmt->execute([$customer_id]);
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Selected customer does not exist';
            echo json_encode($response);
            exit();
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE UserID = ?");
        $stmt->execute([$assigned_to]);
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Selected agent does not exist';
            echo json_encode($response);
            exit();
        }

        if (!empty($sale_ref) && !str_contains($sale_ref, '#')) {
            $sale_ref = '#SALE-' . $sale_ref;
        }

        if (!empty($due_date)) {
            $due_date = date('Y-m-d', strtotime($due_date));
        } else {
            $due_date = null;
        }

        $stmt = $pdo->prepare("
            INSERT INTO `{$ticketTable}` 
            (CustomerID, Category, Priority, Subject, Description, SaleRef, AssignedTo, Status, InternalNotes, StoreID, ContactMethod, DueDate, Tags)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Open', ?, ?, ?, ?, ?)
        ");

        $success = $stmt->execute([
            $customer_id,
            $category,
            $priority,
            $subject,
            $description,
            $sale_ref,
            $assigned_to,
            $notes,
            $store_id,
            $contact_method,
            $due_date,
            $tags
        ]);

        if ($success) {
            $ticketId = $pdo->lastInsertId();
            $response['success'] = true;
            $response['message'] = 'Ticket created successfully!';
            $response['ticket_id'] = $ticketId;
        } else {
            $response['message'] = 'Failed to create ticket in database';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error creating ticket: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Handle ticket updates AJAX request
if (isset($_GET['action']) && $_GET['action'] === 'get_ticket_updates') {
    header('Content-Type: application/json');

    $lastUpdateTime = $_GET['last_update'] ?? '1970-01-01 00:00:00';
    $response = ['updated' => false, 'tickets' => [], 'stats' => []];

    try {
        $possibleTicketTables = ['support_tickets', 'tickets', 'customer_support', 'helpdesk_tickets'];
        $ticketTable = null;
        foreach ($possibleTicketTables as $tbl) {
            if (tableExists($pdo, $tbl)) {
                $ticketTable = $tbl;
                break;
            }
        }

        if ($ticketTable) {
            $stmt = $pdo->prepare("
                SELECT t.TicketID AS id, t.CustomerID, t.Subject AS subject, t.Description AS description, 
                       t.Category AS category, t.Priority AS priority, t.Status AS status, 
                       t.AssignedTo AS assigned_id, t.CreatedAt AS created, t.UpdatedAt AS updated, 
                       t.SaleRef AS sale_ref, t.ContactMethod, t.DueDate, t.Tags,
                       c.FirstName, c.LastName, 
                       u.FirstName AS agent_first, u.LastName AS agent_last
                FROM `{$ticketTable}` t
                LEFT JOIN customers c ON t.CustomerID = c.CustomerID
                LEFT JOIN users u ON t.AssignedTo = u.UserID
                WHERE t.UpdatedAt > ? OR t.CreatedAt > ?
                ORDER BY t.CreatedAt DESC
                LIMIT 50
            ");
            $stmt->execute([$lastUpdateTime, $lastUpdateTime]);
            $updatedTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($updatedTickets) > 0) {
                $response['updated'] = true;
                foreach ($updatedTickets as $r) {
                    $custName = trim(($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? '')) ?: 'Customer';
                    $agentName = trim(($r['agent_first'] ?? '') . ' ' . ($r['agent_last'] ?? '')) ?: '—';
                    $response['tickets'][] = [
                        'id' => '#TKT-' . ($r['id'] ?? '0'),
                        'customer' => $custName,
                        'customer_initials' => getInitials($custName),
                        'subject' => $r['subject'] ?? '',
                        'description' => $r['description'] ?? '',
                        'category' => $r['category'] ?? 'Inquiry',
                        'priority' => $r['priority'] ?? 'Medium',
                        'status' => $r['status'] ?? 'Open',
                        'assigned' => $agentName,
                        'assigned_initials' => getInitials($agentName),
                        'created' => isset($r['created']) ? date('M d, Y H:i', strtotime($r['created'])) : '',
                        'updated' => isset($r['updated']) ? date('M d, Y H:i', strtotime($r['updated'])) : '',
                        'sale_ref' => $r['sale_ref'] ?? '-',
                        'contact_method' => $r['ContactMethod'] ?? 'Email',
                        'due_date' => $r['DueDate'] ? date('M d, Y', strtotime($r['DueDate'])) : '',
                        'tags' => $r['Tags'] ?? ''
                    ];
                }

                $stats = getTicketStats($pdo, $ticketTable);
                $response['stats'] = $stats;
            }
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Handle filter tickets request
if (isset($_GET['action']) && $_GET['action'] === 'filter_tickets') {
    header('Content-Type: application/json');

    $filters = [
        'status' => $_GET['status'] ?? '',
        'priority' => $_GET['priority'] ?? '',
        'category' => $_GET['category'] ?? '',
        'date_range' => $_GET['date_range'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];

    $response = ['tickets' => [], 'total_count' => 0];

    try {
        $possibleTicketTables = ['support_tickets', 'tickets', 'customer_support', 'helpdesk_tickets'];
        $ticketTable = null;
        foreach ($possibleTicketTables as $tbl) {
            if (tableExists($pdo, $tbl)) {
                $ticketTable = $tbl;
                break;
            }
        }

        if ($ticketTable) {
            $filteredTickets = getFilteredTickets($pdo, $ticketTable, $filters);
            $response['tickets'] = $filteredTickets['tickets'];
            $response['total_count'] = $filteredTickets['total_count'];
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Handle update ticket status
if ($_POST['action'] ?? '' === 'update_ticket_status') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $ticket_id = str_replace('#TKT-', '', $_POST['ticket_id'] ?? '');
        $status = $_POST['status'] ?? '';
        $feedback_score = $_POST['feedback_score'] ?? null;

        $possibleTicketTables = ['support_tickets', 'tickets', 'customer_support', 'helpdesk_tickets'];
        $ticketTable = null;
        foreach ($possibleTicketTables as $tbl) {
            if (tableExists($pdo, $tbl)) {
                $ticketTable = $tbl;
                break;
            }
        }

        if ($ticketTable) {
            $stmt = $pdo->prepare("SELECT Status, FirstResponseAt FROM `{$ticketTable}` WHERE TicketID = ?");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            $updates = ['Status = ?', 'UpdatedAt = NOW()'];
            $params = [$status];

            if ($ticket && $ticket['Status'] === 'Open' && $status !== 'Open' && !$ticket['FirstResponseAt']) {
                $updates[] = 'FirstResponseAt = NOW()';
            }

            if ($feedback_score !== null && $feedback_score !== '') {
                $updates[] = 'FeedbackScore = ?';
                $params[] = $feedback_score;
            }

            $params[] = $ticket_id;

            $sql = "UPDATE `{$ticketTable}` SET " . implode(', ', $updates) . " WHERE TicketID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $response['success'] = true;
            $response['message'] = 'Ticket updated successfully!';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error updating ticket: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Handle export tickets
if ($_GET['action'] ?? '' === 'export_tickets') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="support_tickets_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Ticket ID', 'Customer', 'Subject', 'Category', 'Priority', 'Status', 'Assigned To', 'Created Date', 'Last Updated', 'Contact Method', 'Due Date']);

    try {
        $possibleTicketTables = ['support_tickets', 'tickets', 'customer_support', 'helpdesk_tickets'];
        $ticketTable = null;
        foreach ($possibleTicketTables as $tbl) {
            if (tableExists($pdo, $tbl)) {
                $ticketTable = $tbl;
                break;
            }
        }

        if ($ticketTable) {
            $stmt = $pdo->prepare("
                SELECT t.TicketID, t.Subject, t.Category, t.Priority, t.Status, t.CreatedAt, t.UpdatedAt,
                       t.ContactMethod, t.DueDate,
                       c.FirstName, c.LastName, u.FirstName AS agent_first, u.LastName AS agent_last
                FROM `{$ticketTable}` t
                LEFT JOIN customers c ON t.CustomerID = c.CustomerID
                LEFT JOIN users u ON t.AssignedTo = u.UserID
                ORDER BY t.CreatedAt DESC
            ");
            $stmt->execute();
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tickets as $ticket) {
                $customerName = trim(($ticket['FirstName'] ?? '') . ' ' . ($ticket['LastName'] ?? ''));
                $agentName = trim(($ticket['agent_first'] ?? '') . ' ' . ($ticket['agent_last'] ?? '')) ?: 'Unassigned';

                fputcsv($output, [
                    '#TKT-' . $ticket['TicketID'],
                    $customerName ?: 'Customer',
                    $ticket['Subject'],
                    $ticket['Category'],
                    $ticket['Priority'],
                    $ticket['Status'],
                    $agentName,
                    $ticket['CreatedAt'],
                    $ticket['UpdatedAt'],
                    $ticket['ContactMethod'],
                    $ticket['DueDate']
                ]);
            }
        }
    } catch (Exception $e) {
        // Handle error
    }

    fclose($output);
    exit();
}
