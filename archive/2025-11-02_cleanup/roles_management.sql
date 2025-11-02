-- =====================================================
-- Role Management - Enhanced Roles with Permissions
-- Shoe Retail ERP System
-- =====================================================

USE ShoeRetailERP;

-- =====================================================
-- ENHANCED ROLES TABLE WITH ALL PERMISSIONS
-- =====================================================

-- Clear existing roles (optional - comment out if keeping existing)
-- DELETE FROM Roles;

-- Insert or Update Core Roles with Comprehensive Permissions

-- 1. Cashier
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Cashier', 'Point of Sale operator who processes customer purchases', 
'{
  "can_process_sale": true,
  "can_view_sales": true,
  "can_process_cash": true,
  "can_process_card": true,
  "can_view_inventory_limited": true,
  "can_apply_discount": false,
  "can_process_refund": false,
  "module_access": ["Sales Management"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Point of Sale operator who processes customer purchases',
  Permissions = '{
    "can_process_sale": true,
    "can_view_sales": true,
    "can_process_cash": true,
    "can_process_card": true,
    "can_view_inventory_limited": true,
    "can_apply_discount": false,
    "can_process_refund": false,
    "module_access": ["Sales Management"]
  }';

-- 2. Sales Manager
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Sales Manager', 'Manages sales operations, approves returns and generates sales reports',
'{
  "can_process_sale": true,
  "can_view_sales": true,
  "can_process_refunds": true,
  "can_approve_refunds": true,
  "can_generate_sales_reports": true,
  "can_apply_discount": true,
  "can_view_loyalty_points": true,
  "can_manage_sales_staff": true,
  "module_access": ["Sales Management", "Customer Management"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Manages sales operations, approves returns and generates sales reports',
  Permissions = '{
    "can_process_sale": true,
    "can_view_sales": true,
    "can_process_refunds": true,
    "can_approve_refunds": true,
    "can_generate_sales_reports": true,
    "can_apply_discount": true,
    "can_view_loyalty_points": true,
    "can_manage_sales_staff": true,
    "module_access": ["Sales Management", "Customer Management"]
  }';

-- 3. Inventory Manager
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Inventory Manager', 'Manages stock entry, tracks inventory levels, handles returns and stock alerts',
'{
  "can_manage_inventory": true,
  "can_process_stock_entry": true,
  "can_view_stock_reports": true,
  "can_handle_returns": true,
  "can_respond_stock_alerts": true,
  "can_create_stock_transfer": true,
  "can_view_purchase_orders": true,
  "can_manage_inventory_staff": true,
  "module_access": ["Inventory Management", "Procurement"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Manages stock entry, tracks inventory levels, handles returns and stock alerts',
  Permissions = '{
    "can_manage_inventory": true,
    "can_process_stock_entry": true,
    "can_view_stock_reports": true,
    "can_handle_returns": true,
    "can_respond_stock_alerts": true,
    "can_create_stock_transfer": true,
    "can_view_purchase_orders": true,
    "can_manage_inventory_staff": true,
    "module_access": ["Inventory Management", "Procurement"]
  }';

-- 4. Procurement Manager
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Procurement Manager', 'Creates and manages purchase orders, handles supplier communications, processes goods receipt',
'{
  "can_create_purchase_order": true,
  "can_manage_suppliers": true,
  "can_process_goods_receipt": true,
  "can_approve_purchase_order": true,
  "can_manage_supplier_payments": true,
  "can_view_stock_levels": true,
  "can_manage_procurement_staff": true,
  "module_access": ["Procurement", "Inventory Management"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Creates and manages purchase orders, handles supplier communications, processes goods receipt',
  Permissions = '{
    "can_create_purchase_order": true,
    "can_manage_suppliers": true,
    "can_process_goods_receipt": true,
    "can_approve_purchase_order": true,
    "can_manage_supplier_payments": true,
    "can_view_stock_levels": true,
    "can_manage_procurement_staff": true,
    "module_access": ["Procurement", "Inventory Management"]
  }';

