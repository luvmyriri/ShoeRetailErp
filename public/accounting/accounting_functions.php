<?php
// accounting_functions.php - FULLY PDO COMPATIBLE
// Works with config/database.php (PDO Singleton)

class AccountingManager {
    private $db;

    public function __construct($db) {
        $this->db = $db; // PDO instance
    }

    // ===== FINANCIAL SUMMARY =====
    public function getFinancialSummary($startDate = null, $endDate = null) {
        try {
            $params = [];
            $dateFilter = "";
            if ($startDate && $endDate) {
                $dateFilter = "WHERE SaleDate BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
            }

            // Revenue
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(TotalAmount), 0) as total_revenue FROM Sales $dateFilter");
            $stmt->execute($params);
            $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

            // Expenses
            $expFilter = $dateFilter ? str_replace('SaleDate', 'ExpenseDate', $dateFilter) : "";
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(Amount), 0) as total_expenses FROM Expenses $expFilter");
            $stmt->execute($params);
            $expenses = $stmt->fetch(PDO::FETCH_ASSOC)['total_expenses'];

            $netIncome = $revenue - $expenses;

            // Receivables
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(AmountDue - PaidAmount), 0) as total_receivables FROM AccountsReceivable WHERE PaymentStatus != 'Paid'");
            $stmt->execute();
            $receivables = $stmt->fetch(PDO::FETCH_ASSOC)['total_receivables'];

            // Payables
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(AmountDue - PaidAmount), 0) as total_payables FROM AccountsPayable WHERE PaymentStatus != 'Paid'");
            $stmt->execute();
            $payables = $stmt->fetch(PDO::FETCH_ASSOC)['total_payables'];

            return [
                'success' => true,
                'data' => [
                    'total_revenue' => number_format($revenue, 2),
                    'total_expenses' => number_format($expenses, 2),
                    'net_income' => number_format($netIncome, 2),
                    'total_receivables' => number_format($receivables, 2),
                    'total_payables' => number_format($payables, 2)
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== ACCOUNTS RECEIVABLE =====
    public function getAccountsReceivable($status = null) {
        try {
            $query = "SELECT 
                        ar.ARID, ar.SaleID,
                        CONCAT(c.FirstName, ' ', COALESCE(c.LastName, '')) as CustomerName,
                        c.Email, c.Phone,
                        ar.AmountDue, ar.PaidAmount, (ar.AmountDue - ar.PaidAmount) as Balance,
                        ar.DueDate, ar.PaymentStatus, ar.PaymentDate,
                        CASE 
                            WHEN ar.DueDate < CURDATE() AND ar.PaymentStatus != 'Paid' THEN 'Overdue'
                            WHEN ar.DueDate = CURDATE() THEN 'Due Today'
                            ELSE ar.PaymentStatus
                        END as Status,
                        DATEDIFF(CURDATE(), ar.DueDate) as DaysOverdue,
                        s.StoreName
                      FROM AccountsReceivable ar
                      LEFT JOIN Customers c ON ar.CustomerID = c.CustomerID
                      LEFT JOIN Sales sa ON ar.SaleID = sa.SaleID
                      LEFT JOIN Stores s ON sa.StoreID = s.StoreID";

            $params = [];
            if ($status) {
                $query .= " WHERE ar.PaymentStatus = ?";
                $params[] = $status;
            }
            $query .= " ORDER BY ar.DueDate ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== ACCOUNTS PAYABLE =====
    public function getAccountsPayable($status = null) {
        try {
            $query = "SELECT 
                        ap.APID, ap.PurchaseOrderID,
                        s.SupplierName, s.ContactName, s.Email, s.Phone,
                        ap.AmountDue, ap.PaidAmount, (ap.AmountDue - ap.PaidAmount) as Balance,
                        ap.DueDate, ap.PaymentStatus, ap.PaymentDate,
                        CASE 
                            WHEN ap.DueDate < CURDATE() AND ap.PaymentStatus != 'Paid' THEN 'Overdue'
                            WHEN ap.DueDate = CURDATE() THEN 'Due Today'
                            ELSE ap.PaymentStatus
                        END as Status,
                        DATEDIFF(CURDATE(), ap.DueDate) as DaysOverdue,
                        st.StoreName
                      FROM AccountsPayable ap
                      LEFT JOIN Suppliers s ON ap.SupplierID = s.SupplierID
                      LEFT JOIN PurchaseOrders po ON ap.PurchaseOrderID = po.PurchaseOrderID
                      LEFT JOIN Stores st ON po.StoreID = st.StoreID";

            $params = [];
            if ($status) {
                $query .= " WHERE ap.PaymentStatus = ?";
                $params[] = $status;
            }
            $query .= " ORDER BY ap.DueDate ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== GENERAL LEDGER =====
    public function getGeneralLedger($accountType = null, $startDate = null, $endDate = null) {
        try {
            $query = "SELECT 
                        gl.LedgerID, gl.TransactionDate, gl.AccountType, gl.AccountName,
                        gl.Description, gl.Debit, gl.Credit, gl.ReferenceID, gl.ReferenceType,
                        s.StoreName, gl.CreatedBy
                      FROM GeneralLedger gl
                      LEFT JOIN Stores s ON gl.StoreID = s.StoreID
                      WHERE 1=1";

            $params = [];
            if ($accountType) {
                $query .= " AND gl.AccountType = ?";
                $params[] = $accountType;
            }
            if ($startDate && $endDate) {
                $query .= " AND DATE(gl.TransactionDate) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            $query .= " ORDER BY gl.TransactionDate DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== INCOME STATEMENT =====
    public function getIncomeStatement($startDate, $endDate) {
        try {
            $stmt = $this->db->prepare("SELECT AccountName, SUM(Credit) as Amount
                      FROM GeneralLedger
                      WHERE AccountType = 'Revenue'
                      AND DATE(TransactionDate) BETWEEN ? AND ?
                      GROUP BY AccountName");
            $stmt->execute([$startDate, $endDate]);
            $revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalRevenue = array_sum(array_column($revenue, 'Amount'));

            $stmt = $this->db->prepare("SELECT AccountName, SUM(Debit) as Amount
                      FROM GeneralLedger
                      WHERE AccountType = 'Expense'
                      AND DATE(TransactionDate) BETWEEN ? AND ?
                      GROUP BY AccountName");
            $stmt->execute([$startDate, $endDate]);
            $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalExpenses = array_sum(array_column($expenses, 'Amount'));

            $netIncome = $totalRevenue - $totalExpenses;

            return [
                'success' => true,
                'data' => [
                    'revenue' => $revenue,
                    'total_revenue' => $totalRevenue,
                    'expenses' => $expenses,
                    'total_expenses' => $totalExpenses,
                    'net_income' => $netIncome,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== BALANCE SHEET =====
    public function getBalanceSheet($asOfDate) {
        try {
            $stmt = $this->db->prepare("SELECT AccountName, SUM(Debit - Credit) as Amount
                      FROM GeneralLedger
                      WHERE AccountType = 'Asset'
                      AND DATE(TransactionDate) <= ?
                      GROUP BY AccountName");
            $stmt->execute([$asOfDate]);
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalAssets = array_sum(array_column($assets, 'Amount'));

            $stmt = $this->db->prepare("SELECT AccountName, SUM(Credit - Debit) as Amount
                      FROM GeneralLedger
                      WHERE AccountType = 'Liability'
                      AND DATE(TransactionDate) <= ?
                      GROUP BY AccountName");
            $stmt->execute([$asOfDate]);
            $liabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalLiabilities = array_sum(array_column($liabilities, 'Amount'));

            $stmt = $this->db->prepare("SELECT AccountName, SUM(Credit - Debit) as Amount
                      FROM GeneralLedger
                      WHERE AccountType = 'Equity'
                      AND DATE(TransactionDate) <= ?
                      GROUP BY AccountName");
            $stmt->execute([$asOfDate]);
            $equity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalEquity = array_sum(array_column($equity, 'Amount'));

            return [
                'success' => true,
                'data' => [
                    'assets' => $assets,
                    'total_assets' => $totalAssets,
                    'liabilities' => $liabilities,
                    'total_liabilities' => $totalLiabilities,
                    'equity' => $equity,
                    'total_equity' => $totalEquity,
                    'as_of_date' => $asOfDate
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== RECORD PAYMENTS =====
    public function recordReceivablePayment($arid, $amount, $paymentDate) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT AmountDue, PaidAmount, SaleID FROM AccountsReceivable WHERE ARID = ?");
            $stmt->execute([$arid]);
            $ar = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ar) throw new Exception("AR not found");

            $newPaid = $ar['PaidAmount'] + $amount;
            $status = $newPaid >= $ar['AmountDue'] ? 'Paid' : 'Partial';

            $stmt = $this->db->prepare("UPDATE AccountsReceivable SET PaidAmount = ?, PaymentStatus = ?, PaymentDate = ? WHERE ARID = ?");
            $stmt->execute([$newPaid, $status, $paymentDate, $arid]);

            $user = $_SESSION['username'] ?? 'System';
            $stmt = $this->db->prepare("INSERT INTO GeneralLedger (TransactionDate, AccountType, AccountName, Description, Debit, ReferenceID, ReferenceType, CreatedBy)
                                        VALUES (?, 'Asset', 'Cash', 'Payment received for AR', ?, ?, 'Payment', ?)");
            $stmt->execute([$paymentDate, $amount, $ar['SaleID'], $user]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Payment recorded'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function recordPayablePayment($apid, $amount, $paymentDate) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT AmountDue, PaidAmount, PurchaseOrderID FROM AccountsPayable WHERE APID = ?");
            $stmt->execute([$apid]);
            $ap = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ap) throw new Exception("AP not found");

            $newPaid = $ap['PaidAmount'] + $amount;
            $status = $newPaid >= $ap['AmountDue'] ? 'Paid' : 'Partial';

            $stmt = $this->db->prepare("UPDATE AccountsPayable SET PaidAmount = ?, PaymentStatus = ?, PaymentDate = ? WHERE APID = ?");
            $stmt->execute([$newPaid, $status, $paymentDate, $apid]);

            $user = $_SESSION['username'] ?? 'System';
            $stmt = $this->db->prepare("INSERT INTO GeneralLedger (TransactionDate, AccountType, AccountName, Description, Credit, ReferenceID, ReferenceType, CreatedBy)
                                        VALUES (?, 'Asset', 'Cash', 'Payment made for AP', ?, ?, 'Payment', ?)");
            $stmt->execute([$paymentDate, $amount, $ap['PurchaseOrderID'], $user]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Payment recorded'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== TAX RECORDS =====
    public function getTaxRecords($startDate = null, $endDate = null) {
        try {
            $query = "SELECT tr.TaxRecordID, tr.TransactionID, tr.TransactionType, tr.TaxAmount,
                             tr.TaxDate, tr.TaxType, tr.TaxRate, s.StoreName
                      FROM TaxRecords tr
                      LEFT JOIN Stores s ON tr.StoreID = s.StoreID
                      WHERE 1=1";

            $params = [];
            if ($startDate && $endDate) {
                $query .= " AND DATE(tr.TaxDate) BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
            }
            $query .= " ORDER BY tr.TaxDate DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== BUDGETS =====
    public function getBudgets($status = null, $storeId = null) {
        try {
            $query = "SELECT b.*, s.StoreName, u.FirstName, u.LastName 
                      FROM budgets b
                      LEFT JOIN stores s ON b.StoreID = s.StoreID
                      LEFT JOIN users u ON b.ApprovedBy = u.UserID
                      WHERE 1=1";

            $params = [];
            if ($status) {
                $query .= " AND b.Status = ?";
                $params[] = $status;
            }
            if ($storeId) {
                $query .= " AND b.StoreID = ?";
                $params[] = $storeId;
            }
            $query .= " ORDER BY b.CreatedAt DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function approveBudget($budgetId, $approvedAmount) {
        try {
            $this->db->beginTransaction();
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $this->db->prepare("UPDATE budgets SET ApprovedAmount = ?, Status = 'Approved', ApprovedBy = ? WHERE BudgetID = ?");
            $stmt->execute([$approvedAmount, $userId, $budgetId]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Budget approved'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function allocateBudget($budgetId) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM budgets WHERE BudgetID = ? AND Status = 'Approved'");
            $stmt->execute([$budgetId]);
            $budget = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$budget) throw new Exception("Budget not approved");

            $stmt = $this->db->prepare("UPDATE budgets SET Status = 'Allocated' WHERE BudgetID = ?");
            $stmt->execute([$budgetId]);

            $desc = "Budget allocation: {$budget['Department']} - {$budget['StoreID']}";
            $user = $_SESSION['username'] ?? 'System';
            $stmt = $this->db->prepare("INSERT INTO GeneralLedger (TransactionDate, AccountType, AccountName, Description, Debit, ReferenceID, ReferenceType, StoreID, CreatedBy)
                                        VALUES (NOW(), 'Expense', 'Budget Allocation', ?, ?, ?, 'Budget', ?, ?)");
            $stmt->execute([$desc, $budget['ApprovedAmount'], $budgetId, $budget['StoreID'], $user]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Budget allocated'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== PAYROLL =====
    public function getPayrollSummary($storeId = null, $month = null, $year = null) {
        try {
            $query = "SELECT p.*, e.FirstName, e.LastName, e.HourlyRate, s.StoreName, d.name as DepartmentName
                      FROM payroll p
                      JOIN employees e ON p.EmployeeID = e.EmployeeID
                      LEFT JOIN stores s ON e.StoreID = s.StoreID
                      LEFT JOIN departments d ON e.DepartmentID = d.id
                      WHERE MONTH(p.PayPeriodEnd) = ? AND YEAR(p.PayPeriodEnd) = ?";

            $params = [$month, $year];
            if ($storeId) {
                $query .= " AND e.StoreID = ?";
                $params[] = $storeId;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ===== EMPLOYEE SALARIES & DEPARTMENTS =====
    public function getEmployeeSalaries() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM v_employee_salary_details ORDER BY DepartmentName, EmployeeName");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getDepartments() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM departments WHERE status = 'Active' ORDER BY name");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getSalaryGradesByDepartment($departmentId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM department_salary_grades WHERE DepartmentID = ? ORDER BY MinHourlyRate");
            $stmt->execute([$departmentId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateEmployeeSalary($employeeId, $hourlyRate, $gradeId, $effectiveDate, $notes) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("CALL UpdateEmployeeSalary(?, ?, ?, ?, ?)");
            $stmt->execute([$employeeId, $hourlyRate, $gradeId, $effectiveDate, $notes]);
            $this->db->commit();
            return ['success' => true, 'message' => 'Salary updated'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getDepartmentPayrollSummary($month, $year) {
        try {
            $stmt = $this->db->prepare("SELECT 
                        d.id as DepartmentID, d.name as DepartmentName, d.base_hourly_rate as BaseRate,
                        COUNT(DISTINCT e.EmployeeID) as EmployeeCount,
                        COALESCE(SUM(p.GrossPay), 0) as TotalGross,
                        COALESCE(SUM(p.Deductions), 0) as TotalDeductions,
                        COALESCE(SUM(p.NetPay), 0) as TotalNet,
                        COALESCE(AVG(e.HourlyRate), 0) as AvgHourlyRate
                      FROM departments d
                      LEFT JOIN employees e ON d.id = e.DepartmentID AND e.Status = 'Active'
                      LEFT JOIN payroll p ON e.EmployeeID = p.EmployeeID 
                        AND MONTH(p.PayPeriodEnd) = ? AND YEAR(p.PayPeriodEnd) = ?
                      WHERE d.status = 'Active'
                      GROUP BY d.id, d.name, d.base_hourly_rate
                      ORDER BY d.name");
            $stmt->execute([$month, $year]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getSalaryAuditLog($employeeId = null, $startDate = null, $endDate = null) {
        try {
            $query = "SELECT sal.*, CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                             d.name as DepartmentName, og.GradeName as OldGrade, ng.GradeName as NewGrade
                      FROM salary_audit_log sal
                      JOIN employees e ON sal.EmployeeID = e.EmployeeID
                      LEFT JOIN departments d ON e.DepartmentID = d.id
                      LEFT JOIN department_salary_grades og ON sal.OldGradeID = og.GradeID
                      LEFT JOIN department_salary_grades ng ON sal.NewGradeID = ng.GradeID
                      WHERE 1=1";

            $params = [];
            if ($employeeId) {
                $query .= " AND sal.EmployeeID = ?";
                $params[] = $employeeId;
            }
            if ($startDate && $endDate) {
                $query .= " AND DATE(sal.ChangeDate) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            $query .= " ORDER BY sal.ChangeDate DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function addDepartment($name, $baseRate, $description) {
        try {
            $stmt = $this->db->prepare("INSERT INTO departments (name, base_hourly_rate, description, status) VALUES (?, ?, ?, 'Active')");
            $stmt->execute([$name, $baseRate, $description]);
            return ['success' => true, 'message' => 'Department added', 'id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function addSalaryGrade($departmentId, $gradeName, $minRate, $maxRate, $description) {
        try {
            $stmt = $this->db->prepare("INSERT INTO department_salary_grades (DepartmentID, GradeName, MinHourlyRate, MaxHourlyRate, Description) 
                                        VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$departmentId, $gradeName, $minRate, $maxRate, $description]);
            return ['success' => true, 'message' => 'Grade added', 'id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    public function generatePayroll($month, $year, $storeId = null) {
        try {
            $this->db->beginTransaction();

            // Get active employees
            $query = "SELECT e.EmployeeID, e.HourlyRate, e.StoreID
                      FROM employees e
                      WHERE e.Status = 'Active'";
            $params = [];
            if ($storeId) {
                $query .= " AND e.StoreID = ?";
                $params[] = $storeId;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($employees)) {
                throw new Exception("No active employees found.");
            }

            $payPeriodStart = "$year-$month-01";
            $payPeriodEnd = date('Y-m-t', strtotime($payPeriodStart)); // Last day of month

            $inserted = 0;
            foreach ($employees as $emp) {
                // Calculate hours worked (simplified: 160 hours/month)
                $hoursWorked = 160;
                $grossPay = $emp['HourlyRate'] * $hoursWorked;

                // Deductions (example: 10% tax + 5% benefits)
                $tax = $grossPay * 0.10;
                $benefits = $grossPay * 0.05;
                $totalDeductions = $tax + $benefits;
                $netPay = $grossPay - $totalDeductions;

                // Check if already exists
                $stmt = $this->db->prepare("SELECT 1 FROM payroll WHERE EmployeeID = ? AND MONTH(PayPeriodEnd) = ? AND YEAR(PayPeriodEnd) = ?");
                $stmt->execute([$emp['EmployeeID'], $month, $year]);
                if ($stmt->fetch()) {
                    continue; // Skip if already processed
                }

                $stmt = $this->db->prepare("INSERT INTO payroll 
                    (EmployeeID, PayPeriodStart, PayPeriodEnd, HoursWorked, GrossPay, Deductions, NetPay, PaymentStatus, GeneratedAt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
                $stmt->execute([
                    $emp['EmployeeID'],
                    $payPeriodStart,
                    $payPeriodEnd,
                    $hoursWorked,
                    $grossPay,
                    $totalDeductions,
                    $netPay
                ]);
                $inserted++;
            }

            $this->db->commit();
            return [
                'success' => true,
                'message' => "Payroll generated for $inserted employees.",
                'period' => date('F Y', strtotime("$year-$month"))
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    public function processPayrollPayment($payrollId, $paymentDate = null) {
        try {
            $this->db->beginTransaction();

            if (!$paymentDate) $paymentDate = date('Y-m-d');

            $stmt = $this->db->prepare("SELECT p.*, e.FirstName, e.LastName FROM payroll p JOIN employees e ON p.EmployeeID = e.EmployeeID WHERE p.PayrollID = ?");
            $stmt->execute([$payrollId]);
            $pay = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pay) throw new Exception("Payroll record not found");
            if ($pay['PaymentStatus'] === 'Paid') throw new Exception("Already paid");

            $stmt = $this->db->prepare("UPDATE payroll SET PaymentStatus = 'Paid', PaymentDate = ? WHERE PayrollID = ?");
            $stmt->execute([$paymentDate, $payrollId]);

            $user = $_SESSION['username'] ?? 'System';
            $desc = "Payroll payment: {$pay['FirstName']} {$pay['LastName']} - " . date('M Y', strtotime($pay['PayPeriodEnd']));
            $stmt = $this->db->prepare("INSERT INTO GeneralLedger 
                (TransactionDate, AccountType, AccountName, Description, Credit, ReferenceID, ReferenceType, StoreID, CreatedBy)
                VALUES (?, 'Expense', 'Salaries & Wages', ?, ?, ?, 'Payroll', ?, ?)");
            $stmt->execute([$paymentDate, $desc, $pay['NetPay'], $payrollId, $pay['StoreID'], $user]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Payroll paid successfully'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>