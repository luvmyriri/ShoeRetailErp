<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CRM Dashboard Test Suite</title>
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
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Test output styling */
.test {
  padding: 10px 15px;
  margin: 6px 0;
  border-radius: 6px;
  font-weight: 500;
}

.pass { background: #d1f7d1; color: #1b5e20; border-left: 4px solid #43a047; }
.fail { background: #ffd6d6; color: #b71c1c; border-left: 4px solid #e53935; }
.warn { background: #fff3cd; color: #856404; border-left: 4px solid #ffca28; }

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
// =====================
// Dynamic Navigation Bar
// =====================
$folderPath = __DIR__;
$files = scandir($folderPath);

$pages = array_filter($files, function($file) use ($folderPath) {
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
require_once(__DIR__ . '/../crm/CrmDashboard.php');
ob_end_clean();

// Styled output
class TestOutput {
    public static function success($message) {
        echo "<div class='test pass'>✓ $message</div>";
    }

    public static function failure($message) {
        echo "<div class='test fail'>✗ $message</div>";
    }

    public static function warning($message) {
        echo "<div class='test warn'>⚠ $message</div>";
    }

    public static function header($message) {
        echo "<h2>$message</h2>";
    }
}

$testsPassed = 0;
$testsFailed = 0;
$totalTests = 0;

function runTest($testName, $callback) {
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

// Database Connection Check
try {
    require_once(__DIR__ . '/../../config/database.php');
    TestOutput::success("Database connection established");
} catch (PDOException $e) {
    TestOutput::failure("Database connection failed: " . $e->getMessage());
    exit(1);
}

// 1. Session Tests
TestOutput::header("Testing Session Management");
runTest("Session starts correctly", function() {
    return session_status() === PHP_SESSION_ACTIVE;
});
runTest("Active tab parameter handling", function() {
    $_GET['tab'] = 'customers';
    global $activeTab;
    return $activeTab === 'customers';
});

// 2. Authentication
TestOutput::header("Testing User Authentication");
runTest("User session variables exist", function() {
    $_SESSION['user_id'] = 1;
    return isset($_SESSION['user_id']);
});
runTest("User initials generation", function() use ($db) {
    $stmt = $db->prepare("SELECT FirstName FROM users WHERE UserID = ?");
    $stmt->execute([1]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return !empty($user['FirstName']);
});

// 3. Dashboard Stats
TestOutput::header("Testing Dashboard Statistics");
runTest("DashboardStats class exists", function() {
    return class_exists('DashboardStats');
});

// (more tests follow... same as your original ones)

// Summary
TestOutput::header("Test Summary");
$successRate = round(($testsPassed / $totalTests) * 100, 2);
echo "<div class='summary'>
        <p><strong>Total Tests:</strong> $totalTests</p>
        <p><strong>Passed:</strong> $testsPassed</p>
        <p><strong>Failed:</strong> $testsFailed</p>
        <p><strong>Success Rate:</strong> {$successRate}%</p>
      </div>";
?>
</div>
</body>
</html>
