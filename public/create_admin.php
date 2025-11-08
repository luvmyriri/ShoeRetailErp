<?php
// One-time script to create/update an Admin account.
// Usage:
//  - GET:   /ShoeRetailErp/public/create_admin.php?username=admin&password=Pass!&email=you@example.com
//  - POST:  open /ShoeRetailErp/public/create_admin.php and submit the form
// Delete this file after use.

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET) && empty($_POST)) {
    // Render a simple POST form for convenience
    echo '<!DOCTYPE html><html><body style="font-family:Arial; padding:20px;">'
       . '<h2>Create/Update Admin</h2>'
       . '<form method="post">'
       . 'Username: <input name="username" value="admin" required><br><br>'
       . 'Email: <input name="email" type="email" value="admin@shoeretailerp.local" required><br><br>'
       . 'Password: <input name="password" type="password" required><br><br>'
       . '<button type="submit">Apply</button>'
       . '</form>'
       . '</body></html>';
    exit;
}

try {
    $db = getDB();

    // Allow POST or GET
    $src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $username = isset($src['username']) ? trim($src['username']) : 'admin';
    $password = isset($src['password']) ? (string)$src['password'] : 'Admin@123';
    $email    = isset($src['email']) ? trim($src['email']) : 'admin@shoeretailerp.local';

    if ($username === '' || $password === '' || $email === '') {
        http_response_code(400);
        echo 'username, password, and email must be non-empty.';
        exit;
    }

    // Check if user already exists
    $exists = dbFetchOne("SELECT UserID FROM users WHERE Username = ? OR Email = ? LIMIT 1", [$username, $email]);

    // Hash password (bcrypt)
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    if (!$hash) { throw new Exception('Failed to hash password'); }

    if ($exists) {
        // Update existing to ensure usable password and admin role
        dbUpdate(
            "UPDATE users SET PasswordHash = ?, Email = ?, Role = 'Admin', Status = 'Active' WHERE UserID = ?",
            [$hash, $email, $exists['UserID']]
        );
        echo 'Admin account updated.';
    } else {
        // Insert new admin
        $userId = dbInsert(
            "INSERT INTO users (Username, PasswordHash, FirstName, LastName, Email, Role, StoreID, Status) VALUES (?, ?, ?, ?, ?, 'Admin', NULL, 'Active')",
            [$username, $hash, 'System', 'Administrator', $email]
        );
        if ($userId > 0) {
            echo 'Admin account created.';
        } else {
            http_response_code(500);
            echo 'Failed to create admin user.';
        }
    }
    echo "\nUsername: " . htmlspecialchars($username);
    echo "\nPassword: (the one you provided)";
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}
