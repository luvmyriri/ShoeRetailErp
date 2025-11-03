# Shoe Retail ERP - Integration Quick Start Guide

## 🎯 Current Status

✅ **Complete**: 7 departmental modules built with frontend/backend  
✅ **Complete**: Database standardized with lowercase table names  
✅ **Complete**: API endpoints standardized across 5 critical modules  
✅ **Complete**: Authentication & authorization implemented  
✅ **In Progress**: Cross-module workflow integration  

## 🚀 Quick Integration Implementation (Next Steps)

### Step 1: Include Workflow Handlers in Your Modules
```php
// At the top of your Sales/Procurement/HR module files
require_once __DIR__ . '/../includes/workflow_handlers.php';
```

### Step 2: Use Workflow Handler in Sales Module
```php
// In sales/index.php or sales API endpoint when processing a sale
try {
    $result = WorkflowHandler::processSale([
        'customer_id' => $_POST['customer_id'],
        'store_id' => $_SESSION['store_id'],
        'items' => $cartItems, // Array of items with product_id, quantity, price
        'payment_method' => $_POST['payment_method'],
        'total_amount' => $totalAmount
    ]);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'sale_id' => $result['sale_id']]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

### Step 3: Use Workflow Handler in Procurement Module
```php
// In procurement/goodreceipts.php when receiving goods
try {
    $result = WorkflowHandler::processGoodsReceipt([
        'po_id' => $_POST['po_id'],
        'notes' => $_POST['notes'] ?? null
    ]);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'receipt_id' => $result['receipt_id']]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

### Step 4: Use Workflow Handler in HR Module
```php
// In hr/payroll_management.php when processing payroll
try {
    $result = WorkflowHandler::processPayroll([
        'employee_id' => $employeeId,
        'period' => '2024-12',
        'allowances' => $allowances,
        'taxes' => $taxes,
        'benefits' => $benefits,
        'deductions' => $deductions,
        'store_id' => $_SESSION['store_id']
    ]);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'payroll_id' => $result['payroll_id']]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

## 📊 Module Integration Checklist

### Sales Integration
- [ ] Sales module calls `WorkflowHandler::processSale()` when creating order
- [ ] Inventory automatically decremented
- [ ] AR entry automatically created for credit sales
- [ ] GL entries automatically recorded
- [ ] Customer loyalty points automatically updated
- [ ] Dashboard refreshed with new sale data

### Procurement Integration
- [ ] Procurement module calls `WorkflowHandler::processGoodsReceipt()` on goods receipt
- [ ] Inventory automatically incremented
- [ ] Stock movements recorded
- [ ] AP entry automatically created
- [ ] GL entries automatically recorded
- [ ] PO status updated to "Received"

### HR-Accounting Integration
- [ ] HR module calls `WorkflowHandler::processPayroll()` for payroll processing
- [ ] Salary expense GL entries created
- [ ] Tax liability entries created
- [ ] Benefits expense entries created
- [ ] Employee payment status updated
- [ ] Dashboard shows payroll processing status

### Customer Integration
- [ ] Customer profile shows purchase history
- [ ] Loyalty points displayed in profile
- [ ] AR balance visible in customer details
- [ ] Support tickets linked to customer
- [ ] Order history populated automatically

### Dashboard Integration
- [ ] Total products from inventory
- [ ] Low stock alerts updated real-time
- [ ] Today's sales totaled from sales module
- [ ] Outstanding AR displayed from accounting
- [ ] Active users counted
- [ ] Financial metrics aggregated

## 🔗 Data Flow Examples

### Complete Sale Processing Flow
```
1. Customer makes purchase (Sales Module)
   ↓
2. WorkflowHandler::processSale() called with order data
   ↓
3. Sale record created in database
   ↓
4. Inventory decremented for each item (Inventory Module)
   ↓
5. Stock movements recorded for audit trail
   ↓
6. AR entry created if credit sale (Accounting Module)
   ↓
7. GL entries recorded (Accounting Module)
   - Debit Accounts Receivable
   - Credit Sales Revenue
   ↓
8. Customer loyalty points incremented (Customers Module)
   ↓
9. Transaction committed to database
   ↓
10. Dashboard updated with new metrics
```

### Complete Goods Receipt Flow
```
1. Goods arrive from supplier (Procurement Module)
   ↓
2. WorkflowHandler::processGoodsReceipt() called with PO ID
   ↓
3. Goods receipt record created
   ↓
4. Inventory incremented for each PO item (Inventory Module)
   ↓
5. Stock movements recorded for audit trail
   ↓
6. AP entry created (Accounting Module)
   ↓
7. GL entries recorded (Accounting Module)
   - Debit Inventory
   - Credit Accounts Payable
   ↓
8. PO status updated to "Received"
   ↓
9. Transaction committed to database
   ↓
10. Dashboard inventory totals updated
```

## 🛠️ API Endpoints Ready to Use

All endpoints accept JSON POST with proper authentication:

```bash
# Sales
POST /api/sales.php?action=create_sale
POST /api/sales.php?action=get_orders

# Inventory
POST /api/inventory.php?action=transfer_stock
POST /api/inventory.php?action=get_products
GET /api/inventory.php?action=get_low_stock

