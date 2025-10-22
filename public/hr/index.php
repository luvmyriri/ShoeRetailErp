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
    <title>HR Management - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Human Resources</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / HR Management</div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary"><i class="fas fa-plus"></i> Add Employee</button>
                    <button class="btn btn-secondary"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>

            <!-- HR Key Metrics -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Total Employees</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;">28</div>
                                    <div style="font-size: 11px; color: #666;">↑ 2 new this month</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">👥</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">On Leave</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;">3</div>
                                    <div style="font-size: 11px; color: #666;">Currently away</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">🏖️</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Pending Leave</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;">5</div>
                                    <div style="font-size: 11px; color: #666;">⚠️ Needs approval</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">⏳</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Monthly Payroll</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;">₱156,400</div>
                                    <div style="font-size: 11px; color: #666;">Processed 25-Oct</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">💰</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HR Sections -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-6" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <h3 style="margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">👥 Employee Directory</h3>
                        </div>
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="font-size: 12px; color: #666; line-height: 1.8;">
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f0f0f0;">
                                    <span>Cashiers</span>
                                    <span style="font-weight: 600; color: #333;">12</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f0f0f0;">
                                    <span>Sales Managers</span>
                                    <span style="font-weight: 600; color: #333;">4</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f0f0f0;">
                                    <span>Inventory Staff</span>
                                    <span style="font-weight: 600; color: #333;">6</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f0f0f0;">
                                    <span>Store Managers</span>
                                    <span style="font-weight: 600; color: #333;">3</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0;">
                                    <span>Admin Staff</span>
                                    <span style="font-weight: 600; color: #333;">3</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <h3 style="margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">📋 Quick Actions</h3>
                        </div>
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <button class="btn btn-primary" style="background-color: #714B67; color: white; border: none; padding: 0.75rem; border-radius: 0.5rem; font-size: 12px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-user-plus"></i> Add Employee
                                </button>
                                <button class="btn btn-secondary" style="background-color: #F5B041; color: #333; border: none; padding: 0.75rem; border-radius: 0.5rem; font-size: 12px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-clock"></i> Timesheets
                                </button>
                                <button class="btn btn-secondary" style="background-color: #F5B041; color: #333; border: none; padding: 0.75rem; border-radius: 0.5rem; font-size: 12px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-money-bill"></i> Process Payroll
                                </button>
                                <button class="btn btn-secondary" style="background-color: #F5B041; color: #333; border: none; padding: 0.75rem; border-radius: 0.5rem; font-size: 12px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-tasks"></i> Leave Requests
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-12" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <h3 style="margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">⏳ Pending Approvals</h3>
                        </div>
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="font-size: 12px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead style="background-color: #f9fafb; border-bottom: 1px solid #eee;">
                                        <tr>
                                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #666;">Request Type</th>
                                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #666;">Employee</th>
                                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #666;">Submitted</th>
                                            <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #666;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td style="padding: 0.75rem;"><span style="background-color: #E8F4F8; color: #00A3E0; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 600;">Leave Request</span></td>
                                            <td style="padding: 0.75rem;">Maria Santos</td>
                                            <td style="padding: 0.75rem; color: #999;">Oct 20, 2025</td>
                                            <td style="padding: 0.75rem; text-align: right;">
                                                <button style="background-color: #27AE60; color: white; border: none; padding: 0.35rem 0.75rem; border-radius: 0.25rem; font-size: 11px; font-weight: 600; cursor: pointer; margin-right: 0.5rem;">✓ Approve</button>
                                                <button style="background-color: #E74C3C; color: white; border: none; padding: 0.35rem 0.75rem; border-radius: 0.25rem; font-size: 11px; font-weight: 600; cursor: pointer;">✕ Reject</button>
                                            </td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td style="padding: 0.75rem;"><span style="background-color: #FEF3C7; color: #B45309; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 600;">Timesheet</span></td>
                                            <td style="padding: 0.75rem;">John Reyes</td>
                                            <td style="padding: 0.75rem; color: #999;">Oct 19, 2025</td>
                                            <td style="padding: 0.75rem; text-align: right;">
                                                <button style="background-color: #27AE60; color: white; border: none; padding: 0.35rem 0.75rem; border-radius: 0.25rem; font-size: 11px; font-weight: 600; cursor: pointer; margin-right: 0.5rem;">✓ Approve</button>
                                                <button style="background-color: #E74C3C; color: white; border: none; padding: 0.35rem 0.75rem; border-radius: 0.25rem; font-size: 11px; font-weight: 600; cursor: pointer;">✕ Reject</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 0.75rem;"><span style="background-color: #DDD6FE; color: #6366F1; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 600;">Role Assignment</span></td>
                                            <td style="padding: 0.75rem;">Ana Flores</td>
                                            <td style="padding: 0.75rem; color: #999;">Oct 21, 2025</td>
                                            <td style="padding: 0.75rem; text-align: right;">
                                                <button style="background-color: #27AE60; color: white; border: none; padding: 0.35rem 0.75rem; border-radius: 0.25rem; font-size: 11px; font-weight: 600; cursor: pointer; margin-right: 0.5rem;">✓ Approve</button>
                                                <button style="background-color: #E74C3C; color: white; border: none; padding: 0.35rem 0.75rem; border-radius: 0.25rem; font-size: 11px; font-weight: 600; cursor: pointer;">✕ Reject</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HR Modules Grid -->
            <div style="margin-bottom: 1rem;">
                <h2 style="margin: 0 0 0.75rem 0; font-size: 16px; font-weight: 600; color: #333;">HR Modules</h2>
            </div>
            <div class="row">
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                            <div style="font-size: 40px; margin-bottom: 0.75rem;">👤</div>
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Employee Directory</h3>
                            <p style="margin: 0; font-size: 12px; color: #666;">Manage employee records & profiles</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                            <div style="font-size: 40px; margin-bottom: 0.75rem;">📋</div>
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Timesheets</h3>
                            <p style="margin: 0; font-size: 12px; color: #666;">Track hours & attendance</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                            <div style="font-size: 40px; margin-bottom: 0.75rem;">💰</div>
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Payroll</h3>
                            <p style="margin: 0; font-size: 12px; color: #666;">Process salaries & benefits</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                            <div style="font-size: 40px; margin-bottom: 0.75rem;">🏖️</div>
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Leave Management</h3>
                            <p style="margin: 0; font-size: 12px; color: #666;">Manage leave requests</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                            <div style="font-size: 40px; margin-bottom: 0.75rem;">🔐</div>
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Roles & Permissions</h3>
                            <p style="margin: 0; font-size: 12px; color: #666;">Assign roles & access control</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                            <div style="font-size: 40px; margin-bottom: 0.75rem;">📊</div>
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Reports</h3>
                            <p style="margin: 0; font-size: 12px; color: #666;">HR analytics & compliance</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/app.js"></script>
</body>
</html>
