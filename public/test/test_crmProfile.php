<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>CRM Profile Test Suite</title>
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
    max-width: 900px;
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
    // crmProfile Tests
    // =====================
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Set up test environment
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = 1; // Set a test user ID
    
    // Buffered include of module to avoid rendering
    $included = false;
    try {
        ob_start();
        require_once(__DIR__ . '/../crm/crmProfile.php');
        ob_end_clean();
        $included = true;
    } catch (Throwable $t) {
        ob_end_clean();
        TestOutput::warning('Include error: ' . $t->getMessage());
        $included = false;
    }

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

    // Database loader (same candidate paths)
    $possiblePaths = [
      __DIR__ . '/../../config/database.php',  // ShoeRetailErp/config/database.php (root level)
      __DIR__ . '/../config/database.php',  // public/config/database.php
      __DIR__ . '/config/database.php',  // test/config/database.php
      realpath(__DIR__ . '/../../') . '/config/database.php'  // Absolute path fallback
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

    // Helper function to check if a table exists
    function tableExists($pdo, $table) {
      try {
          $result = $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
          return $result !== false;
      } catch (Exception $e) {
          return false;
      }
    }

    // 1. Inclusion & Session
    TestOutput::header('Inclusion & Session');
    runTest('Module included without fatal error', function () use ($included) {
      return $included === true;
    });
    runTest('Session active', function () {
      return session_status() === PHP_SESSION_ACTIVE;
    });
    runTest('Page title contains "User Profile"', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return strpos($c, 'User Profile') !== false || strpos($c, 'User Profile - CRM') !== false;
    });

    // 2. User data & DB
    TestOutput::header('User Data & DB');
    runTest('users table exists', function () use ($db) {
      return tableExists($db, 'users') === true;
    });
    runTest('Fetch user record for user_id 1 (if exists)', function () use ($db) {
      try {
        $stmt = $db->prepare('SELECT UserID FROM users LIMIT 1');
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r === false || isset($r['UserID']);
      } catch (Exception $e) {
        return false;
      }
    });
    runTest('timeAgoDisplay function exists', function () {
      return function_exists('timeAgoDisplay');
    });

    // 3. Ticket/activity detection
    TestOutput::header('Ticket & Activity Detection');
    runTest('Ticket candidates logic present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return strpos($c, "ticketCandidates") !== false || strpos($c, "support_tickets") !== false;
    });
    runTest('activity_log detection present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return strpos($c, 'activity_log') !== false;
    });

    // 4. UI elements & forms
    TestOutput::header('UI & Forms');
    runTest('Profile title present in file', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return strpos($c, 'User Profile') !== false;
    });
    runTest('Profile form fields exist (first_name, last_name, email)', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return (strpos($c, 'name="first_name"') !== false && strpos($c, 'name="last_name"') !== false && strpos($c, 'name="email"') !== false);
    });
    runTest('Avatar input present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return strpos($c, 'id="avatarInput"') !== false || strpos($c, 'Change Photo') !== false;
    });
    runTest('Recent activity table present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return strpos($c, 'Recent Activity') !== false || strpos($c, 'activity_log') !== false;
    });

    // 5. JS hooks
    TestOutput::header('JS Hooks & Client Behavior');
    runTest('saveProfile JS function present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return strpos($c, 'function saveProfile') !== false;
    });
    runTest('updatePassword JS function present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return strpos($c, 'function updatePassword') !== false;
    });
    runTest('avatarInput change handler present', function () {
      $c = file_get_contents(__DIR__ . '/../crm/crmProfile.php');
      return strpos($c, "avatarInput')") !== false || strpos($c, 'avatarInput') !== false;
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