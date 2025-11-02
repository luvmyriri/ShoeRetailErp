# Shoe Retail ERP System - Complete Implementation Guide

## Project Overview
This is a comprehensive Enterprise Resource Planning (ERP) system for shoe retail businesses, integrating inventory management, sales, procurement, customer management, accounting, and human resources modules with role-based access control.

---

## Implemented Components

### 1. Database Helper Functions (`includes/db_helper.php`)
**Purpose**: Centralized database operations with consistent error handling

**Functions Implemented**:
- `getDB()` - Singleton database connection
- `dbFetchAll($query, $params)` - Fetch multiple rows
- `dbFetchOne($query, $params)` - Fetch single row
- `dbInsert($query, $params)` - Execute INSERT and return ID
- `dbUpdate($query, $params)` - Execute UPDATE/DELETE
- `dbExecute($query, $params)` - Execute arbitrary queries
- `hashPassword($password)` - Secure password hashing (bcrypt)
- `verifyPassword($password, $hash)` - Password verification
- `jsonResponse($data, $statusCode)` - Standardized JSON responses
- `logInfo($message, $context)` - Info logging
- `logError($message, $context)` - Error logging

**Usage Example**:
```php
$user = dbFetchOne("SELECT * FROM Users WHERE UserID = ?", [$userId]);
$productId = dbInsert("INSERT INTO Products (SKU, Brand) VALUES (?, ?)", [$sku, $brand]);
$affected = dbUpdate("UPDATE Inventory SET Quantity = ? WHERE ProductID = ?", [$qty, $prodId]);
```

---

### 2. API Endpoints

#### **Inventory Management API** (`api/inventory.php`)
**Endpoints**:
- `get_products` - Fetch all products with pagination & search
- `get_product` - Get specific product details
- `add_product` - Add new product (requires can_manage_inventory permission)
- `update_product` - Update product details
- `stock_entry` - Record new stock entry (requires can_process_stock_entry)
- `get_low_stock` - Fetch low stock items
- `get_stock_report` - Generate stock report
- `process_return` - Handle product returns
- `export_inventory` - Export inventory to CSV

**Permission Checks**: All endpoints verify user role permissions before execution

#### **Procurement API** (`api/procurement_complete.php`)
**Endpoints**:
- `create_purchase_order` - Create new purchase order (requires can_create_purchase_order)
- `get_purchase_orders` - List all purchase orders
- `get_purchase_order` - Get PO details with line items
- `receive_purchase_order` - Record goods receipt (requires can_process_goods_receipt)
- `get_suppliers` - List all suppliers
- `add_supplier` - Add new supplier (requires can_manage_suppliers)
- `update_supplier` - Update supplier details
- `get_pending_orders` - Count pending orders
- `export_purchase_orders` - Export POs to CSV

#### **HR & Accounting API** (`api/hr_accounting.php`)
**HR Endpoints**:
- `add_employee` - Add new employee (requires can_manage_employees)
- `get_employees` - List employees by store
- `assign_role` - Assign role to employee (requires can_assign_roles)
- `get_employee_roles` - Get employee's active roles
- `get_roles` - List all active roles
- `record_timesheet` - Record employee hours (requires can_manage_employees)
- `get_timesheets` - Fetch timesheets by employee/date range

**Accounting Endpoints**:
- `record_ledger_entry` - Record GL transaction (requires can_manage_ledger)
- `get_general_ledger` - Fetch GL entries by date/account type
- `get_accounts_receivable` - List AR invoices
- `process_ar_payment` - Record customer payment (requires can_process_ar_ap)
- `get_accounts_payable` - List AP invoices
- `get_financial_summary` - Generate financial summary
- `export_ledger` - Export GL to CSV

#### **Sales API** (`api/sales.php`)
**Endpoints**:
- `get_orders` - Fetch sales orders with pagination
- `create_sale` - Process new sale (requires can_process_sale)
- `get_sale_details` - Get order details
- `get_invoices` - List invoices
- `process_return` - Handle sales returns (requires can_process_refunds)
- `get_sales_summary` - Sales statistics
- `get_daily_sales` - Today's sales metrics

---

### 3. Frontend JavaScript Application (`public/js/erp-app.js`)

**Core Object**: `ERP` - Centralized application state and methods

**Initialization**:
```javascript
ERP.init() // Called on page load
```

**Main Features**:
- Event listener setup for navigation, forms, modals, and tabs
- API communication with automatic error handling
- Dynamic module loading (Inventory, Sales, Procurement, etc.)
- Form validation and submission handling
- Real-time search and filtering
- Modal management
- Alert/notification system

**Module Functions**:

**Inventory Module**:
```javascript
ERP.loadInventory() // Load inventory interface
ERP.loadInventoryData() // Fetch products from API
ERP.populateInventoryTable(products) // Render product table
ERP.editProduct(productId) // Open edit modal
ERP.addStock(productId) // Quick stock addition
ERP.exportInventory() // Export to CSV
```

