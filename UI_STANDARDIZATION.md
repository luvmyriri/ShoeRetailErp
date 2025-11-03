# UI Standardization - Global Theme Implementation

## Overview
All modules now use a unified design system via `/public/css/style.css` for consistent look and feel across the entire ERP system.

## Changes Made

### 1. **Navigation Bar** (Updated)
- **Location**: `public/includes/navbar.php`
- **Features**:
  - Clean design (icons removed for cleaner look)
  - Dropdown support for multi-page modules
  - Role-based access control
  - Consistent styling across all pages
  
**Modules with Dropdowns**:
- **Procurement**: Orders, Quality Check, Good Receipts, Reports
- **CRM**: Dashboard, Customers, Support, Loyalty

### 2. **Global Head Include** (New)
- **Location**: `public/includes/head.php`
- **Includes**:
  - Global theme CSS (`style.css`)
  - Font Awesome icons
  - Meta tags and responsive viewport
  - Base responsive styles

### 3. **Module Template** (New)
- **Location**: `public/includes/MODULE_TEMPLATE.php`
- **Purpose**: Reference template for proper module structure

## How to Update Your Module

### Step 1: Update HTML Structure
Replace your module's `<head>` section:

**Old (custom CSS approach)**:
```html
<link rel="stylesheet" href="styles/mymodule.css">
```

**New (global theme approach)**:
```html
<?php include '../includes/head.php'; ?>
```

### Step 2: Include Navbar
Replace your custom navigation with:

```html
<?php include '../includes/navbar.php'; ?>
```

### Step 3: Update Main Content Wrapper
Ensure your main content uses these classes:

```html
<div class="main-wrapper">
    <main class="main-content">
        <!-- Your content here -->
    </main>
</div>
```

### Step 4: Remove Custom CSS Files
Delete module-specific CSS files (or keep minimal module-specific overrides only):

**Files to Remove/Deprecate**:
- `crm/styles/crmGlobalStyles.css`
- `procurement/css/*.css`
- `css/pos_style.css`

## CSS Classes Available (from Global Theme)

All classes from `public/css/style.css` are now available globally:

### Layout
- `.container`, `.row`, `.col-*`, `.col-md-*`
- `.main-wrapper`, `.main-content`

### Cards & Components
- `.card`, `.card-header`, `.card-body`, `.card-footer`
- `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-danger`
- `.form-group`, `.form-label`, `.form-control`
- `.table`, `.badge`, `.alert`
- `.modal`, `.dropdown`

### Typography
- `h1-h6` (auto-styled)
- `.text-primary`, `.text-success`, `.text-danger`
- `.text-center`, `.text-left`, `.text-right`

### Spacing
- `.mt-1 through .mt-4` (margin-top)
- `.mb-1 through .mb-4` (margin-bottom)
- `.mx-auto` (margin auto)
- `.px-2` (padding horizontal)

### Display & Utilities
- `.d-flex`, `.d-block`, `.d-none`
- `.flex-1`, `.gap-2`
- `.rounded`, `.rounded-sm`
- `.cursor-pointer`, `.opacity-50`

## Color Variables

All colors defined in `:root` CSS variables:

- **Primary**: `--primary-color`, `--primary-light`, `--primary-dark`
- **Semantic**: `--success-color`, `--danger-color`, `--warning-color`, `--info-color`
- **Neutral**: `--gray-50` through `--gray-900`

Example usage:
```css
color: var(--primary-color);
background-color: var(--gray-50);
```

## Navbar Dropdown Implementation

For modules with multiple pages, dropdowns are automatically enabled:

**Configuration in navbar.php**:
```php
'procurement' => [
    'label' => 'Procurement',
    'url' => '/ShoeRetailErp/public/procurement/index.php',
    'pages' => [
        ['label' => 'Orders', 'url' => '...'],
        ['label' => 'Quality Check', 'url' => '...'],
        // Add more pages here
    ]
]
```

**Styling**:
- Automatic `.navbar-dropdown-toggle::after` (shows ▼ symbol)
- `.navbar-dropdown-content` appears on hover
- `.navbar-dropdown-item` for submenu items

## Migration Checklist

- [ ] Update `<head>` to include `head.php`
- [ ] Replace custom navbar with `navbar.php` include
- [ ] Remove custom CSS files
- [ ] Use `.main-wrapper` and `.main-content` classes
- [ ] Update buttons to use `.btn` classes
- [ ] Update forms to use `.form-control` classes
- [ ] Update tables to use `.table` class
- [ ] Test responsive design on mobile
- [ ] Test navbar dropdowns (if applicable)
- [ ] Verify all module links work

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Responsive design: Mobile, Tablet, Desktop
- CSS Variables support required (all modern browsers)

## Questions?

For modules requiring custom styling:
1. Keep custom styles minimal
2. Override global CSS variables if needed
3. Use BEM naming convention: `.module-component__element--modifier`
4. Document any custom additions

---

**Last Updated**: November 3, 2025
**Theme Version**: 1.0
