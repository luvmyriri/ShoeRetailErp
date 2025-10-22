# Shoe Retail ERP - System Process Flow Documentation

## Overview
This document outlines the complete process flows for the Shoe Retail ERP system, detailing each module's operations, role-based access control, data transformations, and cross-module integrations. The system manages Inventory, Sales, Procurement, Customer Management, Accounting, and Human Resources for a shoe retail business.

---

## 1. INVENTORY MANAGEMENT MODULE

### 1.1 Stock Entry Process
**Purpose**: Add new inventory or receive restocked goods from suppliers.

**Flow Diagram**:
```
Supplier Delivery → Inventory Encoder Input → Validate Stock Details → 
Update Products Table → Update Inventory Table → Record in General Ledger → 
Dashboard Update
```

**Actors**: Inventory Manager, Inventory Encoder, Store Manager
**Required Permissions**: `can_process_stock_entry`, `can_manage_inventory`

**Process Steps**:
1. Inventory Encoder receives supplier delivery documentation
2. Accesses "Add Stock" form in Inventory module
3. Enters product details (SKU, Brand, Model, Size, Color, Quantity, Cost)
4. System validates:
   - Product exists or creates new entry
   - Quantity is positive
   - User has `can_process_stock_entry` permission
5. Updates `Products` table with product information
6. Increments `Inventory.Quantity` for specific store
7. Records cost in `GeneralLedger` (Asset: Inventory)
8. Dashboard displays updated stock levels in real-time

**Example Data Flow**:
```
Input: 100 pairs Nike Air Max, SKU: SNK-AJX-10-M, Cost: $80/pair
→ Products table: SKU recorded with brand/model details
→ Inventory table: Store A, Quantity: +100
→ GeneralLedger: Debit Asset (Inventory): $8,000
→ Dashboard: Stock card shows 100 units available
```

---

### 1.2 Stock Tracking & Monitoring

**Flow Diagram**:
```
Real-time Inventory Query → Filter by Store/Product/Size → 
Display Stock Levels → Highlight Low/High Stock → Export Report
```

**Actors**: Inventory Manager, Inventory Analyst, Store Manager
**Required Permissions**: `can_view_stock_reports`

**Process Steps**:
1. User accesses "Stock Levels" dashboard
2. System queries `Inventory` joined with `Products` and `Stores`
3. Displays stock metrics:
   - Total units per product
   - Units per size
   - Units per store
   - Stock movement (in/out)
4. Highlights items below minimum threshold (configurable, default: 10 units)
5. Provides filtering options (brand, model, size, store)
6. Allows export as CSV/PDF

**Dashboard Widgets**:
- **Total Products**: Count from Products table
- **Low Stock Items**: Count where Quantity < MinimumThreshold
- **Inventory Value**: SUM(Quantity × Cost)
- **Stock Health**: Percentage of items at optimal levels

**Example Query**:
```sql
SELECT p.SKU, p.Brand, p.Model, p.Size, i.Quantity, i.StoreID, s.StoreName
FROM Inventory i
JOIN Products p ON i.ProductID = p.ProductID
JOIN Stores s ON i.StoreID = s.StoreID
WHERE i.Quantity < p.MinimumThreshold
ORDER BY i.Quantity ASC;
```

---

### 1.3 Automated Stock Alerts

**Flow Diagram**:
```
Cron Job → Check Stock Levels → Compare with Thresholds → 
Generate Alert → Send to Inventory Manager → Log Alert → 
Trigger Procurement if Needed
```

**Actors**: System (automated), Inventory Manager
**Configuration**: Minimum threshold (10), Maximum threshold (100)

**Process Steps**:
1. PHP cron job runs daily at 8:00 AM
2. Queries products below minimum stock
3. Sends email alert to Inventory Manager with:
   - Product details (SKU, Brand, Model)
   - Current quantity
   - Recommended reorder quantity
4. Logs alert in `StockAlerts` table
5. Dashboard displays pending alerts
6. Procures can create purchase order directly from alert

**Alert Conditions**:
- Low Stock: Quantity ≤ MinimumThreshold (10 units)
- Overstock: Quantity ≥ MaximumThreshold (100 units)
- Dead Stock: No sales in 90 days

---

### 1.4 Return & Refund Processing

**Flow Diagram**:
```
Customer Return Request → Verify Product/Condition → 
Update Sales Record → Increase Inventory → 
Reverse General Ledger Entry → Process Refund → 
Update Customer Records
```

**Actors**: Sales Manager, Store Manager, Inventory Manager
**Required Permissions**: `can_process_refunds`, `can_manage_inventory`

**Process Steps**:
1. Customer initiates return with receipt/order ID
2. Sales Manager verifies:
   - Product exists and matches receipt
   - Return reason (defective, wrong size, changed mind)
   - Return window (typically 30 days)
