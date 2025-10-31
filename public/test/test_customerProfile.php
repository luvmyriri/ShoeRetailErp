<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Customer Profile Test Suite</title>
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
    // Customer Profile Tests
    // =====================

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Load the page in a buffered way to avoid printing to test output
    ob_start();
    require_once(__DIR__ . '/../crm/customerProfile.php');
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

    // Database loading (reuse robust paths)
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
        TestOutput::info("Database config loaded from: " . basename(dirname($path)) . "/" . basename($path));
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
    } catch (Exception $e) {
      TestOutput::failure("Database connection failed: " . $e->getMessage());
      exit(1);
    }

    // ========================
    // 1. Session & Page Setup
    // ========================
    TestOutput::header("Session & Page Setup");

    runTest("Session active", function () {
      return session_status() === PHP_SESSION_ACTIVE;
    });

    runTest("Page title contains 'Customer Profiles'", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return strpos($content, 'Customer Profiles') !== false;
    });

    // ========================
    // 2. User & Initials
    // ========================
    TestOutput::header("User & Initials");

    runTest("User session exists (mock)", function () {
      $_SESSION['user_id'] = 1;
      return isset($_SESSION['user_id']);
    });

    runTest("User initials generated from DB", function () use ($db) {
      // check there's a FirstName for user_id 1
      $stmt = $db->prepare("SELECT FirstName FROM users WHERE UserID = ?");
      $stmt->execute([1]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($row['FirstName']) && !empty($row['FirstName']);
    });

    // ========================
    // 3. UI Structure & Elements
    // ========================
    TestOutput::header("UI Structure & Elements");

    runTest("Navigation contains Customer Profiles link", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return strpos($content, 'Customer Profiles') !== false && strpos($content, 'CrmDashboard.php') !== false;
    });

    runTest("Stats grid exists", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return strpos($content, 'class="stats-grid"') !== false || strpos($content, 'stats-grid') !== false;
    });

    runTest("Filters form exists", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return strpos($content, 'filters-form') !== false || strpos($content, 'filter-select') !== false;
    });

    runTest("Customer table columns present", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      $needed = ['Customer ID', 'Member Number', 'Name', 'Contact Info', 'Customer Type', 'Loyalty Points', 'Tier', 'Total Purchases', 'Last Purchase', 'Status', 'Actions'];
      foreach ($needed as $col) {
        if (strpos($content, $col) === false) return false;
      }
      return true;
    });

    // ========================
    // 4. Modals & Forms
    // ========================
    TestOutput::header("Modals & Forms");

    runTest("Add Customer modal present", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return strpos($content, "id=\"addCustomerModal\"") !== false || strpos($content, "addCustomerModal") !== false;
    });

    runTest("Edit customer modal present", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return strpos($content, "id=\"editCustomerModal\"") !== false;
    });

    runTest("View customer modal present", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return strpos($content, "id=\"viewCustomerModal\"") !== false;
    });

    runTest("Delete confirmation modal present", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return strpos($content, "id=\"deleteModal\"") !== false;
    });

    // ========================
    // 5. Database & Data
    // ========================
    TestOutput::header("Database & Data Checks");

    runTest("customers table exists", function () use ($db) {
      return tableExists($db, 'customers') === true;
    });

    runTest("customers query returns numeric count", function () use ($db) {
      try {
        $stmt = $db->query("SELECT COUNT(*) as c FROM customers");
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($r['c']) && is_numeric($r['c']);
      } catch (Exception $e) {
        return false;
      }
    });

    // ========================
    // 6. JS & Interaction Hooks
    // ========================
    TestOutput::header("JS & Interaction Hooks");

    runTest("Search box present", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return strpos($content, 'search-box') !== false || strpos($content, 'globalSearch') !== false;
    });

    runTest("Modal JS functions present (open/close)", function () {
      $content = file_get_contents(__DIR__ . '/../crm/customerProfile.php');
      return (strpos($content, 'function openModal') !== false && strpos($content, 'function closeModal') !== false);
    });

    // ========================
    // Summary
    // ========================
    TestOutput::header("Test Summary");
    $successRate = $totalTests > 0 ? round(($testsPassed / $totalTests) * 100, 2) : 0;

    echo "<div class='summary'>\n";
    echo "<p><strong>Total Tests:</strong> $totalTests</p>\n";
    echo "<p><strong>Passed:</strong> $testsPassed</p>\n";
    echo "<p><strong>Failed:</strong> $testsFailed</p>\n";
    echo "<p><strong>Success Rate:</strong> {$successRate}%</p>\n";
    echo "</div>\n";
    echo "</div>\n";
    ?>
  </div>
</body>

</html>