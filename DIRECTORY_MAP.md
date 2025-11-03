# ERP System Complete Directory Map

## 📁 Root Structure

```
ShoeRetailErp/
├── api/                          (Backend API endpoints)
├── config/                        (Configuration files)
├── docs/                          (Documentation)
├── examples/                      (Example files)
├── includes/                      (Shared functions & helpers)
├── logs/                          (Application logs)
├── public/                        (Frontend & UI)
├── sql/                           (Database files)
├── login.php                      (Authentication)
├── logout.php                     (Sign out)
└── *.md files                     (Documentation)
```

---

## 📋 API Layer (`/api/`)

Backend endpoints for each module. Called by frontend via AJAX.

```
api/
├── hr.php                    ← HR Module (OLD - NEEDS MIGRATION)
├── hr_integrated.php         ← HR Module (NEW - INTEGRATED ✅)
├── procurement.php           ← Procurement Module (NEEDS INTEGRATION)
├── sales.php                 ← Sales Module (NEEDS INTEGRATION)
├── inventory.php             ← Inventory Module (NEEDS INTEGRATION)
├── accounting.php            ← Accounting Module (NEEDS INTEGRATION)
├── crm.php                   ← CRM Module (NEEDS INTEGRATION)
├── customers.php             ← Customers Module (NEEDS INTEGRATION)
├── dashboard.php             ← Dashboard Analytics (NEEDS INTEGRATION)
├── hr_accounting.php         ← HR/Accounting Bridge (LEGACY)
├── procurement_complete.php  ← Procurement Complete Handler (LEGACY)
├── test.php                  ← Testing Endpoint
└── test_endpoints.php        ← Endpoint Tester
```

**Purpose**: Process frontend requests, handle database operations, return JSON responses

---

## 🎨 Frontend Layer (`/public/`)

UI pages and components for each module. Displayed in browser.

### Structure:
```
public/
├── index.php                 ← Dashboard/Home
├── profile.php               ← User Profile
├── settings.php              ← System Settings
├── css/                      ← Global Styles
│   ├── style.css
│   └── pos_style.css
├── js/                       ← Global Scripts
│   ├── app.js
│   └── erp-app.js
├── includes/
│   └── navbar.php            ← Navigation Bar
├── templates/                ← HTML Templates
│   ├── dashboard.html
│   ├── home.html
│   ├── sales.html
│   ├── procurement.html
│   ├── inventory.html
│   ├── accounting.html
│   ├── customer_management.html
│   └── role-management.html
│
├── hr/                       ← HR Module UI
│   ├── index.php             ← HR Dashboard
│   ├── employees.php         ← Employee List
│   ├── employee_directory.php ← Employee Directory
│   ├── departments.php       ← Department Management
│   ├── assign_roles.php      ← Role Assignment
│   ├── employee-timesheet.php ← Timesheet Entry
│   ├── timesheets.php        ← Timesheet Approval
│   ├── details.php           ← Employee Details
│   ├── leave_management.php  ← Leave Requests
│   ├── payroll_branch_select.php ← Branch Selection
│   ├── payroll_departments.php   ← Department Payroll
│   ├── payroll_management.php    ← Payroll Processing
│   ├── payroll_records.php       ← Payroll Records
│   └── reports.php           ← HR Reports
│
├── procurement/              ← Procurement Module UI
│   ├── index.php             ← Purchase Orders
│   ├── Connection.php        ← DB Connection Helper
│   ├── addsupplier.php       ← Add/Edit Supplier
│   ├── editsupplier.php      ← Edit Supplier
│   ├── goodreceipts.php      ← Goods Receipt
│   ├── recieve_order.php     ← Receive Order
│   ├── orderfulfillment.php  ← Order Fulfillment
│   ├── qualitychecking.php   ← Quality Check
│   ├── process_order.php     ← Process Order
│   ├── reports.php           ← Procurement Reports
│   ├── css/                  ← Procurement Styles
│   │   ├── index.css
│   │   ├── addsupplier.css
│   │   ├── goodreceipts.css
│   │   ├── orderfulfillment.css
│   │   ├── qualitychecking.css
│   │   └── reports.css
│   └── js/                   ← Procurement Scripts
│       ├── index.js
│       ├── goodreceipts.js
│       ├── orderfulfillment.js
│       ├── qualitychecking.js
│       └── reports.js
│
├── sales/                    ← Sales Module UI
│   ├── index.php             ← Sales Dashboard
│   ├── pos.php               ← Point of Sale System
│   └── sales_static.php      ← Static Sales Page
│
├── inventory/                ← Inventory Module UI
│   └── index.php             ← Inventory Dashboard
│
├── accounting/               ← Accounting Module UI
│   ├── index.php             ← Accounting Dashboard
│   ├── accounting_api.php    ← Accounting API Handler
│   ├── accounting_functions.php ← Accounting Functions
│   └── modals/               ← Modal Dialogs
│       ├── add_dept_modal.php
│       ├── add_grade_modal.php
│       ├── approve_budget_modal.php
│       ├── audit_log_modal.php
│       ├── dept_payroll_modal.php
│       ├── payment_modal.php
│       └── salary_modal.php
│
├── crm/                      ← CRM Module UI
│   ├── CrmDashboard.php      ← CRM Dashboard
│   ├── crmProfile.php        ← CRM User Profile
│   ├── customerProfile.php   ← Customer Profile
│   ├── customerSupport.php   ← Customer Support
│   ├── loyaltyProgram.php    ← Loyalty Program
│   ├── reportsManagement.php ← CRM Reports
│   ├── get_deal_details.php  ← Deal Details
│   ├── get_lead_details.php  ← Lead Details
│   ├── get_task_details.php  ← Task Details
│   └── styles/
│       └── crmGlobalStyles.css
│
└── customers/                ← Customers Module UI
    └── index.php             ← Customer Management
```

