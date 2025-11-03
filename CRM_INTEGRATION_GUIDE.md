# CRM Module Integration Guide

## Overview
The CRM module is now fully integrated into the main ERP system through a unified wrapper. Users will no longer jump to a separate UI; instead, all CRM functionality is accessible through the main navigation with consistent styling.

## Architecture

### New Structure
```
/crm/
├── index.php                    (NEW - Main entry point)
├── crm-integration.css          (NEW - Integration styles)
├── CrmDashboard.php             (Existing - Content)
├── customerProfile.php          (Existing - Content)
├── customerSupport.php          (Existing - Content)
├── loyaltyProgram.php           (Existing - Content)
├── reportsManagement.php        (Existing - Content)
└── styles/                      (DEPRECATED - Custom styles)
    └── crmGlobalStyles.css
```

### How It Works

1. **User clicks CRM in navbar**
2. → Navigates to `/crm/index.php?page=dashboard`
3. → Wrapper loads global navbar & theme
4. → Wrapper includes page content (e.g., CrmDashboard.php)
5. → CRM-specific content rendered within global layout
6. → Integration CSS normalizes any conflicting styles
7. → Users see seamless, unified UI

## Navigation Flow

**CRM Dropdown Menu**:
```
CRM
├── Dashboard        → /crm/index.php?page=dashboard
├── Customers       → /crm/index.php?page=customers
├── Support         → /crm/index.php?page=support
├── Loyalty         → /crm/index.php?page=loyalty
└── Reports         → /crm/index.php?page=reports
```

## Key Changes

### 1. CRM Wrapper (index.php)
- ✅ Uses global theme via `includes/head.php`
- ✅ Uses shared navbar via `includes/navbar.php`
- ✅ Routes subpages via URL parameters (?page=)
- ✅ Maintains session & auth checks
- ✅ Strips duplicate HTML structure from subpages
- ✅ Applies integration CSS for styling normalization

### 2. Integration CSS (crm-integration.css)
- ✅ Hides duplicate navbars from subpages
- ✅ Normalizes card, button, form styling
- ✅ Applies global spacing & colors
- ✅ Responsive design fixes
- ✅ Alert, modal, table normalization

### 3. Navigation Updates (navbar.php)
- ✅ All CRM links now point to wrapper
- ✅ Dropdown shows all CRM subpages
- ✅ Active state detection

## For CRM Developers

### CRM Pages (No Changes Required)
The existing CRM pages (CrmDashboard.php, customerProfile.php, etc.) continue to work as-is. The wrapper handles:
- Navigation integration
- Style normalization
- Session management
- HTML structure cleanup

### Adding New CRM Pages

**Step 1: Create your page** (e.g., `mypage.php`)
```php
<?php
// Your existing CRM logic here
?>
<!-- Your content HTML -->
```

**Step 2: Register in wrapper** (`index.php`)
```php
$valid_pages = ['dashboard', 'customers', 'support', 'loyalty', 'reports', 'mypage'];

$page_map = [
    // ... existing entries
    'mypage' => 'mypage.php',
];

$page_titles = [
    // ... existing entries
    'mypage' => 'My Page Title',
];
```

**Step 3: Add to navbar** (`includes/navbar.php`)
```php
'crm' => [
    // ... existing config
    'pages' => [
        // ... existing pages
        ['label' => 'My Page', 'url' => '/ShoeRetailErp/public/crm/index.php?page=mypage'],
    ]
]
```

## Styling Guidelines

### Using Global Variables in CRM
```css
/* Instead of custom colors */
color: var(--primary-color);
background: var(--gray-50);
margin: var(--spacing-lg);
padding: var(--spacing-md);
```

### CRM Content Selector
```css
/* Target CRM content specifically */
.crm-content .your-class {
    /* Your styles */
}
```

### Colors Available
- Primary: `var(--primary-color)`, `var(--primary-light)`, `var(--primary-dark)`
- Status: `var(--success-color)`, `var(--danger-color)`, `var(--warning-color)`
- Neutral: `var(--gray-50)` through `var(--gray-900)`

## Cleanup Tasks

### Delete Deprecated Files
Once verified that CRM works smoothly, you can delete:
- `/crm/styles/crmGlobalStyles.css`
- `/crm/styles/` directory (if empty)

These are no longer needed as the global theme handles all styling.

### Test Before Cleanup
1. Navigate through each CRM subpage
2. Verify buttons, forms, tables render correctly
3. Check responsive design on mobile
4. Test sorting, filtering, modals
5. Verify no console errors

## Troubleshooting

### CRM Pages Show Broken Layout
**Cause**: Custom CRM styles conflicting with integration CSS  
**Solution**: Check `crm-integration.css` has `!important` flags where needed

### Duplicate Navbars
**Cause**: Subpage not properly stripped by wrapper  
**Solution**: Check regex in `index.php` matches your HTML structure

### Styling Not Applying
**Cause**: Wrong CSS selector or variable name  
**Solution**: Verify selector prefixes with `.crm-content`

### Links Not Working
**Cause**: Wrong page parameter name  
**Solution**: Check page name in `$valid_pages` array matches URL parameter

## Benefits of Integration

✅ **Consistent UI** - All modules look & feel the same  
✅ **Better Navigation** - Unified navbar with dropdowns  
✅ **Simplified Maintenance** - Single theme to update  
✅ **Improved UX** - No jarring UI changes when switching modules  
✅ **Responsive** - Global responsive design  
✅ **Accessible** - Standardized components  

## Questions?

Refer to main UI standardization guide: `UI_STANDARDIZATION.md`

---
**Last Updated**: November 3, 2025
**CRM Integration Version**: 1.0