3. Inspects returned item
4. If approved:
   - Updates `Sales` table: Status = "Refunded"
   - Increases `Inventory.Quantity` for store
   - Reverses revenue in `GeneralLedger`
   - Reverses tax in `TaxRecords`
   - Updates `AccountsReceivable` (if credit sale)
   - Processes refund to original payment method
5. If rejected: Creates note in `SupportTickets`
6. Dashboard shows return metrics

**Example Data Flow**:
```
Input: Customer returns defective Nike Air Max (Order #12345)
→ Sales: Status changed from "Completed" to "Refunded"
→ Inventory: Store A quantity +1
→ GeneralLedger: Reverse revenue entry, adjust tax
→ AccountsReceivable: Reduce receivable if credit sale
→ Dashboard: Refunds widget updated
```

---

## 2. SALES MANAGEMENT MODULE

### 2.1 Point of Sale (POS) Process

**Flow Diagram**:
```
Customer → Cashier Scan Products → Check Stock → 
Apply Discounts → Calculate Tax → Process Payment → 
Update Inventory → Generate Invoice → Update Customer Points → 
Record Sale Financials
```

**Actors**: Cashier, Sales Manager
**Required Permissions**: `can_process_sale`

**Process Steps**:
1. Cashier accesses POS interface
2. Scans/searches product (by SKU or barcode)
3. System checks stock in `Inventory`
4. For each item:
   - Adds to cart with quantity
   - Displays unit price from `Products`
   - Shows running total
5. Applies discounts (manual or loyalty-based):
   - Loyalty points redemption: Points ÷ 10 = $ discount
   - Promotional discounts: Applied by code
6. Calculates total:
   - Subtotal = Sum of (Quantity × UnitPrice)
   - Tax = Subtotal × 12% (Philippines VAT)
   - Total = Subtotal + Tax
7. Payment processing:
   - Cash: Received amount, calculate change
   - Card: Validate payment terminal response
   - Credit: Set due date (30 days)
8. System creates transaction:
   - Inserts into `Sales` table
   - Inserts items into `SaleDetails`
   - Decreases `Inventory.Quantity`
   - Updates `Customers.LoyaltyPoints`
   - Records revenue/tax in `GeneralLedger`
   - If credit: Creates `AccountsReceivable` entry
   - Logs transaction in `TaxRecords`
9. Generates and prints invoice
10. Displays confirmation on dashboard

**Example Transaction**:
```
Items:
  - Nike Air Max (Size 10): 1 × $100 = $100
  - Adidas Stan Smith (Size 9): 2 × $80 = $160

Subtotal: $260
Tax (12%): $31.20
Total: $291.20
Payment: Cash, Received $300, Change: $8.80

Loyalty Points: 29 points added to customer account

Ledger Entry:
  Debit: Cash $291.20
  Credit: Sales Revenue $260
  Credit: Sales Tax Payable $31.20
```

---

### 2.2 Order Processing (Online/In-Store)

**Flow Diagram**:
```
Customer Order → Verify Stock → Create Sale Record → 
Prepare Fulfillment → Update Inventory → 
Generate Packing List → Track Shipment → Record Financials
```

**Actors**: Sales Manager, Online Sales, Store Manager
**Required Permissions**: `can_process_sale`

**Process Steps**:
1. Order received (online form, phone, or in-store)
2. Sales Manager reviews order details
3. Checks stock availability in `Inventory`
4. If insufficient stock:
   - Backorder option presented to customer
   - Payment authorized for future fulfillment
5. If in stock:
   - Creates sale in `Sales` table
   - Adds items to `SaleDetails`
   - Generates packing list
   - Notifies warehouse/store for fulfillment
   - Updates financial records same as POS
6. Tracks fulfillment status:
   - Pending → Prepared → Shipped → Delivered
7. Sends customer shipment notification

---

### 2.3 Return & Refund Handling

**Flow Diagram**:
```
Return Request → Verify Sale → Inspect Item → 
Approve/Reject → Update Sale Status → 
Reverse Inventory/Financials → Process Refund
```

**Actors**: Sales Manager, Store Manager
**Required Permissions**: `can_process_refunds`

**Process Steps**:
1. Customer submits return request with order ID
2. Sales Manager verifies sale in `Sales` table
3. Checks return eligibility:
   - Within 30-day window
   - Item condition acceptable
   - Original receipt available
4. If approved:
   - Updates `Sales.Status` = "Refunded"
   - Increments `Inventory.Quantity`
   - Reverses `GeneralLedger` entry
   - Reverses `TaxRecords` entry
   - Updates `AccountsReceivable` if credit sale
   - Issues refund to original payment method
   - Updates `Customers.LoyaltyPoints` (removes points if redeemed)
5. Generates return receipt
6. Logs in audit trail

---

