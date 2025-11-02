# Shoe Retail ERP - Quick Reference Guide

## Common Tasks

### Load Data from API
```javascript
// Load products
ERP.fetchAPI('/ShoeRetailErp/api/inventory.php?action=get_products')
    .then(data => console.log(data.data))
    .catch(err => console.error(err));

// Create new item
ERP.fetchAPI('/ShoeRetailErp/api/inventory.php?action=add_product', 'POST', {
    sku: 'SKU001',
    brand: 'Nike',
    model: 'Air Max',
    size: '10',
    color: 'Black',
    cost_price: 50,
    selling_price: 100,
    supplier_id: 1
}).then(res => alert(res.message));
```

### Process Sales
```php
// In PHP backend
$saleId = processSale(
    $customerId,      // Customer ID
    $storeId,         // Store ID
    $products,        // Array of products
    'Cash',           // Payment method
    $discountAmount   // Discount
);
// Returns sale ID and updates inventory, GL, AR
```

### Manage Inventory
```php
// Add stock
updateInventory($productId, $storeId, $quantity);

// Check low stock
$lowStock = getLowStockItems($storeId);

// Export inventory
header('Location: /ShoeRetailErp/api/inventory.php?action=export_inventory');
```

### Employee & Role Management
```php
// Add employee
$empId = dbInsert(
    "INSERT INTO Employees (FirstName, LastName, Email, Phone, Salary, StoreID) 
     VALUES (?, ?, ?, ?, ?, ?)",
    [$fname, $lname, $email, $phone, $salary, $storeId]
);

// Assign role
assignRoleToEmployee($empId, $roleId, '2025-01-01', '2025-12-31');

// Check permission
if (!hasPermission($empId, 'can_manage_inventory')) {
    throw new Exception('Unauthorized');
}
```

### Financial Transactions
```php
// Record GL entry
recordGeneralLedger(
    'Asset',           // Account type
    'Inventory',       // Account name
    'Stock purchase',  // Description
    1000,              // Debit amount
    0,                 // Credit amount
    $poId,             // Reference ID
    'Purchase',        // Reference type
    $storeId           // Store
);

// Process AR payment
processARPayment($arId, $paymentAmount, 'Check');

// Get receivables
$receivables = dbFetchAll(
    "SELECT * FROM AccountsReceivable WHERE PaymentStatus != 'Paid'"
);
```

### Create Purchase Order
```php
// API call
ERP.fetchAPI('/ShoeRetailErp/api/procurement_complete.php?action=create_purchase_order', 'POST', {
    supplier_id: 1,
    store_id: 1,
    products: [
        { product_id: 1, quantity: 50, unit_cost: 40, subtotal: 2000 },
        { product_id: 2, quantity: 30, unit_cost: 35, subtotal: 1050 }
    ],
    expected_delivery_date: '2025-02-15'
});
```

## API Endpoint Cheat Sheet

### Inventory
| Endpoint | Method | Permission |
|----------|--------|-----------|
| `api/inventory.php?action=get_products` | GET | None |
| `api/inventory.php?action=add_product` | POST | can_manage_inventory |
| `api/inventory.php?action=stock_entry` | POST | can_process_stock_entry |
| `api/inventory.php?action=get_low_stock` | GET | can_view_stock_reports |
| `api/inventory.php?action=process_return` | POST | can_manage_inventory |

### Sales
| Endpoint | Method | Permission |
|----------|--------|-----------|
| `api/sales.php?action=get_orders` | GET | None |
| `api/sales.php?action=create_sale` | POST | can_process_sale |
| `api/sales.php?action=process_return` | POST | can_process_refunds |
| `api/sales.php?action=get_sales_summary` | GET | can_view_all_reports |

### Procurement
| Endpoint | Method | Permission |
|----------|--------|-----------|
| `api/procurement_complete.php?action=get_purchase_orders` | GET | None |
| `api/procurement_complete.php?action=create_purchase_order` | POST | can_create_purchase_order |
| `api/procurement_complete.php?action=receive_purchase_order` | POST | can_process_goods_receipt |
| `api/procurement_complete.php?action=add_supplier` | POST | can_manage_suppliers |

### HR & Accounting
| Endpoint | Method | Permission |
|----------|--------|-----------|
| `api/hr_accounting.php?action=add_employee` | POST | can_manage_employees |
| `api/hr_accounting.php?action=assign_role` | POST | can_assign_roles |
| `api/hr_accounting.php?action=record_timesheet` | POST | can_manage_employees |
| `api/hr_accounting.php?action=record_ledger_entry` | POST | can_manage_ledger |
| `api/hr_accounting.php?action=process_ar_payment` | POST | can_process_ar_ap |
| `api/hr_accounting.php?action=get_financial_summary` | GET | can_generate_financial_reports |

## Database Query Examples

### Get Active Users
```php
$users = dbFetchAll(
    "SELECT u.*, r.RoleName FROM Users u 
     LEFT JOIN Roles r ON u.RoleID = r.RoleID 
     WHERE u.Status = 'Active' ORDER BY u.FirstName"
);
```

### Low Stock Report
```php
$lowStock = dbFetchAll(
    "SELECT p.*, i.Quantity, s.StoreName 
     FROM Products p
     JOIN Inventory i ON p.ProductID = i.ProductID
     JOIN Stores s ON i.StoreID = s.StoreID
     WHERE i.Quantity <= p.MinStockLevel ORDER BY i.Quantity ASC"
);
```