**Sales Module**:
```javascript
ERP.loadSales() // Load sales interface
ERP.loadSalesData() // Fetch orders
ERP.populateSalesTable(orders) // Render orders table
ERP.viewSaleDetails(saleId) // Show sale details
```

**Utility Functions**:
```javascript
ERP.fetchAPI(url, method, data) // AJAX calls with error handling
ERP.handleFormSubmit(form) // Process form submissions
ERP.showAlert(message, type, duration) // Display notifications
ERP.formatCurrency(amount) // Format monetary values
ERP.debounce(func, delay) // Debounce function calls
```

---

### 4. Role Management Functions (`includes/role_management_functions.php`)

**Role Creation**:
```php
createRole($roleName, $description, $permissions, $isActive)
```

**Employee Role Assignment**:
```php
assignRoleToEmployee($employeeID, $roleID, $startDate, $endDate)
getEmployeeRoles($employeeID, $activeOnly)
removeRoleAssignment($assignmentID)
```

**Role Distribution**:
```php
distributeRoleToStores($employeeID, $roleID, $storeIDs, $startDate, $endDate)
getEmployeeRoleDistributions($employeeID, $storeID)
```

**Permission Checking**:
```php
hasPermission($employeeID, $permissionKey, $storeID)
hasModuleAccess($employeeID, $moduleName)
hasRole($employeeID, $roleName, $storeID)
```

**Audit & Reporting**:
```php
getRoleAssignmentHistory($employeeID, $limit)
getEmployeesByRole($roleID, $storeID)
getRoleManagementStats()
```

---

### 5. Core Business Functions (`includes/core_functions.php`)

**Inventory Operations**:
- `getAllProducts($storeId, $searchTerm)` - Get products
- `addProduct($data)` - Add new product
- `updateInventory($productId, $storeId, $quantity)` - Update stock
- `getLowStockItems($storeId)` - Get low stock alerts
- `processReturn($saleId, $returnItems, $reason)` - Handle returns

**Sales Operations**:
- `processSale($customerId, $storeId, $products, $paymentMethod, $discount)` - Create sale
- `getSalesSummary($startDate, $endDate, $storeId)` - Sales report
- `processReturn($saleId, $returnItems, $reason)` - Process refund

**Procurement**:
- `createPurchaseOrder($supplierId, $storeId, $products, $expectedDeliveryDate)`
- `receivePurchaseOrder($purchaseOrderId, $receivedProducts)`
- `getPurchaseOrders($storeId, $status)`

**Customer Management**:
- `addCustomer($data)` - Add customer
- `getCustomer($customerId, $email, $phone)` - Get customer
- `searchCustomers($searchTerm)` - Search customers
- `updateLoyaltyPoints($customerId, $points, $operation)`

**Accounting**:
- `recordGeneralLedger($accountType, $accountName, $description, $debit, $credit, $referenceId, $referenceType, $storeId)`
- `createAccountsReceivable($saleId, $customerId)`
- `createAccountsPayable($purchaseOrderId, $supplierId, $amount, $dueDate)`
- `processARPayment($arId, $amount, $paymentMethod)`
- `getFinancialSummary($startDate, $endDate, $storeId)`
- `getOutstandingReceivables($storeId)`

**Authentication**:
- `authenticateUser($username, $password)` - Login
- `isLoggedIn()` - Check session
- `hasPermission($requiredRole)` - Verify role
- `logoutUser()` - Logout

---

## Process Flow Implementation

### 1. Inventory Management Flow
```
Stock Entry → Validate Permission → Insert Product → Update Inventory 
→ Record in GL → Update Stock Levels → Alert if Low Stock
```

**Key Functions**:
- Permission: `hasPermission($employee, 'can_process_stock_entry')`
- Database: `addProduct()`, `updateInventory()`
- Accounting: `recordGeneralLedger()`

### 2. Sales Processing Flow
```
POS Entry → Check Inventory → Create Sale → Update Stock 
→ Add Loyalty Points → Record Revenue in GL → Create AR if Credit
```

**Key Functions**:
- Check Permission: `hasPermission('can_process_sale')`
- Process: `processSale()`
- Update: `updateInventory()`, `updateLoyaltyPoints()`
- Account: `recordGeneralLedger()`, `createAccountsReceivable()`

### 3. Procurement Flow
```
Low Stock Alert → Create PO → Supplier Fulfills → Record Receipt 
→ Update Inventory → Record in GL → Update AP
```

**Key Functions**:
- Create: `createPurchaseOrder()`
- Receive: `receivePurchaseOrder()`
- Update: `updateInventory()`
- Account: `createAccountsPayable()`

### 4. HR/Payroll Flow
```
Add Employee → Assign Role → Log Timesheets → Calculate Payroll 
→ Record Expense in GL → Generate Pay Slip
```

**Key Functions**:
- Create: Employee created via `add_employee` API
- Assign: `assignRoleToEmployee()`
- Record: `record_timesheet` API
- Payroll: Processing in HR/Accounting API

### 5. Financial Management Flow
```
All Transactions → Record in GL → Generate Reports 
→ Track AR/AP → Reconcile → Generate Financial Statements
```

