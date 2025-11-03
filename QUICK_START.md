# Quick Start Guide - Module Integration

## 🎯 Your Next Tasks

You have successfully set up the foundation. Now integrate the remaining 6 modules one by one.

---

## 📋 What Has Been Done

1. ✅ **Database Schema** - Complete with triggers, procedures, views
2. ✅ **Core Infrastructure** - DB connection, auth, logging
3. ✅ **HR Module** - Fully integrated reference implementation
4. ✅ **Documentation** - 3 comprehensive guides created

---

## 🚀 How to Proceed

### Step 1: Use HR Module as Template
The file `/api/hr_integrated.php` is your reference. It shows the pattern:
- Correct database queries (using `getDB()`)
- Proper authentication checks
- Standardized JSON responses
- Cross-module integration points
- Error handling and logging

### Step 2: Fix Next Module (Procurement)
1. Open `/api/procurement.php`
2. Follow HR pattern:
   ```
   Session start → Includes → Auth check → Action routing → Functions → JSON response
   ```
3. Replace all queries with `getDB()->fetchOne()`, `getDB()->fetchAll()`, etc.
4. Ensure AP records created automatically
5. Test with test endpoints

### Step 3: Repeat for Other Modules
Priority order:
1. Procurement (2-3 hrs)
2. Sales (2-3 hrs)
3. Inventory (2-3 hrs)
4. Accounting (3-4 hrs)
5. CRM (3-4 hrs)
6. Customers (1-2 hrs)

---

## 🔧 Standard Module Structure

```php
<?php
// 1. START SESSION & INCLUDES
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/core_functions.php';
header('Content-Type: application/json');

// 2. CHECK AUTHENTICATION
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

// 3. CHECK AUTHORIZATION (Optional)
if (!hasPermission('Manager')) {
    jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
}

// 4. ROUTE ACTIONS
$action = $_GET['action'] ?? $_POST['action'] ?? null;
try {
    switch ($action) {
        case 'action1':
            function1();
            break;
        case 'action2':
            function2();
            break;
        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    logError("Module error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}

// 5. IMPLEMENT FUNCTIONS
function function1() {
    try {
        $db = getDB();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        // Validate
        if (empty($data['required_field'])) {
            throw new Exception('Missing required field');
        }
        
        // Query
        $result = $db->fetchAll("SELECT * FROM table WHERE id = ?", [$data['id']]);
        
        // Respond
        jsonResponse(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        throw $e;
    }
}
?>
```

---

## 📌 Key Functions to Use

### Database
```php
$db = getDB();
$record = $db->fetchOne($query, $params);      // Get 1 row
$records = $db->fetchAll($query, $params);     // Get many rows
$id = $db->insert($query, $params);            // Insert & return ID
$db->update($query, $params);                  // Update records
$db->delete($query, $params);                  // Delete records

// Transactions
$db->beginTransaction();
try {
    // operations
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
}

// Stored Procedures
$db->callProcedure('ProcedureName', $params);
```

### Authentication/Authorization
```php
isLoggedIn()              // Check if user logged in
hasPermission('Role')     // Check user role (Admin, Manager, etc)
logInfo($msg, $context)   // Log success
logError($msg, $context)  // Log error
```

### Response
```php
jsonResponse([
    'success' => true,
    'message' => 'OK',
    'data' => $result
]);

jsonResponse([
    'success' => false, 
    'message' => 'Error'
], 400);  // HTTP status code
```

---

## 🐛 Common Issues & Solutions

### Issue: Table not found
**Solution**: Check table names in schema (lowercase: `employees`, not `Employees`)

### Issue: Parameter binding errors
**Solution**: Use parameterized queries: `$db->fetchAll($query, [$param1, $param2])`

### Issue: Cross-module data not syncing
**Solution**: Check core functions (e.g., `processSale()` should call stored procedure that updates inventory)

### Issue: GL entries not created
**Solution**: Ensure stored procedures or core functions call `recordGeneralLedger()`

### Issue: Transactions not rolling back
**Solution**: Check try/catch blocks - ensure `throw $e` after catch

---

## ✅ Testing Checklist Per Module

Before moving to next module:

- [ ] All GET endpoints return data correctly
- [ ] All POST endpoints create/update records
- [ ] Error handling works (bad data returns 400)
- [ ] Authentication works (no session = 401)
- [ ] Authorization works (wrong role = 403)
- [ ] Cross-module data flows (if applicable)
- [ ] GL entries created (if financial transaction)
- [ ] Logging works (check `/logs` folder)
- [ ] Database transactions work (test rollback)

