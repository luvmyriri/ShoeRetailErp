<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/core_functions.php';

// REMOVED: Redirect if already logged in - this was causing the loop
// if (isLoggedIn()) {
//     header('Location: index.php');
//     exit;
// }
// Prevent caching of login page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: public/index.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            if (authenticateUser($username, $password)) {
                // Debug: Check what was set in session
                error_log("Login successful. Session data: " . print_r($_SESSION, true));
                
                // Redirect to dashboard
                header('Location: /public/index.php');
                header('Location: public/index.php');
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

// Debug: Show current session status
if (isLoggedIn()) {
    echo "<!-- DEBUG: User is logged in. Session data: " . print_r($_SESSION, true) . " -->";
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
            background: #714B67;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            overflow: hidden;
            position: relative;
        }

        .shoe-prints-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .shoe-print {
            position: absolute;
            color: rgba(0, 0, 0, 0.15);
            font-size: 2rem;
            animation: float linear infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #e5e5e5;
            animation: cardFadeIn 0.4s ease-out;
        }

        @keyframes cardFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: white;
            color: #2d2d2d;
            padding: 2.5rem 1.5rem 1.5rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .login-header h2 {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.3px;
            color: var(--primary-color);
        }

        .login-header .subtitle {
            font-size: 0.9rem;
            color: #717171;
            margin: 0;
            font-weight: 400;
        }

        .login-header i {
            margin-right: 0.5rem;
            font-size: 1.75rem;
            color: var(--primary-color);
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
            font-weight: 500;
            color: #2d2d2d;
            font-size: 0.9rem;
        }

        .form-label i {
            margin-right: 0.4rem;
            color: var(--primary-color);
        }

        .form-input {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #d4d4d4;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: white;
            color: #2d2d2d;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.08);
            background: white;
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            background: #5A3B54;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(113, 75, 103, 0.25);
        }

        .btn-login:active {
            transform: translateY(0);
        }


        .login-footer {
            padding: 1rem 1.75rem;
            text-align: center;
            border-top: 1px solid #f0f0f0;
            background: #fafafa;
        }

        .login-footer small {
            color: #717171;
            font-size: 0.8rem;
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

        .alert-success {
            background-color: #D4EDDA;
            color: #155724;
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
    <!-- Animated Shoe Prints Background -->
    <div class="shoe-prints-bg" id="shoeBackground"></div>
    
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

                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
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
                        <div style="position: relative;">
                            <input type="password" class="form-input" id="password" name="password" 
                                   placeholder="Enter your password"
                                   style="padding-right: 3rem;"
                                   required>
                            <button type="button" id="togglePassword" 
                                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-600); cursor: pointer; padding: 0.5rem; font-size: 1.1rem;"
                                    title="Show/Hide Password">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
                
                <div style="text-align: center; margin-top: 1.5rem; font-size: 0.95rem;">
                    Don't have an account? <a href="create_account.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Create Account</a>
                </div>
            </div>
            <div class="login-footer">
                <small>
                    <i class="fas fa-shield-alt"></i> Secure Login System
                </small>
            </div>
        </div>
    </div>

    <script>
        // Create floating shoe prints
        function createShoeprint() {
            const bg = document.getElementById('shoeBackground');
            const shoe = document.createElement('i');
            shoe.className = 'fas fa-shoe-prints shoe-print';
            
            // Random horizontal position
            shoe.style.left = Math.random() * 100 + '%';
            
            // Random animation duration (15-30 seconds)
            const duration = 15 + Math.random() * 15;
            shoe.style.animationDuration = duration + 's';
            
            // Random delay
            shoe.style.animationDelay = Math.random() * 5 + 's';
            
            // Random size variation
            const size = 1.5 + Math.random() * 2;
            shoe.style.fontSize = size + 'rem';
            
            bg.appendChild(shoe);
            
            // Remove after animation completes
            setTimeout(() => {
                shoe.remove();
            }, (duration + 5) * 1000);
        }
        
        // Create initial shoe prints
        for (let i = 0; i < 15; i++) {
            setTimeout(createShoeprint, i * 1000);
        }
        
        // Continuously create new shoe prints
        setInterval(createShoeprint, 2000);
        
        // Auto-focus username field if empty
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (usernameField.value === '') {
                usernameField.focus();
            }
            
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    
                    // Toggle eye icon
                    if (type === 'password') {
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    } else {
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                    }
                });
            }
        });
    </script>
</body>
</html>