**Key Functions**:
- Record: `recordGeneralLedger()`
- Track: `getAccountsReceivable()`, `getAccountsPayable()`
- Report: `getFinancialSummary()`, `generateSalesReport()`

---

## Role-Based Access Control

### Available Permissions
- `can_manage_inventory` - Inventory operations
- `can_process_stock_entry` - Add stock
- `can_view_stock_reports` - View inventory reports
- `can_process_sale` - POS operations
- `can_process_refunds` - Process returns
- `can_create_purchase_order` - Create POs
- `can_process_goods_receipt` - Receive goods
- `can_manage_suppliers` - Supplier management
- `can_manage_customers` - Customer operations
- `can_manage_ledger` - GL transactions
- `can_process_ar_ap` - AR/AP operations
- `can_generate_financial_reports` - Financial reports
- `can_manage_employees` - Employee management
- `can_assign_roles` - Role assignment
- `can_view_all_reports` - All reports

### Permission Check Pattern
```php
// Before any protected operation:
if (!hasPermission($_SESSION['user_id'], 'can_operation')) {
    throw new Exception('Unauthorized');
}
```

---

## API Response Format

All APIs return standardized JSON:
```json
{
    "success": true/false,
    "message": "Operation message",
    "data": {...},
    "total": 100,
    "error": "Error message if failed"
}
```

### Example Success Response
```json
{
    "success": true,
    "message": "Product added successfully",
    "product_id": 123,
    "data": {...}
}
```

### Example Error Response
```json
{
    "success": false,
    "message": "Insufficient permissions"
}
```

---

## Frontend Integration Points

### Dashboard
- Load statistics via `ERP.loadDashboard()`
- Display KPIs: Revenue, Customers, Stock, Fulfillment Rate
- Recent sales table refresh

### Module Navigation
```javascript
ERP.loadModule('inventory')  // Load Inventory
ERP.loadModule('sales')      // Load Sales
ERP.loadModule('procurement') // Load Procurement
ERP.loadModule('accounting')  // Load Accounting
ERP.loadModule('hr')         // Load HR
```

### Form Submission
```javascript
// Auto-handled by erp-form class
<form class="erp-form" data-action="inventory" data-method="add_product">
    <input name="sku" required>
    <button type="submit">Add</button>
</form>
```

---

## Database Tables Used

### Core Tables
- `Products` - Shoe products
- `Inventory` - Stock levels
- `Stores` - Store locations
- `Customers` - Customer data
- `Employees` - Employee records
- `Users` - System users
- `Roles` - Role definitions
- `EmployeeRoles` - Employee role assignments

### Sales/Procurement
- `Sales` - Sale transactions
- `SaleDetails` - Line items
- `PurchaseOrders` - PO records
- `PurchaseOrderDetails` - PO line items
- `Suppliers` - Supplier information

### Financial
- `GeneralLedger` - GL transactions
- `AccountsReceivable` - AR invoices
- `AccountsPayable` - AP invoices
- `TaxRecords` - Tax tracking

### HR
- `Timesheets` - Employee hours
- `Payroll` - Payroll records
- `EmployeeRoleAssignments` - Multi-store role distribution

---

## Testing the Implementation

### 1. Test Database Connection
```php
$test = dbFetchOne("SELECT 1 as test");
if ($test) echo "Database connected!";
```

### 2. Test API Endpoints
```bash
# Get products
curl http://localhost/ShoeRetailErp/api/inventory.php?action=get_products

# Add product
curl -X POST http://localhost/ShoeRetailErp/api/inventory.php?action=add_product \
  -H "Content-Type: application/json" \
  -d '{"sku":"TEST001","brand":"Nike",...}'
```

### 3. Test Frontend
- Navigate to http://localhost/ShoeRetailErp/public/
- Login with test credentials
- Test module navigation
- Test add/edit/delete operations

---

## Future Enhancements

1. **Advanced Reporting**
   - Custom report builder
   - Scheduled report generation
   - Email delivery

2. **Mobile App**
   - React Native mobile app
   - Offline mode support
   - Push notifications

3. **Integration**
   - Third-party POS systems
   - Email/SMS notifications
   - Accounting software sync

4. **Analytics**
   - Predictive analytics
   - Inventory forecasting
   - Customer behavior analysis

---

## Support & Troubleshooting

### Common Issues

**Issue**: 401 Unauthorized on API calls
**Solution**: Ensure user is logged in and session is active

**Issue**: Database connection error
**Solution**: Check DB credentials in config/database.php

**Issue**: Permission denied on operations
**Solution**: Verify user has required role/permission

**Issue**: CORS errors
**Solution**: Ensure API is served from same domain

---

## Conclusion

The Shoe Retail ERP system is now fully implemented with:
✅ Complete database helper functions
✅ API endpoints for all modules
✅ Role-based access control
✅ Frontend JavaScript application
✅ Business logic for all processes
✅ Error handling and logging
✅ CSV export functionality

All process flows from the requirements document are implemented and ready for deployment.