---

## 🔧 Backend Infrastructure

### Config (`/config/`)
```
config/
├── database.php              ← PDO Database Connection (PRIMARY ✅)
└── web.config                ← IIS Configuration
```

### Includes (`/includes/`)
Shared functions used by all modules:

```
includes/
├── core_functions.php        ← Main Business Logic (850+ lines)
│   ├── Inventory functions
│   ├── Sales functions
│   ├── Procurement functions
│   ├── Customers functions
│   ├── Accounting functions
│   ├── Authentication functions
│   └── Utility functions
│
├── db_helper.php             ← Database Helper (DEPRECATED - Use config/database.php)
├── dbconnection.php          ← Legacy DB Connection
├── hr_functions.php          ← HR Specific Functions
└── role_management_functions.php ← Role/Permission Functions
```

### Docs (`/docs/`)
```
docs/
├── FRONTEND_GUIDE.md         ← Frontend Development Guide
└── ROLE_MANAGEMENT_GUIDE.md  ← Role Management Documentation
```

### Examples (`/examples/`)
```
examples/
└── role_management_examples.php ← Role Management Examples
```

---

## 📊 Data Flow Diagram

```
USER (Browser)
    ↓
/public/[module]/index.php (UI Page)
    ↓ AJAX Request
/api/[module].php (Process Request)
    ↓
/includes/core_functions.php (Business Logic)
    ↓
/config/database.php (PDO Connection)
    ↓
MySQL Database
    ├─ Tables
    ├─ Stored Procedures
    ├─ Triggers
    └─ Views
    ↑
    └─ JSON Response
      ↓
    Browser (Display)
```

---

## 🎯 Module Integration Map

### HR Module
**Frontend**: `/public/hr/`
- `index.php` - Main page
- `employees.php` - Employee list & CRUD
- `timesheets.php` - Timesheet management
- `leave_management.php` - Leave requests
- `payroll_management.php` - Payroll processing

**Backend**: `/api/hr_integrated.php` ✅ COMPLETE
- 13 endpoints for employee, attendance, leave, payroll
- GL integration for payroll
- Proper error handling

**Shared Functions**: `/includes/core_functions.php`
- `processSale()`, `receivePurchaseOrder()`, `processARPayment()`

---

### Procurement Module
**Frontend**: `/public/procurement/`
- `index.php` - PO list
- `addsupplier.php` / `editsupplier.php` - Supplier management
- `goodreceipts.php` - Goods receipt
- `orderfulfillment.php` - Order fulfillment
- `qualitychecking.php` - Quality control
- `reports.php` - Reports

**Backend**: `/api/procurement.php` (NEEDS INTEGRATION)
**Helper**: `/public/procurement/Connection.php` (DB connection)

---

### Sales Module
**Frontend**: `/public/sales/`
- `index.php` - Sales dashboard
- `pos.php` - Point of Sale system
- `sales_static.php` - Static sales page

**Backend**: `/api/sales.php` (NEEDS INTEGRATION)

---