## 3. PROCUREMENT MODULE

### 3.1 Purchase Order Creation

**Flow Diagram**:
```
Restock Needed → Create PO → Select Supplier → 
Enter Product Details → Calculate Cost → 
Send to Supplier → Log in Accounts Payable
```

**Actors**: Procurement Manager
**Required Permissions**: `can_create_purchase_order`

**Process Steps**:
1. Procurement Manager triggered by:
   - Stock alert (low inventory)
   - Manual restock request
   - Demand forecast
2. Accesses "Create Purchase Order" form
3. Selects supplier from `Suppliers` table
4. Adds products:
   - Selects product/size
   - Enters quantity
   - Unit cost auto-populated from last purchase or supplier quote
5. System calculates:
   - Line total = Quantity × UnitCost
   - Total amount = Sum of line items
6. Sets expected delivery date
7. Creates purchase order in `PurchaseOrders` table
8. Adds items to `PurchaseOrderDetails`
9. Creates `AccountsPayable` entry
10. Generates PO document (PDF/email)
11. Sends to supplier
12. Sets status to "Pending"

**Example PO**:
```
PO #: PO-2025-001
Supplier: Nike Inc.
Date: 2025-10-22
Expected Delivery: 2025-11-05

Items:
- Nike Air Max (Size 10): 50 units @ $70 = $3,500
- Nike Air Max (Size 9): 50 units @ $70 = $3,500

Total: $7,000
Payment Terms: Net 30 days
```

---

### 3.2 Supplier Management

**Flow Diagram**:
```
Add/Update Supplier → Validate Details → 
Store in Database → Link to Purchases → 
Track Performance Metrics
```

**Actors**: Procurement Manager, Admin
**Required Permissions**: `can_manage_suppliers`

**Process Steps**:
1. Procurement Manager accesses "Suppliers" list
2. Creates new or edits existing supplier
3. Enters details:
   - Company name
   - Contact person
   - Phone/Email
   - Address
   - Payment terms
   - Lead time (days to delivery)
4. System validates:
   - Unique email/phone
   - Valid contact information
5. Stores in `Suppliers` table
6. System tracks supplier metrics:
   - Total purchases
   - Average lead time
   - Quality rating (based on returns/issues)
   - Payment reliability

---

### 3.3 Goods Receipt & Verification

**Flow Diagram**:
```
Supplier Delivery Received → Verify Items → 
Check Quality → Update PO Status → 
Increase Inventory → Confirm Payable → 
Log Receipt
```

**Actors**: Procurement Manager, Inventory Manager, Inventory Encoder
**Required Permissions**: `can_process_goods_receipt`

**Process Steps**:
1. Receives delivery notification or physical goods
2. Accesses "Goods Receipt" form
3. Links to pending purchase order
4. For each item:
   - Counts/verifies quantity received
   - Inspects quality
   - Compares against PO details
5. System flags discrepancies:
   - Quantity mismatch (under/over delivery)
   - Damaged goods
   - Wrong items
6. If acceptable:
   - Updates `PurchaseOrders.Status` = "Received"
   - Increases `Inventory.Quantity`
   - Confirms `AccountsPayable` entry
   - Records receipt in `GeneralLedger` (Asset: Inventory)
   - Updates supplier performance metrics
7. If issues:
   - Creates return RMA
   - Notifies supplier
   - Holds payment until resolution
8. Generates goods receipt report

**Example Receipt**:
```
PO #: PO-2025-001
Received Items:
- Nike Air Max (Size 10): Ordered 50, Received 50 ✓
- Nike Air Max (Size 9): Ordered 50, Received 48 (2 damaged)

Status: Partial - 2 units damaged, RMA created
Inventory Updated: +98 units
AccountsPayable: Adjusted for returned items
```

---

## 4. CUSTOMER MANAGEMENT MODULE

### 4.1 Customer Profile Management

**Flow Diagram**:
```
New Customer → Enter Details → Validate Information → 
Store in Database → Assign Customer ID → 
Link to Sales/Loyalty Program
```

**Actors**: Customer Service, Sales Manager, Admin
**Required Permissions**: `can_manage_customers`

**Process Steps**:
1. Customer Service accesses "Customers" section
2. Creates new customer profile:
   - Name
   - Email
   - Phone
   - Address
   - Birth date (for promotions)
3. System validates:
   - Unique email/phone
   - Valid format
4. Auto-generates unique Customer ID
5. Stores in `Customers` table
6. Initializes:
   - LoyaltyPoints = 0
   - TotalSpent = 0
   - LastPurchaseDate = NULL
7. Can link to existing sales/accounts
8. Creates customer card in dashboard

---

### 4.2 Loyalty Program Management

**Flow Diagram**:
```
Sale Completed → Calculate Loyalty Points → 
Update Customer Points Balance → 
Enable Redemption in Future Purchases → 
Track Point History
```

