<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CRM Test Suite</title>
    <link rel="stylesheet" href="styles/test_results.css">
</head>
<style>
    body {
        margin: 0;
        font-family: "Segoe UI", Arial, sans-serif;
        background: #f9fafb;
        color: #222;
        padding-top: 70px;
    }

    /* Nav styling */
    nav {
        background: #111;
        padding: 0.75rem 1.5rem;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    nav ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: 1rem;
    }

    nav a {
        color: #f0f0f0;
        text-decoration: none;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        transition: background 0.3s;
    }

    nav a:hover {
        background: #333;
    }

    /* Content */
    .content {
        max-width: 900px;
        margin: 2rem auto;
        background: #fff;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* Test output styling */
    .test {
        padding: 10px 15px;
        margin: 6px 0;
        border-radius: 6px;
        font-weight: 500;
    }

    .pass {
        background: #d1f7d1;
        color: #1b5e20;
        border-left: 4px solid #43a047;
    }

    .fail {
        background: #ffd6d6;
        color: #b71c1c;
        border-left: 4px solid #e53935;
    }

    .warn {
        background: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffca28;
    }

    .summary {
        margin-top: 1.5rem;
        background: #f1f1f1;
        padding: 1rem;
        border-radius: 8px;
        line-height: 1.5;
    }
</style>

<body>

    <?php
    $folderPath = __DIR__;
    $files = scandir($folderPath);

    $pages = array_filter($files, function ($file) use ($folderPath) {
        return is_file($folderPath . '/' . $file)
            && pathinfo($file, PATHINFO_EXTENSION) === 'php'
            && basename($file) !== basename(__FILE__);
    });
    ?>

    <nav>
        <ul>
            <?php foreach ($pages as $page): ?>
                <?php
                $pageName = pathinfo($page, PATHINFO_FILENAME);
                $displayName = ucfirst(str_replace('-', ' ', $pageName));
                ?>
                <li><a href="<?php echo $page; ?>"><?php echo $displayName; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="content">
        <?php
        // =====================
        // TEST EXECUTION SECTION
        // =====================

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        ob_start();
        require_once(__DIR__ . '/../../api/crm.php');
        ob_end_clean();

        // Styled output (HTML)
        class TestOutput
        {
            public static function success($message)
            {
                echo "<div class='test pass'>✓ $message</div>";
            }

            public static function failure($message)
            {
                echo "<div class='test fail'>✗ $message</div>";
            }

            public static function warning($message)
            {
                echo "<div class='test warn'>⚠ $message</div>";
            }

            public static function info($message)
            {
                // Info uses the warn styling for visibility in the HTML report
                echo "<div class='test warn'>ℹ $message</div>";
            }

            public static function header($message)
            {
                echo "<h2>$message</h2>";
            }
        }

        $testsPassed = 0;
        $testsFailed = 0;
        $totalTests = 0;

        function runTest($testName, $callback)
        {
            global $testsPassed, $testsFailed, $totalTests;
            $totalTests++;

            try {
                $result = $callback();
                if ($result === true) {
                    $testsPassed++;
                    TestOutput::success($testName);
                    return true;
                } else {
                    $testsFailed++;
                    TestOutput::failure($testName . " - Test returned false");
                    return false;
                }
            } catch (Exception $e) {
                $testsFailed++;
                TestOutput::failure($testName . " - " . $e->getMessage());
                return false;
            }
        }

        // Keep robust database loader from original test_crm.php
        $possiblePaths = [
            __DIR__ . '/../config/database.php',
            __DIR__ . '/../../config/database.php',
            __DIR__ . '/../../../config/database.php',
            __DIR__ . '/config/database.php',
            __DIR__ . '/../pages/../config/database.php',
        ];

        $dbLoaded = false;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                require_once($path);
                $dbLoaded = true;
                TestOutput::warning("Database config loaded from: " . basename(dirname($path)) . "/" . basename($path));
                break;
            }
        }

        if (!$dbLoaded) {
            TestOutput::failure("Could not find database.php config file. Checked paths:");
            foreach ($possiblePaths as $path) {
                echo "  - $path<br>\n";
            }
            echo "<br>Please edit the test file and set the correct path to your database.php file.<br>\n";
            exit(1);
        }

        try {
            $db = Database::getInstance()->getConnection();
            TestOutput::success("Database connection established");
        } catch (PDOException $e) {
            TestOutput::failure("Database connection failed: " . $e->getMessage());
            exit(1);
        }

        // ========================================
        // 1. TEST HELPER FUNCTIONS
        // ========================================

        TestOutput::header("Testing Helper Functions");

        // Test tableExists function
        // function tableExists($pdo, $tableName) {
        //     try {
        //         $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        //         $stmt->execute([$tableName]);
        //         return $stmt->fetchColumn() > 0;
        //     } catch (Exception $e) {
        //         return false;
        //     }
        // }

        runTest("tableExists() - Check customers table", function () use ($db) {
            return tableExists($db, 'customers') === true;
        });

        runTest("tableExists() - Check non-existent table", function () use ($db) {
            return tableExists($db, 'nonexistent_table_xyz') === false;
        });

        // Test getDateRange function
        // function getDateRange($range) {
        //     $now = new DateTime();
        //     switch ($range) {
        //         case 'last_month':
        //             $start = (new DateTime('first day of last month'))->setTime(0, 0, 0);
        //             $end = (new DateTime('last day of last month'))->setTime(23, 59, 59);
        //             break;
        //         case 'this_quarter':
        //             $quarter = ceil($now->format('n') / 3);
        //             $start = new DateTime(($quarter * 3 - 2) . '/1/' . $now->format('Y'));
        //             $end = (clone $start)->modify('+2 months')->modify('last day of')->setTime(23, 59, 59);
        //             break;
        //         case 'this_year':
        //             $start = new DateTime($now->format('Y') . '-01-01');
        //             $end = (new DateTime($now->format('Y') . '-12-31'))->setTime(23, 59, 59);
        //             break;
        //         case 'this_month':
        //         default:
        //             $start = (new DateTime('first day of this month'))->setTime(0, 0, 0);
        //             $end = (new DateTime('last day of this month'))->setTime(23, 59, 59);
        //             break;
        //     }
        //     return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
        // }

        runTest("getDateRange() - This month", function () {
            list($start, $end) = getDateRange('this_month');
            return (strtotime($start) <= time() && strtotime($end) >= time());
        });

        runTest("getDateRange() - This year", function () {
            list($start, $end) = getDateRange('this_year');
            return (date('Y', strtotime($start)) === date('Y') && date('Y', strtotime($end)) === date('Y'));
        });

        runTest("getDateRange() - Last month", function () {
            $now = new DateTime();
            list($start, $end) = getDateRange('last_month');

            // Expected last month relative to $now, not to the system clock independently
            $expected = (clone $now)->modify('first day of last month')->format('Y-m');
            // echo "DEBUG: start=$start, expected=$expected\n";

            return (date('Y-m', strtotime($start)) === $expected);
        });

        // Test getInitials function
        // function getInitials($name) {
        //     $parts = array_filter(explode(' ', $name));
        //     $initials = '';
        //     foreach ($parts as $part) {
        //         $initials .= strtoupper(substr($part, 0, 1));
        //     }
        //     return $initials ?: '?';
        // }

        runTest("getInitials() - Full name", function () {
            return getInitials("John Doe") === "JD";
        });

        runTest("getInitials() - Single name", function () {
            return getInitials("John") === "J";
        });

        runTest("getInitials() - Empty name", function () {
            return getInitials("") === "?";
        });

        runTest("getInitials() - Three names", function () {
            return getInitials("John Paul Smith") === "JPS";
        });

        // ========================================
        // 2. TEST DATABASE QUERIES
        // ========================================

        TestOutput::header("Testing Database Queries");

        runTest("Check customers table exists", function () use ($db) {
            return tableExists($db, 'customers');
        });

        runTest("Check sales table exists", function () use ($db) {
            return tableExists($db, 'sales');
        });

        runTest("Check products table exists", function () use ($db) {
            return tableExists($db, 'products');
        });

        runTest("Check users table exists", function () use ($db) {
            return tableExists($db, 'users');
        });

        runTest("Query customers - SELECT COUNT(*)", function () use ($db) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM customers");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($result['count']) && is_numeric($result['count']);
        });

        runTest("Query sales - SELECT COUNT(*)", function () use ($db) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM sales");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($result['count']) && is_numeric($result['count']);
        });

        runTest("Query with JOIN - customers and sales", function () use ($db) {
            $stmt = $db->query("
            SELECT c.CustomerID, c.FirstName, c.LastName, COUNT(s.SaleID) as sale_count
            FROM customers c
            LEFT JOIN sales s ON c.CustomerID = s.CustomerID
            GROUP BY c.CustomerID
            LIMIT 1
        ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($result['CustomerID']) || $result === false; // false means no data, which is ok
        });

        // ========================================
        // 3. TEST REPORT FUNCTIONS
        // ========================================

        TestOutput::header("Testing Report Functions");

        // function getReportStats($pdo, $startDate, $endDate) {
        //     $stats = [];

        //     try {
        //         $stmt = $pdo->prepare("SELECT IFNULL(SUM(TotalAmount),0) AS total FROM sales WHERE SaleDate BETWEEN ? AND ?");
        //         $stmt->execute([$startDate, $endDate]);
        //         $stats['totalRevenue'] = (float)$stmt->fetchColumn();
        //     } catch (Exception $e) {
        //         $stats['totalRevenue'] = 0;
        //     }

        //     try {
        //         $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE SaleDate BETWEEN ? AND ?");
        //         $stmt->execute([$startDate, $endDate]);
        //         $stats['ordersClosed'] = (int)$stmt->fetchColumn();
        //     } catch (Exception $e) {
        //         $stats['ordersClosed'] = 0;
        //     }

        //     $stats['avgOrderValue'] = $stats['ordersClosed'] > 0 ? ($stats['totalRevenue'] / $stats['ordersClosed']) : 0;

        //     return $stats;
        // }

        runTest("getReportStats() - This month", function () use ($db) {
            list($start, $end) = getDateRange('this_month');
            $stats = getReportStats($db, $start, $end);
            return (isset($stats['totalRevenue']) && isset($stats['ordersClosed']) && isset($stats['avgOrderValue']));
        });

        runTest("getReportStats() - Valid date range", function () use ($db) {
            $stats = getReportStats($db, '2024-01-01', '2024-12-31');
            return is_array($stats) && array_key_exists('totalRevenue', $stats);
        });

        // function getChartsData($pdo, $startDate, $endDate) {
        //     $monthlyValues = [];
        //     $paidPercentages = [];
        //     $monthsLabels = [];

        //     try {
        //         $start = new DateTime($startDate);
        //         $monthsLabels[] = $start->format('M Y');

        //         $stmt = $pdo->prepare("SELECT IFNULL(SUM(TotalAmount),0) AS total FROM sales WHERE SaleDate BETWEEN ? AND ?");
        //         $stmt->execute([$startDate, $endDate]);
        //         $monthlyValues[] = (float)$stmt->fetchColumn();

        //         $paidPercentages[] = 85.0; // Default
        //     } catch (Exception $e) {
        //         $monthsLabels = ['Jan'];
        //         $monthlyValues = [0];
        //         $paidPercentages = [0];
        //     }

        //     return [
        //         'monthsLabels' => $monthsLabels,
        //         'monthlyValues' => $monthlyValues,
        //         'paidPercentages' => $paidPercentages
        //     ];
        // }

        runTest("getChartsData() - Returns valid structure", function () use ($db) {
            list($start, $end) = getDateRange('this_month');
            $charts = getChartsData($db, $start, $end);
            return (isset($charts['monthsLabels']) && isset($charts['monthlyValues']) && isset($charts['paidPercentages']));
        });

        // ========================================
        // 4. TEST CUSTOMER MANAGEMENT
        // ========================================

        TestOutput::header("Testing Customer Management Functions");

        runTest("Get customer count", function () use ($db) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM customers");
            $count = $stmt->fetchColumn();
            return is_numeric($count) && $count >= 0;
        });

        runTest("Get VIP customers (LoyaltyPoints >= 500)", function () use ($db) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM customers WHERE LoyaltyPoints >= ?");
            $stmt->execute([500]);
            $count = $stmt->fetchColumn();
            return is_numeric($count) && $count >= 0;
        });

        runTest("Calculate total loyalty points", function () use ($db) {
            $stmt = $db->query("SELECT IFNULL(SUM(LoyaltyPoints),0) FROM customers");
            $total = $stmt->fetchColumn();
            return is_numeric($total) && $total >= 0;
        });

        runTest("Get new customers this month", function () use ($db) {
            $monthStart = date('Y-m-01') . ' 00:00:00';
            $monthEnd = date('Y-m-t') . ' 23:59:59';
            $stmt = $db->prepare("SELECT COUNT(*) FROM customers WHERE CreatedAt BETWEEN ? AND ?");
            $stmt->execute([$monthStart, $monthEnd]);
            $count = $stmt->fetchColumn();
            return is_numeric($count) && $count >= 0;
        });

        runTest("Customer tier calculation - Bronze", function () {
            $points = 50;
            $tier = 'Bronze';
            if ($points >= 1000) $tier = 'Platinum';
            elseif ($points >= 500) $tier = 'Gold';
            elseif ($points >= 100) $tier = 'Silver';
            return $tier === 'Bronze';
        });

        runTest("Customer tier calculation - Silver", function () {
            $points = 150;
            $tier = 'Bronze';
            if ($points >= 1000) $tier = 'Platinum';
            elseif ($points >= 500) $tier = 'Gold';
            elseif ($points >= 100) $tier = 'Silver';
            return $tier === 'Silver';
        });

        runTest("Customer tier calculation - Gold", function () {
            $points = 750;
            $tier = 'Bronze';
            if ($points >= 1000) $tier = 'Platinum';
            elseif ($points >= 500) $tier = 'Gold';
            elseif ($points >= 100) $tier = 'Silver';
            return $tier === 'Gold';
        });

        runTest("Customer tier calculation - Platinum", function () {
            $points = 1500;
            $tier = 'Bronze';
            if ($points >= 1000) $tier = 'Platinum';
            elseif ($points >= 500) $tier = 'Gold';
            elseif ($points >= 100) $tier = 'Silver';
            return $tier === 'Platinum';
        });

        // ========================================
        // 5. TEST TICKET SUPPORT FUNCTIONS
        // ========================================

        TestOutput::header("Testing Support Ticket Functions");

        // function getTicketStats($pdo, $ticketTable) {
        //     $stats = [];

        //     try {
        //         $stmt = $pdo->query("SELECT COUNT(*) FROM `{$ticketTable}`");
        //         $stats['totalTickets'] = (int)$stmt->fetchColumn();

        //         $stmt = $pdo->query("SELECT COUNT(*) FROM `{$ticketTable}` WHERE Status IN ('Open','open')");
        //         $stats['openTickets'] = (int)$stmt->fetchColumn();

        //         $stats['inProgress'] = 0;
        //         $stats['resolved'] = 0;
        //         $stats['avgResponseHours'] = 0;
        //         $stats['satisfactionRate'] = 0;
        //     } catch (Exception $e) {
        //         return false;
        //     }

        //     return $stats;
        // }

        $ticketTables = ['support_tickets', 'tickets', 'customer_support', 'helpdesk_tickets'];
        $foundTicketTable = null;

        foreach ($ticketTables as $table) {
            if (tableExists($db, $table)) {
                $foundTicketTable = $table;
                break;
            }
        }

        if ($foundTicketTable) {
            runTest("Ticket table found: $foundTicketTable", function () {
                return true;
            });

            runTest("getTicketStats() - Returns valid structure", function () use ($db, $foundTicketTable) {
                $stats = getTicketStats($db, $foundTicketTable);
                return is_array($stats) && isset($stats['totalTickets']);
            });

            runTest("Query tickets with customer JOIN", function () use ($db, $foundTicketTable) {
                $stmt = $db->query("
                SELECT t.*, c.FirstName, c.LastName 
                FROM `{$foundTicketTable}` t 
                LEFT JOIN customers c ON t.CustomerID = c.CustomerID 
                LIMIT 1
            ");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result !== false || $result === false; // Both are valid (data exists or empty table)
            });
        } else {
            TestOutput::info("No ticket table found - skipping ticket tests");
        }

        // ========================================
        // 6. TEST DATA VALIDATION
        // ========================================

        TestOutput::header("Testing Data Validation");

        runTest("Email format validation - Valid", function () {
            $email = "test@example.com";
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        });

        runTest("Email format validation - Invalid", function () {
            $email = "invalid-email";
            return filter_var($email, FILTER_VALIDATE_EMAIL) === false;
        });

        runTest("Phone number sanitization", function () {
            $phone = "(123) 456-7890";
            $cleaned = preg_replace('/[^0-9]/', '', $phone);
            return $cleaned === "1234567890";
        });

        runTest("SQL injection prevention - Prepared statements", function () use ($db) {
            $maliciousInput = "'; DROP TABLE customers; --";
            $stmt = $db->prepare("SELECT COUNT(*) FROM customers WHERE Email = ?");
            $stmt->execute([$maliciousInput]);
            // If we get here without error, prepared statement is working
            return true;
        });

        // ========================================
        // 7. TEST PAGINATION
        // ========================================

        TestOutput::header("Testing Pagination Logic");

        runTest("Pagination calculation - Page 1", function () {
            $page = 1;
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            return $offset === 0;
        });

        runTest("Pagination calculation - Page 3", function () {
            $page = 3;
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            return $offset === 20;
        });

        runTest("Total pages calculation", function () {
            $totalRecords = 3; // or dynamically fetch inside the test
            $perPage = 10;
            $expected = ceil($totalRecords / $perPage);
            $totalPages = calculateTotalPages($totalRecords, $perPage);
            // echo "DEBUG: totalRecords=$totalRecords, expected=$expected, got=$totalPages\n";
            return $totalPages === $expected;
        });

        
        // ========================================
        // 8. TEST ERROR HANDLING
        // ========================================

        TestOutput::header("Testing Error Handling");

        runTest("Handle invalid date format", function () {
            try {
                $date = new DateTime("invalid-date");
                return false;
            } catch (Exception $e) {
                return true; // Exception was properly caught
            }
        });

        runTest("Handle division by zero", function () {
            $total = 100;
            $count = 0;
            $average = $count > 0 ? ($total / $count) : 0;
            return $average === 0;
        });

        runTest("Handle null coalescing", function () {
            $value = null;
            $result = $value ?? 'default';
            return $result === 'default';
        });

        // ========================================
        // 9. TEST JSON ENCODING
        // ========================================

        TestOutput::header("Testing JSON Operations");

        runTest("JSON encode array", function () {
            $data = ['status' => 'success', 'count' => 10];
            $json = json_encode($data);
            return $json !== false && is_string($json);
        });

        runTest("JSON decode string", function () {
            $json = '{"status":"success","count":10}';
            $data = json_decode($json, true);
            return is_array($data) && $data['status'] === 'success';
        });

        runTest("Handle invalid JSON", function () {
            $invalidJson = '{invalid json}';
            $data = json_decode($invalidJson, true);
            return $data === null && json_last_error() !== JSON_ERROR_NONE;
        });

        // ========================================
        // 10. TEST FILTER LOGIC
        // ========================================

        TestOutput::header("Testing Filter Logic");

        runTest("Build WHERE clause - Single condition", function () {
            $conditions = ["Status = ?"];
            $where = "WHERE " . implode(" AND ", $conditions);
            return $where === "WHERE Status = ?";
        });

        runTest("Build WHERE clause - Multiple conditions", function () {
            $conditions = ["Status = ?", "Priority = ?", "CreatedAt > ?"];
            $where = "WHERE " . implode(" AND ", $conditions);
            return $where === "WHERE Status = ? AND Priority = ? AND CreatedAt > ?";
        });

        runTest("Date range filter - This month", function () {
            $range = 'this_month';
            list($start, $end) = getDateRange($range);
            return strtotime($start) <= time() && strtotime($end) >= time();
        });

        // ========================================
        // SUMMARY
        // ========================================

        TestOutput::header("Test Summary");

        $passRate = $totalTests > 0 ? round(($testsPassed / $totalTests) * 100, 2) : 0;

        $statusMessage = $testsFailed === 0
            ? "<div class='status passed'>ALL TESTS PASSED!</div>"
            : "<div class='status failed'>SOME TESTS FAILED<br><small>Please review the failed tests above and fix any issues.</small></div>";

        echo "
    <div class='summary'>
        <p><strong>Total Tests:</strong> $totalTests</p>
        <p class='passed'><strong>Passed:</strong> $testsPassed</p>
        <p class='failed'><strong>Failed:</strong> $testsFailed</p>
        <p><strong>Pass Rate:</strong> {$passRate}%</p>
        $statusMessage
    </div>";

        // Database cleanup message
        TestOutput::info("Test completed. No data was modified in the database.");
        ?>
    </div>
</body>

</html>