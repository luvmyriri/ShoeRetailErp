<?php
/**
 * MODULE TEMPLATE
 * 
 * All module pages should follow this structure for consistent UI/UX
 * 
 * Copy this template and modify as needed:
 */
?>
<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Load database if needed
require __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Module Name - Shoe Retail ERP</title>
    
    <!-- Global Head Include (CSS, Meta, Icons) -->
    <?php include __DIR__ . '/head.php'; ?>
    
    <!-- Add module-specific CSS only if needed (should be minimal) -->
    <style>
        /* Keep module-specific styles minimal */
    </style>
</head>
<body>
    <!-- Shared Navbar (same for all modules) -->
    <?php include __DIR__ . '/navbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-wrapper">
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Module Title</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / Module
                    </div>
                </div>
                <div class="page-header-actions">
                    <!-- Action buttons go here -->
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="row">
                <div class="col-12">
                    <!-- Your module content here -->
                </div>
            </div>
        </main>
    </div>
    
    <!-- Scripts -->
    <script src="/ShoeRetailErp/public/js/app.js"></script>
</body>
</html>