-- 5. Customer Service
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Customer Service', 'Manages customer profiles, handles support tickets, tracks loyalty programs',
'{
  "can_manage_customers": true,
  "can_create_support_tickets": true,
  "can_view_loyalty_points": true,
  "can_create_customer": true,
  "can_update_customer": true,
  "can_view_customer_history": true,
  "can_resolve_support_tickets": true,
  "module_access": ["Customer Management", "Sales Management"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Manages customer profiles, handles support tickets, tracks loyalty programs',
  Permissions = '{
    "can_manage_customers": true,
    "can_create_support_tickets": true,
    "can_view_loyalty_points": true,
    "can_create_customer": true,
    "can_update_customer": true,
    "can_view_customer_history": true,
    "can_resolve_support_tickets": true,
    "module_access": ["Customer Management", "Sales Management"]
  }';

-- 6. Accountant
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Accountant', 'Manages general ledger, processes AR/AP, handles taxes, generates financial reports',
'{
  "can_manage_ledger": true,
  "can_process_ar_ap": true,
  "can_generate_financial_reports": true,
  "can_manage_taxes": true,
  "can_reconcile_accounts": true,
  "can_view_sales": true,
  "can_view_purchases": true,
  "can_manage_payroll_expenses": true,
  "module_access": ["Accounting", "Sales Management", "Procurement"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Manages general ledger, processes AR/AP, handles taxes, generates financial reports',
  Permissions = '{
    "can_manage_ledger": true,
    "can_process_ar_ap": true,
    "can_generate_financial_reports": true,
    "can_manage_taxes": true,
    "can_reconcile_accounts": true,
    "can_view_sales": true,
    "can_view_purchases": true,
    "can_manage_payroll_expenses": true,
    "module_access": ["Accounting", "Sales Management", "Procurement"]
  }';

