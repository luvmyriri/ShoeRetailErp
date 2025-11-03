# Database Schema Fixes - Sales Module

## Overview
Fixed critical SQL query errors in the Sales module due to column name mismatches between code and actual database schema.

## Issues Found & Fixed

### 1. **Sales Table Column Names**
**Before:** `subtotal`, `tax`, `discount`, `created_at`
**After:** `TotalAmount`, `TaxAmount`, `DiscountAmount`, `SaleDate`

**Affected Queries:**
- Daily sales statistics
- Sales orders listing and display

**Files Modified:**
- `/sales/index.php` (lines 157-188)

### 2. **Sales Detail/Items Table**
**Before:** `sales_items` table
**After:** `saledetails` table

**Column Mapping:**
| Old | New |
|-----|-----|
| sale_id | SaleID |
| product_id | ProductID |
| quantity | Quantity |
| unit_price | UnitPrice |
| subtotal | Subtotal |
| (new) | SalesUnitID |
| (new) | QuantityBase |

**Changes:**
- Added `SalesUnitID` (defaults to 1 for base unit)
- Added `QuantityBase` parameter

### 3. **Invoices Table**
**Before:**
- Table: `invoices`
- Date column: `invoice_date`
- Items: `sales_items` table

**After:**
- Table: `invoices` (unchanged)
- Date column: `InvoiceDate`
- Items: `invoiceitems` table (separate table)

**Column Mapping:**
| Old | New |
|-----|-----|
| i.id | InvoiceID |
| i.invoice_number | InvoiceNumber |
| i.total_amount | TotalAmount |
| (items) sales_items | invoiceitems |
| si.sale_id | ii.InvoiceID |

### 4. **Returns Table (if exists)**
**Expected Structure:**
| Column | Type | Notes |
|--------|------|-------|
| ReturnID | int | Primary key |
| SaleID | int | Foreign key to sales |
| ReturnDate | date | Date of return |
| Reason | text | Return reason |
| (other columns) | ... | ... |

### 5. **Customer Fields**
**Updated Queries:**
- `id` → `CustomerID`
- `first_name`, `last_name` → `FirstName`, `LastName`
- `member_code` → `MemberNumber`

### 6. **Store Fields**
**Updated Queries:**
- `id` → `StoreID`
- `name` → `StoreName`

### 7. **Product Fields**
**Updated Queries:**
- `id` → `ProductID`
- `sku` → `SKU`
- `name` → `Model`
- `price` → `SellingPrice`

## Error Prevention

All affected query sections now include:
1. **Try-catch blocks** to gracefully handle missing tables
2. **Error logging** for debugging
3. **Safe defaults** when tables don't exist
4. **Column aliases** to normalize data for frontend display

## Testing Recommendations

1. Test sales creation with multiple items
2. Verify invoice generation
3. Check daily statistics calculations
4. Validate orders listing with joins
5. Test return processing (if applicable)

## Future Prevention

To prevent similar issues:
1. Maintain schema documentation (✓ done in this file)
2. Use database views/aliases for legacy code compatibility
3. Implement schema migration scripts for all modules
4. Add unit tests for all database queries
5. Use ORM or query builders with type safety

## Schema Reference

**Sales Module Tables:**
- `sales` - Main sales transactions
- `saledetails` - Line items for sales
- `invoices` - Invoice records
- `invoiceitems` - Line items for invoices
- `returns` - Return transactions (if implemented)
- `accountsreceivable` - AR tracking

**Related Tables:**
- `customers` - Customer master data
- `products` - Product master data
- `stores` - Store master data
- `units` - Units of measure

---
**Last Updated:** 2025-11-02
**Applied to:** Sales Module (`/public/sales/index.php`)
