<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

// Role-based access control
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'Accounting'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-secondary { background: #6c757d; color: white; }
        .filter-bar { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .filter-bar input, .filter-bar select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .financial-report { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .report-section { margin-bottom: 30px; }
        .report-section h4 { margin-bottom: 15px; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .report-line { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .report-total { font-weight: bold; font-size: 1.1em; border-top: 2px solid #333; margin-top: 10px; padding-top: 10px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 50; top: 100; width: 120%; height: auto; overflow: auto; background-color: rgba(0,0,0,0.4);  }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; border-radius: 8px; width: 90%; max-width: 600px; max-height: 85vh; overflow-y: auto; }
        .modal-header { padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 20px; border-top: 1px solid #ddd; text-align: right; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .dept-summary-card { background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #007bff; }
        .dept-summary-card h4 { margin: 0 0 10px 0; color: #007bff; }
        .dept-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 10px; }
        .dept-stat-item { text-align: center; padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .dept-stat-label { font-size: 12px; color: #666; }
        .dept-stat-value { font-size: 18px; font-weight: bold; color: #333; }
    </style>
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Accounting Management</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Accounting</div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" onclick="exportReport()"><i class="fas fa-download"></i> Export Report</button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row" style="margin-bottom: 2rem;">
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value" id="totalRevenue">₱0.00</div><div class="stat-label">Total Revenue</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">📉</div><div class="stat-value" id="totalExpenses">₱0.00</div><div class="stat-label">Total Expenses</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">📊</div><div class="stat-value" id="netIncome">₱0.00</div><div class="stat-label">Net Income</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">💳</div><div class="stat-value" id="totalReceivables">₱0.00</div><div class="stat-label">Receivables</div></div></div>
            </div>

            <!-- Tabs -->
            <ul class="nav-tabs">
                <li><a href="#" class="nav-link active" data-tab="arTab">Accounts Receivable</a></li>
                <li><a href="#" class="nav-link" data-tab="apTab">Accounts Payable</a></li>
                <li><a href="#" class="nav-link" data-tab="ledgerTab">General Ledger</a></li>
                <li><a href="#" class="nav-link" data-tab="reportsTab">Financial Reports</a></li>
                <li><a href="#" class="nav-link" data-tab="budgetTab">Budget Allocation</a></li>
                <li><a href="#" class="nav-link" data-tab="payrollTab">Payroll</a></li>
                <li><a href="#" class="nav-link" data-tab="salariesTab">Employee Salaries</a></li>
                <li><a href="#" class="nav-link" data-tab="departmentsTab">Departments</a></li>
            </ul>

            <!-- AR Tab -->
            <div id="arTab" class="tab-pane active">
                <div class="card">
                    <div class="card-header">
                        <h3>Accounts Receivable</h3>
                        <div class="filter-bar">
                            <select id="arStatusFilter" onchange="loadReceivables()">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Partial">Partial</option>
                                <option value="Overdue">Overdue</option>
                                <option value="Paid">Paid</option>
                            </select>
                            <button class="btn btn-sm btn-primary" onclick="loadReceivables()"><i class="fas fa-refresh"></i> Refresh</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>AR ID</th><th>Sale ID</th><th>Customer</th><th>Store</th><th>Amount Due</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody id="receivablesTableBody"><tr><td colspan="10" class="text-center">Loading...</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AP Tab -->
            <div id="apTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Accounts Payable</h3>
                        <div class="filter-bar">
                            <select id="apStatusFilter" onchange="loadPayables()">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Partial">Partial</option>
                                <option value="Overdue">Overdue</option>
                                <option value="Paid">Paid</option>
                            </select>
                            <button class="btn btn-sm btn-primary" onclick="loadPayables()"><i class="fas fa-refresh"></i> Refresh</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>AP ID</th><th>PO #</th><th>Supplier</th><th>Store</th><th>Amount Due</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody id="payablesTableBody"><tr><td colspan="10" class="text-center">Loading...</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ledger Tab -->
            <div id="ledgerTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>General Ledger</h3>
                        <div class="filter-bar">
                            <select id="ledgerAccountType" onchange="loadLedger()">
                                <option value="">All Account Types</option>
                                <option value="Revenue">Revenue</option>
                                <option value="Expense">Expense</option>
                                <option value="Asset">Asset</option>
                                <option value="Liability">Liability</option>
                                <option value="Equity">Equity</option>
                            </select>
                            <input type="date" id="ledgerStartDate" onchange="loadLedger()">
                            <input type="date" id="ledgerEndDate" onchange="loadLedger()">
                            <button class="btn btn-sm btn-primary" onclick="loadLedger()"><i class="fas fa-refresh"></i> Refresh</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Date</th><th>Account Type</th><th>Account Name</th><th>Description</th><th>Debit</th><th>Credit</th><th>Store</th><th>Reference</th></tr></thead>
                                <tbody id="ledgerTableBody"><tr><td colspan="8" class="text-center">Loading...</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Tab -->
            <div id="reportsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Financial Reports</h3>
                        <div class="filter-bar">
                            <select id="reportType" onchange="handleReportTypeChange()">
                                <option value="income">Income Statement</option>
                                <option value="balance">Balance Sheet</option>
                                <option value="tax">Tax Summary</option>
                            </select>
                            <input type="date" id="reportStartDate" value="">
                            <input type="date" id="reportEndDate" value="">
                            <button class="btn btn-sm btn-primary" onclick="generateReport()"><i class="fas fa-chart-line"></i> Generate Report</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="reportContent"><p class="text-center">Select report parameters and click "Generate Report"</p></div>
                    </div>
                </div>
            </div>

            <!-- Budget Tab -->
            <div id="budgetTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Budget Management</h3>
                        <div class="filter-bar">
                            <select id="budgetStatus" onchange="loadBudgets()">
                                <option value="">All</option>
                                <option value="Proposed">Proposed</option>
                                <option value="Approved">Approved</option>
                                <option value="Allocated">Allocated</option>
                            </select>
                            <select id="budgetStore" onchange="loadBudgets()">
                                <option value="">All Stores</option>
                            </select>
                            <button class="btn btn-sm btn-primary" onclick="loadBudgets()">Refresh</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead><tr><th>Store</th><th>Department</th><th>Period</th><th>Proposed</th><th>Approved</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="budgetTableBody"><tr><td colspan="7" class="text-center">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payroll Tab -->
            <div id="payrollTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Payroll Summary</h3>
                        <div class="filter-bar">
                            <input type="month" id="payrollMonth" value="<?= date('Y-m') ?>">
                            <button class="btn btn-sm btn-success" onclick="generatePayroll()">Generate Payroll</button>
                            <button class="btn btn-sm btn-info" onclick="showDepartmentPayroll()">Dept Summary</button>
                            <button class="btn btn-sm btn-primary" onclick="loadPayroll()">Refresh</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="departmentPayrollSummary" style="display:none; margin-bottom: 20px;">
                            <h4>Department Payroll Summary</h4>
                            <div id="deptPayrollContent"></div>
                        </div>
                        <table class="table">
                            <thead><tr><th>Employee</th><th>Department</th><th>Store</th><th>Hours</th><th>Gross</th><th>Deductions</th><th>Net Pay</th><th>Status</th></tr></thead>
                            <tbody id="payrollTableBody"><tr><td colspan="8" class="text-center">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Salaries Tab -->
            <div id="salariesTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Employee Salary Accounts</h3>
                        <button class="btn btn-sm btn-success" onclick="showSalaryAuditLog()">View Audit Log</button>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead><tr><th>Name</th><th>Department</th><th>Store</th><th>Salary Grade</th><th>Current Rate</th><th>Grade Range</th><th>Status</th><th>Bank Account</th><th>Actions</th></tr></thead>
                            <tbody id="salariesTableBody"><tr><td colspan="9" class="text-center">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Departments Tab -->
            <div id="departmentsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Department Management</h3>
                        <button class="btn btn-sm btn-success" onclick="showAddDepartmentModal()"><i class="fas fa-plus"></i> Add Department</button>
                    </div>
                    <div class="card-body">
                        <div id="departmentsContainer"><p class="text-center">Loading...</p></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <?php include 'modals/payment_modal.php'; ?>
    <?php include 'modals/salary_modal.php'; ?>
    <?php include 'modals/add_dept_modal.php'; ?>
    <?php include 'modals/add_grade_modal.php'; ?>
    <?php include 'modals/approve_budget_modal.php'; ?>
    <?php include 'modals/audit_log_modal.php'; ?>
    <?php include 'modals/dept_payroll_modal.php'; ?>

    <script src="../js/app.js"></script>
    <script>
        // === GLOBAL VARIABLES ===
        let currentPaymentType = '', currentPaymentId = 0, currentEmployeeId = 0, currentBudgetId = 0;

        // === DOM READY ===
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const firstDay = new Date(today.replace(/\d{2}$/, '01')).toISOString().split('T')[0];
            document.getElementById('ledgerStartDate').value = firstDay;
            document.getElementById('ledgerEndDate').value = today;
            document.getElementById('reportStartDate').value = firstDay;
            document.getElementById('reportEndDate').value = today;

            loadFinancialSummary();
            loadReceivables();
            loadStoresForBudget();
        });

        // === TAB NAVIGATION ===
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
                document.getElementById(this.dataset.tab).style.display = 'block';
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                const tab = this.dataset.tab;
                if (tab === 'arTab') loadReceivables();
                else if (tab === 'apTab') loadPayables();
                else if (tab === 'ledgerTab') loadLedger();
                else if (tab === 'budgetTab') { loadStoresForBudget(); loadBudgets(); }
                else if (tab === 'payrollTab') loadPayroll();
                else if (tab === 'salariesTab') loadEmployeeSalaries();
                else if (tab === 'departmentsTab') loadDepartmentsTab();
            });
        });

        // === FINANCIAL SUMMARY ===
        function loadFinancialSummary() {
            fetch('accounting_api.php?action=get_summary')
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        document.getElementById('totalRevenue').textContent = '₱' + parseFloat(d.data.total_revenue || 0).toFixed(2);
                        document.getElementById('totalExpenses').textContent = '₱' + parseFloat(d.data.total_expenses || 0).toFixed(2);
                        document.getElementById('netIncome').textContent = '₱' + parseFloat(d.data.net_income || 0).toFixed(2);
                        document.getElementById('totalReceivables').textContent = '₱' + parseFloat(d.data.total_receivables || 0).toFixed(2);
                    }
                })
                .catch(err => {
                    console.error('Summary Error:', err);
                    showAlert('Could not load summary.', 'error');
                });
        }

        // === ACCOUNTS RECEIVABLE ===
        function loadReceivables() {
            const status = document.getElementById('arStatusFilter').value;
            fetch(`accounting_api.php?action=get_receivables${status ? '&status=' + status : ''}`)
                .then(r => r.json())
                .then(d => {
                    const tbody = document.getElementById('receivablesTableBody');
                    if (!d.success || d.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No records</td></tr>';
                        return;
                    }
                    tbody.innerHTML = d.data.map(r => `
                        <tr>
                            <td>${r.ARID}</td>
                            <td>${r.SaleID}</td>
                            <td>${r.CustomerName}<br><small>${r.Email}</small></td>
                            <td>${r.StoreName || 'N/A'}</td>
                            <td>₱${parseFloat(r.AmountDue).toFixed(2)}</td>
                            <td>₱${parseFloat(r.PaidAmount).toFixed(2)}</td>
                            <td>₱${parseFloat(r.Balance).toFixed(2)}</td>
                            <td>${formatDate(r.DueDate)}</td>
                            <td>${getStatusBadge(r.Status)}</td>
                            <td>
                                ${r.Balance > 0 ? `<button class="btn btn-sm btn-success" onclick="openPaymentModal(${r.ARID}, 'receivable', ${r.AmountDue}, ${r.PaidAmount})">Pay</button>` : '<span class="badge badge-success">Paid</span>'}
                            </td>
                        </tr>
                    `).join('');
                });
        }

        // === ACCOUNTS PAYABLE ===
        function loadPayables() {
            const status = document.getElementById('apStatusFilter').value;
            fetch(`accounting_api.php?action=get_payables${status ? '&status=' + status : ''}`)
                .then(r => r.json())
                .then(d => {
                    const tbody = document.getElementById('payablesTableBody');
                    if (!d.success || d.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No records</td></tr>';
                        return;
                    }
                    tbody.innerHTML = d.data.map(r => `
                        <tr>
                            <td>${r.APID}</td>
                            <td>${r.PurchaseOrderID}</td>
                            <td>${r.SupplierName}<br><small>${r.Email}</small></td>
                            <td>${r.StoreName || 'N/A'}</td>
                            <td>₱${parseFloat(r.AmountDue).toFixed(2)}</td>
                            <td>₱${parseFloat(r.PaidAmount).toFixed(2)}</td>
                            <td>₱${parseFloat(r.Balance).toFixed(2)}</td>
                            <td>${formatDate(r.DueDate)}</td>
                            <td>${getStatusBadge(r.Status)}</td>
                            <td>
                                ${r.Balance > 0 ? `<button class="btn btn-sm btn-danger" onclick="openPaymentModal(${r.APID}, 'payable', ${r.AmountDue}, ${r.PaidAmount})">Pay</button>` : '<span class="badge badge-success">Paid</span>'}
                            </td>
                        </tr>
                    `).join('');
                });
        }

        // === GENERAL LEDGER ===
        function loadLedger() {
            const type = document.getElementById('ledgerAccountType').value;
            const start = document.getElementById('ledgerStartDate').value;
            const end = document.getElementById('ledgerEndDate').value;
            let url = 'accounting_api.php?action=get_ledger';
            if (type) url += `&account_type=${type}`;
            if (start && end) url += `&start_date=${start}&end_date=${end}`;
            fetch(url)
                .then(r => r.json())
                .then(d => {
                    const tbody = document.getElementById('ledgerTableBody');
                    if (!d.success || d.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No entries</td></tr>';
                        return;
                    }
                    tbody.innerHTML = d.data.map(r => `
                        <tr>
                            <td>${formatDateTime(r.TransactionDate)}</td>
                            <td><span class="badge badge-info">${r.AccountType}</span></td>
                            <td>${r.AccountName}</td>
                            <td>${r.Description || ''}</td>
                            <td class="text-right">${r.Debit > 0 ? '₱' + parseFloat(r.Debit).toFixed(2) : '-'}</td>
                            <td class="text-right">${r.Credit > 0 ? '₱' + parseFloat(r.Credit).toFixed(2) : '-'}</td>
                            <td>${r.StoreName || 'N/A'}</td>
                            <td><small>${r.ReferenceType} #${r.ReferenceID}</small></td>
                        </tr>
                    `).join('');
                });
        }

        // === REPORTS ===
        function handleReportTypeChange() {
            const type = document.getElementById('reportType').value;
            const start = document.getElementById('reportStartDate');
            if (type === 'balance') {
                start.style.display = 'none';
                start.previousElementSibling.textContent = 'As of:';
            } else {
                start.style.display = 'inline-block';
                start.previousElementSibling.textContent = 'Start Date:';
            }
        }

        function generateReport() {
            const type = document.getElementById('reportType').value;
            const start = document.getElementById('reportStartDate').value;
            const end = document.getElementById('reportEndDate').value;
            if (type === 'income') generateIncomeStatement(start, end);
            else if (type === 'balance') generateBalanceSheet(end);
            else if (type === 'tax') generateTaxSummary(start, end);
        }

        function generateIncomeStatement(start, end) {
            fetch(`accounting_api.php?action=get_income_statement&start_date=${start}&end_date=${end}`)
                .then(r => r.json()).then(d => renderReport(d, 'Income Statement', start, end));
        }

        function generateBalanceSheet(asOf) {
            fetch(`accounting_api.php?action=get_balance_sheet&as_of_date=${asOf}`)
                .then(r => r.json()).then(d => renderBalanceSheet(d, asOf));
        }

        function generateTaxSummary(start, end) {
            fetch(`accounting_api.php?action=get_tax_records&start_date=${start}&end_date=${end}`)
                .then(r => r.json()).then(d => renderTaxSummary(d, start, end));
        }

        function renderReport(d, title, start, end) {
            if (!d.success) return showAlert(d.message, 'error');
            const html = `
                <div class="financial-report">
                    <h3>${title} – ${formatDate(start)} to ${formatDate(end)}</h3>
                    <div class="report-section"><h4>Revenue</h4>${d.data.revenue.map(i=>`<div class="report-line"><span>${i.AccountName}</span><span>₱${parseFloat(i.Amount).toFixed(2)}</span></div>`).join('')}<div class="report-total"><span>Total Revenue</span><span>₱${parseFloat(d.data.total_revenue).toFixed(2)}</span></div></div>
                    <div class="report-section"><h4>Expenses</h4>${d.data.expenses.map(i=>`<div class="report-line"><span>${i.AccountName}</span><span>₱${parseFloat(i.Amount).toFixed(2)}</span></div>`).join('')}<div class="report-total"><span>Total Expenses</span><span>₱${parseFloat(d.data.total_expenses).toFixed(2)}</span></div></div>
                    <div class="report-total"><span>Net Income</span><span>₱${parseFloat(d.data.net_income).toFixed(2)}</span></div>
                </div>`;
            document.getElementById('reportContent').innerHTML = html;
        }

        function renderBalanceSheet(d, asOf) {
            if (!d.success) return showAlert(d.message, 'error');
            const html = `
                <div class="financial-report">
                    <h3>Balance Sheet – As of ${formatDate(asOf)}</h3>
                    <div class="report-section"><h4>Assets</h4>${d.data.assets.map(i=>`<div class="report-line"><span>${i.AccountName}</span><span>₱${parseFloat(i.Amount).toFixed(2)}</span></div>`).join('')}<div class="report-total"><span>Total Assets</span><span>₱${parseFloat(d.data.total_assets).toFixed(2)}</span></div></div>
                    <div class="report-section"><h4>Liabilities</h4>${d.data.liabilities.map(i=>`<div class="report-line"><span>${i.AccountName}</span><span>₱${parseFloat(i.Amount).toFixed(2)}</span></div>`).join('')}<div class="report-total"><span>Total Liabilities</span><span>₱${parseFloat(d.data.total_liabilities).toFixed(2)}</span></div></div>
                    <div class="report-section"><h4>Equity</h4>${d.data.equity.map(i=>`<div class="report-line"><span>${i.AccountName}</span><span>₱${parseFloat(i.Amount).toFixed(2)}</span></div>`).join('')}<div class="report-total"><span>Total Equity</span><span>₱${parseFloat(d.data.total_equity).toFixed(2)}</span></div></div>
                </div>`;
            document.getElementById('reportContent').innerHTML = html;
        }

        function renderTaxSummary(d, start, end) {
            if (!d.success) return showAlert(d.message, 'error');
            const rows = d.data.map(t => `<tr><td>${formatDate(t.TaxDate)}</td><td>${t.TransactionType}</td><td>${t.TaxType}</td><td>₱${parseFloat(t.TaxAmount).toFixed(2)}</td><td>${t.StoreName||'—'}</td></tr>`).join('');
            const html = `<div class="financial-report"><h3>Tax Summary</h3><table class="table"><thead><tr><th>Date</th><th>Transaction</th><th>Tax Type</th><th>Amount</th><th>Store</th></tr></thead><tbody>${rows}</tbody></table></div>`;
            document.getElementById('reportContent').innerHTML = html;
        }

        // === PAYMENT MODAL ===
        function openPaymentModal(id, type, due, paid) {
            currentPaymentType = type;
            currentPaymentId = id;
            document.getElementById('paymentModalTitle').textContent = type === 'receivable' ? 'Record Receivable Payment' : 'Record Payable Payment';
            document.getElementById('amountDue').textContent = '₱' + parseFloat(due).toFixed(2);
            document.getElementById('alreadyPaid').textContent = '₱' + parseFloat(paid).toFixed(2);
            const balance = due - paid;
            document.getElementById('balance').textContent = '₱' + balance.toFixed(2);
            document.getElementById('paymentAmount').value = balance.toFixed(2);
            document.getElementById('paymentAmount').max = balance;
            const now = new Date().toISOString().slice(0, 16);
            document.getElementById('paymentDate').value = now;
            document.getElementById('paymentModal').style.display = 'block';
        }

        function closePaymentModal() { document.getElementById('paymentModal').style.display = 'none'; }

        function submitPayment() {
            const amount = document.getElementById('paymentAmount').value;
            const date = document.getElementById('paymentDate').value;
            if (!amount || amount <= 0) return showAlert('Invalid amount', 'error');
            const action = currentPaymentType === 'receivable' ? 'record_receivable_payment' : 'record_payable_payment';
            const idField = currentPaymentType === 'receivable' ? 'arid' : 'apid';
            const form = new FormData();
            form.append('action', action);
            form.append(idField, currentPaymentId);
            form.append('amount', amount);
            form.append('payment_date', date.replace('T', ' ') + ':00');
            fetch('accounting_api.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(d => {
                    showAlert(d.message, d.success ? 'success' : 'error');
                    if (d.success) {
                        closePaymentModal();
                        loadFinancialSummary();
                        currentPaymentType === 'receivable' ? loadReceivables() : loadPayables();
                    }
                });
        }

        // === BUDGETS ===
        function loadStoresForBudget() {
            fetch('accounting_api.php?action=get_stores')
                .then(r => r.json())
                .then(d => {
                    const sel = document.getElementById('budgetStore');
                    sel.innerHTML = '<option value="">All Stores</option>';
                    d.data.forEach(s => sel.innerHTML += `<option value="${s.StoreID}">${s.StoreName}</option>`);
                });
        }

        function loadBudgets() {
            const status = document.getElementById('budgetStatus').value;
            const storeId = document.getElementById('budgetStore').value;
            let url = 'accounting_api.php?action=get_budgets';
            if (status) url += `&status=${status}`;
            if (storeId) url += `&store_id=${storeId}`;
            fetch(url)
                .then(r => r.json())
                .then(d => {
                    const tbody = document.getElementById('budgetTableBody');
                    if (!d.success || d.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No budgets</td></tr>';
                        return;
                    }
                    tbody.innerHTML = d.data.map(r => `
                        <tr>
                            <td>${r.StoreName}</td>
                            <td>${r.Department}</td>
                            <td>${r.Period}</td>
                            <td>₱${parseFloat(r.ProposedAmount).toFixed(2)}</td>
                            <td>₱${parseFloat(r.ApprovedAmount || 0).toFixed(2)}</td>
                            <td>${getStatusBadge(r.Status)}</td>
                            <td>
                                ${r.Status === 'Proposed' ? `<button class="btn btn-sm btn-warning" onclick="openApproveBudgetModal(${r.BudgetID}, '${r.StoreName}', '${r.Department}', '${r.Period}', ${r.ProposedAmount})">Approve</button>` : ''}
                                ${r.Status === 'Approved' ? `<button class="btn btn-sm btn-success" onclick="allocateBudget(${r.BudgetID})">Allocate</button>` : ''}
                            </td>
                        </tr>
                    `).join('');
                });
        }

        function allocateBudget(id) {
            if (!confirm('Allocate this budget?')) return;
            const form = new FormData();
            form.append('action', 'allocate_budget');
            form.append('budget_id', id);
            fetch('accounting_api.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(d => {
                    showAlert(d.message, d.success ? 'success' : 'error');
                    if (d.success) loadBudgets();
                });
        }

        // === PAYROLL ===
        function loadPayroll() {
            const month = document.getElementById('payrollMonth').value;
            const [year, mon] = month.split('-');
            fetch(`accounting_api.php?action=get_payroll_summary&month=${mon}&year=${year}`)
                .then(r => r.json())
                .then(d => {
                    const tbody = document.getElementById('payrollTableBody');
                    if (!d.success || d.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No payroll</td></tr>';
                        return;
                    }
                    tbody.innerHTML = d.data.map(p => `
                        <tr>
                            <td>${p.FirstName} ${p.LastName}</td>
                            <td>${p.DepartmentName || '—'}</td>
                            <td>${p.StoreName}</td>
                            <td>${p.HoursWorked}</td>
                            <td>₱${parseFloat(p.GrossPay).toFixed(2)}</td>
                            <td>₱${parseFloat(p.Deductions).toFixed(2)}</td>
                            <td>₱${parseFloat(p.NetPay).toFixed(2)}</td>
                            <td>${getStatusBadge(p.Status)}</td>
                        </tr>
                    `).join('');
                });
        }

        function generatePayroll() {
            if (!confirm('Generate payroll for this month?')) return;
            const month = document.getElementById('payrollMonth').value;
            const [year, mon] = month.split('-');
            const form = new FormData();
            form.append('action', 'generate_payroll');
            form.append('month', mon);
            form.append('year', year);
            fetch('accounting_api.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(d => {
                    showAlert(d.message, d.success ? 'success' : 'error');
                    if (d.success) loadPayroll();
                });
        }

        function showDepartmentPayroll() {
            const month = document.getElementById('payrollMonth').value;
            const [year, mon] = month.split('-');
            fetch(`accounting_api.php?action=get_department_payroll_summary&month=${mon}&year=${year}`)
                .then(r => r.json())
                .then(d => {
                    if (!d.success) return showAlert(d.message, 'error');
                    const content = d.data.map(dept => `
                        <div class="dept-summary-card">
                            <h4>${dept.DepartmentName}</h4>
                            <div class="dept-stats">
                                <div class="dept-stat-item"><div class="dept-stat-label">Employees</div><div class="dept-stat-value">${dept.EmployeeCount}</div></div>
                                <div class="dept-stat-item"><div class="dept-stat-label">Gross Pay</div><div class="dept-stat-value">₱${parseFloat(dept.TotalGross).toFixed(2)}</div></div>
                                <div class="dept-stat-item"><div class="dept-stat-label">Deductions</div><div class="dept-stat-value">₱${parseFloat(dept.TotalDeductions).toFixed(2)}</div></div>
                                <div class="dept-stat-item"><div class="dept-stat-label">Net Pay</div><div class="dept-stat-value">₱${parseFloat(dept.TotalNet).toFixed(2)}</div></div>
                            </div>
                        </div>
                    `).join('');
                    document.getElementById('deptPayrollContent').innerHTML = content;
                    document.getElementById('departmentPayrollSummary').style.display = 'block';
                });
        }

        // === SALARIES ===
        function loadEmployeeSalaries() {
            fetch('accounting_api.php?action=get_employee_salaries')
                .then(r => r.json())
                .then(d => {
                    const tbody = document.getElementById('salariesTableBody');
                    if (!d.success || d.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No employees</td></tr>';
                        return;
                    }
                    tbody.innerHTML = d.data.map(e => `
                        <tr>
                            <td>${e.EmployeeName}</td>
                            <td>${e.DepartmentName}</td>
                            <td>${e.StoreName}</td>
                            <td>${e.GradeName || '—'}</td>
                            <td>₱${parseFloat(e.HourlyRate).toFixed(2)}</td>
                            <td>₱${parseFloat(e.MinRate).toFixed(2)} – ₱${parseFloat(e.MaxRate).toFixed(2)}</td>
                            <td>${e.Status}</td>
                            <td>${e.BankAccount || '—'}</td>
                            <td><button class="btn btn-sm btn-primary" onclick="openSalaryModal(${e.EmployeeID}, '${e.EmployeeName}', '${e.DepartmentName}', ${e.DepartmentID})">Update</button></td>
                        </tr>
                    `).join('');
                });
        }

        function openSalaryModal(empId, name, dept, deptId) {
            currentEmployeeId = empId;
            document.getElementById('salaryEmployeeName').textContent = name;
            document.getElementById('salaryDepartment').textContent = dept;
            document.getElementById('salaryEmployeeId').value = empId;
            document.getElementById('salaryModal').style.display = 'block';
            loadSalaryGrades(deptId);
        }

        function loadSalaryGrades(deptId) {
            fetch(`accounting_api.php?action=get_salary_grades&department_id=${deptId}`)
                .then(r => r.json())
                .then(d => {
                    const sel = document.getElementById('salaryGradeId');
                    sel.innerHTML = '<option value="">Select Grade</option>';
                    d.data.forEach(g => {
                        sel.innerHTML += `<option value="${g.GradeID}" data-min="${g.MinHourlyRate}" data-max="${g.MaxHourlyRate}">${g.GradeName}</option>`;
                    });
                });
        }

        function updateSalaryRange() {
            const sel = document.getElementById('salaryGradeId');
            const opt = sel.options[sel.selectedIndex];
            const min = opt ? opt.dataset.min || 0 : 0;
            const max = opt ? opt.dataset.max || 0 : 0;
            document.getElementById('gradeRange').textContent = `₱${min} – ₱${max}`;
        }

        function submitSalaryUpdate() {
            const rate = document.getElementById('newHourlyRate').value;
            const gradeId = document.getElementById('salaryGradeId').value;
            const date = document.getElementById('salaryEffectiveDate').value;
            const notes = document.getElementById('salaryNotes').value;
            if (!rate || !gradeId) return showAlert('Fill required fields', 'error');
            const form = new FormData();
            form.append('action', 'update_employee_salary');
            form.append('employee_id', currentEmployeeId);
            form.append('hourly_rate', rate);
            form.append('grade_id', gradeId);
            form.append('effective_date', date);
            form.append('notes', notes);
            fetch('accounting_api.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(d => {
                    showAlert(d.message, d.success ? 'success' : 'error');
                    if (d.success) {
                        closeSalaryModal();
                        loadEmployeeSalaries();
                    }
                });
        }

        // === DEPARTMENTS ===
        function loadDepartmentsTab() {
            fetch('accounting_api.php?action=get_departments')
                .then(r => r.json())
                .then(d => {
                    const container = document.getElementById('departmentsContainer');
                    if (!d.success || d.data.length === 0) {
                        container.innerHTML = '<p class="text-center">No departments</p>';
                        return;
                    }
                    container.innerHTML = d.data.map(dept => `
                        <div class="dept-summary-card">
                            <h4>${dept.name}</h4>
                            <p>${dept.description || 'No description'}</p>
                            <div class="dept-stats">
                                <div class="dept-stat-item"><div class="dept-stat-label">Base Rate</div><div class="dept-stat-value">₱${parseFloat(dept.base_hourly_rate).toFixed(2)}</div></div>
                                <div class="dept-stat-item"><div class="dept-stat-label">Status</div><div class="dept-stat-value">${dept.status}</div></div>
                            </div>
                            <button class="btn btn-sm btn-info" onclick="showAddGradeModal(${dept.id})"><i class="fas fa-layer-group"></i> Grades</button>
                        </div>
                    `).join('');
                });
        }

        // === UTILS ===
        function getStatusBadge(status) {
            const map = { 'Paid': 'success', 'Pending': 'warning', 'Overdue': 'danger', 'Partial': 'info', 'Due Today': 'warning', 'Proposed': 'secondary', 'Approved': 'info', 'Allocated': 'success' };
            return `<span class="badge badge-${map[status] || 'secondary'}">${status}</span>`;
        }

        function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A'; }
        function formatDateTime(d) { return d ? new Date(d).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A'; }

        function showAlert(msg, type) {
            const div = document.createElement('div');
            div.className = `alert alert-${type}`;
            div.textContent = msg;
            document.querySelector('.alert-container').appendChild(div);
            setTimeout(() => div.remove(), 3000);
        }

        function exportReport() {
            const content = document.getElementById('reportContent').innerHTML;
            if (!content.includes('financial-report')) return showAlert('Generate a report first', 'info');
            const win = window.open('', '_blank');
            win.document.write(`<html><head><title>Report</title><style>${document.head.querySelector('style').innerHTML}</style></head><body>${content}</body></html>`);
            win.document.close();
            win.print();
        }

        // Close modals on outside click
        window.onclick = e => {
            ['paymentModal', 'salaryModal', 'addDeptModal', 'addGradeModal', 'approveBudgetModal', 'auditLogModal', 'deptPayrollModal'].forEach(id => {
                const modal = document.getElementById(id);
                if (e.target === modal) modal.style.display = 'none';
            });
        };

        function closeSalaryModal() { document.getElementById('salaryModal').style.display = 'none'; }
        function closeAddDeptModal() { document.getElementById('addDeptModal').style.display = 'none'; }
        function closeAddGradeModal() { document.getElementById('addGradeModal').style.display = 'none'; }
        function closeApproveBudgetModal() { document.getElementById('approveBudgetModal').style.display = 'none'; }
        function closeAuditLogModal() { document.getElementById('auditLogModal').style.display = 'none'; }
        function closeDeptPayrollModal() { document.getElementById('deptPayrollModal').style.display = 'none'; }

        function showAddDepartmentModal() {
            document.getElementById('addDeptModal').style.display = 'block';
        }

        function showAddGradeModal(deptId) {
            document.getElementById('gradeDeptId').value = deptId;
            document.getElementById('addGradeModal').style.display = 'block';
        }

        function submitAddDepartment() {
            const name = document.getElementById('deptName').value.trim();
            const desc = document.getElementById('deptDescription').value.trim();
            const rate = document.getElementById('deptBaseRate').value;
            if (!name || !rate) return showAlert('Name and base rate required', 'error');

            const form = new FormData();
            form.append('action', 'add_department');
            form.append('name', name);
            form.append('description', desc);
            form.append('base_rate', rate);

            fetch('accounting_api.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(d => {
                    showAlert(d.message, d.success ? 'success' : 'error');
                    if (d.success) {
                        closeAddDeptModal();
                        loadDepartmentsTab();
                    }
                });
        }

        function submitAddGrade() {
            const deptId = document.getElementById('gradeDeptId').value;
            const name = document.getElementById('gradeName').value.trim();
            const min = document.getElementById('gradeMinRate').value;
            const max = document.getElementById('gradeMaxRate').value;
            if (!name || !min || !max) return showAlert('All fields required', 'error');

            const form = new FormData();
            form.append('action', 'add_salary_grade');
            form.append('department_id', deptId);
            form.append('grade_name', name);
            form.append('min_rate', min);
            form.append('max_rate', max);

            fetch('accounting_api.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(d => {
                    showAlert(d.message, d.success ? 'success' : 'error');
                    if (d.success) {
                        closeAddGradeModal();
                        loadDepartmentsTab();
                    }
                });
        }

        function showSalaryAuditLog() {
            document.getElementById('auditLogModal').style.display = 'block';
            loadSalaryAuditLog();
        }

        function loadSalaryAuditLog() {
            const start = document.getElementById('auditStartDate').value;
            const end = document.getElementById('auditEndDate').value;
            let url = 'accounting_api.php?action=get_salary_audit_log';
            if (start) url += `&start_date=${start}`;
            if (end) url += `&end_date=${end}`;

            fetch(url)
                .then(r => r.json())
                .then(d => {
                    const tbody = document.getElementById('auditLogTableBody');
                    if (!d.success || d.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No records</td></tr>';
                        return;
                    }
                    tbody.innerHTML = d.data.map(log => `
                        <tr>
                            <td>${formatDateTime(log.ChangeDate)}</td>
                            <td>${log.EmployeeName}</td>
                            <td>₱${parseFloat(log.OldRate).toFixed(2)}</td>
                            <td>₱${parseFloat(log.NewRate).toFixed(2)}</td>
                            <td>${log.OldGrade || '—'}</td>
                            <td>${log.NewGrade || '—'}</td>
                            <td><small>${log.ChangedBy}</small></td>
                        </tr>
                    `).join('');
                });
        }
    </script>
</body>
</html>