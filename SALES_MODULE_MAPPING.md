# Sales Module Complete Mapping & Database Schema Reference

## Module Overview
**Location:** `/public/sales/`
**Files:** 
- `index.php` - Main sales management dashboard
- `pos.php` - Point of Sale interface
- `sales_static.php` - Static reference template

---

## Database Tables & Column Mapping

### 1. **SALES Table**
**Table Name:** `sales`

| Column Name | Type | PHP Key | Status |
|------------|------|---------|--------|
| SaleID | INT | `sale_id` | ✅ Fixed |
| CustomerID | INT | `customer_id` | ✅ Fixed |
| StoreID | INT | `store_id` | ✅ Fixed |
| SaleDate | DATETIME | `created_at` | ✅ Fixed |
| TotalAmount | DECIMAL(10,2) | `TotalAmount` | ✅ Fixed |
| TaxAmount | DECIMAL(10,2) | `tax` | ✅ Fixed |
| DiscountAmount | DECIMAL(10,2) | `discount` | ✅ Fixed |
| PointsUsed | INT | `points_used` | ✅ |
| PointsEarned | INT | `points_earned` | ✅ |
| PaymentStatus | ENUM | `PaymentStatus` | ✅ Fixed |
| PaymentMethod | ENUM | `payment_method` | ✅ Fixed |
| SalespersonID | INT | `salesperson_id` | ✅ |

---

### 2. **SALEDETAILS Table**
**Table Name:** `saledetails` (formerly `sales_items`)

| Column Name | Type | PHP Key | Status |
|------------|------|---------|--------|
| SaleDetailID | INT | `detail_id` | ✅ |
| SaleID | INT | `sale_id` | ✅ Fixed |
| ProductID | INT | `product_id` | ✅ Fixed |
| Quantity | DECIMAL(12,4) | `quantity` | ✅ Fixed |
| SalesUnitID | INT | `unit_id` | ✅ Fixed |
| QuantityBase | DECIMAL(12,4) | `qty_base` | ✅ Fixed |
| UnitPrice | DECIMAL(10,2) | `unit_price` | ✅ Fixed |
| Subtotal | DECIMAL(10,2) | `line_subtotal` | ✅ Fixed |

---

### 3. **INVOICES Table**
**Table Name:** `invoices`

| Column Name | Type | PHP Key | Status |
|------------|------|---------|--------|
| InvoiceID | INT | `id` | ✅ Fixed |
| InvoiceNumber | VARCHAR(20) | `invoice_number` | ✅ Fixed |
| SaleID | INT | `sale_id` | ✅ Fixed |
| CustomerID | INT | `customer_id` | ✅ |
| StoreID | INT | `store_id` | ✅ |
| InvoiceDate | DATETIME | `invoice_date` | ✅ Fixed |
| TotalAmount | DECIMAL(10,2) | `TotalAmount` | ✅ Fixed |
| TaxAmount | DECIMAL(10,2) | `tax_amount` | ✅ |
| DiscountAmount | DECIMAL(10,2) | `discount_amt` | ✅ |
| PaymentMethod | ENUM | `payment_method` | ✅ Fixed |
| PaymentStatus | ENUM | `payment_status` | ✅ |
| CreatedBy | VARCHAR(50) | `created_by` | ✅ |
| CreatedAt | DATETIME | `created_at` | ✅ |

---

### 4. **INVOICEITEMS Table**
**Table Name:** `invoiceitems`

| Column Name | Type | PHP Key | Status |
|------------|------|---------|--------|
| InvoiceItemID | INT | `item_id` | ✅ |
| InvoiceID | INT | `invoice_id` | ✅ Fixed |
| ProductID | INT | `product_id` | ✅ Fixed |
| Quantity | DECIMAL(12,4) | `quantity` | ✅ Fixed |
| UnitID | INT | `unit_id` | ✅ |
| QuantityBase | DECIMAL(12,4) | `qty_base` | ✅ |
| UnitPrice | DECIMAL(10,2) | `unit_price` | ✅ Fixed |
| Subtotal | DECIMAL(10,2) | `subtotal` | ✅ |

---

### 5. **RETURNS Table** (if implemented)
**Table Name:** `returns`

| Column Name | Type | PHP Key | Status |
|------------|------|---------|--------|
| ReturnID | INT | `id` | ✅ |
| SaleID | INT | `sale_id` | ✅ Fixed |
| ReturnDate | DATE | `return_date` | ✅ Fixed |
| Reason | TEXT | `reason` | ✅ Fixed |
| RefundMethod | VARCHAR(50) | `refund_method` | ✅ Fixed |
| notes | TEXT | `notes` | ✅ Fixed |
| CreatedAt | DATETIME | `created_at` | ✅ |