-- 7. HR Manager
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('HR Manager', 'Manages employee records, assigns roles, distributes roles, processes payroll',
'{
  "can_manage_employees": true,
  "can_assign_roles": true,
  "can_process_payroll": true,
  "can_approve_leave": true,
  "can_manage_timesheets": true,
  "can_view_payroll_expenses": true,
  "can_manage_attendance": true,
  "module_access": ["HR", "Accounting"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Manages employee records, assigns roles, distributes roles, processes payroll',
  Permissions = '{
    "can_manage_employees": true,
    "can_assign_roles": true,
    "can_process_payroll": true,
    "can_approve_leave": true,
    "can_manage_timesheets": true,
    "can_view_payroll_expenses": true,
    "can_manage_attendance": true,
    "module_access": ["HR", "Accounting"]
  }';

-- 8. Store Manager
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Store Manager', 'Oversees store operations including inventory, sales, customer service, staff',
'{
  "can_manage_inventory": true,
  "can_view_inventory": true,
  "can_process_sale": true,
  "can_process_refunds": true,
  "can_view_store_reports": true,
  "can_manage_store_staff": true,
  "can_view_store_sales": true,
  "can_view_employee_assignments": true,
  "module_access": ["Inventory Management", "Sales Management", "Customer Management", "HR"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Oversees store operations including inventory, sales, customer service, staff',
  Permissions = '{
    "can_manage_inventory": true,
    "can_view_inventory": true,
    "can_process_sale": true,
    "can_process_refunds": true,
    "can_view_store_reports": true,
    "can_manage_store_staff": true,
    "can_view_store_sales": true,
    "can_view_employee_assignments": true,
    "module_access": ["Inventory Management", "Sales Management", "Customer Management", "HR"]
  }';

-- 9. Admin
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Admin', 'Full system access for oversight, user management, configurations',
'{
  "can_manage_all": true,
  "can_manage_users": true,
  "can_manage_roles": true,
  "can_manage_systems": true,
  "can_generate_reports": true,
  "can_manage_employees": true,
  "can_view_audit_logs": true,
  "module_access": ["All"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Full system access for oversight, user management, configurations',
  Permissions = '{
    "can_manage_all": true,
    "can_manage_users": true,
    "can_manage_roles": true,
    "can_manage_systems": true,
    "can_generate_reports": true,
    "can_manage_employees": true,
    "can_view_audit_logs": true,
    "module_access": ["All"]
  }';

-- =====================================================
-- INVENTORY DEPARTMENT SPECIALIZED ROLES
-- =====================================================

-- 10. Inventory Analyst
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Inventory Analyst', 'Analyzes stock levels, forecasts demand, generates inventory reports',
'{
  "can_view_stock_reports": true,
  "can_forecast_stock": true,
  "can_generate_inventory_reports": true,
  "can_analyze_stock_trends": true,
  "can_view_purchase_orders": true,
  "can_view_inventory": true,
  "module_access": ["Inventory Management", "Procurement"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Analyzes stock levels, forecasts demand, generates inventory reports',
  Permissions = '{
    "can_view_stock_reports": true,
    "can_forecast_stock": true,
    "can_generate_inventory_reports": true,
    "can_analyze_stock_trends": true,
    "can_view_purchase_orders": true,
    "can_view_inventory": true,
    "module_access": ["Inventory Management", "Procurement"]
  }';

-- 11. Inventory Clerk
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Inventory Clerk', 'Performs stock counts, updates inventory records, validates stock data accuracy',
'{
  "can_update_inventory": true,
  "can_perform_stock_count": true,
  "can_validate_stock_data": true,
  "can_view_inventory": true,
  "can_process_stock_entry": true,
  "module_access": ["Inventory Management"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Performs stock counts, updates inventory records, validates stock data accuracy',
  Permissions = '{
    "can_update_inventory": true,
    "can_perform_stock_count": true,
    "can_validate_stock_data": true,
    "can_view_inventory": true,
    "can_process_stock_entry": true,
    "module_access": ["Inventory Management"]
  }';

-- 12. Inventory Counter
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Inventory Counter', 'Conducts physical stock checks and reports discrepancies',
'{
  "can_perform_stock_count": true,
  "can_view_inventory": true,
  "can_report_stock_discrepancies": true,
  "module_access": ["Inventory Management"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Conducts physical stock checks and reports discrepancies',
  Permissions = '{
    "can_perform_stock_count": true,
    "can_view_inventory": true,
    "can_report_stock_discrepancies": true,
    "module_access": ["Inventory Management"]
  }';

-- 13. Inventory Encoder
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Inventory Encoder', 'Enters stock data into system, ensures accurate records',
'{
  "can_process_stock_entry": true,
  "can_add_stock_data": true,
  "can_update_inventory": true,
  "can_view_inventory": true,
  "module_access": ["Inventory Management"]
}', 'Yes')
ON DUPLICATE KEY UPDATE 
  Description = 'Enters stock data into system, ensures accurate records',
  Permissions = '{
    "can_process_stock_entry": true,
    "can_add_stock_data": true,
    "can_update_inventory": true,
    "can_view_inventory": true,
    "module_access": ["Inventory Management"]
  }';

-- =====================================================
-- VERIFY ROLES CREATION
-- =====================================================

SELECT 'Roles Summary' AS Status;
SELECT 
    RoleID,
    RoleName,
    Description,
    IsActive,
    JSON_EXTRACT(Permissions, '$.module_access') AS ModuleAccess
FROM Roles
ORDER BY RoleName;

-- =====================================================
-- ROLE PERMISSION REFERENCE VIEW
-- =====================================================

CREATE OR REPLACE VIEW v_role_permissions AS
SELECT
    RoleID,
    RoleName,
    Description,
    IsActive,
    JSON_EXTRACT(Permissions, '$.module_access') AS ModuleAccess,
    Permissions AS FullPermissions
FROM Roles
ORDER BY RoleName;

-- =====================================================
-- END OF ROLE MANAGEMENT SCRIPT
-- =====================================================