**Actors**: Customer Service, Sales Manager (automatic process)
**Required Permissions**: `can_view_loyalty_points`

**Process Steps**:
1. After each sale (POS or order):
   - System calculates: Points = SaleAmount ÷ 10 (rounded down)
   - Example: $100 sale = 10 points
2. Updates `Customers.LoyaltyPoints` += calculated points
3. Records point transaction in `LoyaltyTransactions` table:
   - TransactionID
   - CustomerID
   - PointsEarned
   - TransactionDate
   - SourceSaleID
4. Customer can view point balance in:
   - Receipt at POS
   - Account dashboard
   - Customer portal
5. Redemption:
   - 1 point = $0.10 redemption
   - Example: 100 points = $10 discount on next purchase
   - Can be applied at POS by scanning customer loyalty card/ID
6. Tracks point expiration (if applicable):
   - Points expire after 12 months of inactivity

**Example Loyalty Flow**:
```
Customer John Doe:
- Purchase 1: $150 → Earned 15 points (Total: 15)
- Purchase 2: $80 → Earned 8 points (Total: 23)
- Purchase 3: $200, Redeems 20 points ($2 discount)
  → Earned 20 points, Used 20 points (Total: 23)
```

---

### 4.3 Customer Support Ticketing

**Flow Diagram**:
```
Customer Complaint/Inquiry → Create Support Ticket → 
Assign to Support Agent → Track Resolution → 
Update Customer Status → Close Ticket → Log Feedback
```

**Actors**: Customer Service
**Required Permissions**: `can_create_support_tickets`

**Process Steps**:
1. Customer submits support request:
   - Via phone, email, or online form
   - Provides order number/issue description
2. Customer Service creates ticket in `SupportTickets`:
   - CustomerID
   - StoreID
   - Description
   - Priority (Low/Medium/High/Critical)
   - Status = "Open"
   - CreatedDate = current timestamp
3. System auto-assigns ticket number: TKT-YYYYMMDD-#### 
4. Customer Service investigates:
   - Pulls related sales record
   - Contacts customer if needed
   - Researches resolution
5. Updates ticket:
   - Status: In Progress
   - Notes: Investigation findings
6. Takes corrective action (e.g., issue refund, replacement)
7. Closes ticket:
   - Status: Resolved/Closed
   - ClosedDate: current timestamp
   - ResolutionNotes: final resolution provided
8. Sends customer confirmation
9. System tracks metrics:
   - Average resolution time
   - Customer satisfaction rating
   - Issue categories

**Example Ticket**:
```
Ticket ID: TKT-20251022-0001
Customer: John Doe
Issue: Received wrong shoe size
Priority: High
Status: Resolved
Resolution: Shipped replacement, authorized return label
Satisfaction Rating: 5/5 stars
```

---

## 5. ACCOUNTING MODULE

### 5.1 General Ledger Management

**Flow Diagram**:
```
Transaction Occurs (Sale/Purchase/Expense) → 
Create Ledger Entry → Classify Account Type → 
Link to Source → Record Debit/Credit → 
Dashboard Update → Period Closing
```

**Actors**: Accountant, Admin
**Required Permissions**: `can_manage_ledger`

**Process Steps**:
1. Transaction generates in another module:
   - Sales creates Revenue entry
   - Procurement creates Asset entry
   - Payroll creates Expense entry
2. PHP automatically inserts into `GeneralLedger`:
   - TransactionID (auto-generated)
   - AccountType (Asset, Liability, Equity, Revenue, Expense)
   - Amount (absolute value)
   - DebitCredit (indicating direction)
   - Description
   - ReferenceID (links to source table: SaleID, POID, etc.)
   - ReferenceType (Sale, PurchaseOrder, Expense, Payroll)
   - TransactionDate
3. Accountant can:
   - View all entries in General Ledger
   - Filter by date range, account type, store
   - Generate trial balance
4. System maintains accounting equation:
   - Assets = Liabilities + Equity
   - Revenue - Expense = Net Income

**Example Entries**:
```
Sale of $291.20 creates:
  DR: Cash (Asset) $291.20
  CR: Sales Revenue (Revenue) $260
  CR: Sales Tax Payable (Liability) $31.20

Purchase of $7,000 creates:
  DR: Inventory (Asset) $7,000
  CR: Accounts Payable (Liability) $7,000
```

---

### 5.2 Accounts Receivable Management

**Flow Diagram**:
```
Credit Sale Created → Generate AR Entry → 
Track Due Date → Monitor Payment Status → 
Issue Reminders → Record Payment → Close AR
```

**Actors**: Accountant
**Required Permissions**: `can_process_ar_ap`