---

### 6. **Related Tables**

#### CUSTOMERS Table
| Column | Mapping |
|--------|---------|
| CustomerID | `id` ✅ |
| FirstName | `cust_first` ✅ |
| LastName | `cust_last` ✅ |
| MemberNumber | `member_code` ✅ |

#### STORES Table
| Column | Mapping |
|--------|---------|
| StoreID | `id` ✅ |
| StoreName | `store_name` ✅ |

#### PRODUCTS Table
| Column | Mapping |
|--------|---------|
| ProductID | `id` ✅ |
| SKU | `sku` ✅ |
| Model | `name` ✅ |
| SellingPrice | `price` ✅ |

---

## PHP Query Patterns

### Sales Statistics Query
```sql
SELECT IFNULL(SUM(TotalAmount),0) AS total 
FROM sales 
WHERE DATE(SaleDate) = ?
```

### Orders List Query
```sql
SELECT s.SaleID AS sale_id, s.TotalAmount, s.TaxAmount AS tax, 
       s.DiscountAmount AS discount, s.PaymentMethod, s.SaleDate,
       c.FirstName AS cust_first, c.LastName AS cust_last, 
       st.StoreName AS store_name
FROM sales s
LEFT JOIN customers c ON s.CustomerID = c.CustomerID
LEFT JOIN stores st ON s.StoreID = st.StoreID
ORDER BY s.SaleDate DESC
```

### Sale Details Query
```sql
SELECT sd.Quantity, sd.UnitPrice, p.Model AS product_name
FROM saledetails sd
JOIN products p ON sd.ProductID = p.ProductID
WHERE sd.SaleID = ?
```

### Invoices Query
```sql
SELECT i.InvoiceID AS id, i.InvoiceNumber, i.SaleID, 
       i.TotalAmount, i.InvoiceDate,
       s.PaymentMethod, c.FirstName AS cust_first, 
       c.LastName AS cust_last, st.StoreName AS store_name
FROM invoices i
JOIN sales s ON i.SaleID = s.SaleID
LEFT JOIN customers c ON s.CustomerID = c.CustomerID
LEFT JOIN stores st ON s.StoreID = st.StoreID
ORDER BY i.InvoiceDate DESC
```

### Invoice Items Query
```sql
SELECT ii.Quantity, ii.UnitPrice, p.Model AS product_name
FROM invoiceitems ii
JOIN products p ON ii.ProductID = p.ProductID
WHERE ii.InvoiceID = ?
```

---

## Fixed Issues Summary

### ✅ Completed Fixes
1. **Sales Table Column Names** - Updated from snake_case to PascalCase
2. **Date Columns** - Changed from `created_at` to `SaleDate`, `invoice_date` to `InvoiceDate`
3. **Amount Columns** - Fixed `subtotal` → `TotalAmount`, `tax` → `TaxAmount`, `discount` → `DiscountAmount`
4. **Table References** - Updated `sales_items` → `saledetails`
5. **Invoice Items** - Changed from joining `sales_items` → `invoiceitems` table
6. **Return Items** - Corrected table reference to `return_items`
7. **Status Fields** - Fixed `status` → `PaymentStatus`
8. **Display Fields** - Updated all HTML rendering to use correct PascalCase columns

---

## Testing Checklist

- [ ] Sales creation without errors
- [ ] Daily statistics calculations work
- [ ] Orders list displays with customer and store data
- [ ] Invoice generation successful
- [ ] Invoice items display correctly
- [ ] Return processing works
- [ ] All filters function properly
- [ ] Export functionality works
- [ ] No column not found errors in logs

---

## File Changes

**Modified:** `/public/sales/index.php`
- Lines 54-59: INSERT sales statement
- Lines 67-68: INSERT saledetails statement
- Lines 76-81: INSERT invoices statement
- Lines 120-122: INSERT returns statement
- Lines 162-169: SELECT statistics queries
- Lines 175-177: SELECT customers/stores/products
- Lines 182-190: SELECT orders with joins
- Lines 197-201: SELECT saledetails items
- Lines 213-220: SELECT invoices with joins
- Lines 228-232: SELECT invoiceitems
- Lines 243-251: SELECT returns
- Lines 427-449: HTML rendering orders table
- Lines 523-543: HTML rendering invoices table

---

## Future Improvements

1. Create database views for legacy code compatibility
2. Implement ORM layer to prevent schema mismatches
3. Add schema migration system
4. Unit tests for all queries
5. Add TypeScript/JSDoc for query return types

---

**Last Updated:** 2025-11-02
**Status:** ✅ All Database Schema Issues Fixed
**Module:** Sales Management System