---

## 📊 Module Dependencies

```
Procurement depends on:
  ├─ Core functions (createAccountsPayable, recordGeneralLedger)
  └─ Inventory (stock movements)

Sales depends on:
  ├─ Core functions (processSale, createAccountsReceivable)
  ├─ Inventory (stock decrement)
  ├─ Accounting (GL, AR, tax)
  └─ Customers (loyalty points)

Inventory depends on:
  ├─ Core functions (stock movement tracking)
  └─ Database (inventory, stockmovements tables)

Accounting depends on:
  ├─ Sales (AR data)
  ├─ Procurement (AP data)
  ├─ HR (payroll GL entries)
  └─ Database (GL, AR, AP tables)
```

---

## 📞 Quick Reference

### Database Tables (Lowercase!)
- `employees`, `attendance`, `payroll`, `leaverequests`, `leavebalances`
- `purchaseorders`, `purchaseorderdetails`, `accountspayable`
- `sales`, `saledetails`, `accountsreceivable`, `customerpayments`
- `inventory`, `stockmovements`
- `generalledger`, `taxrecords`
- `customers`, `stores`, `suppliers`

### Stored Procedures
- `ProcessSale` - Handle complete sale transaction
- `ReceivePurchaseOrder` - Process PO receipt
- `GeneratePayroll` - Create payroll records

### Stored Procedure Example
```sql
CALL GeneratePayroll(1, '2025-01-01', '2025-01-31', 0)
```

### Views (Reporting)
- `v_financial_summary`
- `v_inventory_summary`
- `v_outstanding_receivables`
- `v_sales_summary`
- `v_purchaseorderdetails`

---

## 🎓 Learning Path

1. **Read**: `INTEGRATION_GUIDE.md` - Understand architecture
2. **Review**: `api/hr_integrated.php` - See implementation pattern
3. **Study**: `includes/core_functions.php` - Available functions
4. **Check**: `MODULE_ENDPOINTS.md` - Know expected endpoints
5. **Code**: Start with simple CRUD endpoints
6. **Test**: Verify each endpoint works
7. **Integrate**: Add cross-module flows
8. **Document**: Update endpoint list

---

## 📈 Progress Tracking

Use this checklist:

```
Procurement: [ ] Structure [ ] Queries [ ] Auth [ ] Core functions [ ] Tests
Sales:       [ ] Structure [ ] Queries [ ] Auth [ ] Core functions [ ] Tests
Inventory:   [ ] Structure [ ] Queries [ ] Auth [ ] Core functions [ ] Tests
Accounting:  [ ] Structure [ ] Queries [ ] Auth [ ] Core functions [ ] Tests
CRM:         [ ] Structure [ ] Queries [ ] Auth [ ] Core functions [ ] Tests
Customers:   [ ] Structure [ ] Queries [ ] Auth [ ] Core functions [ ] Tests
```

---

## 🎯 Current Status Summary

```
HR Module:        ✅ COMPLETE (654 lines, 13 endpoints)
Procurement:      ⏳ READY (skeleton exists, needs integration)
Sales:            ⏳ READY (skeleton exists, needs integration)
Inventory:        ⏳ READY (skeleton exists, needs integration)
Accounting:       ⏳ READY (skeleton exists, needs integration)
CRM:              ⏳ READY (skeleton exists, needs review)
Customers:        ⏳ READY (skeleton exists, needs completion)

Database:         ✅ COMPLETE
Infrastructure:   ✅ COMPLETE
Documentation:    ✅ COMPLETE

Total Estimated Remaining Time: 15-20 hours
```

---

## 💡 Pro Tips

1. **Use VS Code** or IDE with find/replace to convert queries quickly
2. **Test one function at a time** - don't build whole module before testing
3. **Use Postman** or curl to test endpoints
4. **Check logs** - `/logs/error.log` and `/logs/info.log` have detailed info
5. **Keep it DRY** - reuse core functions, don't duplicate code
6. **Test edge cases** - empty data, wrong types, missing fields
7. **Version control** - commit after each module completes

---

## 🚦 Ready to Start?

1. Open `api/procurement.php`
2. Follow the HR module pattern
3. Replace legacy DB calls with `getDB()` pattern
4. Test each endpoint
5. Commit changes
6. Move to next module

**Go build! 🚀**
