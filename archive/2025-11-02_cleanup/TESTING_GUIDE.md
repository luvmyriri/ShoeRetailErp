# Testing & Troubleshooting Guide

## Step 1: Test Database Connection

Visit this URL to verify database connection:
```
http://localhost/ShoeRetailErp/api/test.php
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Database connected!",
  "test": {
    "test": 1
  }
}
```

If you see an error, check:
- Is MySQL running?
- Are credentials correct in `config/database.php`?
- Does database `ShoeRetailERP` exist?

---

## Step 2: Test the Working Inventory Page

**URL:**
```
http://localhost/ShoeRetailErp/public/inventory/working.php
```

Login if needed, then you should see:
- Two stat cards (Total Products, Low Stock)
- Search box
- Empty product table with message "No products found"

---

## Step 3: Add a Product

1. Click **"Add Product"** button
2. Fill in the form:
   - SKU: `TEST001`
   - Brand: `Nike`
   - Model: `Air Max`
   - Size: `10`
   - Color: `Black`
   - Cost Price: `50`
   - Selling Price: `100`
3. Click **"Add Product"**
4. You should see success alert
5. Product appears in table

**If it doesn't work:**
- Open browser console (F12)
- Check for JavaScript errors
- Look at Network tab to see API response
- Check `/api/inventory.php?action=add_product` response

---

## Step 4: Add Stock

1. Click the **"+"** button on any product
2. Enter quantity (e.g., 50)
3. Click OK
4. Quantity updates in table

---

## Step 5: Search Products

1. Type in search box (e.g., "Nike")
2. Results filter automatically
3. Clear and search again with different term

---

## Step 6: Export

1. Click **"Export"** button
2. CSV file downloads with inventory data

---

## Debugging Checklist

- [ ] MySQL is running on `localhost`
- [ ] Database credentials are correct (`root` / `0428` / `ShoeRetailERP`)
- [ ] Database tables exist (Products, Inventory)
- [ ] Browser console shows no errors (F12)
- [ ] API returns JSON responses
- [ ] Session is active (user logged in)

---

## Common Issues

### Issue: "No products found" but should have data
**Solution:**
```sql
-- Check if Products table is empty
SELECT COUNT(*) FROM Products;

-- If empty, insert test data:
INSERT INTO Products (SKU, Brand, Model, Size, Color, CostPrice, SellingPrice, MinStockLevel, MaxStockLevel, Status) 
VALUES ('NIKE001', 'Nike', 'Air Max', '10', 'Black', 50, 100, 10, 100, 'Active');
```

### Issue: Modal doesn't open
**Solution:**
- Check browser console for errors
- Verify CSS is loaded
- Ensure JavaScript files are in `/public/js/`

### Issue: Add Product button does nothing
**Solution:**
- Check Network tab (F12)
- Verify `/api/inventory.php` is being called
- Check response for errors
- Verify request has `application/json` header

### Issue: Data not persisting
**Solution:**
- Check database connection works (Step 1)
- Verify Products and Inventory tables exist
- Check MySQL error logs
- Try INSERT manually in MySQL

---

## Quick Database Check

```sql
-- Connect to database
mysql -u root -p ShoeRetailERP

-- Check tables
SHOW TABLES;

-- Check Products table structure
DESCRIBE Products;

-- Check Inventory table structure
DESCRIBE Inventory;

-- Check for existing products
SELECT * FROM Products LIMIT 5;

-- Insert test product if needed
INSERT INTO Products (SKU, Brand, Model, Size, Color, CostPrice, SellingPrice, MinStockLevel, MaxStockLevel, Status) 
VALUES ('TEST001', 'Nike', 'Air Max', '10', 'Black', 50, 100, 10, 100, 'Active');

-- Verify insertion
SELECT * FROM Products WHERE SKU = 'TEST001';
```

---

## Browser Console Debugging

Open **F12** and click **Console** tab. You'll see:
- API request URLs being called
- Response status and data
- Any JavaScript errors

**Example successful log:**
```
/ShoeRetailErp/api/inventory.php?action=get_products
Response: {success: true, data: [...]}
```

**Example error log:**
```
Error: {"success": false, "message": "Unauthorized"}
```

If you see "Unauthorized", you need to be logged in.

---

## Testing All Features

| Feature | URL | Expected Result |
|---------|-----|-----------------|
| Database | `/api/test.php` | Connection OK message |
| Inventory | `/public/inventory/working.php` | Page loads with table |
| Add Product | Click button in inventory | Modal opens, form submits |
| Add Stock | Click + button | Quantity increases |
| Search | Type in search box | Results filter |
| Export | Click export button | CSV downloads |

---

## API Endpoints to Test Directly

### Get Products
```
http://localhost/ShoeRetailErp/api/inventory.php?action=get_products
```

### Add Product
```
POST http://localhost/ShoeRetailErp/api/inventory.php?action=add_product
Body: {
  "sku": "TEST002",
  "brand": "Adidas",
  "model": "Stan Smith",
  "size": "11",
  "color": "White",
  "cost_price": 45,
  "selling_price": 95
}
```

### Export Inventory
```
http://localhost/ShoeRetailErp/api/inventory.php?action=export_inventory
```

---

**All features should now be working!** If you encounter any issues, refer to the debugging checklist above.
