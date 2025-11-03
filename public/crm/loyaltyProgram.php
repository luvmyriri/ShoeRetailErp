<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Program - CRM - Shoe Retail ERP</title>
    <?php include '../includes/head.php'; ?>
    <link rel="stylesheet" href="crm-integration.css">
    <link rel="stylesheet" href="enhanced-modal-styles.css">
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Loyalty Program</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / CRM / Loyalty
                    </div>
                </div>
                <div class="page-header-actions">
                    <!-- Actions here -->
                </div>
            </div>

            <div class="row" style="margin-bottom: 2rem;">
                <div class="col-md-4" style="margin-bottom: 1rem;">
                    <div class="stat-card">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-value" id="totalMembers">0</div>
                        <div class="stat-label">Loyalty Members</div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 1rem;">
                    <div class="stat-card">
                        <div class="stat-icon">🎁</div>
                        <div class="stat-value" id="activeRewards">0</div>
                        <div class="stat-label">Active Rewards</div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 1rem;">
                    <div class="stat-card">
                        <div class="stat-icon">💎</div>
                        <div class="stat-value" id="totalPointsRedeemed">0</div>
                        <div class="stat-label">Points Redeemed</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">Loyalty Tiers</h3>
                </div>
                <div class="card-body">
                    <div class="row" style="margin: -1rem;">
                        <div class="col-md-3" style="padding: 1rem;">
                            <div style="border: 2px solid #ddd; padding: 1rem; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; margin-bottom: 0.5rem;">🥉</div>
                                <div style="font-weight: 600; margin-bottom: 0.5rem;">Bronze</div>
                                <div style="font-size: 12px; color: #666;">0-5,000 points</div>
                            </div>
                        </div>
                        <div class="col-md-3" style="padding: 1rem;">
                            <div style="border: 2px solid #ddd; padding: 1rem; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; margin-bottom: 0.5rem;">🥈</div>
                                <div style="font-weight: 600; margin-bottom: 0.5rem;">Silver</div>
                                <div style="font-size: 12px; color: #666;">5,000-10,000 points</div>
                            </div>
                        </div>
                        <div class="col-md-3" style="padding: 1rem;">
                            <div style="border: 2px solid #ddd; padding: 1rem; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; margin-bottom: 0.5rem;">🥇</div>
                                <div style="font-weight: 600; margin-bottom: 0.5rem;">Gold</div>
                                <div style="font-size: 12px; color: #666;">10,000+ points</div>
                            </div>
                        </div>
                        <div class="col-md-3" style="padding: 1rem;">
                            <div style="border: 2px solid #ddd; padding: 1rem; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; margin-bottom: 0.5rem;">💎</div>
                                <div style="font-weight: 600; margin-bottom: 0.5rem;">Platinum</div>
                                <div style="font-size: 12px; color: #666;">25,000+ points</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/app.js"></script>
</body>
</html>
