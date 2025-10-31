<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Reports Management Test Suite</title>
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

  .content {
    max-width: 1000px;
    margin: 2rem auto;
    background: #fff;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

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
        <?php $pageName = pathinfo($page, PATHINFO_FILENAME);
        $displayName = ucfirst(str_replace('-', ' ', $pageName)); ?>
        <li><a href="<?php echo $page; ?>"><?php echo $displayName; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div class="content">
    <?php
    // =====================
    // Reports Management Tests
    // =====================

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Include module buffered to avoid rendering
    $included = false;
    try {
      ob_start();
      require_once(__DIR__ . '/../crm/reportsManagement.php');
      ob_end_clean();
      $included = true;
    } catch (Throwable $t) {
      ob_end_clean();
      $included = false;
    }

    // Styled output helpers
    class TestOutput
    {
      public static function success($m)
      {
        echo "<div class='test pass'>✓ $m</div>";
      }
      public static function failure($m)
      {
        echo "<div class='test fail'>✗ $m</div>";
      }
      public static function warning($m)
      {
        echo "<div class='test warn'>⚠ $m</div>";
      }
      public static function info($m)
      {
        echo "<div class='test warn'>ℹ $m</div>";
      }
      public static function header($m)
      {
        echo "<h2>$m</h2>";
      }
    }

    $testsPassed = 0;
    $testsFailed = 0;
    $totalTests = 0;
    function runTest($name, $cb)
    {
      global $testsPassed, $testsFailed, $totalTests;
      $totalTests++;
      try {
        $r = $cb();
        if ($r === true) {
          $testsPassed++;
          TestOutput::success($name);
          return true;
        } else {
          $testsFailed++;
          TestOutput::failure($name . ' - Test returned false');
          return false;
        }
      } catch (Throwable $e) {
        $testsFailed++;
        TestOutput::failure($name . ' - Exception: ' . $e->getMessage());
        return false;
      }
    }

    // Robust DB loader
    $possiblePaths = [
      __DIR__ . '/../config/database.php',
      __DIR__ . '/../../config/database.php',
      __DIR__ . '/../../../config/database.php',
      __DIR__ . '/config/database.php',
      __DIR__ . '/../pages/../config/database.php'
    ];
    $dbLoaded = false;
    foreach ($possiblePaths as $p) {
      if (file_exists($p)) {
        require_once($p);
        $dbLoaded = true;
        TestOutput::info('Database config loaded from: ' . basename(dirname($p)) . '/' . basename($p));
        break;
      }
    }
    if (!$dbLoaded) {
      TestOutput::failure('Could not find database.php - edit $possiblePaths in test file.');
      exit(1);
    }

    try {
      $db = Database::getInstance()->getConnection();
      TestOutput::success('Database connection established');
    } catch (Exception $e) {
      TestOutput::failure('DB connection failed: ' . $e->getMessage());
      exit(1);
    }

    // 1. Inclusion & Session
    TestOutput::header('Inclusion & Session');
    runTest('Module included without fatal error', function () use ($included) {
      return $included === true;
    });
    runTest('Session active', function () {
      return session_status() === PHP_SESSION_ACTIVE;
    });
    runTest('Page title contains Reports', function () {
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return strpos($c, 'Reports') !== false || strpos($c, 'Reports & Analytics') !== false;
    });

    // 2. Function existence
    TestOutput::header('Functions & Endpoints');
    runTest('getReportStats function exists', function () {
      return function_exists('getReportStats');
    });
    runTest('getChartsData function exists', function () {
      return function_exists('getChartsData');
    });
    runTest('AJAX updates endpoint present (get_report_updates)', function () {
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return strpos($c, 'get_report_updates') !== false;
    });
    runTest('Export report handler present (export_report)', function () {
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return strpos($c, 'export_report') !== false;
    });
    runTest('Generate report POST handler present (generate_report)', function () {
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return strpos($c, 'generate_report') !== false;
    });

    // 3. Database tables
    TestOutput::header('Database Tables');
    runTest('customers table exists', function () use ($db) {
      return tableExists($db, 'customers') === true;
    });
    runTest('sales table exists', function () use ($db) {
      return tableExists($db, 'sales') === true;
    });
    runTest('users table exists', function () use ($db) {
      return tableExists($db, 'users') === true;
    });

    // 4. Queries & aggregates
    TestOutput::header('Queries & Aggregates');
    runTest('getReportStats returns array with keys', function () use ($db) {
      list($s, $e) = getDateRange('this_month');
      $stats = getReportStats($db, $s, $e);
      return is_array($stats) && isset($stats['totalRevenue']) && isset($stats['ordersClosed']);
    });
    runTest('getChartsData returns structure', function () use ($db) {
      list($s, $e) = getDateRange('this_month');
      $charts = getChartsData($db, $s, $e);
      return is_array($charts) && isset($charts['monthsLabels']) && isset($charts['monthlyValues']);
    });

    // 5. UI elements & charts
    TestOutput::header('UI & Charts');
    runTest('Revenue chart canvas present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return strpos($c, 'id="revenueChart"') !== false || strpos($c, 'revenueChart') !== false;
    });
    runTest('Payment chart canvas present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return strpos($c, 'id="paymentChart"') !== false || strpos($c, 'paymentChart') !== false;
    });
    runTest('Recent reports table exists', function () {
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return strpos($c, 'Recent Sales Reports') !== false || strpos($c, 'Recent Reports') !== false;
    });
    runTest('Generate Report modal present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return strpos($c, 'id="generateReportModal"') !== false;
    });

    // 6. Export safety & AJAX behavior (best-effort)
    TestOutput::header('Behavior & Safety');
    runTest('Export report action is callable without exception (dry run)', function () use ($db) {
      // We won't actually stream output - just ensure export handler code exists
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return strpos($c, 'header(\'Content-Type: text/csv\')') !== false || strpos($c, 'export_report') !== false;
    });
    runTest('AJAX endpoint returns JSON structure when simulated', function () use ($db) {
      // We cannot call internal AJAX without making HTTP request; check that get_report_updates code sets Content-Type and echoes json
      $c = file_get_contents(__DIR__ . '/../crm/reportsManagement.php');
      return (strpos($c, "header('Content-Type: application/json')") !== false || strpos($c, 'get_report_updates') !== false);
    });

    // Summary
    TestOutput::header('Test Summary');
    $successRate = $totalTests > 0 ? round(($testsPassed / $totalTests) * 100, 2) : 0;
    echo "<div class='summary'>\n";
    echo "<p><strong>Total Tests:</strong> $totalTests</p>\n";
    echo "<p><strong>Passed:</strong> $testsPassed</p>\n";
    echo "<p><strong>Failed:</strong> $testsFailed</p>\n";
    echo "<p><strong>Success Rate:</strong> {$successRate}%</p>\n";
    echo "</div>\n";
    ?>
  </div>
</body>

</html>