### Inventory Module
**Frontend**: `/public/inventory/`
- `index.php` - Inventory dashboard

**Backend**: `/api/inventory.php` (NEEDS INTEGRATION)

---

### Accounting Module
**Frontend**: `/public/accounting/`
- `index.php` - Accounting dashboard
- `accounting_api.php` - API handler
- `accounting_functions.php` - Functions
- `modals/` - Modal dialogs for various operations

**Backend**: `/api/accounting.php` (NEEDS INTEGRATION)

---

### CRM Module
**Frontend**: `/public/crm/`
- `CrmDashboard.php` - CRM Dashboard
- `customerProfile.php` - Customer details
- `loyaltyProgram.php` - Loyalty program
- `customerSupport.php` - Support tickets
- `reportsManagement.php` - Reports

**Backend**: `/api/crm.php` (NEEDS INTEGRATION)

---

### Customers Module
**Frontend**: `/public/customers/`
- `index.php` - Customer management

**Backend**: `/api/customers.php` (NEEDS INTEGRATION)

---

## 🔐 Authentication Files

```
Root Level:
├── login.php                 ← Login page (Entry point)
└── logout.php                ← Logout handler
```

These handle session creation and destruction.

---

## 📝 Log Files

```
logs/
├── error.log                 ← Application errors
└── info.log                  ← Info messages
```

Generated by `logError()` and `logInfo()` functions in `core_functions.php`

---

## 🗄️ SQL Files

```
sql/
└── ERP_DEFAULT_SCHEMA_FINAL.sql ← Complete database schema
    ├─ 30+ tables
    ├─ Stored procedures (3)
    ├─ Triggers (6)
    └─ Views (5)
```

---

## 📊 Complete Integration Status

| Module | Frontend | Backend API | Core Functions | Status |
|--------|----------|-------------|-----------------|--------|
| **HR** | ✅ 12 pages | ✅ hr_integrated.php | ✅ Complete | ✅ INTEGRATED |
| **Procurement** | ✅ 9 pages | 🔧 procurement.php | ⏳ Partial | 🔄 NEEDS WORK |
| **Sales** | ✅ 3 pages | 🔧 sales.php | ⏳ Partial | 🔄 NEEDS WORK |
| **Inventory** | ✅ 1 page | 🔧 inventory.php | ⏳ Partial | 🔄 NEEDS WORK |
| **Accounting** | ✅ 8 pages | 🔧 accounting.php | ⏳ Partial | 🔄 NEEDS WORK |
| **CRM** | ✅ 9 pages | 🔧 crm.php | ⏳ Partial | 🔄 NEEDS WORK |
| **Customers** | ✅ 1 page | 🔧 customers.php | ⏳ Partial | 🔄 NEEDS WORK |

---

## 🚀 How Frontend & Backend Connect

### Example: HR Module

**Frontend** (`/public/hr/employees.php`):
```javascript
// Fetch employees via AJAX
fetch('/api/hr_integrated.php?action=get_employees')
    .then(r => r.json())
    .then(data => displayEmployees(data));
```

**Backend** (`/api/hr_integrated.php`):
```php
case 'get_employees':
    getEmployeesHR();  // Function calls getDB()->fetchAll()
    break;
```

**Response** (JSON):
```json
{
  "success": true,
  "data": [
    {"EmployeeID": 1, "FirstName": "John", ...},
    ...
  ]
}
```

---

## 📌 Key Files to Understand

1. **`/config/database.php`** - DB connection, use this everywhere
2. **`/includes/core_functions.php`** - All business logic
3. **`/api/hr_integrated.php`** - Reference implementation
4. **`/public/hr/index.php`** - Main HR frontend entry
5. **`login.php`** - Authentication entry point

---

## 💡 Integration Workflow

For each remaining module:

1. **Frontend exists** in `/public/[module]/`
2. **Backend skeleton exists** in `/api/[module].php`
3. **Update backend** using HR pattern:
   - Use `getDB()` from `/config/database.php`
   - Call functions from `/includes/core_functions.php`
   - Return JSON responses via `jsonResponse()`
4. **Connect frontend to backend** via AJAX calls
5. **Test endpoints** with test data
6. **Move to next module**

---

## 🎯 Next Priority

1. Update `/api/procurement.php` 
2. Connect to `/public/procurement/` pages
3. Test all procurement endpoints
4. Move to sales, then others
5. Use `/api/hr_integrated.php` as reference

All files are in place. Time to integrate! 🚀