### Daily Sales Report
```php
$dailySales = dbFetchOne(
    "SELECT 
        DATE(SaleDate) as date,
        COUNT(*) as transactions,
        SUM(TotalAmount) as revenue,
        SUM(TaxAmount) as taxes
     FROM Sales 
     WHERE DATE(SaleDate) = ? 
     GROUP BY DATE(SaleDate)",
    [date('Y-m-d')]
);
```

### Overdue Receivables
```php
$overdue = dbFetchAll(
    "SELECT ar.*, c.FirstName, c.LastName, 
            DATEDIFF(CURDATE(), ar.DueDate) as days_overdue
     FROM AccountsReceivable ar
     JOIN Customers c ON ar.CustomerID = c.CustomerID
     WHERE ar.PaymentStatus != 'Paid' AND ar.DueDate < CURDATE()
     ORDER BY ar.DueDate ASC"
);
```

## Frontend Code Examples

### Add Product Modal
```html
<form class="erp-form" data-action="inventory" data-method="add_product">
    <input type="text" name="sku" placeholder="SKU" required>
    <input type="text" name="brand" placeholder="Brand" required>
    <input type="number" name="cost_price" step="0.01" placeholder="Cost" required>
    <input type="number" name="selling_price" step="0.01" placeholder="Price" required>
    <button type="submit">Add Product</button>
</form>
```

### Load and Display Data
```javascript
ERP.fetchAPI('/ShoeRetailErp/api/inventory.php?action=get_low_stock')
    .then(data => {
        const html = data.data.map(item => `
            <tr>
                <td>${item.SKU}</td>
                <td>${item.Brand}</td>
                <td>${item.Quantity}</td>
                <td>${item.MinStockLevel}</td>
            </tr>
        `).join('');
        document.getElementById('tbody').innerHTML = html;
    });
```

### Handle Form Submission
```javascript
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = new FormData(this);
    ERP.fetchAPI('/ShoeRetailErp/api/inventory.php?action=add_product', 'POST', 
        Object.fromEntries(data)
    ).then(res => {
        ERP.showSuccess(res.message);
        // Reload data
        location.reload();
    }).catch(err => {
        ERP.showError('Error: ' + err.message);
    });
});
```

## Error Handling Patterns

### API Error Response
```javascript
try {
    const response = await ERP.fetchAPI(url);
    // Handle success
} catch (error) {
    console.error('API Error:', error.message);
    ERP.showError(error.message);
}
```

### Database Error Handling
```php
try {
    $result = dbFetchOne($query, $params);
    if (!$result) {
        throw new Exception('No data found');
    }
    // Process result
} catch (Exception $e) {
    logError('Query failed', ['error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
```

## Performance Tips

1. **Use Pagination**
   ```javascript
   // Add limit and offset to queries
   fetch(url + '?action=get_products&limit=20&offset=0')
   ```

2. **Index Database Columns**
   ```sql
   CREATE INDEX idx_products_sku ON Products(SKU);
   CREATE INDEX idx_inventory_quantity ON Inventory(Quantity);
   ```

3. **Cache API Responses**
   ```javascript
   ERP.cache = {};
   ERP.getCachedData = function(key) {
       return ERP.cache[key];
   };
   ```

4. **Use Debouncing for Search**
   ```javascript
   const debounce = (fn, delay) => {
       let timeout;
       return (...args) => {
           clearTimeout(timeout);
           timeout = setTimeout(() => fn(...args), delay);
       };
   };
   ```

## Common Debugging

### Check Session
```php
session_start();
var_dump($_SESSION);
```

### Verify Permissions
```php
$roles = getEmployeeRoles($employeeId, true);
var_dump($roles);
```

### Test API
```bash
curl -X GET "http://localhost/ShoeRetailErp/api/inventory.php?action=get_products"
curl -X POST "http://localhost/ShoeRetailErp/api/inventory.php?action=add_product" \
  -H "Content-Type: application/json" \
  -d '{"sku":"TEST","brand":"Nike"}'
```

### Enable Logging
```php
logInfo("Debug info", ['var1' => $value1, 'var2' => $value2]);
// Check logs/app.log
```

## Security Best Practices

1. **Always Check Permissions**
   ```php
   if (!hasPermission($userId, 'permission_key')) {
       throw new Exception('Unauthorized');
   }
   ```

2. **Sanitize Input**
   ```php
   $safe = htmlspecialchars($input);
   // Use parameterized queries (already done with dbFetchAll, etc.)
   ```

3. **Validate Data**
   ```php
   if (empty($data['field']) || strlen($data['field']) > 255) {
       throw new Exception('Invalid input');
   }
   ```

4. **Use HTTPS in Production**
   - All API calls should use HTTPS
   - Set secure cookie flags

5. **Rate Limiting**
   - Implement request throttling
   - Log suspicious activity

---

## File Structure Reference

```
ShoeRetailErp/
├── config/
│   └── database.php          # DB credentials
├── includes/
│   ├── db_helper.php         # Database functions
│   ├── core_functions.php    # Business logic
│   └── role_management_functions.php
├── api/
│   ├── inventory.php
│   ├── sales.php
│   ├── procurement_complete.php
│   └── hr_accounting.php
├── public/
│   ├── js/
│   │   └── erp-app.js        # Frontend JS
│   └── index.php
├── logs/
│   ├── app.log
│   └── error.log
└── DATABASE_SCHEMA_UPDATE.sql
```

---

## Quick Command Reference

```bash
# Test API
curl -X GET "http://localhost/ShoeRetailErp/api/inventory.php?action=get_products"

# Check logs
tail -f logs/app.log
tail -f logs/error.log

# Export data
wget "http://localhost/ShoeRetailErp/api/inventory.php?action=export_inventory"
```

This quick reference should help you work with the ERP system efficiently!
