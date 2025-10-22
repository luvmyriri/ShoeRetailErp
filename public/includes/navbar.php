<?php
/**
 * Shared Navbar Component
 * Used across all module pages for seamless navigation
 * Include this file at the top of each module page
 */

// Get current page to highlight active link
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Map directories to nav items
$nav_map = [
    'index' => 'index.php' === $current_page && dirname($_SERVER['PHP_SELF']) === dirname(__DIR__),
    'inventory' => $current_dir === 'inventory',
    'sales' => $current_dir === 'sales',
    'procurement' => $current_dir === 'procurement',
    'accounting' => $current_dir === 'accounting',
    'customers' => $current_dir === 'customers',
    'hr' => $current_dir === 'hr',
];
?>

<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-brand"><i class="fas fa-shoe-prints"></i><span>Shoe Retail ERP</span></div>
        <ul class="navbar-nav">
            <li><a href="/ShoeRetailErp/public/index.php" <?php echo ($_SERVER['PHP_SELF'] === '/ShoeRetailErp/public/index.php') ? 'class="active"' : ''; ?>>Home</a></li>
            <li><a href="/ShoeRetailErp/public/inventory/index.php" <?php echo $nav_map['inventory'] ? 'class="active"' : ''; ?>>Inventory</a></li>
            <li><a href="/ShoeRetailErp/public/sales/index.php" <?php echo $nav_map['sales'] ? 'class="active"' : ''; ?>>Sales</a></li>
            <li><a href="/ShoeRetailErp/public/procurement/index.php" <?php echo $nav_map['procurement'] ? 'class="active"' : ''; ?>>Procurement</a></li>
            <li><a href="/ShoeRetailErp/public/accounting/index.php" <?php echo $nav_map['accounting'] ? 'class="active"' : ''; ?>>Accounting</a></li>
            <li><a href="/ShoeRetailErp/public/customers/index.php" <?php echo $nav_map['customers'] ? 'class="active"' : ''; ?>>Customers</a></li>
            <li><a href="/ShoeRetailErp/public/hr/index.php" <?php echo $nav_map['hr'] ? 'class="active"' : ''; ?>>HR</a></li>
        </ul>
        <div class="navbar-right">
            <div class="navbar-search"><input type="text" placeholder="Search..."></div>
            <div class="dropdown"><div class="navbar-avatar"><i class="fas fa-user"></i></div>
                <div class="dropdown-menu">
                    <a href="/ShoeRetailErp/public/profile.php" class="dropdown-item"><i class="fas fa-user-circle"></i> Profile</a>
                    <a href="/ShoeRetailErp/public/settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="/ShoeRetailErp/logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>
