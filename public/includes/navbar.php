<?php
/**
 * Shared Navbar Component - Global Theme
 * Used across all module pages for seamless navigation
 * Include this file at the top of each module page
 */

// Get current page and directory
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_dashboard = ($current_page === 'index.php' && dirname($_SERVER['PHP_SELF']) === dirname(__DIR__));

// Get user role and name
$userRole = $_SESSION['role'] ?? 'Guest';
$userName = $_SESSION['full_name'] ?? 'User';

// Define module access by role
$roleAccess = [
    'Admin' => ['inventory', 'sales', 'procurement', 'accounting', 'crm', 'hr'],
    'Manager' => ['inventory', 'sales', 'procurement', 'accounting', 'crm'],
    'Inventory' => ['inventory'],
    'Sales' => ['sales', 'crm'],
    'Procurement' => ['procurement', 'inventory'],
    'Accounting' => ['accounting'],
    'HR' => ['hr'],
    'Cashier' => ['sales', 'crm'],
    'Support' => ['crm'],
];

// Get allowed modules for current user
$allowedModules = $roleAccess[$userRole] ?? [];

// Function to check if user can access module
function canAccessModule($module) {
    global $allowedModules;
    return in_array($module, $allowedModules);
}

// Function to check if module is currently active
function isModuleActive($module) {
    global $current_dir, $is_dashboard;
    if ($module === 'dashboard') {
        return $is_dashboard;
    }
    return $current_dir === $module;
}

// Module definitions with subpages for dropdowns
$modules = [
    'inventory' => [
        'label' => 'Inventory',
        'url' => '/ShoeRetailErp/public/inventory/index.php',
        'pages' => []
    ],
    'sales' => [
        'label' => 'Sales',
        'url' => '/ShoeRetailErp/public/sales/index.php',
        'pages' => []
    ],
    'procurement' => [
        'label' => 'Procurement',
        'url' => '/ShoeRetailErp/public/procurement/index.php',
        'pages' => [
            ['label' => 'Orders', 'url' => '/ShoeRetailErp/public/procurement/index.php'],
            ['label' => 'Quality Check', 'url' => '/ShoeRetailErp/public/procurement/qualitychecking.php'],
            ['label' => 'Good Receipts', 'url' => '/ShoeRetailErp/public/procurement/goodreceipts.php'],
            ['label' => 'Reports', 'url' => '/ShoeRetailErp/public/procurement/reports.php'],
        ]
    ],
    'accounting' => [
        'label' => 'Accounting',
        'url' => '/ShoeRetailErp/public/accounting/index.php',
        'pages' => []
    ],
    'crm' => [
        'label' => 'CRM',
        'url' => '/ShoeRetailErp/public/crm/index.php',
        'pages' => [
            ['label' => 'Dashboard', 'url' => '/ShoeRetailErp/public/crm/CrmDashboard.php'],
            ['label' => 'Customers', 'url' => '/ShoeRetailErp/public/crm/customerProfile.php'],
            ['label' => 'Support', 'url' => '/ShoeRetailErp/public/crm/customerSupport.php'],
            ['label' => 'Loyalty', 'url' => '/ShoeRetailErp/public/crm/loyaltyProgram.php'],
            ['label' => 'Reports', 'url' => '/ShoeRetailErp/public/crm/reportsManagement.php'],
        ]
    ],
    'hr' => [
        'label' => 'HR',
        'url' => '/ShoeRetailErp/public/hr/index.php',
        'pages' => []
    ],
];
?>

<style>
/* Improved navbar spacing */
.navbar {
    padding: var(--spacing-md) 0;
}

.navbar-container {
    padding: var(--spacing-md) var(--spacing-xl) !important;
    gap: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: nowrap;
}

.navbar-brand {
    min-width: max-content;
    flex-shrink: 0;
    gap: var(--spacing-md);
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--primary-color);
}

.navbar-nav {
    display: flex;
    gap: var(--spacing-3xl);
    margin: 0;
    flex: 1;
    justify-content: center;
    list-style: none;
    padding: 0;
}

.navbar-nav li {
    position: relative;
    white-space: nowrap;
}

.navbar-nav a {
    padding: var(--spacing-md) 0 !important;
    font-weight: 500;
    color: var(--gray-700);
    text-decoration: none;
    transition: all var(--transition-fast);
    border-bottom: 2px solid transparent;
    display: block;
    white-space: nowrap;
}

.navbar-nav a:hover {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.navbar-nav a.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
    font-weight: 600;
}

.navbar-right {
    display: flex;
    gap: var(--spacing-xl) !important;
    flex-shrink: 0;
    align-items: center;
}

/* Navbar dropdown styling */
.navbar-dropdown {
    position: relative;
    display: inline-block;
}

.navbar-dropdown-toggle {
    cursor: pointer;
    user-select: none;
    padding: var(--spacing-md) 0 !important;
    border: none;
    background: none;
    font-weight: 500;
    color: var(--gray-700);
    text-decoration: none;
    transition: all var(--transition-fast);
    border-bottom: 2px solid transparent;
    display: block;
    white-space: nowrap;
}