**Process Steps**:
1. When credit sale created (PaymentStatus = "Credit"):
   - System generates `AccountsReceivable` entry:
     - SaleID
     - CustomerID
     - AmountDue = total sale amount
     - DueDate = current date + 30 days
     - Status = "Pending"
2. Accountant tracks AR:
   - Views AR aging report (0-30, 31-60, 61-90, 90+ days)
   - Identifies overdue accounts
3. System auto-generates reminders:
   - 5 days before due date
   - On due date
   - Every 7 days if overdue
4. Customer payment:
   - Accountant records payment in AR entry
   - Updates Status = "Paid"
   - Records cash receipt in `GeneralLedger`
5. Can create credit memos for disputes
6. Reports show:
   - Total AR outstanding
   - Percentage by age group
   - Collections rate

**Example AR Entry**:
```
SaleID: #12345
Customer: John Doe
Amount Due: $291.20
Due Date: 2025-11-21
Current Status: Pending (21 days old)
Payment Reminder: Sent (overdue by 1 day)
```

---

### 5.3 Accounts Payable Management

**Flow Diagram**:
```
Purchase Order Created → Generate AP Entry → 
Goods Receipt Received → Confirm AP → 
Process Payment → Record in Ledger → Close AP
```

**Actors**: Accountant, Procurement Manager
**Required Permissions**: `can_process_ar_ap`

**Process Steps**:
1. When purchase order created:
   - System generates preliminary `AccountsPayable` entry
   - AmountDue = PO total
   - Status = "Pending"
   - DueDate = current date + 30 days
2. Upon goods receipt:
   - Confirms AP entry
   - Status = "Confirmed"
3. Accountant tracks AP:
   - Views AP aging report
   - Identifies due payments
4. Payment processing:
   - Prepares check/bank transfer
   - Enters payment details
   - Updates AP Status = "Paid"
   - Records payment in `GeneralLedger`:
     - DR: Accounts Payable (Liability)
     - CR: Cash (Asset)
5. Reports show:
   - Total AP outstanding
   - Supplier payment performance
   - Cash flow forecast

---

### 5.4 Tax Management

**Flow Diagram**:
```
Transaction with Tax Calculated → 
Record in TaxRecords → Categorize by Type → 
Track for Compliance → Generate Tax Report → 
File Returns
```

**Actors**: Accountant
**Required Permissions**: `can_manage_ledger`

**Process Steps**:
1. When sale/purchase occurs with tax:
   - System inserts into `TaxRecords`:
     - TransactionID
     - TaxAmount
     - TaxType (VAT-Output for sales, VAT-Input for purchases)
     - TransactionDate
     - TaxableAmount
2. Monthly tax reconciliation:
   - Accountant reviews `TaxRecords` by month
   - Calculates:
     - Total Output Tax (from sales): $X
     - Total Input Tax (from purchases): $Y
     - Net Tax Payable = X - Y
3. Tax reporting:
   - Generates VAT report for BIR filing
   - Exports transaction details for audit trail
4. Payment:
   - Calculates tax payment due
   - Records payment to tax authority
   - Updates tax liability in `GeneralLedger`

---

### 5.5 Financial Reporting

**Flow Diagram**:
```
Select Report Type & Date Range → Query Ledger Data → 
Calculate Totals & Balances → Format Report → 
Generate PDF/Excel → Dashboard Display
```

**Actors**: Accountant, Manager, Admin
**Required Permissions**: `can_generate_financial_reports`

**Process Steps**:
1. Accountant selects report type:
   - **Income Statement**: Revenue - Expenses = Net Income
   - **Balance Sheet**: Assets = Liabilities + Equity
   - **Cash Flow**: Operating, Investing, Financing activities
   - **Trial Balance**: All account balances pre-close
   - **Sales Report**: By date, store, category
   - **Expense Report**: By category, department
2. System queries `GeneralLedger` for specified period
3. Calculates:
   - Account balances (sum of debits/credits)
   - Subtotals by account type
   - Net figures (revenue - expense, etc.)
4. Generates formatted report:
   - Header with company name, report type, date
   - Account lines with amounts
   - Subtotals and totals
5. Exports options:
   - PDF for distribution
   - Excel for further analysis
6. Dashboard displays key metrics:
   - Net Income
   - Total Revenue
   - Total Expense
   - Profit Margin

**Example Income Statement**:
```
INCOME STATEMENT
For Month Ending: 2025-10-31

Revenue:
  Sales Revenue: $50,000
Total Revenue: $50,000

Expenses:
  Payroll: $8,000
  Utilities: $1,200
  Supplies: $500
Total Expenses: $9,700

Net Income: $40,300
```

---

### 5.6 Bank Reconciliation

**Flow Diagram**:
```
Download Bank Statement → Upload/Enter into System → 
Match Ledger Entries → Identify Discrepancies → 
Adjust for Timing Differences → Confirm Balance
```

