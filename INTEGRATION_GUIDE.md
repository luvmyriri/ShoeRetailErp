# Shoe Retail ERP - Module Integration Guide

## System Architecture

### Database Schema (ERP_DEFAULT_SCHEMA_FINAL.sql)
- **Core Tables**: stores, users, suppliers, roles, customers
- **Products**: products, product_units, units
- **Inventory**: inventory, stockmovements
- **Sales**: sales, saledetails, invoices, invoiceitems, returns, customerpayments
- **Procurement**: purchaseorders, purchaseorderdetails, accountspayable, supplierpayments, transaction_history_precurement, procurement_returns
- **HR**: employees, branches, departments, leavetypes, leavebalances, leaverequests, attendance, payroll
- **Accounting**: generalledger, expenses, accountsreceivable, taxrecords
- **Support**: supporttickets

### API Modules Location
All API endpoints are in `/api/` folder:
- `hr.php` - HR Management (employees, attendance, payroll, leave)
- `procurement.php` - Purchase Orders, Suppliers, AP
- `crm.php` - Customer Relations, Reports
- `sales.php` - Orders, Invoices, Returns, AR
- `inventory.php` - Products, Stock Movements
- `accounting.php` - GL, Reports, Financial Statements
- `customers.php` - Customer Management
- `dashboard.php` - Dashboard Analytics

### Support Files
- `/config/database.php` - Database connection (PDO)
- `/includes/core_functions.php` - Business logic functions
- `/includes/db_helper.php` - Database helper functions (PDO deprecated, use config/database.php)
- `/includes/role_management_functions.php` - Role/Permission functions

### Frontend Pages
- `/public/index.php` - Dashboard
- `/public/profile.php` - User Profile
- `/public/settings.php` - System Settings
- `login.php` - Authentication
- `logout.php` - Logout

## Module Integration Points

### 1. HR ↔ Accounting
**Flow**: Employee Payroll → GL Entries
- Payroll creation automatically records GL entry
- Deduction: GL (Expense)
- Credit: GL (Liability - Payroll Payable)

### 2. Procurement ↔ Inventory ↔ Accounting
**Flow**: PO → Receipt → Inventory → GL & AP
- PO created with status "Pending"
- Upon receipt: Inventory updated, AP created
- GL entry: (Asset: Inventory, Liability: AP)

### 3. Sales ↔ Inventory ↔ Accounting
**Flow**: Sale → Inventory ↔ GL & AR
- Sale processed → Inventory decremented → GL entries
- GL: (Revenue, COGS)
- If credit sale → AR record created

### 4. CRM ↔ Sales ↔ Customers
**Flow**: Customer interaction → Order → Payment tracking
- Customer creation in CRM
- Orders tracked per customer
- Loyalty points managed
- Support tickets linked to customers

## API Response Format

All endpoints return standardized JSON:

```json
{
  "success": true/false,
  "message": "Description",
  "data": {},
  "error": "Error details if applicable"
}
```

Status Codes:
- 200: Success
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 500: Server Error

## Authentication
- Session-based via `login.php`
- Functions: `isLoggedIn()`, `hasPermission($role)`
- Roles: Admin, Manager, Cashier, Accountant, Support, Inventory, Sales, Procurement, HR

## Key Integration Functions

### Core Functions (core_functions.php)
- `processSale()` - Handles complete sale with inventory & GL
- `receivePurchaseOrder()` - Receives PO, updates inventory & AP
- `processARPayment()` - Records customer payment with GL entries
- `recordGeneralLedger()` - Records accounting entries
- `createAccountsReceivable()` - Creates AR from sale
- `createAccountsPayable()` - Creates AP from PO

### Database Functions (database.php - PDO)
- `getDB()->fetchOne($query, $params)` - Single row
- `getDB()->fetchAll($query, $params)` - Multiple rows
- `getDB()->insert($query, $params)` - Insert record
- `getDB()->update($query, $params)` - Update record
- `getDB()->delete($query, $params)` - Delete record
- `getDB()->beginTransaction()` / `commit()` / `rollback()`

## Implementation Status

### ✅ COMPLETE
- Database Schema (ERP_DEFAULT_SCHEMA_FINAL.sql)
- Core Functions (core_functions.php)
- Database Configuration (database.php - PDO)

### 🔧 IN PROGRESS - NEED INTEGRATION
- HR Module (hr.php) - Fix table names, integrate GL
- Procurement Module (procurement.php) - Ensure AP creation
- Sales Module (sales.php) - Ensure AR, GL, Inventory hooks
- Inventory Module (inventory.php) - Stock movements
- Accounting Module (accounting.php) - GL queries
- CRM Module (crm.php) - Customer reports
- Customers Module (customers.php) - Customer CRUD

## Next Steps
1. Standardize all modules to use Database class (config/database.php)
2. Ensure all modules use jsonResponse() for consistency
3. Verify cross-module data flows
4. Add missing function implementations
5. Test end-to-end workflows