# Procurement
POST /api/procurement.php?action=create_purchase_order
GET /api/procurement.php?action=get_po_details
POST /api/procurement.php?action=create_goods_receipt

# Accounting
GET /api/accounting.php?action=get_accounts_receivable
GET /api/accounting.php?action=get_accounts_payable
GET /api/accounting.php?action=get_general_ledger

# Customers
GET /api/customers.php?action=get_customers
GET /api/customers.php?action=get_customer
POST /api/customers.php?action=update_customer

# HR
GET /api/hr_integrated.php?action=get_employees
POST /api/hr_integrated.php?action=create_attendance
GET /api/hr_integrated.php?action=get_payroll
```

## 📋 Pre-Integration Verification

Before starting integration, verify:

- [ ] All modules have backend API files in `/api/`
- [ ] All API files include core_functions.php
- [ ] Database tables are lowercase
- [ ] Session management initialized in each module
- [ ] Authentication checks in place
- [ ] Error handling with try-catch blocks

## ⚙️ Configuration Checklist

### Database Setup
- [ ] All tables exist with correct lowercase names
- [ ] Foreign keys configured properly
- [ ] Indexes created for performance
- [ ] Database connection working via PDO

### API Setup
- [ ] All endpoints return JSON format
- [ ] Error messages standardized
- [ ] Response codes correct (200, 400, 401, 403)
- [ ] CORS headers configured if needed

### Frontend Setup
- [ ] Navbar includes all modules
- [ ] Role-based menu working
- [ ] Session check implemented
- [ ] Redirect to login for unauthorized access

### Logging Setup
- [ ] logs/ directory exists
- [ ] logInfo() and logError() working
- [ ] Audit trail enabled for all transactions
- [ ] Error logs checked regularly

## 🧪 Testing the Integration

### Unit Test: Single Module
```php
// Test sales module
$saleData = [
    'customer_id' => 1,
    'store_id' => 1,
    'items' => [
        ['product_id' => 1, 'quantity' => 2, 'price' => 1000]
    ],
    'payment_method' => 'Cash',
    'total_amount' => 2000
];

$result = WorkflowHandler::processSale($saleData);
assert($result['success'] === true, "Sale creation failed");
```

### Integration Test: Multi-Module
```php
// 1. Create sale
$saleResult = WorkflowHandler::processSale($saleData);
$saleId = $saleResult['sale_id'];

// 2. Verify inventory decremented
$inventory = $db->fetchOne(
    "SELECT Quantity FROM inventory WHERE ProductID = ?",
    [1]
);
assert($inventory['Quantity'] === 98, "Inventory not decremented");

// 3. Verify AR created
$ar = $db->fetchOne(
    "SELECT * FROM accountsreceivable WHERE SaleID = ?",
    [$saleId]
);
assert($ar !== null, "AR not created");

// 4. Verify GL entries created
$glCount = $db->fetchOne(
    "SELECT COUNT(*) as cnt FROM generalledger WHERE ReferenceID = ?",
    [$saleId]
);
assert($glCount['cnt'] >= 2, "GL entries not created");
```

## 📞 Support & Troubleshooting

### Common Issues

**Issue**: "Class WorkflowHandler not found"
**Solution**: Verify `require_once` is at top of file and path is correct

**Issue**: "Database transaction failed"
**Solution**: Check error logs, verify all tables exist and lowercase

**Issue**: "API returns 403 Forbidden"
**Solution**: Verify user has correct role, check hasPermission() implementation

**Issue**: "Inventory not updating"
**Solution**: Verify stock movements table has correct columns, check for transaction rollback

## 📈 Next Steps

1. **Implement Sales Integration** (Week 1)
   - Test sale creation workflow
   - Verify inventory deduction
   - Confirm AR and GL entries

2. **Implement Procurement Integration** (Week 1)
   - Test goods receipt workflow
   - Verify inventory increment
   - Confirm AP and GL entries

3. **Implement HR Payroll Integration** (Week 2)
   - Test payroll workflow
   - Verify GL salary entries
   - Confirm tax and benefits entries

4. **Implement Dashboard Integration** (Week 2)
   - Real-time metric updates
   - Cross-module data aggregation
   - Performance optimization

5. **Complete Testing** (Week 3)
   - UAT with users
   - Performance testing
   - Security audit

6. **Go Live** (Week 4)
   - Deploy to production
   - Monitor system
   - Support users

## 🎓 Training Resources

Each module has documentation in its folder:
- `/public/[module]/README.md` - User guide
- `/api/[module].php` - API documentation
- `/includes/workflow_handlers.php` - Integration examples

## ✅ Success Criteria

Integration is successful when:

✓ Sales automatically update inventory  
✓ Sales automatically create AR entries  
✓ Goods receipts automatically increment inventory  
✓ Goods receipts automatically create AP entries  
✓ Payroll automatically creates GL entries  
✓ Customer profiles show all linked data  
✓ Dashboard shows real-time metrics  
✓ All transactions logged with audit trail  
✓ Users can seamlessly navigate all modules  
✓ System handles errors gracefully  

---

**Last Updated**: December 2024  
**Status**: Ready for Integration  
**Estimated Integration Time**: 3-4 weeks  
**Support**: Contact development team  