**Actors**: Accountant
**Required Permissions**: `can_generate_financial_reports`

**Process Steps**:
1. Accountant obtains bank statement from bank
2. Uploads statement to system or manually enters transactions
3. System attempts to auto-match:
   - Bank transactions with `GeneralLedger` entries
   - Matches by amount and approximate date (±3 days)
4. Identifies discrepancies:
   - Deposits in ledger not on bank statement → Pending deposits
   - Checks in ledger not cleared → Outstanding checks
   - Bank charges not recorded → Adjustments needed
5. Accountant reconciles:
   - Adjusts `GeneralLedger` for timing differences
   - Investigates significant variances
   - Creates journal entries for reconciling items
6. Confirms bank balance matches ledger cash balance
7. Locks transactions for the period

---

## 6. HUMAN RESOURCES MODULE

### 6.1 Employee Management

**Flow Diagram**:
```
New Employee Hired → Enter Details → Assign to Store → 
Set Compensation → Create File → Dashboard Update → 
Track Employment Records
```

**Actors**: HR Manager, Admin
**Required Permissions**: `can_manage_employees`

**Process Steps**:
1. HR Manager receives hire request
2. Creates employee record in `Employees`:
   - EmployeeID (auto-generated)
   - FirstName, LastName
   - Email, Phone
   - HireDate
   - Salary (annual)
   - StoreID (assigned store)
   - Department
   - Status = "Active"
3. System validates:
   - Unique email/phone
   - Valid hire date (not future)
4. Stores in database, generates employee ID badge
5. System links employee to:
   - `EmployeeRoles` for permission assignment
   - `Timesheets` for hours tracking
   - `Payroll` for compensation

---

### 6.2 Role & Permission Assignment

**Flow Diagram**:
```
Select Employee → Choose Role(s) → 
Assign Store Location → Set Active Period → 
Verify Permissions → Store Assignment → 
Dashboard Permissions Update
```

**Actors**: HR Manager, Admin
**Required Permissions**: `can_assign_roles`

**Process Steps**:
1. HR Manager accesses "Employee Roles" section
2. Selects employee from `Employees` list
3. Available roles:
   - Cashier: Process sales, process returns
   - Sales Manager: Override prices, manage sales
   - Inventory Manager: All inventory operations
   - Procurement Manager: Create and manage POs
   - Accountant: Manage ledger, reports
   - Customer Service: Manage tickets, customer data
   - HR Manager: Manage employees, payroll
   - Store Manager: Oversee all store operations
   - Admin: Full system access
4. Adds one or more roles to employee
5. System validates role availability per store
6. Sets active period:
   - StartDate (today or future)
   - EndDate (optional, for temporary assignments)
7. Stores in `EmployeeRoles` table:
   - EmployeeID, RoleID, StartDate, EndDate, IsActive
8. System updates PHP permission checks:
   - When user logs in, pulls roles from `EmployeeRoles`
   - Loads permissions from `Roles.Permissions` JSON
   - Grants access to appropriate modules/features
9. Dashboard updated with:
   - Roles assigned per employee
   - Active role count per store

**Example Assignment**:
```
Employee: Jane Smith
Roles Assigned:
  - Cashier (Store A, Start: 2025-10-22)
  - Sales Manager (Store A, Start: 2026-01-01)
  
PHP Permission Load:
  On login, Jane gets:
    - POS access (Cashier)
    - Sales override permissions (Sales Manager from Jan 1)
```

---

### 6.3 Timesheet Management

**Flow Diagram**:
```
Employee Clock-In/Out → Record Hours → 
Manager Approval → Calculate OT → 
Store Timesheet → Use for Payroll → 
Track Attendance
```

**Actors**: Employee, Store Manager, HR Manager
**Required Permissions**: `can_manage_employees`

**Process Steps**:
1. Employee clocks in via:
   - POS terminal (biometric or PIN)
   - Mobile app
   - Manual entry by Manager
2. System records:
   - EmployeeID
   - ClockInTime
   - ClockOutTime
   - WorkDate
3. Automatically calculates:
   - HoursWorked = (ClockOutTime - ClockInTime) / 60
   - Breaks deducted if configured
4. Store Manager reviews timesheet:
   - Verifies hours accuracy
   - Approves or flags discrepancies
   - Can manually adjust with notes
5. System detects overtime:
   - If HoursWorked > 8 hours: OTHours = HoursWorked - 8
   - Flags for payroll calculation
6. Stores in `Timesheets` table:
   - TimesheetID, EmployeeID, WorkDate, HoursWorked, OTHours, Status
7. Generates timesheet report:
   - Daily, weekly, monthly views
   - Shows attendance, punctuality metrics

**Example Timesheet**:
```
Employee: John Doe
Date: 2025-10-22
Clock In: 08:00 AM
Clock Out: 06:30 PM
Hours Worked: 10.5 hours
OT Hours: 2.5 hours
Status: Approved

Weekly Total: 42.5 hours
OT Hours This Week: 2.5 hours
```

