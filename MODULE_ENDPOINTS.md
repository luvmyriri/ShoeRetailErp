# Module API Endpoints Reference

## HR Module (`/api/hr_integrated.php`)

### Employee Management
- `GET  /api/hr_integrated.php?action=get_employees` - List employees
- `POST /api/hr_integrated.php?action=add_employee` - Create employee
- `POST /api/hr_integrated.php?action=update_employee` - Update employee
- `GET  /api/hr_integrated.php?action=get_employee&employee_id=1` - Get employee details

### Attendance
- `GET  /api/hr_integrated.php?action=get_attendance` - Get attendance records
- `POST /api/hr_integrated.php?action=log_attendance` - Log attendance
- `GET  /api/hr_integrated.php?action=get_attendance_report` - Attendance report

### Leave Management
- `POST /api/hr_integrated.php?action=request_leave` - Request leave
- `GET  /api/hr_integrated.php?action=get_leave_requests` - Get leave requests
- `POST /api/hr_integrated.php?action=approve_leave` - Approve leave
- `POST /api/hr_integrated.php?action=reject_leave` - Reject leave
- `GET  /api/hr_integrated.php?action=get_leave_balance&employee_id=1` - Get leave balance

### Payroll
- `POST /api/hr_integrated.php?action=process_payroll` - Process payroll
- `GET  /api/hr_integrated.php?action=get_payroll_records` - Get payroll records
- `GET  /api/hr_integrated.php?action=get_employee_payroll&employee_id=1` - Get employee payroll

---

## Procurement Module (`/api/procurement.php`)

**Status**: Needs integration with standardized approach

### Endpoints to implement/fix:
- `POST /api/procurement.php?action=create_purchase_order` - Create PO
- `GET  /api/procurement.php?action=get_purchase_orders` - List POs
- `POST /api/procurement.php?action=receive_purchase_order` - Receive goods
- `GET  /api/procurement.php?action=get_suppliers` - List suppliers
- `POST /api/procurement.php?action=add_supplier` - Add supplier

**Integration needed with**:
- Inventory (stock movements)
- Accounting (AP creation, GL entries)

---

## Sales Module (`/api/sales.php`)

**Status**: Needs integration with standardized approach

### Endpoints to implement/fix:
- `GET  /api/sales.php?action=get_orders` - List sales orders
- `POST /api/sales.php?action=create_sale` - Process sale
- `GET  /api/sales.php?action=get_sale_details&sale_id=1` - Get sale details
- `GET  /api/sales.php?action=get_invoices` - Get invoices
- `POST /api/sales.php?action=process_return` - Process return/refund
- `GET  /api/sales.php?action=get_sales_summary` - Sales report

**Integration needed with**:
- Inventory (stock decrement)
- Accounting (GL entries, AR creation)
- Customers (loyalty points)

---

## Inventory Module (`/api/inventory.php`)

**Status**: Needs integration with standardized approach

### Endpoints to implement/fix:
- `GET  /api/inventory.php?action=get_products` - List products
- `POST /api/inventory.php?action=add_product` - Add product
- `GET  /api/inventory.php?action=get_product&product_id=1` - Get product details
- `GET  /api/inventory.php?action=get_low_stock` - Get low stock items
- `GET  /api/inventory.php?action=get_stock_view` - Stock across stores
- `GET  /api/inventory.php?action=get_stock_movements` - Stock movement history
- `POST /api/inventory.php?action=transfer_stock` - Transfer between stores

---

## Accounting Module (`/api/accounting.php`)

**Status**: Needs integration with standardized approach

### Endpoints to implement/fix:
- `GET  /api/accounting.php?action=get_accounts_receivable` - Get AR
- `GET  /api/accounting.php?action=get_accounts_payable` - Get AP
- `POST /api/accounting.php?action=process_ar_payment` - Record AR payment
- `POST /api/accounting.php?action=process_ap_payment` - Record AP payment
- `GET  /api/accounting.php?action=get_general_ledger` - Get GL entries
- `GET  /api/accounting.php?action=get_financial_summary` - Financial summary
- `GET  /api/accounting.php?action=get_income_statement` - Income statement
- `GET  /api/accounting.php?action=get_balance_sheet` - Balance sheet
- `GET  /api/accounting.php?action=get_ar_aging` - AR aging report

---

## CRM Module (`/api/crm.php`)

**Status**: Needs integration with standardized approach

### Endpoints to implement/fix:
- Customer reports and analytics
- Sales pipeline tracking
- Customer interaction history

---

## Customers Module (`/api/customers.php`)

**Status**: Needs integration with standardized approach

### Endpoints to implement/fix:
- `GET  /api/customers.php?action=get_customers` - List customers
- `POST /api/customers.php?action=add_customer` - Add customer
- `POST /api/customers.php?action=update_customer` - Update customer
- `GET  /api/customers.php?action=get_customer&customer_id=1` - Get customer

---

## Dashboard Module (`/api/dashboard.php`)

**Status**: Needs review

### Endpoints:
- Dashboard statistics
- Key metrics
- Quick links

---

## Integration Points Between Modules

```
SALES → INVENTORY
  Sale created
  → Stock decremented via ProcessSale stored procedure
  → Stock movements recorded

SALES → ACCOUNTING
  Sale created
  → GL entries recorded (Revenue, COGS)
  → AR created (if credit sale)
  → Tax recorded

PROCUREMENT → INVENTORY
  PO received
  → Inventory increased via ReceivePurchaseOrder stored procedure
  → Stock movements recorded

PROCUREMENT → ACCOUNTING
  PO received
  → GL entries recorded (Asset: Inventory, Liability: AP)
  → AP created

HR → ACCOUNTING
  Payroll processed
  → GL entries recorded (Expense, Liability)
  → Payroll records created

CUSTOMER PAYMENT → ACCOUNTING
  Payment received
  → GL entries (Asset, AR reduction)
  → AR status updated

SUPPLIER PAYMENT → ACCOUNTING
  Payment made
  → GL entries (Liability reduction, Asset reduction)
  → AP status updated
```

---

## Database Connection Pattern

All modules should use:

```php
// config/database.php - Use PDO class
$db = getDB();
$result = $db->fetchAll($query, $params);
$record = $db->fetchOne($query, $params);
$id = $db->insert($query, $params);
$db->update($query, $params);
$db->delete($query, $params);

// Transactions
$db->beginTransaction();
// ... operations ...
$db->commit();   // or $db->rollback();

// Stored procedures
$db->callProcedure('ProcedureName', $params);
```

---

## Response Format (All Endpoints)

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

Error Response:
```json
{
  "success": false,
  "message": "Error description",
  "data": null
}
```

HTTP Status Codes:
- 200: Success
- 400: Bad Request / Invalid data
- 401: Unauthorized (not logged in)
- 403: Forbidden (insufficient permissions)
- 404: Not Found
- 500: Server Error

---

## Authentication & Authorization

All endpoints require:
1. Session validation via `isLoggedIn()` 
2. Role-based permission check via `hasPermission('Role')`

Roles hierarchy:
```
Cashier (1) < Support (2) < Accountant (3) < Manager (4) < Admin (5)
```

---

## Implementation Priority

1. **✅ HR Module** - COMPLETE (`hr_integrated.php`)
2. **🔄 Procurement Module** - IN PROGRESS
3. **🔄 Sales Module** - IN PROGRESS
4. **🔄 Inventory Module** - IN PROGRESS
5. **🔄 Accounting Module** - IN PROGRESS
6. **⏳ CRM Module** - PENDING
7. **⏳ Customers Module** - PENDING
8. **⏳ Dashboard Module** - PENDING
