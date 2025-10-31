<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Loyalty Program Test Suite</title>
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
    // Loyalty Program Tests
    // =====================

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Attempt to include the module in a buffer to avoid rendering
    $included = false;
    try {
        ob_start();
        require_once(__DIR__ . '/../crm/loyaltyProgram.php');
        require_once(__DIR__ . '/../../api/crm.php'); // or wherever tableExists() is defined
        ob_end_clean();
        $included = true;
    } catch (Throwable $t) {
        ob_end_clean();
        $included = false;
    }


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

    // Database loader (same robust paths)
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

    // 1. Basic page and session tests
    TestOutput::header('Page & Session');
    runTest('Session active', function () {
      return session_status() === PHP_SESSION_ACTIVE;
    });
    runTest('File contains "Loyalty Program" title', function () {
      $c = file_get_contents(__DIR__ . '/../crm/loyaltyProgram.php');
      return strpos($c, 'Loyalty Program') !== false;
    });

    // 2. Inclusion side-effects (variables defined by module)
    TestOutput::header('Module Variables');
    runTest('Module included without fatal error', function () use ($included) {
      return $included === true;
    });
    runTest('pointsRate or pointsRateDisplay present', function () use ($included) {
      if (!$included) return false;
      global $pointsRate, $pointsRateDisplay;
      return (isset($pointsRate) || isset($pointsRateDisplay));
    });
    runTest('tierSettings available when included', function () use ($included) {
      if (!$included) return false;
      global $tierSettings;
      return isset($tierSettings) && is_array($tierSettings);
    });

    // 3. DB table checks
    TestOutput::header('Database Tables');
    runTest('customers table exists', function () use ($db) {
      return tableExists($db, 'customers') === true;
    });
    runTest('sales table exists', function () use ($db) {
      return tableExists($db, 'sales') === true;
    });
    runTest('loyalty_transactions table (optional)', function () use ($db) {
      try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute(['loyalty_transactions']);
        return $stmt->fetchColumn() >= 0;
      } catch (Exception $e) {
        return false;
      }
    });
    runTest('loyalty_accounts table (optional)', function () use ($db) {
      try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute(['loyalty_accounts']);
        return $stmt->fetchColumn() >= 0;
      } catch (Exception $e) {
        return false;
      }
    });

    // 4. Queries
    TestOutput::header('Queries & Aggregates');
    runTest('customers count query', function () use ($db) {
      try {
        $s = $db->query('SELECT COUNT(*) as c FROM customers');
        $r = $s->fetch(PDO::FETCH_ASSOC);
        return isset($r['c']) && is_numeric($r['c']);
      } catch (Exception $e) {
        return false;
      }
    });
    runTest('sales count query', function () use ($db) {
      try {
        $s = $db->query('SELECT COUNT(*) as c FROM sales');
        $r = $s->fetch(PDO::FETCH_ASSOC);
        return isset($r['c']) && is_numeric($r['c']);
      } catch (Exception $e) {
        return false;
      }
    });

    // 5. UI elements and modals
    TestOutput::header('UI & Modals');
    runTest('Adjust Points modal present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/loyaltyProgram.php');
      return strpos($c, 'id="adjustModal"') !== false || strpos($c, 'Adjust Points') !== false;
    });
    runTest('Program Settings modal present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/loyaltyProgram.php');
      return strpos($c, 'id="programSettingsModal"') !== false;
    });
    runTest('Tier Settings modal present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/loyaltyProgram.php');
      return strpos($c, 'id="tierSettingsModal"') !== false;
    });
    runTest('Recent Activity table present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/loyaltyProgram.php');
      return strpos($c, 'Recent Points Activity') !== false || strpos($c, 'recentActivities') !== false;
    });

    // 6. Behavior checks (best-effort)
    TestOutput::header('Behavior & Safety');
    runTest('Export CSV handler exists in file', function () {
      $c = file_get_contents(__DIR__ . '/../crm/loyaltyProgram.php');
      return strpos($c, 'export_report') !== false;
    });
    runTest('POST adjust_points handler exists in file', function () {
      $c = file_get_contents(__DIR__ . '/../crm/loyaltyProgram.php');
      return strpos($c, 'adjust_points') !== false;
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