---

### 6.4 Payroll Processing

**Flow Diagram**:
```
Gather Timesheets & Adjustments → 
Calculate Gross Pay → Deduct Taxes & Deductions → 
Calculate Net Pay → Generate Pay Slip → 
Process Payment → Record in Payroll & Ledger → 
Distribute Pay Stubs
```

**Actors**: HR Manager, Accountant
**Required Permissions**: `can_process_payroll`

**Process Steps**:
1. HR Manager triggers payroll process (typically monthly)
2. System queries `Timesheets` for pay period:
   - Collects all worked hours per employee
   - Identifies overtime
3. For each employee:
   - **Gross Pay Calculation**:
     - RegularHours = HoursWorked (up to 160/month standard)
     - Gross = (Salary ÷ 160) × RegularHours
     - OT Premium = (Gross ÷ RegularHours) × 1.25 × OTHours
     - Total Gross = Gross + OT Premium
   - **Deductions**:
     - Income Tax (withheld per Philippines tax tables)
     - SSS (social security)
     - PhilHealth (health insurance)
     - PAG-IBIG (housing fund)
     - Other deductions (uniforms, loans, etc.)
   - **Net Pay**:
     - Net = Gross - Deductions
4. System generates pay slip:
   - Employee ID, name, period
   - Detailed breakdown of gross, deductions, net
   - YTD totals
5. Stores in `Payroll` table:
   - EmployeeID, PeriodEndDate, GrossPay, Deductions, NetPay, PaymentMethod
6. Records in `GeneralLedger`:
   - DR: Payroll Expense $X (total gross)
   - CR: Cash/Payable $X (net paid)
   - CR: Tax Payable (withholdings)
7. Processes payment:
   - Bank transfer if direct deposit
   - Cash if manual payment
8. Distributes pay stubs to employees
9. Generates payroll report for compliance

**Example Payroll Entry**:
```
Employee: Jane Smith
Period: Oct 1-31, 2025

Gross Pay:
  Regular (160 hours @ $10/hr): $1,600
  OT (10 hours @ $12.50/hr): $125
  Total Gross: $1,725

Deductions:
  Income Tax: $172.50
  SSS: $86.25
  PhilHealth: $43.13
  PAG-IBIG: $100
  Total Deductions: $401.88

Net Pay: $1,323.12

Ledger Entry:
  DR: Payroll Expense $1,725
  CR: Cash $1,323.12
  CR: Tax Payable $172.50
  CR: SSS Payable $86.25
  CR: PhilHealth Payable $43.13
  CR: PAG-IBIG Payable $100
```

---

## 7. CROSS-MODULE INTEGRATION FLOWS

### 7.1 Complete Sales Cycle

```
Customer Entry
    ↓
Inventory Check (Product Available?)
    ↓
POS Transaction
    ↓
─────────────────────────────────────────────────────
│                                                   │
Sales Module         Inventory Module    Accounting Module
- Record Sale        - Decrease Stock    - Revenue Entry
- Generate Invoice   - Alert if Low      - Tax Entry
- Add Loyalty Points                     - AR if Credit
    │                    │                    │
    └────────────────────┴────────────────────┘
                    ↓
        Dashboard Updates
        - Sales card: +1
        - Inventory card: -1
        - Revenue total: +$
            ↓
    Return/Refund (Optional)
            ↓
    ─────────────────────────────
    │                           │
    Reverse all entries from cycle
    Update Stock: +1
    Update AR: -$
    Update Loyalty: -points (if redeemed)
```

### 7.2 Restocking Cycle

```
Low Stock Alert
    ↓
Procurement Manager Reviews
    ↓
Create Purchase Order
    ↓
─────────────────────────────────────────────────────
│                                                   │
Procurement          Accounting              Inventory
- PO Created         - AP Entry              - Alert Cleared
- Send to Supplier   - Log Payable          - Forecast Updated
    │                    │                        │
    Supplier Delivery
    ↓
─────────────────────────────────────────────────────
│                                                   │
Goods Receipt        Confirm AP             Update Inventory
- Verify Items       - Record Cost          - Increase Stock
- Log Receipt        - Update Ledger        - Remove Alert
    │                    │                        │
    └────────────────────┴────────────────────────┘
                    ↓
            Dashboard Updates
            - Inventory cards
            - AP total
            - Stock levels
```

### 7.3 Employee to Payroll Cycle

