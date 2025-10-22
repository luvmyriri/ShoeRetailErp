<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Shoe Retail ERP</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="alert-container"></div>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand"><i class="fas fa-shoe-prints"></i><span>Shoe Retail ERP</span></div>
            <ul class="navbar-nav">
                <li><a href="/ShoeRetailErp/public/index.php">Home</a></li>
                <li><a href="/ShoeRetailErp/public/inventory/index.php">Inventory</a></li>
                <li><a href="/ShoeRetailErp/public/sales/index.php">Sales</a></li>
                <li><a href="/ShoeRetailErp/public/procurement/index.php">Procurement</a></li>
                <li><a href="/ShoeRetailErp/public/accounting/index.php">Accounting</a></li>
                <li><a href="/ShoeRetailErp/public/customers/index.php">Customers</a></li>
            </ul>
            <div class="navbar-right">
                <div class="navbar-search"><input type="text" placeholder="Search..."></div>
                <div class="dropdown"><div class="navbar-avatar"><i class="fas fa-user"></i></div>
                    <div class="dropdown-menu">
                        <a href="/ShoeRetailErp/public/profile.php" class="dropdown-item active"><i class="fas fa-user-circle"></i> Profile</a>
                        <a href="/ShoeRetailErp/public/settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="/ShoeRetailErp/logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>My Profile</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / <span>Profile</span>
                    </div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" onclick="editProfile()"><i class="fas fa-edit"></i> Edit Profile</button>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 40px; color: white;">
                                <i class="fas fa-user"></i>
                            </div>
                            <h4><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin User'; ?></h4>
                            <p style="color: #666;">System Administrator</p>
                            <div style="margin-top: 1rem;">
                                <span class="badge" style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 12px;">
                                    <i class="fas fa-circle" style="font-size: 8px; margin-right: 4px;"></i>Active
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="margin-top: 1rem;">
                        <div class="card-header"><h3>Quick Stats</h3></div>
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <span>Login Sessions</span>
                                <strong>45</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <span>Last Login</span>
                                <strong><?php echo date('M d, Y'); ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Account Created</span>
                                <strong>Jan 15, 2024</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3>Profile Information</h3></div>
                        <div class="card-body">
                            <form id="profileForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input type="text" class="form-control" value="Admin" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input type="text" class="form-control" value="User" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" class="form-control" value="admin@shoeretail.com" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'admin'; ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" class="form-control" value="+1 (555) 123-4567" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <input type="text" class="form-control" value="System Administrator" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Department</label>
                                    <input type="text" class="form-control" value="Management" readonly>
                                </div>
                                <div class="form-group" id="editButtons" style="display: none;">
                                    <button type="button" class="btn btn-success" onclick="saveProfile()">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="cancelEdit()">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card" style="margin-top: 1rem;">
                        <div class="card-header"><h3>Security Settings</h3></div>
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <strong>Change Password</strong>
                                    <div style="font-size: 14px; color: #666;">Update your account password</div>
                                </div>
                                <button class="btn btn-outline-primary" onclick="changePassword()">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>Two-Factor Authentication</strong>
                                    <div style="font-size: 14px; color: #666;">Add an extra layer of security</div>
                                </div>
                                <button class="btn btn-outline-success">
                                    <i class="fas fa-shield-alt"></i> Enable 2FA
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="js/app.js"></script>
    <script>
        function editProfile() {
            const inputs = document.querySelectorAll('#profileForm input:not([type="hidden"])');
            const editButtons = document.getElementById('editButtons');
            
            inputs.forEach(input => {
                if (input.getAttribute('value') !== 'System Administrator' && 
                    input.type !== 'email' && 
                    input.getAttribute('value') !== '<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'admin'; ?>') {
                    input.removeAttribute('readonly');
                    input.style.backgroundColor = '#fff';
                }
            });
            
            editButtons.style.display = 'block';
            event.target.style.display = 'none';
        }

        function saveProfile() {
            showAlert('Profile updated successfully!', 'success');
            cancelEdit();
        }

        function cancelEdit() {
            const inputs = document.querySelectorAll('#profileForm input');
            const editButtons = document.getElementById('editButtons');
            const editBtn = document.querySelector('.page-header-actions button');
            
            inputs.forEach(input => {
                input.setAttribute('readonly', '');
                input.style.backgroundColor = '#f8f9fa';
            });
            
            editButtons.style.display = 'none';
            editBtn.style.display = 'inline-block';
        }

        function changePassword() {
            showAlert('Password change functionality coming soon!', 'info');
        }
    </script>
</body>
</html>