<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Role-based access control
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'Sales', 'Customer Service'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}

require __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support - CRM - Shoe Retail ERP</title>
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
                    <h1>Customer Support</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / CRM / Support
                    </div>
                </div>
                <div class="page-header-actions">
                    <!-- Actions here -->
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0;">Support Tickets</h3>
                        <button class="btn btn-primary btn-sm" onclick="openTicketModal()"><i class="fas fa-plus"></i> New Ticket</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Customer</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="7" style="text-align: center; padding: 2rem;">No support tickets</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- New Support Ticket Modal - Enhanced -->
            <div id="ticketModal" class="modal-enhanced modal-lg">
                <div class="modal-enhanced-content">
                    <div class="modal-enhanced-header">
                        <div class="modal-enhanced-header-content">
                            <h2 class="modal-enhanced-title">Create Support Ticket</h2>
                            <p class="modal-enhanced-subtitle">Log a new support request or issue</p>
                        </div>
                        <button class="modal-enhanced-close" onclick="closeModal('ticketModal')">×</button>
                    </div>
                    <div class="modal-enhanced-body">
                        <form id="ticketForm" method="POST">
                            <input type="hidden" name="create_ticket" value="1">
                            <div class="modal-section">
                                <h3 class="modal-section-title"><i class="fas fa-ticket-alt"></i> Ticket Information</h3>
                                <div class="modal-form-grid">
                                    <div class="modal-form-group full-width">
                                        <label class="modal-form-label">Customer <span class="modal-form-label-required">*</span></label>
                                        <select class="modal-form-control modal-form-select" name="customer_id" required>
                                            <option value="" selected disabled>Select a customer...</option>
                                            <option value="1">Customer 1</option>
                                            <option value="2">Customer 2</option>
                                        </select>
                                        <div class="modal-form-hint">Select the customer this ticket is for</div>
                                    </div>
                                    <div class="modal-form-group full-width">
                                        <label class="modal-form-label">Subject <span class="modal-form-label-required">*</span></label>
                                        <input type="text" class="modal-form-control" name="subject" placeholder="Brief description of the issue" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-divider"></div>
                            <div class="modal-section">
                                <h3 class="modal-section-title"><i class="fas fa-align-left"></i> Details</h3>
                                <div class="modal-form-group full-width">
                                    <label class="modal-form-label">Description</label>
                                    <textarea class="modal-form-control modal-form-textarea" name="description" placeholder="Provide detailed information about the issue..."></textarea>
                                    <div class="modal-form-hint">Include any relevant details to help resolve the issue faster</div>
                                </div>
                            </div>
                            <div class="modal-divider"></div>
                            <div class="modal-section">
                                <h3 class="modal-section-title"><i class="fas fa-cog"></i> Priority & Status</h3>
                                <div class="modal-form-grid">
                                    <div class="modal-form-group">
                                        <label class="modal-form-label">Priority</label>
                                        <select class="modal-form-control modal-form-select" name="priority">
                                            <option value="Low">Low</option>
                                            <option value="Medium" selected>Medium</option>
                                            <option value="High">High</option>
                                            <option value="Critical">Critical</option>
                                        </select>
                                    </div>
                                    <div class="modal-form-group">
                                        <label class="modal-form-label">Status</label>
                                        <select class="modal-form-control modal-form-select" name="status">
                                            <option value="Open" selected>Open</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Waiting">Waiting for Customer</option>
                                            <option value="Resolved">Resolved</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-info-box">
                                <div class="modal-info-box-icon"><i class="fas fa-clock"></i></div>
                                <div class="modal-info-box-content">
                                    <div class="modal-info-box-title">Response Time</div>
                                    <div class="modal-info-box-text">Critical issues will be addressed within 1 hour, High within 4 hours, Medium within 24 hours.</div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-enhanced-footer">
                        <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('ticketModal')"><i class="fas fa-times"></i> Cancel</button>
                        <button type="submit" class="modal-btn modal-btn-primary" onclick="document.getElementById('ticketForm').submit()"><i class="fas fa-check"></i> Create Ticket</button>
                    </div>
                </div>
            </div>
            
            <script>
            function openTicketModal() {
                document.getElementById('ticketModal').classList.add('active');
            }
            
            function closeModal(modalId) {
                document.getElementById(modalId).classList.remove('active');
            }
            </script>
        </main>
    </div>

    <script src="../js/app.js"></script>
</body>
</html>