```
Employee Hired
    ↓
HR Creates Record
    ↓
Assign Role(s)
    ↓
─────────────────────────────────
│                               │
Employee Works              Timesheet
- Clocks In/Out            - Hours Recorded
- Processes Sales          - Manager Approves
- Handles Returns              │
    │                          │
    └──────────────────────────┘
                ↓
        Monthly Payroll Run
            ↓
    ─────────────────────────────────────────
    │                                       │
    Calculate Pay              Record Ledger Entry
    - Gross                   - Expense: Payroll
    - Deductions              - Payable: Net
    - Net                     - Payable: Taxes
        │                           │
        Generate Pay Slip       Accounting Updated
        │                           │
        └───────────────────────────┘
            ↓
    Process Payment (Transfer/Cash)
    Distribute Pay Stubs
    Update Dashboard
```

---

## 8. ROLE-BASED ACCESS CONTROL

### 8.1 Permission Matrix

| Module | Cashier | Sales Mgr | Inv Mgr | Proc Mgr | Customer Svc | Accountant | HR Mgr | Admin |
|--------|---------|-----------|---------|----------|--------------|-----------|--------|-------|
| **Sales** | POS ✓ | ✓ | - | - | - | View | - | ✓ |
| **Inventory** | View | View | ✓ | View | - | View | - | ✓ |
| **Procurement** | - | - | View | ✓ | - | View | - | ✓ |
| **Customers** | - | View | - | - | ✓ | View | - | ✓ |
| **Accounting** | - | - | - | - | - | ✓ | - | ✓ |
| **HR** | - | - | - | - | - | View | ✓ | ✓ |

### 8.2 PHP Permission Check Example

```php
// Check if user has specific permission
function checkUserPermission($employeeID, $permission) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM EmployeeRoles er
        JOIN Roles r ON er.RoleID = r.RoleID
        WHERE er.EmployeeID = ?
        AND er.IsActive = 'Yes'
        AND JSON_CONTAINS(r.Permissions, ?)
    ");
    
    $stmt->execute([$employeeID, json_encode($permission)]);
    return $stmt->fetchColumn() > 0;
}

// Usage in POS
if (!checkUserPermission($employeeID, 'can_process_sale')) {
    die('Error: Insufficient permissions to process sale');
}
```

---

## 9. DASHBOARD METRICS & MONITORING

### 9.1 Real-time Dashboard Indicators

**Sales Module**:
- Today's Sales: $X (sum of today's sales revenue)
- Orders Processed: N (count of sales today)
- Avg Transaction: $X (total sales ÷ order count)
- Refunds/Returns: N (count of returns)

**Inventory Module**:
- Total Products: N (count from Products)
- Low Stock Items: N (count where Qty < Min)
- Inventory Value: $X (sum of Qty × Cost)
- Stock Health: X% (optimal items ÷ total items)

**Procurement Module**:
- Open POs: N (count where Status = Pending)
- On Order: N (sum of PO item quantities)
- Expected Deliveries: Date
- Supplier Performance: Rating

**Accounting Module**:
- Total Revenue: $X (month-to-date)
- Total Expenses: $X (month-to-date)
- Net Income: $X
- AR Outstanding: $X
- AP Outstanding: $X

**HR Module**:
- Employees: N (total active)
- Current Payroll: $X (monthly costs)
- Attendance Rate: X%
- Open Positions: N

---

## 10. AUDIT TRAIL & COMPLIANCE

### 10.1 Transaction Logging

All transactions logged with:
- TransactionID (unique identifier)
- EmployeeID (who made change)
- Timestamp (when change occurred)
- Module (which module)
- Action (what changed)
- OldValue (previous state)
- NewValue (new state)
- IPAddress (for security)

### 10.2 Compliance Reports

- Sales Tax Report: For BIR filing
- Payroll Report: Employee compensation records
- Inventory Count: Physical vs. system records
- Accounts Reconciliation: Bank reconciliation report
- Audit Trail: All user actions for review

---

## 11. IMPLEMENTATION NOTES

### 11.1 Database Transactions
- All multi-table updates use MySQL transactions to ensure data consistency
- Example: Sale must update Sales, Inventory, GeneralLedger atomically

### 11.2 Error Handling
- Validate all user inputs before processing
- Check stock availability before sale completion
- Verify role permissions before sensitive operations
- Log all errors in error_log table for troubleshooting

### 11.3 Performance Optimization
- Index frequently queried columns (CustomerID, ProductID, EmployeeID)
- Cache dashboard metrics for 5-minute intervals
- Implement pagination for large data sets
- Use prepared statements to prevent SQL injection

### 11.4 Backup & Recovery
- Daily database backups at 2:00 AM
- Monthly full backups archived off-site
- Transaction logs maintained for point-in-time recovery
- Disaster recovery plan documented

---

## END OF SYSTEM PROCESS FLOW DOCUMENTATION

Last Updated: 2025-10-22
Version: 1.0
Prepared For: Shoe Retail ERP System
Database Artifact ID: ec25f7f7-b2b5-4f89-810c-bc9babcf850d
