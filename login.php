<?php


session_start();
require_once 'includes/core_functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            if (authenticateUser($username, $password)) {
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Login system temporarily unavailable. Please try again later.';
            logError('Login system error', ['error' => $e->getMessage()]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Shoe Retail ERP</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #714B67;
            --primary-light: #8B5E7F;
            --secondary-color: #F5B041;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, #5A3B54 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 12px -2px rgba(0, 0, 0, 0.1), 3px 6px 10px -1px rgba(0, 0, 0, 0.07), 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: none;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #5A3B54 100%);
            color: white;
            padding: 2.5rem 1.5rem;
            text-align: center;
        }

        .login-header h2 {
            font-size: 1.875rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.5px;
        }

        .login-header .subtitle {
            font-size: 0.95rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 500;
        }

        .login-header i {
            margin-right: 0.5rem;
            font-size: 1.75rem;
        }

        .login-body {
            padding: 2rem 1.75rem;
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
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: white;
            color: var(--gray-900);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.1);
            background: white;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem 1rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, #5A3B54 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px -4px rgba(0, 0, 0, 0.12), 4px 8px 14px -2px rgba(0, 0, 0, 0.08);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .divider {
            margin: 1.5rem 0;
            border: none;
            border-top: 1px solid var(--gray-200);
        }

        .demo-info {
            background: var(--gray-50);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--gray-200);
        }

        .demo-info small {
            color: var(--gray-600);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .demo-info strong {
            color: var(--primary-color);
            font-weight: 600;
        }

        .demo-info i {
            margin-right: 0.3rem;
            color: var(--primary-color);
        }

        .login-footer {
            padding: 1rem 1.75rem;
            text-align: center;
            border-top: 1px solid var(--gray-100);
            background: var(--gray-50);
        }

        .login-footer small {
            color: var(--gray-600);
            font-size: 0.85rem;
        }

        .login-footer i {
            margin-right: 0.3rem;
            color: var(--primary-color);
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

        .alert i {
            margin-right: 0.5rem;
        }

        @media (max-width: 480px) {
            .login-header {
                padding: 2rem 1.25rem;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }

            .login-body {
                padding: 1.5rem 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-shoe-prints"></i>Shoe Retail ERP</h2>
                <p class="subtitle">Secure Login</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i>Username
                        </label>
                        <input type="text" class="form-input" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               placeholder="Enter your username"
                               required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>Password
                        </label>
                        <input type="password" class="form-input" id="password" name="password" 
                               placeholder="Enter your password"
                               required>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
                
                <hr class="divider">
                
                <div class="demo-info">
                    <small>
                        <i class="fas fa-info-circle"></i><strong>Demo Credentials:</strong><br>
                        <strong>admin</strong> / password<br>
                        <strong>manager1</strong> / password<br>
                        <strong>cashier1</strong> / password
                    </small>
                </div>
            </div>
            <div class="login-footer">
                <small>
                    <i class="fas fa-shield-alt"></i> Secure Login System
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus username field if empty
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (usernameField.value === '') {
                usernameField.focus();
            }
        });
    </script>
</body>
</html>