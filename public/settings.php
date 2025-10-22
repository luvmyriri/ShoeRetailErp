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
    <title>Settings - Shoe Retail ERP</title>
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
                        <a href="/ShoeRetailErp/public/profile.php" class="dropdown-item"><i class="fas fa-user-circle"></i> Profile</a>
                        <a href="/ShoeRetailErp/public/settings.php" class="dropdown-item active"><i class="fas fa-cog"></i> Settings</a>
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
                    <h1>Settings</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / <span>Settings</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header" style="border-bottom: none; padding-bottom: 0;"><h3>Settings</h3></div>
                        <div class="card-body" style="padding: 0;">
                            <div class="settings-menu">
                                <a href="#general" class="settings-item active" data-tab="general">
                                    <i class="fas fa-sliders-h"></i> General
                                </a>
                                <a href="#notifications" class="settings-item" data-tab="notifications">
                                    <i class="fas fa-bell"></i> Notifications
                                </a>
                                <a href="#security" class="settings-item" data-tab="security">
                                    <i class="fas fa-shield-alt"></i> Security
                                </a>
                                <a href="#appearance" class="settings-item" data-tab="appearance">
                                    <i class="fas fa-palette"></i> Appearance
                                </a>
                                <a href="#integrations" class="settings-item" data-tab="integrations">
                                    <i class="fas fa-plug"></i> Integrations
                                </a>
                                <a href="#about" class="settings-item" data-tab="about">
                                    <i class="fas fa-info-circle"></i> About
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <!-- General Settings -->
                    <div id="general" class="settings-tab active">
                        <div class="card">
                            <div class="card-header"><h3>General Settings</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Company Name</label>
                                    <input type="text" class="form-control" value="Shoe Retail ERP" placeholder="Enter company name">
                                </div>
                                <div class="form-group">
                                    <label>Company Email</label>
                                    <input type="email" class="form-control" value="admin@shoeretail.com" placeholder="Enter company email">
                                </div>
                                <div class="form-group">
                                    <label>Currency</label>
                                    <select class="form-control">
                                        <option>Philippine Peso (₱)</option>
                                        <option>US Dollar ($)</option>
                                        <option>Euro (€)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date Format</label>
                                    <select class="form-control">
                                        <option>MM/DD/YYYY</option>
                                        <option>DD/MM/YYYY</option>
                                        <option>YYYY-MM-DD</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Time Zone</label>
                                    <select class="form-control">
                                        <option>Asia/Manila (UTC+8)</option>
                                        <option>America/New_York (UTC-5)</option>
                                        <option>Europe/London (UTC+0)</option>
                                    </select>
                                </div>
                                <button class="btn btn-primary" onclick="saveSettings()"><i class="fas fa-save"></i> Save Changes</button>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Settings -->
                    <div id="notifications" class="settings-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header"><h3>Notification Settings</h3></div>
                            <div class="card-body">
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>Email Notifications</strong>
                                            <div style="font-size: 14px; color: #666;">Receive emails for important events</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>Low Stock Alerts</strong>
                                            <div style="font-size: 14px; color: #666;">Notify when stock levels are low</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>New Order Alerts</strong>
                                            <div style="font-size: 14px; color: #666;\">Notify on new order submissions</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>System Notifications</strong>
                                            <div style="font-size: 14px; color: #666;\">Notify about system updates and maintenance</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <button class="btn btn-primary" onclick="saveSettings()" style="margin-top: 1rem;"><i class="fas fa-save"></i> Save Changes</button>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div id="security" class="settings-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header"><h3>Security Settings</h3></div>
                            <div class="card-body">
                                <div style="margin-bottom: 2rem;">
                                    <h4>Password Policy</h4>
                                    <div class="form-group">
                                        <label>Minimum Password Length</label>
                                        <input type="number" class="form-control" value="8" min="6">
                                    </div>
                                    <div class="form-group">
                                        <label>Require Special Characters</label>
                                        <label class="toggle-switch">
                                            <input type="checkbox" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div style="margin-bottom: 2rem;">
                                    <h4>Session Security</h4>
                                    <div class="form-group">
                                        <label>Session Timeout (minutes)</label>
                                        <input type="number" class="form-control" value="30" min="5">
                                    </div>
                                    <div class="form-group">
                                        <label>Require Login for Every Session</label>
                                        <label class="toggle-switch">
                                            <input type="checkbox">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <button class="btn btn-primary" onclick="saveSettings()"><i class="fas fa-save"></i> Save Changes</button>
                            </div>
                        </div>
                    </div>

                    <!-- Appearance Settings -->
                    <div id="appearance" class="settings-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header"><h3>Appearance Settings</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Theme</label>
                                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                            <input type="radio" name="theme" value="light" checked>
                                            <span>Light</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                            <input type="radio" name="theme" value="dark">
                                            <span>Dark</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                            <input type="radio" name="theme" value="auto">
                                            <span>Auto</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Primary Color</label>
                                    <input type="color" class="form-control" value="#667eea" style="height: 40px; cursor: pointer;">
                                </div>
                                <div class="form-group">
                                    <label>Language</label>
                                    <select class="form-control">
                                        <option>English</option>
                                        <option>Filipino</option>
                                        <option>Spanish</option>
                                    </select>
                                </div>
                                <button class="btn btn-primary" onclick="saveSettings()"><i class="fas fa-save"></i> Save Changes</button>
                            </div>
                        </div>
                    </div>

                    <!-- Integrations -->
                    <div id="integrations" class="settings-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header"><h3>API Integrations</h3></div>
                            <div class="card-body">
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>Payment Gateway</strong>
                                            <div style="font-size: 14px; color: #666;\">Stripe Integration</div>
                                        </div>
                                        <button class="btn btn-outline-primary">Configure</button>
                                    </div>
                                </div>
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>Email Service</strong>
                                            <div style="font-size: 14px; color: #666;\">Gmail/SMTP Configuration</div>
                                        </div>
                                        <button class="btn btn-outline-primary">Configure</button>
                                    </div>
                                </div>
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>Cloud Storage</strong>
                                            <div style="font-size: 14px; color: #666;\">Google Drive/AWS Integration</div>
                                        </div>
                                        <button class="btn btn-outline-primary">Configure</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- About -->
                    <div id="about" class="settings-tab" style="display: none;">
                        <div class="card">
                            <div class="card-header"><h3>About</h3></div>
                            <div class="card-body">
                                <div style="margin-bottom: 1rem;">
                                    <strong>Version:</strong> 1.0.0
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong>Build Date:</strong> October 22, 2024
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong>License:</strong> MIT License
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong>Documentation:</strong> <a href="#" style="color: #667eea; text-decoration: none;">View Docs</a>
                                </div>
                                <div>
                                    <strong>Support:</strong> <a href="mailto:support@shoeretail.com" style="color: #667eea; text-decoration: none;">support@shoeretail.com</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="js/app.js"></script>
    <script>
        document.querySelectorAll('.settings-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all items
                document.querySelectorAll('.settings-item').forEach(i => i.classList.remove('active'));
                document.querySelectorAll('.settings-tab').forEach(t => t.style.display = 'none');
                
                // Add active class to clicked item
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).style.display = 'block';
            });
        });

        function saveSettings() {
            showAlert('Settings saved successfully!', 'success');
        }
    </script>
    <style>
        .settings-menu {
            display: flex;
            flex-direction: column;
        }
        .settings-item {
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        .settings-item:hover {
            background-color: #f8f9fa;
            color: #667eea;
        }
        .settings-item.active {
            background-color: #f0f2ff;
            color: #667eea;
            border-left-color: #667eea;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 26px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #667eea;
        }
        input:checked + .slider:before {
            transform: translateX(24px);
        }
    </style>
</body>
</html>