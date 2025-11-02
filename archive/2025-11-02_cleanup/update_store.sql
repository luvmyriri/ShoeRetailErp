-- Update stores table with Maria Collections Bagong Silang
-- Remove old stores and add the actual store

-- Temporarily disable safe update mode
SET SQL_SAFE_UPDATES = 0;

-- Clear existing stores
DELETE FROM stores WHERE StoreID > 0;

-- Reset auto increment
ALTER TABLE stores AUTO_INCREMENT = 1;

-- Insert Maria Collections Bagong Silang
INSERT INTO stores (StoreName, Location, ManagerName, ContactPhone, Status, CreatedAt) 
VALUES (
    'Maria Collections Bagong Silang',
    'Bagong Silang, Caloocan City',
    'Maria',
    '09XX-XXX-XXXX',
    'Active',
    NOW()
);

-- Re-enable safe update mode
SET SQL_SAFE_UPDATES = 1;

-- Verify the insert
SELECT * FROM stores;