.navbar-dropdown-toggle:hover {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.navbar-dropdown-toggle::after {
    content: ' ▼';
    font-size: 10px;
    margin-left: 4px;
    transition: transform var(--transition-fast);
    display: inline-block;
}

.navbar-dropdown-toggle.active::after {
    transform: rotate(180deg);
}

/* Show dropdown on click */
.navbar-dropdown-content {
    display: none;
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    background-color: white;
    min-width: 160px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1100;
    border-radius: var(--radius-sm);
    border-top: 2px solid var(--primary-color);
    overflow: visible;
    padding: 0.25rem 0;
}

.navbar-dropdown-content.show {
    display: block !important;
}

.navbar-dropdown-item {
    display: block;
    padding: 0.4rem 1rem;
    color: var(--gray-700);
    text-decoration: none;
    transition: all var(--transition-fast);
    font-size: 12px;
    white-space: nowrap;
    line-height: 1.3;
}

.navbar-dropdown-item:first-child {
    padding-top: 0.4rem;
}

.navbar-dropdown-item:last-child {
    padding-bottom: 0.4rem;
}

.navbar-dropdown-item:hover {
    background-color: var(--gray-100);
    color: var(--primary-color);
    padding-left: 1.25rem;
}

.navbar-dropdown-item.active {
    color: var(--primary-color);
    font-weight: 600;
    background-color: var(--gray-50);
}
</style>

<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-brand" onclick="window.location.href='/ShoeRetailErp/public/index.php'" style="cursor: pointer;">
            <span>Shoe Retail ERP</span>
        </div>
        <ul class="navbar-nav">
            <li><a href="/ShoeRetailErp/public/index.php" class="<?php echo $is_dashboard ? 'active' : ''; ?>">Dashboard</a></li>
            
            <?php foreach ($modules as $key => $module): ?>
                <?php if (canAccessModule($key)): ?>
                    <?php if (!empty($module['pages'])): ?>
                        <!-- Module with dropdown -->
                        <li class="navbar-dropdown">
                            <button type="button" class="navbar-dropdown-toggle <?php echo isModuleActive($key) ? 'active' : ''; ?>" onclick="toggleDropdown(this, event)">
                                <?php echo htmlspecialchars($module['label']); ?>
                            </button>
                            <div class="navbar-dropdown-content">
                                <?php foreach ($module['pages'] as $page): ?>
                                    <a href="<?php echo htmlspecialchars($page['url']); ?>" class="navbar-dropdown-item">
                                        <?php echo htmlspecialchars($page['label']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </li>
                    <?php else: ?>
                        <!-- Simple module link -->
                        <li>
                            <a href="<?php echo htmlspecialchars($module['url']); ?>" class="<?php echo isModuleActive($key) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($module['label']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <div class="navbar-right">
            <div class="navbar-search"><input type="text" placeholder="Search..."></div>
            <div class="dropdown">
                <div class="navbar-avatar" title="<?php echo htmlspecialchars($userName . ' (' . $userRole . ')'); ?>"><?php echo substr($userName, 0, 1); ?></div>
                <div class="dropdown-menu">
                    <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($userName); ?></div>
                        <div style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($userRole); ?></div>
                    </div>
                    <a href="/ShoeRetailErp/public/profile.php" class="dropdown-item">Profile</a>
                    <a href="/ShoeRetailErp/public/settings.php" class="dropdown-item">Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="/ShoeRetailErp/logout.php" class="dropdown-item">Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
// Module dropdown toggle on click
function toggleDropdown(button, event) {
    event.preventDefault();
    event.stopPropagation();
    
    const dropdown = button.nextElementSibling;
    const isOpen = dropdown.classList.contains('show');
    
    // Close all other dropdowns
    document.querySelectorAll('.navbar-dropdown-content.show').forEach(d => {
        if (d !== dropdown) {
            d.classList.remove('show');
            const btn = d.previousElementSibling;
            if (btn) btn.classList.remove('active');
        }
    });
    
    // Toggle this dropdown
    if (isOpen) {
        dropdown.classList.remove('show');
        button.classList.remove('active');
    } else {
        dropdown.classList.add('show');
        button.classList.add('active');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.navbar-dropdown')) {
        document.querySelectorAll('.navbar-dropdown-content.show').forEach(d => {
            d.classList.remove('show');
            const btn = d.previousElementSibling;
            if (btn) btn.classList.remove('active');
        });
    }
});

// User avatar dropdown handler
(function() {
    'use strict';
    
    function initUserDropdown() {
        const avatar = document.querySelector('.navbar-avatar');
        const userDropdown = document.querySelector('.dropdown-menu');
        
        if (!avatar || !userDropdown) return;
        
        // Toggle dropdown on avatar click
        avatar.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!avatar.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserDropdown);
    } else {
        initUserDropdown();
    }
})();
</script>
