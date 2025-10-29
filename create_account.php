<?php
// create_account.php
require_once 'includes/core_functions.php';

// === NO ADMIN CHECK ===
// Anyone can access this page (even guests)
// If you want *only logged-in users* (but not admin), uncomment the line below:
// if (!isLoggedIn()) { header('Location: login.php'); exit; }

$error = '';
$success = '';

// Get stores for dropdown
$stores = getAllStores();

if ($_POST && isset($_POST['username'], $_POST['password'], $_POST['first_name'], $_POST['role'])) {
    $username    = sanitizeInput($_POST['username']);
    $password    = $_POST['password'];
    $first_name  = sanitizeInput($_POST['first_name']);
    $last_name   = sanitizeInput($_POST['last_name'] ?? '');
    $email       = sanitizeInput($_POST['email'] ?? '');
    $role        = $_POST['role'];
    
    // Fixed: Handle StoreID properly
    $store_id = !empty($_POST['store_id']) ? (int)$_POST['store_id'] : null;

    // Validation
    if (empty($username) || empty($password) || empty($first_name) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
        $error = 'Invalid email address.';
    } elseif (dbFetchOne("SELECT UserID FROM Users WHERE Username = ?", [$username])) {
        $error = 'Username already exists.';
    } elseif (!empty($email) && dbFetchOne("SELECT UserID FROM Users WHERE Email = ?", [$email])) {
        $error = 'Email already exists.';
    } else {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $query = "
                INSERT INTO Users (Username, PasswordHash, FirstName, LastName, Email, Role, StoreID, Status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')
            ";
            $userId = dbInsert($query, [
                $username, $passwordHash, $first_name, $last_name, $email, $role, $store_id
            ]);

            $success = "Account created successfully! You can now <a href='login.php'>log in</a>.";
            logInfo("Self-registered user", [
                'new_user' => $username,
                'role' => $role,
                'store_id' => $store_id,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            $_POST = [];
        } catch (Exception $e) {
            $error = 'Failed to create account. Please try again.';
            logError('Self-registration failed', ['error' => $e->getMessage(), 'data' => $_POST]);
        }
    }
}

// No navbar at all — even if logged in
$pageTitle = "Register - Shoe Retail ERP";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #714B67;
            --primary-light: #8B5E7F;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-900: #111827;
            --success: #D1FAE5;
            --success-text: #065F46;
        }

        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #5A3B54);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.95rem;
        }

        .form-label i {
            margin-right: 0.4rem;
            color: var(--primary-color);
            width: 1.2em;
            text-align: center;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.2s;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #5A3B54);
            color: white;
            border: none;
            padding: 0.85rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
            border: none;
        }

        .alert-danger {
            background-color: #FADBD8;
            color: #78281F;
        }

        .alert-success {
            background-color: var(--success);
            color: var(--success-text);
        }

        .alert i {
            margin-right: 0.5rem;
        }

        .required {
            color: #e74c3c;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-plus"></i> Create Your Account</h2>
        </div>
        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>Username <span class="required">*</span>
                    </label>
                    <input type="text" name="username" class="form-input" 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i>Password <span class="required">*</span>
                    </label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i>First Name <span class="required">*</span>
                    </label>
                    <input type="text" name="first_name" class="form-input" 
                           value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i>Last Name
                    </label>
                    <input type="text" name="last_name" class="form-input" 
                           value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i>Email
                    </label>
                    <input type="email" name="email" class="form-input" 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user-tag"></i>Role <span class="required">*</span>
                    </label>
                    <select name="role" class="form-select" required>
                        <option value="">-- Select Role --</option>
                        <option value="Cashier" <?= (isset($_POST['role']) && $_POST['role'] === 'Cashier') ? 'selected' : '' ?>>Cashier</option>
                        <option value="Support" <?= (isset($_POST['role']) && $_POST['role'] === 'Support') ? 'selected' : '' ?>>Support</option>
                        <option value="Inventory" <?= (isset($_POST['role']) && $_POST['role'] === 'Inventory') ? 'selected' : '' ?>>Inventory</option>
                        <option value="Sales" <?= (isset($_POST['role']) && $_POST['role'] === 'Sales') ? 'selected' : '' ?>>Sales</option>
                        <option value="Procurement" <?= (isset($_POST['role']) && $_POST['role'] === 'Procurement') ? 'selected' : '' ?>>Procurement</option>
                        <option value="Accounting" <?= (isset($_POST['role']) && $_POST['role'] === 'Accounting') ? 'selected' : '' ?>>Accounting</option>
                        <option value="Customers" <?= (isset($_POST['role']) && $_POST['role'] === 'Customers') ? 'selected' : '' ?>>Customers</option>
                        <option value="HR" <?= (isset($_POST['role']) && $_POST['role'] === 'HR') ? 'selected' : '' ?>>HR</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Store (Optional)
                    </label>
                    <select name="store_id" class="form-select">
                        <option value="">-- No Store --</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?= $store['StoreID'] ?>" 
                                <?= (isset($_POST['store_id']) && $_POST['store_id'] == $store['StoreID']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($store['StoreName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-primary">
                    Create Account
                </button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Sign In</a>
            </div>

            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.85rem; color: var(--gray-600);">
                Only <strong>Cashier</strong>, <strong>Support</strong>, <strong>Inventory</strong>, <strong>Sales</strong>, <strong>Procurement</strong>, <strong>Accounting</strong>, <strong>Customers</strong>, and <strong>HR</strong> roles can self-register.<br>
                Managers and Admins must be created by an administrator.
            </div>
        </div>
    </div>
</div>

</body>
</html>