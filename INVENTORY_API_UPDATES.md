# Inventory Module API - Integration Update

## Overview
The Inventory Management API (`api/inventory.php`) has been fully updated to align with the enterprise integration architecture pattern established by the HR module. All database operations now use the PDO wrapper via `getDB()` with proper authentication, authorization, error handling, and transaction management.

## Key Updates

### 1. Authentication & Authorization
- **Before**: Simple session check with `$_SESSION['user_id']`
- **After**: 
  - Uses `isLoggedIn()` function from `core_functions.php` for secure session validation
  - Implements `hasPermission('Manager')` for role-based access control
  - Ensures only authorized users (Manager role or higher) can access inventory endpoints

### 2. Database Integration
- **Before**: Used deprecated functions (`dbFetchAll`, `dbFetchOne`, `dbInsert`, `dbExecute`)
- **After**: Uses PDO database wrapper methods via `getDB()`:
  - `$db->fetchAll()` - Fetch multiple rows with prepared statements
  - `$db->fetchOne()` - Fetch single row with prepared statements
  - `$db->insert()` - Insert records and return last insert ID
  - `$db->update()` - Update records with prepared statements
  - `$db->execute()` - Execute queries without returning results
  - `$db->beginTransaction()` / `$db->commit()` / `$db->rollback()` - Transaction management

### 3. Table Name Standardization
All table names are now lowercase to maintain consistency with system-wide database schema:
- `products` (was: `Products`)
- `inventory` (was: `Inventory`)
- `stockmovements` (was: `StockMovements`)
- `stores` (was: `Stores`)
- `purchaseorders` (was: `PurchaseOrders`)
- `purchaseorderdetails` (was: `PurchaseOrderDetails`)

### 4. API Endpoints - Enhanced Features

#### `get_products` (GET)
**Enhanced Query**:
- Added store name information via LEFT JOIN with `stores` table
- Added `StockStatus` computed column (Low Stock/Overstock/Normal)
- Uses session-based store ID as default if not provided
- Supports product search by SKU, Brand, Model

**Response includes**:
```json
{
  "success": true,
  "data": [
    {
      "ProductID": 1,
      "SKU": "NIKE-001",
      "Brand": "Nike",
      "Model": "Air Max",
      "StockStatus": "Low Stock",
      "Quantity": 5,
      "StoreName": "Main Store"
    }
  ]
}
```

#### `add_product` (POST)
**Enhancements**:
- Comprehensive input validation (SKU, Brand, Model required)
- Duplicate SKU prevention
- Atomic transaction handling (rollback on failure)
- Automatic inventory entry creation for all active stores
- Structured error responses

**Validation**:
```php
if (empty($data['sku']) || empty($data['brand']) || empty($data['model'])) {
    throw new Exception('SKU, Brand, and Model are required');
}
```

#### `get_product` (GET)
**Enhancements**:
- Retrieves complete product details
- Stock levels across all stores
- Recent stock movements (last 10)
- Proper error handling for missing products

**Response includes**:
```json
{
  "success": true,
  "data": {
    "product": { /* product details */ },
    "stock_levels": [ /* all stores */ ],
    "recent_movements": [ /* last 10 movements */ ]
  }
}
```

#### `transfer_stock` (POST)
**Enhancements**:
- Source and destination validation
- Stock availability checking
- Atomic transaction processing
- Dual stock movement recording (OUT from source, IN to destination)
- Comprehensive audit logging

**Transaction Flow**:
1. Verify source inventory availability
2. Deduct from source store
3. Add to destination store (creates if needed)
4. Record stock movements with audit trail
5. Commit or rollback as needed

#### `get_low_stock` (GET)
**Enhanced filtering**:
- Store-specific low stock filtering
- Uses session store ID by default
- Shows store names and current quantities
- Ordered by lowest stock first

#### `request_restock` (POST)
**New Features**:
- Creates purchase order requests for low-stock items
- Links to supplier information
- Calculates total amount based on cost price
- Transactional purchase order creation with details
- Complete audit logging

### 5. Error Handling
- All endpoints wrapped in try-catch blocks
- Proper error logging via `logError()` function
- Structured error responses with HTTP status codes
- Database operation validation with meaningful messages

### 6. Logging & Audit Trail
All significant operations are logged:
```php
logInfo('Product added', ['product_id' => $productId, 'sku' => $data['sku']]);
logInfo('Restock request created', ['po_id' => $poId, 'product_id' => $data['product_id'], 'quantity' => $data['quantity']]);
```

## Module Integration Points

### Sales Module Integration
- Stock decrements when sales orders are processed
- Automatic stock movement recording

### Procurement Module Integration
- Stock increments when goods receipts are processed
- Links to purchase orders created via `request_restock`

### Accounting Module Integration
- Inventory valuation calculations via `get_inventory_value`
- Cost price-based valuations

## Removed Features
- **Manual stock_entry endpoint**: Disabled to prevent unauthorized manual adjustments
- **Deprecated helper functions**: `getLowStockItems()` and `updateInventory()` - logic integrated directly into endpoints

## API Response Format
All responses follow consistent JSON structure:

**Success Response**:
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { /* endpoint-specific data */ }
}
```

**Error Response**:
```json
{
  "success": false,
  "message": "Descriptive error message"
}
```

HTTP Status Codes:
- `200`: Successful operation
- `400`: Client error (validation, business logic)
- `401`: Unauthorized (not logged in)
- `403`: Forbidden (insufficient permissions)

## Session Handling
- Uses `$_SESSION['store_id']` for store-specific operations when no store_id parameter provided
- Uses `$_SESSION['username']` for audit trail (defaults to 'System' if not available)

## Performance Considerations
- All database queries use prepared statements (SQL injection prevention)
- Proper indexing on ProductID, StoreID in all related tables
- Transaction management for multi-step operations
- LEFT JOINs used to preserve partial data when relationships don't exist

## Testing Recommendations
1. Test authentication bypass attempts
2. Validate all input constraints
3. Test transaction rollback on failure
4. Verify audit logging on all operations
5. Test cross-store stock transfers
6. Validate stock movement recording

## Migration Notes
- Table names changed to lowercase (database migration may be needed)
- Old database helper functions no longer used
- All operations now require Manager role or higher
- Manual stock adjustments no longer allowed (use Procurement/Sales instead)

---
**Date Updated**: December 2024  
**Version**: 2.0 - Enterprise Integrated  
**Status**: Ready for production use with other integrated modules
