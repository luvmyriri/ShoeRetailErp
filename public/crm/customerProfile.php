<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profiles - CRM - Shoe Retail ERP</title>
    <?php include '../includes/head.php'; ?>
    <link rel="stylesheet" href="crm-integration.css">
    <link rel="stylesheet" href="enhanced-modal-styles.css">
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Customer Profiles</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / CRM / Customers
                    </div>
                </div>
                <div class="page-header-actions">
                    <!-- Actions here -->
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0;">Customer Profiles</h3>
                        <button class="btn btn-primary btn-sm" onclick="openAddCustomerModal()"><i class="fas fa-plus"></i> Add Customer</button>
                    </div>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 1rem;">
                        <input type="text" id="searchCustomers" placeholder="Search customers by name or email..." class="form-control" style="padding: 0.75rem;">
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Loyalty Points</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customersBody">
                                <tr><td colspan="7" style="text-align: center; padding: 2rem;">Loading customers...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Add Customer Modal - Enhanced -->
            <div id="addCustomerModal" class="modal-enhanced modal-md">
                <div class="modal-enhanced-content">
                    <div class="modal-enhanced-header">
                        <div class="modal-enhanced-header-content">
                            <h2 class="modal-enhanced-title">Add New Customer</h2>
                            <p class="modal-enhanced-subtitle">Create a new customer profile in the system</p>
                        </div>
                        <button class="modal-enhanced-close" onclick="closeModal('addCustomerModal')">×</button>
                    </div>
                    <div class="modal-enhanced-body">
                        <div class="modal-alert modal-alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div><strong>Required fields</strong> are marked with <span class="modal-form-label-required">*</span></div>
                        </div>
                        <form id="addCustomerForm" method="POST">
                            <input type="hidden" name="add_customer" value="1">
                            <div class="modal-section">
                                <h3 class="modal-section-title"><i class="fas fa-user"></i> Personal Information</h3>
                                <div class="modal-form-grid">
                                    <div class="modal-form-group">
                                        <label class="modal-form-label">First Name <span class="modal-form-label-required">*</span></label>
                                        <input type="text" class="modal-form-control" name="first_name" placeholder="Enter first name" required>
                                    </div>
                                    <div class="modal-form-group">
                                        <label class="modal-form-label">Last Name <span class="modal-form-label-required">*</span></label>
                                        <input type="text" class="modal-form-control" name="last_name" placeholder="Enter last name" required>
                                    </div>
                                    <div class="modal-form-group">
                                        <label class="modal-form-label">Email</label>
                                        <input type="email" class="modal-form-control" name="email" placeholder="name@example.com">
                                    </div>
                                    <div class="modal-form-group">
                                        <label class="modal-form-label">Phone</label>
                                        <input type="tel" class="modal-form-control" name="phone" placeholder="+63 900 000 0000">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-divider"></div>
                            <div class="modal-section">
                                <h3 class="modal-section-title"><i class="fas fa-map-marker-alt"></i> Address</h3>
                                <div class="modal-form-group full-width">
                                    <label class="modal-form-label">Address</label>
                                    <input type="text" class="modal-form-control" name="address" placeholder="Street address">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-enhanced-footer">
                        <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('addCustomerModal')"><i class="fas fa-times"></i> Cancel</button>
                        <button type="submit" class="modal-btn modal-btn-primary" onclick="document.getElementById('addCustomerForm').submit()"><i class="fas fa-user-plus"></i> Add Customer</button>
                    </div>
                </div>
            </div>
            
            <script>
            function openAddCustomerModal() {
                document.getElementById('addCustomerModal').classList.add('active');
            }
            
            function closeModal(modalId) {
                document.getElementById(modalId).classList.remove('active');
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                loadCustomers();
            });
            
            function loadCustomers() {
                const API_URL = '/ShoeRetailErp/api';
                fetch(API_URL + '/crm.php?action=get_customers')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.data) {
                            displayCustomers(data.data);
                        }
                    })
                    .catch(err => console.error('Error:', err));
            }
            
            function displayCustomers(customers) {
                const tbody = document.getElementById('customersBody');
                if (!customers || customers.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No customers found</td></tr>';
                    return;
                }
                
                tbody.innerHTML = customers.map(c => `
                    <tr>
                        <td>#CUST-${String(c.CustomerID).padStart(3, '0')}</td>
                        <td><strong>${c.FirstName} ${c.LastName}</strong></td>
                        <td>${c.Email || '-'}</td>
                        <td>${c.Phone || '-'}</td>
                        <td>${c.LoyaltyPoints || 0}</td>
                        <td><span class="badge badge-${c.Status === 'Active' ? 'success' : 'secondary'}">${c.Status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="alert('View customer ${c.CustomerID}')"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-warning" onclick="alert('Edit customer ${c.CustomerID}')"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                `).join('');
            }
            </script>
        </main>
    </div>

    <script src="../js/app.js"></script>
</body>
</html>
