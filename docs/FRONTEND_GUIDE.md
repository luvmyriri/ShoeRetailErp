# Frontend Guide - Shoe Retail ERP

## Overview

The Shoe Retail ERP frontend is built with a modern, Odoo-inspired design system. It features a responsive layout, intuitive navigation, and comprehensive UI components optimized for desktop and mobile devices.

## Table of Contents

1. [Architecture](#architecture)
2. [Project Structure](#project-structure)
3. [CSS Design System](#css-design-system)
4. [JavaScript Components](#javascript-components)
5. [Templates & Pages](#templates--pages)
6. [Installation & Setup](#installation--setup)
7. [Components Reference](#components-reference)
8. [Customization](#customization)
9. [Browser Support](#browser-support)

---

## Architecture

### Technology Stack

- **HTML5**: Semantic markup
- **CSS3**: Modern styling with CSS variables
- **JavaScript (ES6+)**: Interactive components with no external dependencies
- **Font Awesome 6.4**: Icon library

### Design Principles

- **Responsive**: Mobile-first, works on all screen sizes
- **Accessible**: WCAG compliant, semantic HTML
- **Performance**: Lightweight, no bloated frameworks
- **Maintainable**: Organized, well-documented code

---

## Project Structure

```
public/
├── css/
│   └── style.css          # Main stylesheet (1100+ lines)
├── js/
│   └── app.js             # Main application JavaScript
├── templates/
│   ├── dashboard.html     # Dashboard page
│   └── role-management.html # Role management page
└── index.html             # Entry point (when created)
```

---

## CSS Design System

### Color Palette

```css
Primary Colors:
--primary-color: #714B67         /* Main brand color */
--primary-light: #8B5E7F         /* Hover state */
--primary-dark: #5A3B54          /* Active state */
--secondary-color: #F5B041       /* Accent color */

Status Colors:
--success-color: #27AE60         /* Success/active */
--warning-color: #F39C12         /* Warning/pending */
--danger-color: #E74C3C          /* Danger/error */
--info-color: #3498DB            /* Information */

Neutral Colors:
--gray-50 to --gray-900          /* Grayscale palette */
```

### Spacing System

```css
--spacing-xs: 0.25rem    /* 4px */
--spacing-sm: 0.5rem     /* 8px */
--spacing-md: 1rem       /* 16px */
--spacing-lg: 1.5rem     /* 24px */
--spacing-xl: 2rem       /* 32px */
--spacing-2xl: 3rem      /* 48px */
--spacing-3xl: 4rem      /* 64px */
```

### Typography

```css
Font Sizes:
--font-size-xs: 0.75rem    /* 12px */
--font-size-sm: 0.875rem   /* 14px */
--font-size-base: 1rem     /* 16px */
--font-size-lg: 1.125rem   /* 18px */
--font-size-xl: 1.25rem    /* 20px */
--font-size-2xl: 1.5rem    /* 24px */
--font-size-3xl: 1.875rem  /* 30px */

Font Family:
--font-family-sans: System fonts (Apple, Google, Windows)
--font-family-mono: Monaco, Courier New
```

### Component Variants

#### Buttons

```html
<!-- Primary Button -->
<button class="btn btn-primary">Click Me</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">Click Me</button>

<!-- Success Button -->
<button class="btn btn-success">Success</button>

<!-- Danger Button -->
<button class="btn btn-danger">Delete</button>

<!-- Outline Button -->
<button class="btn btn-outline">Outline</button>

<!-- Sizes -->
<button class="btn btn-sm">Small</button>
<button class="btn btn-lg">Large</button>
<button class="btn btn-block">Full Width</button>
```

#### Badges

```html
<span class="badge badge-primary">Primary</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-warning">Warning</span>
<span class="badge badge-danger">Danger</span>
<span class="badge badge-info">Info</span>
```

#### Alerts

```html
<div class="alert alert-success">
    <div class="alert-icon">✓</div>
    <div>Success message</div>
    <button class="alert-close">&times;</button>
</div>

<div class="alert alert-danger">
    <!-- Similar structure -->
</div>
```

#### Cards

```html
<div class="card">
    <div class="card-header">
        <h3>Card Title</h3>
    </div>
    <div class="card-body">
        <!-- Content -->
    </div>
    <div class="card-footer">
        <!-- Actions -->
    </div>
</div>
```

#### Forms

```html
<div class="form-group">
    <label class="form-label">Field Label</label>
    <input type="text" class="form-control" placeholder="Enter value">
    <div class="form-help">Helper text</div>
</div>

<div class="form-group">
    <label class="form-label">Select</label>
    <div class="form-select">
        <select class="form-control">
            <option>Option 1</option>
            <option>Option 2</option>
        </select>
    </div>
</div>

<div class="form-check">
    <input type="checkbox" id="check1">
    <label for="check1">Checkbox label</label>
</div>
```

#### Tables

```html
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Header 1</th>
                <th>Header 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Data 1</td>
                <td>Data 2</td>
            </tr>
        </tbody>
    </table>
</div>
```

---

## JavaScript Components

### Application State

```javascript
// Access global ERP object
window.ERP

// Available methods
ERP.showAlert(message, type, duration)
ERP.openModal(modalId)
ERP.closeModal()
ERP.collapseSidebar()
ERP.expandSidebar()
ERP.setActiveNavLink(href)
ERP.formatCurrency(amount)
ERP.formatDate(date, format)
ERP.debounce(func, delay)
ERP.throttle(func, limit)
ERP.API.get(endpoint)
ERP.API.post(endpoint, data)
ERP.API.put(endpoint, data)
ERP.API.delete(endpoint)
```

### Alert System

```javascript
// Show alert
ERP.showAlert('Operation successful!', 'success', 5000);
ERP.showAlert('An error occurred', 'danger', 5000);
ERP.showAlert('Warning: Action irreversible', 'warning', 0); // No auto-close
ERP.showAlert('For your information', 'info', 3000);
```

### Modal Management

```javascript
// Open modal
ERP.openModal('roleModal');

// Close modal
ERP.closeModal();

// HTML trigger
<button data-toggle="modal" data-target="myModal">Open</button>
<button data-dismiss="modal">Close</button>
```

### Sidebar Collapse

```javascript
// Collapse sidebar
ERP.collapseSidebar();

// Expand sidebar
ERP.expandSidebar();

// Responsive behavior
// Automatically collapses on screens <= 1024px
```

### Tabs

```html
<ul class="nav-tabs">
    <li><a href="#" class="nav-link active" data-tab="tab1">Tab 1</a></li>
    <li><a href="#" class="nav-link" data-tab="tab2">Tab 2</a></li>
</ul>

<div id="tab1" class="tab-pane active">Content 1</div>
<div id="tab2" class="tab-pane">Content 2</div>
```

### Dropdowns

```html
<div class="dropdown">
    <button class="btn btn-primary">Menu</button>
    <div class="dropdown-menu">
        <button class="dropdown-item">Option 1</button>
        <button class="dropdown-item">Option 2</button>
        <div class="dropdown-divider"></div>
        <button class="dropdown-item">Option 3</button>
    </div>
</div>
```

### Tooltips

```html
<button data-tooltip="Helper text">Hover me</button>
<button data-tooltip="Help" data-tooltip-position="bottom">Bottom</button>
<button data-tooltip="Help" data-tooltip-position="left">Left</button>
<button data-tooltip="Help" data-tooltip-position="right">Right</button>
```

### Keyboard Shortcuts

- **Ctrl+K / Cmd+K**: Focus search
- **Escape**: Close modals and dropdowns

### API Helper

```javascript
// GET request
ERP.API.get('/api/roles')
    .then(data => console.log(data))
    .catch(error => console.error(error));

// POST request
ERP.API.post('/api/roles', { name: 'Manager' })
    .then(data => console.log(data))
    .catch(error => console.error(error));

// PUT request
ERP.API.put('/api/roles/1', { name: 'Updated' })
    .then(data => console.log(data))
    .catch(error => console.error(error));

// DELETE request
ERP.API.delete('/api/roles/1')
    .then(data => console.log(data))
    .catch(error => console.error(error));
```

---

## Templates & Pages

### Dashboard (`dashboard.html`)

**Features:**
- Statistics cards with real-time data
- Recent sales table
- Quick stats with progress bars
- Upcoming tasks widget

**Key Elements:**
```html
<div class="stat-card">
    <div class="stat-icon">📊</div>
    <div class="stat-value">$45,231</div>
    <div class="stat-label">Total Revenue</div>
</div>
```

### Role Management (`role-management.html`)

**Features:**
- Role listing with cards
- Tabbed interface (All Roles, Assignments, Permissions)
- Create/Edit role modal
- Permission matrix
- Employee assignment table

**Key Elements:**
```html
<!-- Role Modal -->
<div id="roleModal" class="modal">
    <div class="modal-header">
        <h3>Create New Role</h3>
        <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
        <!-- Form content -->
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline">Cancel</button>
        <button class="btn btn-primary">Create</button>
    </div>
</div>
```

---

## Installation & Setup

### Prerequisites

- Modern web browser (Chrome, Firefox, Safari, Edge)
- Apache/Nginx server with PHP 7.4+
- No npm/webpack required - pure HTML/CSS/JS

### File Organization

1. **Create directories:**
   ```bash
   mkdir -p public/css public/js public/templates
   ```

2. **Add files:**
   - Copy `style.css` to `public/css/`
   - Copy `app.js` to `public/js/`
   - Copy `.html` templates to `public/templates/`

3. **Link in your PHP:**
   ```php
   // In your PHP template
   require_once 'public/templates/dashboard.html';
   ```

### Server Configuration

**Apache (.htaccess):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /ShoeRetailErp/
    
    # Rewrite URLs to index
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>
```

---

## Components Reference

### Layout Classes

```html
<!-- Container -->
<div class="container"><!-- Max width 1280px --></div>

<!-- Grid System -->
<div class="row">
    <div class="col-12">Full width</div>
    <div class="col-6">Half width</div>
    <div class="col-3">Quarter width</div>
    <div class="col-4">Third width</div>
</div>

<!-- Responsive -->
<div class="col-md-6">Half on medium, full on small</div>
```

### Page Structure

```html
<!-- Navigation -->
<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-brand">Logo</div>
        <ul class="navbar-nav"><!-- Menu --></ul>
        <div class="navbar-right"><!-- Search, user --></div>
    </div>
</nav>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-menu"><!-- Menu items --></div>
</aside>

<!-- Main Content -->
<div class="main-wrapper">
    <main class="main-content">
        <div class="page-header"><!-- Page title & actions --></div>
        <!-- Content -->
    </main>
</div>
```

### Utility Classes

```html
<!-- Margin -->
<div class="mt-1 mt-2 mt-3 mt-4">Margin top</div>
<div class="mb-1 mb-2 mb-3 mb-4">Margin bottom</div>
<div class="mx-auto">Center horizontally</div>

<!-- Padding -->
<div class="px-2">Horizontal padding</div>

<!-- Display -->
<div class="d-flex">Flexbox</div>
<div class="d-none">Hidden</div>
<div class="d-block">Block display</div>

<!-- Flex -->
<div class="flex-1">Flex: 1</div>
<div class="gap-2">Gap between flex items</div>

<!-- Text -->
<div class="text-center">Center text</div>
<div class="text-primary">Primary color text</div>
<div class="text-muted">Muted text</div>

<!-- Other -->
<div class="rounded">Border radius</div>
<div class="opacity-50">50% opacity</div>
<div class="cursor-pointer">Pointer cursor</div>
```

---

## Customization

### Changing Colors

Edit CSS variables in `style.css`:

```css
:root {
    --primary-color: #714B67;
    --success-color: #27AE60;
    /* ... */
}
```

### Custom Styling

Add custom CSS after `style.css`:

```css
/* Custom brand styling */
.navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.sidebar {
    background-color: #1a1a2e;
}
```

### Adding New Components

1. **Add CSS:**
   ```css
   .my-component {
       /* styles */
   }
   ```

2. **Add JavaScript (if needed):**
   ```javascript
   // In app.js, add to window.ERP
   window.ERP.myComponent = {
       init: function() { /* ... */ }
   };
   ```

3. **Use in HTML:**
   ```html
   <div class="my-component">Content</div>
   ```

---

## Responsive Breakpoints

```css
/* Desktop: 1024px+ (default) */
/* Sidebar: 250px wide, text visible */

/* Tablet: 768px - 1023px */
@media (max-width: 1024px)
/* Sidebar: 70px wide, text hidden */

/* Mobile: < 768px */
@media (max-width: 768px)
/* Sidebar: off-screen, toggle available */
/* Single column layout */

/* Small Mobile: < 480px */
@media (max-width: 480px)
/* Reduced spacing and font sizes */
```

---

## Browser Support

| Browser | Version | Support |
|---------|---------|---------|
| Chrome  | 90+     | ✓ Full  |
| Firefox | 88+     | ✓ Full  |
| Safari  | 14+     | ✓ Full  |
| Edge    | 90+     | ✓ Full  |
| IE 11   | -       | ✗ Not Supported |

---

## Performance Tips

1. **Minify CSS/JS for production:**
   ```bash
   # Use any minifier (online or CLI tool)
   ```

2. **Lazy load images:**
   ```html
   <img src="image.jpg" loading="lazy" alt="Description">
   ```

3. **Cache static assets:**
   - Set proper cache headers in server config
   - Use versioning for CSS/JS files

4. **Optimize images:**
   - Use WebP format with fallback
   - Compress with tools like TinyPNG

---

## Accessibility (A11y)

- Semantic HTML (nav, main, section, article)
- ARIA labels where needed
- Keyboard navigation support
- Color contrast meets WCAG AA
- Focus indicators visible
- Form labels associated with inputs

---

## Support & Maintenance

- Review browser compatibility regularly
- Update Font Awesome library when needed
- Test responsive design on multiple devices
- Monitor CSS file size and performance
- Keep JavaScript modular and documented

---

## Quick Reference

### Component Examples

See `dashboard.html` and `role-management.html` for complete examples.

### CSS Classes Quick List

```
Layout: container, row, col-*, main-wrapper, main-content
Navigation: navbar, navbar-brand, navbar-nav, navbar-right
Sidebar: sidebar, sidebar-menu, sidebar-link, sidebar-collapsed
Components: card, btn, badge, alert, modal, table
Forms: form-group, form-label, form-control, form-check
Utilities: mt-*, mb-*, px-*, d-flex, text-*, gap-*
```

---

## Support

For issues or questions:
1. Check the inline CSS comments in `style.css`
2. Review `app.js` for JavaScript functionality
3. Reference Odoo design patterns: https://odoo.com
4. Test in multiple browsers for compatibility

